<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreContactRequest;
use App\Models\Contact;
use App\Services\ConsentService;
use App\Traits\StandardApiResponses;
use Illuminate\Http\Request;

class ExternalContactController extends Controller
{
    use StandardApiResponses;

    public function __construct(
        protected ConsentService $consentService
    ) {}

    /**
     * List all contacts for the authenticated team.
     * GET /api/v1/contacts
     */
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        if (! $team) {
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

        if (! $team) {
            return $this->error('No team context selected.', 400, null, 'ERR_NO_TEAM_CONTEXT');
        }

        $existing = Contact::where('team_id', $team->id)
            ->where('phone_number', $request->phone_number)
            ->first();

        if (! $existing) {
            $entitlement = app(\App\Services\EntitlementService::class)->for($team);
            if (! $entitlement->can('add_contact')) {
                return $this->error('Contact limit reached: '.$entitlement->denialReason('add_contact'), 422, null, 'ERR_CONTACT_LIMIT_REACHED');
            }
        }

        $attributes = [
            'name' => $request->name ?? ($existing?->name ?? $request->phone_number),
        ];

        if ($request->has('email')) {
            $attributes['email'] = $request->email;
        }

        if ($request->has('custom_attributes')) {
            $attributes['custom_attributes'] = array_merge(
                $existing?->custom_attributes ?? [],
                $request->custom_attributes ?? []
            );
        }

        $contact = Contact::updateOrCreate(
            [
                'team_id' => $team->id,
                'phone_number' => $request->phone_number,
            ],
            $attributes
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
