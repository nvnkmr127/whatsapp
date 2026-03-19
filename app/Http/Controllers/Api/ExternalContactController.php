<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\ConsentService;
use App\Traits\StandardApiResponses;
use App\Http\Requests\Api\StoreContactRequest;
use Illuminate\Http\Request;

class ExternalContactController extends Controller
{
    use StandardApiResponses;

    public function __construct(
        protected ConsentService $consentService
    ) {
    }

    /**
     * List all contacts for the authenticated team.
     * GET /api/v1/contacts
     */
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        if (!$team) {
            return $this->error('No team context selected.', 400, null, 'ERR_NO_TEAM_CONTEXT');
        }

        $contacts = Contact::where('team_id', $team->id)
            ->with(['tags'])
            ->latest()
            ->paginate(50);

        return $this->paginated($contacts, 'Contacts retrieved successfully.');
    }

    /**
     * Create or update a contact.
     * POST /api/v1/contacts
     */
    public function store(StoreContactRequest $request)
    {
        $team = $request->user()->currentTeam;

        if (!$team) {
            return $this->error('No team context selected.', 400, null, 'ERR_NO_TEAM_CONTEXT');
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
            $this->consentService->optIn(
                $contact,
                $request->input('opt_in_source', 'API'),
                $request->input('opt_in_notes', 'Opt-in via API'),
                $request->input('opt_in_proof_url')
            );
        }

        return $this->success(
            $contact->fresh(['tags']),
            'Contact created or updated successfully.',
            201
        );
    }
}

