<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\ContactResolver;
use App\Traits\StandardApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboxContactController extends Controller
{
    use StandardApiResponses;

    public function __construct(
        protected ContactResolver $resolver
    ) {}

    /**
     * Resolve contact from phone number.
     */
    public function resolve(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $user = $request->user();
        $teamId = $user?->current_team_id;

        if (! $teamId) {
            return $this->error('No team context selected.', 400);
        }

        $contact = $this->resolver->resolve(
            $request->input('phone'),
            $teamId
        );

        if (! $contact) {
            return $this->error('Contact not found.', 404);
        }

        return $this->success($contact, 'Contact resolved successfully.');
    }

    /**
     * Batch resolve contacts.
     */
    public function resolveBatch(Request $request): JsonResponse
    {
        $request->validate([
            'phones' => 'required|array',
            'phones.*' => 'required|string',
        ]);

        $user = $request->user();
        $teamId = $user?->current_team_id;

        if (! $teamId) {
            return $this->error('No team context selected.', 400);
        }

        $contacts = $this->resolver->resolveBatch(
            $request->input('phones'),
            $teamId
        );

        return $this->success(['contacts' => $contacts], 'Batch resolve completed.');
    }

    /**
     * Update contact with conflict detection.
     */
    public function update(Request $request, Contact $contact): JsonResponse
    {
        $request->validate([
            'version' => 'required|integer',
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|nullable',
            'assigned_to' => 'sometimes|integer|nullable',
            'custom_attributes' => 'sometimes|array',
            'custom_attributes.email' => 'sometimes|email|nullable|max:255',
            'custom_attributes.company' => 'sometimes|string|nullable|max:255',
        ]);

        $user = $request->user();
        if ($contact->team_id !== $user?->current_team_id) {
            return $this->error('Unauthorized', 403);
        }

        // Check for conflicts (optimistic locking)
        if ($contact->version != $request->input('version')) {
            return $this->error(
                'This contact was modified by another user.',
                409,
                [
                    'current_version' => $contact->version,
                    'current_data' => $contact->toArray(),
                ],
                'ERR_CONFLICT'
            );
        }

        // Update contact
        $contact->update($request->only([
            'name',
            'email',
            'assigned_to',
            'custom_attributes',
        ]));

        return $this->success(
            $contact->fresh(),
            'Contact updated successfully.'
        );
    }

    /**
     * Assign contact to agent.
     */
    public function assign(Request $request, Contact $contact): JsonResponse
    {
        $request->validate([
            'agent_id' => 'required|integer|exists:users,id',
        ]);

        $user = $request->user();
        if ($contact->team_id !== $user?->current_team_id) {
            return $this->error('Unauthorized', 403);
        }

        $contact->update([
            'assigned_to' => $request->input('agent_id'),
        ]);

        return $this->success($contact->fresh(), 'Contact assigned successfully.');
    }
}
