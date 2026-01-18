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
        $phone = $this->normalizePhoneNumber($data['phone_number']);
        $data['phone_number'] = $phone;

        if (isset($data['id'])) {
            $contact = Contact::where('team_id', $teamId)->find($data['id']);
        }

        if (isset($contact) && $contact) {
            // Update existing contact
            if ($contact->phone_number !== $phone) {
                // Check if another contact has this number
                $exists = Contact::where('team_id', $teamId)
                    ->where('phone_number', $phone)
                    ->where('id', '!=', $contact->id)
                    ->exists();

                if ($exists) {
                    throw new \Exception("Another contact with this phone number already exists.");
                }
                $contact->phone_number = $phone;
            }
        } else {
            // Create new or find existing by phone
            $contact = Contact::where('team_id', $teamId)->where('phone_number', $phone)->first();

            if (!$contact) {
                $contact = new Contact();
                $contact->team_id = $teamId;
                $contact->phone_number = $phone;
            }
        }

        // Merge Attributes
        if (isset($data['custom_attributes']) && is_array($data['custom_attributes'])) {
            $current = $contact->custom_attributes ?? [];
            $contact->custom_attributes = array_merge($current, $data['custom_attributes']);
            unset($data['custom_attributes']);
        }

        // Fill other fields
        $contact->fill(Arr::except($data, ['team_id', 'phone_number', 'id']));
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
     * Normalize phone number format
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // If doesn't start with +, add default country code
        if (!str_starts_with($phone, '+')) {
            $defaultCode = get_setting('default_country_code', '+91');
            // Remove leading zero if present
            $phone = $defaultCode . ltrim($phone, '0');
        }

        // Store without + for consistency
        return ltrim($phone, '+');
    }

    /**
     * Assign tags to a contact (by Name or ID).
     * Creates new tags if they don't exist for the team.
     */
    public function syncTags(Contact $contact, array $tags)
    {
        $ids = $this->resolveTagIds($contact->team_id, $tags);
        $contact->tags()->sync($ids);

        // Trigger automation for tag assigned
        $this->triggerTagAutomation($contact, $ids);
    }

    /**
     * Add specific tags without removing others.
     */
    public function addTags(Contact $contact, array $tags)
    {
        $ids = $this->resolveTagIds($contact->team_id, $tags);
        $contact->tags()->syncWithoutDetaching($ids);

        // Trigger automation for tag assigned
        $this->triggerTagAutomation($contact, $ids);
    }

    /**
     * Resolve tag input (IDs or Names) to a list of Tag IDs.
     */
    protected function resolveTagIds(int $teamId, array $tags): array
    {
        $ids = [];
        $names = [];

        foreach ($tags as $tag) {
            if (is_numeric($tag)) {
                $ids[] = (int) $tag;
            } else {
                $names[] = $tag;
            }
        }

        if (empty($names)) {
            return array_unique($ids);
        }

        $names = array_unique($names);

        // Find existing tags
        $existingTags = ContactTag::where('team_id', $teamId)
            ->whereIn('name', $names)
            ->get();

        $existingNames = $existingTags->pluck('name')->toArray();
        $existingIds = $existingTags->pluck('id')->toArray();

        // Determine missing tags
        $missingNames = array_diff($names, $existingNames);

        if (!empty($missingNames)) {
            $now = now();
            $newTagsData = [];
            foreach ($missingNames as $name) {
                $newTagsData[] = [
                    'team_id' => $teamId,
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            ContactTag::insert($newTagsData);

            // Fetch the newly created IDs
            $newTags = ContactTag::where('team_id', $teamId)
                ->whereIn('name', $missingNames)
                ->pluck('id')
                ->toArray();

            $existingIds = array_merge($existingIds, $newTags);
        }

        return array_unique(array_merge($ids, $existingIds));
    }

    /**
     * Helper to trigger automation logic for assigned tags.
     */
    protected function triggerTagAutomation(Contact $contact, array $ids)
    {
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
