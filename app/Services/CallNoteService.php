<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\WhatsAppCall;
use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CallNoteService
{
    /**
     * Add a note to a contact after a call and update interaction metadata.
     */
    public function addCallNote(Contact $contact, User $user, string $content, array $metadata = []): Note
    {
        return DB::transaction(function () use ($contact, $user, $content, $metadata) {
            // 1. Create the note
            $note = Note::create([
                'team_id' => $contact->team_id,
                'contact_id' => $contact->id,
                'user_id' => $user->id,
                'body' => $content,
                'type' => 'note',
                'metadata' => array_merge([
                    'type' => 'call',
                    'created_at' => now(),
                ], $metadata),
            ]);

            // 2. Update contact's last interaction
            $contact->update([
                'last_interaction_at' => now(),
            ]);

            // 3. Update custom attributes with outcome if provided
            if (isset($metadata['outcome'])) {
                $customAttributes = $contact->custom_attributes ?? [];
                $customAttributes['last_call_outcome'] = $metadata['outcome'];
                $contact->update(['custom_attributes' => $customAttributes]);
            }

            return $note;
        });
    }
}
