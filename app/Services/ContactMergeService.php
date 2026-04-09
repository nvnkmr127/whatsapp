<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactMergeLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactMergeService
{
    /**
     * Merge source contact into primary contact.
     *
     * @param  Contact  $primary  The contact that remains
     * @param  Contact  $source  The contact that will be deleted
     * @param  int|null  $mergedBy  User ID who performed the merge
     */
    public function merge(Contact $primary, Contact $source, ?int $mergedBy = null): bool
    {
        if ($primary->id === $source->id || $primary->team_id !== $source->team_id) {
            return false;
        }

        return DB::transaction(function () use ($primary, $source, $mergedBy) {
            // Keep track of what we're merging
            $duplicateData = [
                'name' => $source->name,
                'phone_number' => $source->phone_number,
                'email' => $source->email,
                'custom_attributes' => $source->custom_attributes,
                'tags' => $source->tags->pluck('name')->toArray(),
            ];

            // 1. Move Messages
            $source->messages()->update(['contact_id' => $primary->id]);

            // 2. Move Notes
            $source->notes()->update(['contact_id' => $primary->id]);

            // 3. Move Deals
            $source->deals()->update(['contact_id' => $primary->id]);

            // 4. Move Orders
            $source->orders()->update(['contact_id' => $primary->id]);

            // 5. Move Workflow Logs
            $source->workflowLogs()->update(['subject_id' => $primary->id]);

            // 6. Merge Tags (avoid duplicates)
            $sourceTags = $source->tags->pluck('id');
            $primaryTags = $primary->tags->pluck('id');
            $mergedTags = $primaryTags->merge($sourceTags)->unique();
            $primary->tags()->sync($mergedTags);

            // 7. Merge Custom Attributes (Primary wins on conflict)
            $primaryAttributes = $primary->custom_attributes ?? [];
            $sourceAttributes = $source->custom_attributes ?? [];
            $primary->update([
                'custom_attributes' => array_merge($sourceAttributes, $primaryAttributes),
                'email' => $primary->email ?: $source->email,
            ]);

            // 8. Log the merge
            ContactMergeLog::create([
                'team_id' => $primary->team_id,
                'primary_contact_id' => $primary->id,
                'duplicate_contact_id' => $source->id,
                'duplicate_data' => $duplicateData,
                'merged_by' => $mergedBy,
                'merged_at' => now(),
                'confidence_score' => 100.0, // Manual merge is 100%
            ]);

            // 9. Soft delete source contact
            $source->delete();

            return true;
        });
    }
}
