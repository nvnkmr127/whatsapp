<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class ContactService
{
    /**
     * Create or Update a Contact.
     * Merges custom_attributes instead of overwriting.
     */
    public function createOrUpdate(array $data)
    {
        $teamId = $data['team_id'];
        $phone = $data['phone_number']; // formatting logic could go here

        if (isset($data['id'])) {
            $contact = Contact::where('team_id', $teamId)->find($data['id']);
        }

        if (!isset($contact) || !$contact) {
            $contact = Contact::firstOrNew([
                'team_id' => $teamId,
                'phone_number' => $phone,
            ]);
        }

        // Merge Attributes
        if (isset($data['custom_attributes']) && is_array($data['custom_attributes'])) {
            $current = $contact->custom_attributes ?? [];
            $contact->custom_attributes = array_merge($current, $data['custom_attributes']);
            unset($data['custom_attributes']);
        }

        // Fill other fields
        $contact->fill(Arr::except($data, ['team_id', 'phone_number']));
        $contact->save();

        return $contact;
    }

    /**
     * Assign tags to a contact (by Name or ID).
     * Creates new tags if they don't exist for the team.
     */
    public function syncTags(Contact $contact, array $tags)
    {
        $ids = [];

        foreach ($tags as $tagInput) {
            if (is_numeric($tagInput)) {
                $ids[] = $tagInput;
            } else {
                // Find or Create by Name
                $tag = ContactTag::firstOrCreate(
                    ['team_id' => $contact->team_id, 'name' => $tagInput]
                );
                $ids[] = $tag->id;
            }
        }

        $contact->tags()->sync($ids);
    }

    /**
     * Add specific tags without removing others.
     */
    public function addTags(Contact $contact, array $tags)
    {
        $ids = [];
        foreach ($tags as $tagInput) {
            if (is_numeric($tagInput)) {
                $ids[] = $tagInput;
            } else {
                $tag = ContactTag::firstOrCreate(
                    ['team_id' => $contact->team_id, 'name' => $tagInput]
                );
                $ids[] = $tag->id;
            }
        }
        $contact->tags()->syncWithoutDetaching($ids);
    }

    /**
     * Helper to get a custom attribute.
     */
    public function getAttribute(Contact $contact, string $key, $default = null)
    {
        return $contact->custom_attributes[$key] ?? $default;
    }
}
