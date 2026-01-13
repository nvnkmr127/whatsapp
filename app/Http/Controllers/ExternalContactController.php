<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\ConsentService;

class ExternalContactController extends Controller
{
    /**
     * Store a new contact and opt them in (from Website/Landing Page).
     * Endpoint: POST /api/v1/contacts
     * Auth: Sanctum/Token
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:teams,id',
            'phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'], // E.164-ish compliance
            'name' => 'nullable|string',
            'source' => 'nullable|string', // e.g., 'WEBSITE', 'LANDING_PAGE'
            'custom_attributes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // 1. Verify Team Access (if using Token assigned to User, check if User belongs to Team)
        // For simple MVP without team-specific API keys, we assume the Bearer token (User) has access.
        $user = $request->user();
        if (!$user->belongsToTeam(Team::find($request->team_id)) && !$user->ownsTeam(Team::find($request->team_id))) {
            return response()->json(['error' => 'Unauthorized access to this Team'], 403);
        }

        // 2. Create/Find Contact
        $contact = Contact::firstOrCreate(
            ['team_id' => $request->team_id, 'phone_number' => $request->phone],
            ['name' => $request->name ?? $request->phone]
        );

        // Update Custom Attributes (Context)
        if ($request->has('custom_attributes')) {
            $current = $contact->custom_attributes ?? [];
            $new = $request->input('custom_attributes', []);
            // Simple array merge - new keys overwrite old
            $contact->update(['custom_attributes' => array_merge($current, $new)]);
        }

        // 3. Opt-In
        (new ConsentService)->optIn(
            $contact,
            $request->source ?? 'WEBSITE',
            "Opt-in via External API (User ID: {$user->id})"
        );

        return response()->json([
            'success' => true,
            'contact' => $contact,
            'message' => 'Contact subscribed successfully.'
        ]);
    }
}
