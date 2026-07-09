<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Models\WhatsAppCall;
use App\Services\Mcp\McpTool;

class GetCallHistoryTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'get_call_history',
            'description' => 'Get the WhatsApp call history for a contact or the whole team. Returns call direction, status, duration, and cost.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'phone' => ['type' => 'string', 'description' => 'Optional. Contact phone number in E.164 format to filter calls.'],
                    'limit' => ['type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 100],
                ],
                'required' => [],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $query = WhatsAppCall::where('team_id', $team->id)->orderBy('created_at', 'desc');

        $phone = null;
        if (!empty($input['phone'])) {
            $phone = \App\Helpers\PhoneNumberHelper::normalize($input['phone']);
            $contact = \App\Models\Contact::where('team_id', $team->id)->where('phone_number', $phone)->first();
            
            if ($contact) {
                $query->where('contact_id', $contact->id);
            } else {
                return [['type' => 'text', 'text' => "No contact found for phone {$phone}."]];
            }
        }

        $limit = min((int) ($input['limit'] ?? 50), 100);
        $calls = $query->limit($limit)->get()->map(fn($call) => [
            'id'               => $call->id,
            'direction'        => $call->direction,
            'status'           => $call->status,
            'from_number'      => $call->from_number,
            'to_number'        => $call->to_number,
            'duration_seconds' => $call->duration_seconds,
            'cost_amount'      => $call->cost_amount,
            'initiated_at'     => $call->initiated_at?->toDateTimeString(),
            'answered_at'      => $call->answered_at?->toDateTimeString(),
            'ended_at'         => $call->ended_at?->toDateTimeString(),
            'failure_reason'   => $call->failure_reason,
        ]);

        if ($calls->isEmpty()) {
            $msg = $phone ? "No call history found for {$phone}." : "No call history found for this team.";
            return [['type' => 'text', 'text' => $msg]];
        }

        return [['type' => 'text', 'text' => json_encode([
            'total_returned' => $calls->count(),
            'calls'          => $calls->values(),
        ], JSON_PRETTY_PRINT)]];
    }
}
