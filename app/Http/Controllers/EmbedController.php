<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\EmbedTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmbedController extends Controller
{
    protected $tokenService;

    public function __construct(EmbedTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * API: Generate an embed token for a specific phone number.
     * POST /api/v1/embed-token
     */
    public function generateToken(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $user = $request->user();
        if (!$user->currentTeam) {
            return response()->json(['error' => 'No Team Context'], 400);
        }

        // Find or Create Contact (to ensure ID exists)
        // We use the existing logic: simple find or create
        $contact = Contact::firstOrCreate(
            ['team_id' => $user->currentTeam->id, 'phone_number' => $request->phone_number],
            ['name' => $request->input('name', 'Unknown')]
        );

        $permissions = $request->input('permissions', ['read', 'write']);

        if (!is_array($permissions) || array_diff($permissions, ['read', 'write'])) {
            return response()->json(['error' => 'Invalid permissions. Allowed: read, write'], 422);
        }

        $token = $this->tokenService->generateToken($contact, $permissions);

        return response()->json([
            'token' => $token,
            'embed_url' => route('embed.chat', ['token' => $token]),
        ]);
    }

    /**
     * Web: Display the embedded chat.
     * GET /embed/chat?token=...
     */
    public function show(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            abort(403, 'Missing Token');
        }

        $payload = $this->tokenService->validateToken($token);

        if (!$payload) {
            abort(403, 'Invalid or Expired Token');
        }

        $contactId = $payload['contact_id'];

        // Find conversation or create dummy context for the view
        // The view will handle finding the conversation similarly to ContactDetails
        // But we need to ensure the Livewire component knows who to chat with.

        return view('chat.embedded', [
            'contactId' => $contactId,
            'permissions' => $payload['permissions'] ?? ['read', 'write']
        ]);
    }
}
