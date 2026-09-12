<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Ticket;
use App\Services\WhatsAppService;
use App\Traits\StandardApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketApiController extends Controller
{
    use StandardApiResponses;

    /**
     * Inbound Resolution / Status Update Callback from External System.
     * POST /api/v1/tickets/status-update
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket_number' => 'nullable|string',
            'external_id' => 'nullable|string',
            'ticket_id' => 'nullable|integer',
            'status' => 'required|string|in:open,pending,in_progress,resolved,closed',
            'resolution_notes' => 'nullable|string',
            'resolution_image_url' => 'nullable|url',
        ]);

        $ticketNumber = $validated['ticket_number'] ?? null;
        $externalId = $validated['external_id'] ?? null;
        $ticketId = $validated['ticket_id'] ?? null;

        // Locate ticket by ticket_number, external_id, or internal id
        $query = Ticket::query();
        if ($ticketNumber) {
            $query->where('ticket_number', $ticketNumber);
        } elseif ($externalId) {
            $query->where('external_id', $externalId);
        } elseif ($ticketId) {
            $query->where('id', $ticketId);
        } else {
            return $this->error('Please provide ticket_number, external_id, or ticket_id.', 422, null, 'ERR_MISSING_TICKET_IDENTIFIER');
        }

        $ticket = $query->first();

        if (! $ticket) {
            return $this->error('Ticket not found with provided identifier.', 404, null, 'ERR_TICKET_NOT_FOUND');
        }

        $team = $ticket->team;

        // Verify API Callback Key — this route runs without auth:sanctum, so the key is the
        // ONLY thing standing between the public internet and the ability to flip ticket
        // status + send an arbitrary WhatsApp message to the citizen. Fail CLOSED: reject
        // when no key is configured rather than leaving the endpoint wide open.
        $expectedKey = $team->ticket_settings['callback_api_key'] ?? null;
        if (empty($expectedKey)) {
            Log::warning("TicketApiController: callback rejected — no callback_api_key set for Team #{$team->id}. Configure one in Settings > Ticket Integration.", [
                'ip' => $request->ip(),
            ]);

            return $this->error('Callback authentication is not configured for this account.', 403, null, 'ERR_CALLBACK_NOT_CONFIGURED');
        }

        $providedKey = $request->header('X-Callback-Key')
            ?? $request->bearerToken()
            ?? $request->input('api_key');

        if (! is_string($providedKey) || ! hash_equals($expectedKey, $providedKey)) {
            Log::warning("TicketApiController: Unauthorized callback attempt for Ticket #{$ticket->id}", [
                'ip' => $request->ip(),
            ]);

            return $this->error('Unauthorized callback key.', 401, null, 'ERR_UNAUTHORIZED');
        }

        $previousStatus = $ticket->status;
        $newStatus = strtolower($validated['status']);
        $notes = $validated['resolution_notes'] ?? null;
        $imageUrl = $validated['resolution_image_url'] ?? null;

        $updateData = [
            'status' => $newStatus,
        ];

        if ($notes) {
            $updateData['resolution_notes'] = $notes;
        }
        if ($imageUrl) {
            $updateData['resolution_image_url'] = $imageUrl;
        }
        if (in_array($newStatus, ['resolved', 'closed'])) {
            $updateData['resolved_at'] = now();
        }

        $ticket->update($updateData);

        // Notify Citizen on WhatsApp — only when the status actually changed. External
        // systems retry callbacks; without this guard every retry re-spams the citizen.
        $notified = false;
        $contact = $ticket->contact;

        if ($previousStatus !== $newStatus && $contact && ! empty($contact->phone_number)) {
            try {
                $wa = app(WhatsAppService::class)->setTeam($team);
                $policy = app(\App\Services\PolicyService::class);
                $templateName = $team->ticket_settings['resolve_template_name'] ?? null;

                $replacements = [
                    '{{name}}' => $contact->name ?: 'Citizen',
                    '{{ticket_number}}' => $ticket->ticket_number,
                    '{{category}}' => $ticket->category ?: 'Civic Issue',
                    '{{status}}' => strtoupper($newStatus),
                    '{{resolution_notes}}' => $notes ?: 'Issue has been addressed by field personnel.',
                ];

                // If 24h customer window is closed, fallback to Meta-approved Template if configured
                if (! $policy->canSendFreeMessage($contact) && ! empty($templateName)) {
                    $bodyParams = [
                        $contact->name ?: 'Citizen',
                        $ticket->ticket_number,
                        strtoupper($newStatus),
                        $notes ?: 'Issue has been resolved.',
                    ];
                    $wa->sendTemplate($contact->phone_number, $templateName, 'en_US', $bodyParams);
                    $notified = true;
                } else {
                    $template = $team->ticket_settings['resolve_message_template'] ?? null;
                    if (empty($template)) {
                        $template = "Hi {{name}}, your grievance #{{ticket_number}} ({{category}}) has been updated to {{status}}.\n\nUpdate:\n{{resolution_notes}}\n\nThank you for reporting!";
                    }

                    $messageText = str_replace(array_keys($replacements), array_values($replacements), $template);

                    if ($imageUrl) {
                        $wa->sendMedia($contact->phone_number, 'image', $imageUrl, $messageText);
                    } else {
                        $wa->sendText($contact->phone_number, $messageText);
                    }
                    $notified = true;
                }

                Log::info("TicketApiController: Citizen notified for ticket {$ticket->ticket_number} via WhatsApp ({$contact->phone_number})");
            } catch (\App\Exceptions\WhatsAppPolicyException $e) {
                Log::warning("TicketApiController: 24h window closed for contact {$contact->phone_number}. Configure a Meta Template in Settings > Ticket Integration to notify citizens after 24h.");
            } catch (\Throwable $e) {
                Log::error("TicketApiController: Failed to notify citizen for ticket #{$ticket->id}: ".$e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket status updated successfully.',
            'data' => [
                'ticket_number' => $ticket->ticket_number,
                'external_id' => $ticket->external_id,
                'status' => $ticket->status,
                'resolved_at' => $ticket->resolved_at?->toIso8601String(),
                'citizen_notified' => $notified,
            ],
        ]);
    }
}
