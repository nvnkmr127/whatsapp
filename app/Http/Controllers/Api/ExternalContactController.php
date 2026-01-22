<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ExternalContactController extends Controller
{
    /**
     * List all contacts for the authenticated team.
     * GET /api/v1/contacts
     */
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        if (!$team) {
            return response()->json(['error' => 'No team context'], 400);
        }

        $contacts = Contact::where('team_id', $team->id)
            ->with(['tags'])
            ->latest()
            ->paginate(50);

        return response()->json($contacts);
    }

    /**
     * Create or update a contact.
     * POST /api/v1/contacts
     */
    public function store(Request $request)
    {
        $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'custom_attributes' => 'nullable|array',
            'opt_in' => 'nullable|boolean',
            'opt_in_source' => 'nullable|string',
            'opt_in_notes' => 'nullable|string',
            'opt_in_proof_url' => 'nullable|url',
        ]);

        $team = $request->user()->currentTeam;

        if (!$team) {
            return response()->json(['error' => 'No team context'], 400);
        }

        $contact = Contact::updateOrCreate(
            [
                'team_id' => $team->id,
                'phone_number' => $request->phone_number
            ],
            [
                'name' => $request->name ?? $request->phone_number,
                'email' => $request->email,
                'custom_attributes' => $request->custom_attributes ?? [],
            ]
        );

        // Opt-in if requested
        if ($request->boolean('opt_in')) {
            (new \App\Services\ConsentService)->optIn(
                $contact,
                $request->input('opt_in_source', 'API'),
                $request->input('opt_in_notes', 'Opt-in via API'),
                $request->input('opt_in_proof_url')
            );
        }

        return response()->json([
            'success' => true,
            'contact' => $contact->fresh(['tags']),
        ], 201);
    }
}
