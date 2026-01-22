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
     * Thread-safe implementation with database locking.
     */
    public function createOrUpdate(array $data)
    {
        $teamId = $data['team_id'];
        $phone = \App\Helpers\PhoneNumberHelper::normalize($data['phone_number']);
        $data['phone_number'] = $phone;

        return DB::transaction(function () use ($teamId, $phone, $data) {
            // Use lockForUpdate to prevent race conditions
            $contact = Contact::lockForUpdate()
                ->where('team_id', $teamId)
                ->where('phone_number', $phone)
                ->first();

            if (!$contact) {
                // Handle ID-based lookup for updates
                if (isset($data['id'])) {
                    $contact = Contact::lockForUpdate()
                        ->where('team_id', $teamId)
                        ->find($data['id']);

                    // If found by ID but phone changed, check for conflicts
                    if ($contact && $contact->phone_number !== $phone) {
                        $exists = Contact::where('team_id', $teamId)
                            ->where('phone_number', $phone)
                            ->where('id', '!=', $contact->id)
                            ->exists();

                        if ($exists) {
                            throw new \Exception("Another contact with this phone number already exists.");
                        }
                        $contact->phone_number = $phone;
                    }
                }
            }

            // Create new contact if not found
            if (!$contact) {
                $contact = new Contact();
                $contact->team_id = $teamId;
                $contact->phone_number = $phone;
            }

            // Merge custom attributes (deep merge for nested arrays)
            if (isset($data['custom_attributes']) && is_array($data['custom_attributes'])) {
                $current = $contact->custom_attributes ?? [];
                $merged = $this->deepMerge($current, $data['custom_attributes']);

                // Limit to prevent bloat
                if (count($merged) > 50) {
                    throw new \Exception("Custom attributes limit exceeded (max 50 keys)");
                }

                $contact->custom_attributes = $merged;
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
        });
    }

    /**
     * Deep merge arrays recursively.
     */
    protected function deepMerge(array $array1, array $array2): array
    {
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                $array1[$key] = $this->deepMerge($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }
        return $array1;
    }

    /**
     * Normalize phone number format (deprecated - use PhoneNumberHelper instead)
     * @deprecated Use \App\Helpers\PhoneNumberHelper::normalize() instead
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        return \App\Helpers\PhoneNumberHelper::normalize($phone);
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
