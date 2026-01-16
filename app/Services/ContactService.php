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

        // Trigger automation if this is a new contact
        if ($contact->wasRecentlyCreated) {
            try {
                $whatsappService = new WhatsAppService();
                $automationService = new AutomationService($whatsappService);
                $automationService->checkSpecialTriggers($contact, 'contact_added');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Contact Added Automation Trigger Failed: ' . $e->getMessage());
                // Don't throw - contact creation should succeed even if automation fails
            }
        }

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

        // Trigger automation for tag assigned
        if (!empty($ids)) {
            try {
                $whatsappService = new WhatsAppService();
                $automationService = new AutomationService($whatsappService);
                $automationService->checkSpecialTriggers($contact, 'tag_assigned');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Tag Assigned Automation Trigger Failed: ' . $e->getMessage());
                // Don't throw - tag assignment should succeed even if automation fails
            }
        }
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

        // Trigger automation for tag assigned
        if (!empty($ids)) {
            try {
                $whatsappService = new WhatsAppService();
                $automationService = new AutomationService($whatsappService);
                $automationService->checkSpecialTriggers($contact, 'tag_assigned');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Tag Assigned Automation Trigger Failed: ' . $e->getMessage());
                // Don't throw - tag assignment should succeed even if automation fails
            }
        }
    }

    /**
     * Helper to get a custom attribute.
     */
    public function getAttribute(Contact $contact, string $key, $default = null)
    {
        return $contact->custom_attributes[$key] ?? $default;
    }
}
