<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class EmbedTokenService
{
    /**
     * Generate an encrypted token for embedding a contact's chat.
     */
    /**
     * Generate an encrypted token for embedding a contact's chat.
     * Default duration: 10 minutes (Hardened).
     */
    public function generateToken(Contact $contact, array $permissions = ['read', 'write'], int $durationMinutes = 10): string
    {
        $payload = [
            'contact_id' => $contact->id,
            'team_id' => $contact->team_id,
            'permissions' => $permissions,
            'expires_at' => Carbon::now()->addMinutes($durationMinutes)->timestamp,
        ];

        return Crypt::encrypt($payload);
    }

    /**
     * Decrypt and validate the token. Returns payload or null.
     */
    public function validateToken(string $token): ?array
    {
        try {
            $payload = Crypt::decrypt($token);

            if (Carbon::now()->timestamp > $payload['expires_at']) {
                return null; // Expired
            }

            return $payload;

        } catch (\Exception $e) {
            return null; // Invalid
        }
    }
}
