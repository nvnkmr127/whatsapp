<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\ContactResolver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InboxContactController extends Controller
{
    protected $resolver;

    public function __construct(ContactResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Resolve contact from phone number.
     */
    public function resolve(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $teamId = auth()->user()?->current_team_id;
        if (!$teamId) {
            return response()->json([
                'message' => 'No team context',
            ], 400);
        }

        $contact = $this->resolver->resolve(
            $request->input('phone'),
            $teamId
        );

        if (!$contact) {
            return response()->json([
                'message' => 'Contact not found',
            ], 404);
        }

        return response()->json([
            'contact' => $contact,
        ]);
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

        $teamId = auth()->user()?->current_team_id;
        if (!$teamId) {
            return response()->json([
                'message' => 'No team context',
            ], 400);
        }

        $contacts = $this->resolver->resolveBatch(
            $request->input('phones'),
            $teamId
        );

        return response()->json([
            'contacts' => $contacts,
        ]);
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
        ]);

        if ($contact->team_id !== auth()->user()?->current_team_id) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        // Check for conflicts (optimistic locking)
        $clientVersion = $request->input('version');

        if ($contact->version != $clientVersion) {
            return response()->json([
                'error' => 'Conflict detected',
                'message' => 'This contact was modified by another user',
                'current_version' => $contact->version,
                'current_data' => $contact->toArray(),
            ], 409);
        }

        // Update contact
        $contact->update($request->only([
            'name',
            'email',
            'assigned_to',
            'custom_attributes'
        ]));

        return response()->json([
            'contact' => $contact->fresh(),
            'version' => $contact->version,
        ]);
    }

    /**
     * Assign contact to agent.
     */
    public function assign(Request $request, Contact $contact): JsonResponse
    {
        $request->validate([
            'agent_id' => 'required|integer|exists:users,id',
        ]);

        if ($contact->team_id !== auth()->user()?->current_team_id) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $contact->update([
            'assigned_to' => $request->input('agent_id'),
        ]);

        return response()->json([
            'contact' => $contact->fresh(),
        ]);
    }
}
