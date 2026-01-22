<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactMergeLog;
use App\Models\DuplicateDetectionQueue;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\ConsentLog;
use App\Helpers\PhoneNumberHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactDeduplicationService
{
    /**
     * Find duplicate contacts for a given contact.
     * 
     * @param Contact $contact
     * @param float $threshold Minimum confidence score (0-100)
     * @return Collection
     */
    public function findDuplicates(Contact $contact, float $threshold = 60): Collection
    {
        $duplicates = collect();

        // Strategy 1: Exact phone match (100% confidence)
        $exactMatches = $this->findExactPhoneMatches($contact);
        foreach ($exactMatches as $match) {
            $duplicates->push([
                'contact' => $match,
                'score' => $this->calculateDuplicateScore($contact, $match),
            ]);
        }

        // Strategy 2: Fuzzy name matching
        if ($contact->name) {
            $fuzzyMatches = $this->findFuzzyNameMatches($contact);
            foreach ($fuzzyMatches as $match) {
                if (!$duplicates->contains('contact.id', $match->id)) {
                    $score = $this->calculateDuplicateScore($contact, $match);
                    if ($score['score'] >= $threshold) {
                        $duplicates->push([
                            'contact' => $match,
                            'score' => $score,
                        ]);
                    }
                }
            }
        }

        return $duplicates->sortByDesc('score.score');
    }

    /**
     * Find contacts with exact phone number match.
     */
    protected function findExactPhoneMatches(Contact $contact): Collection
    {
        return Contact::where('team_id', $contact->team_id)
            ->where('phone_number', $contact->phone_number)
            ->where('id', '!=', $contact->id)
            ->get();
    }

    /**
     * Find contacts with similar names.
     */
    protected function findFuzzyNameMatches(Contact $contact): Collection
    {
        $candidates = Contact::where('team_id', $contact->team_id)
            ->where('id', '!=', $contact->id)
            ->whereNotNull('name')
            ->get();

        return $candidates->filter(function ($candidate) use ($contact) {
            $similarity = $this->calculateNameSimilarity($contact->name, $candidate->name);
            return $similarity >= 0.8; // 80% similarity threshold
        });
    }

    /**
     * Calculate name similarity using Levenshtein distance.
     */
    protected function calculateNameSimilarity(string $name1, string $name2): float
    {
        $name1 = $this->normalizeNameForComparison($name1);
        $name2 = $this->normalizeNameForComparison($name2);

        if ($name1 === $name2) {
            return 1.0;
        }

        $distance = levenshtein($name1, $name2);
        $maxLength = max(strlen($name1), strlen($name2));

        if ($maxLength === 0) {
            return 0.0;
        }

        return 1 - ($distance / $maxLength);
    }

    /**
     * Normalize name for comparison.
     */
    protected function normalizeNameForComparison(string $name): string
    {
        // Convert to lowercase
        $name = mb_strtolower($name, 'UTF-8');

        // Remove titles
        $name = preg_replace('/\b(mr|mrs|ms|dr|prof)\.?\s+/i', '', $name);

        // Remove middle initials
        $name = preg_replace('/\s+[a-z]\.?\s+/i', ' ', $name);

        // Reverse "Last, First" to "First Last"
        if (strpos($name, ',') !== false) {
            $parts = explode(',', $name);
            if (count($parts) === 2) {
                $name = trim($parts[1]) . ' ' . trim($parts[0]);
            }
        }

        // Common nickname mappings
        $nicknames = [
            'robert' => 'bob',
            'william' => 'bill',
            'richard' => 'dick',
            'mohammed' => 'muhammad',
            'michael' => 'mike',
        ];

        foreach ($nicknames as $formal => $nickname) {
            $name = str_replace($formal, $nickname, $name);
        }

        return trim($name);
    }

    /**
     * Calculate duplicate confidence score.
     */
    public function calculateDuplicateScore(Contact $contact1, Contact $contact2): array
    {
        $score = 0;
        $reasons = [];

        // Phone match (highest weight)
        if ($contact1->phone_number === $contact2->phone_number) {
            $score += 100;
            $reasons[] = 'Exact phone match';
        }

        // Name similarity
        if ($contact1->name && $contact2->name) {
            $nameSimilarity = $this->calculateNameSimilarity($contact1->name, $contact2->name);
            if ($nameSimilarity >= 0.8) {
                $score += 50 * $nameSimilarity;
                $reasons[] = sprintf('Name similarity: %.0f%%', $nameSimilarity * 100);
            }
        }

        // Email match
        if ($contact1->email && $contact2->email && $contact1->email === $contact2->email) {
            $score += 30;
            $reasons[] = 'Email match';
        }

        // Same tags
        $commonTags = $contact1->tags->pluck('id')->intersect($contact2->tags->pluck('id'));
        if ($commonTags->count() > 0) {
            $score += min(10 * $commonTags->count(), 20); // Cap at 20
            $reasons[] = sprintf('%d common tags', $commonTags->count());
        }

        return [
            'score' => min($score, 100), // Cap at 100
            'confidence' => $this->getConfidenceLevel($score),
            'reasons' => $reasons,
        ];
    }

    /**
     * Get confidence level from score.
     */
    protected function getConfidenceLevel(float $score): string
    {
        if ($score >= 100)
            return 'certain';
        if ($score >= 80)
            return 'high';
        if ($score >= 60)
            return 'medium';
        if ($score >= 40)
            return 'low';
        return 'unlikely';
    }

    /**
     * Merge duplicate contacts.
     */
    public function mergeContacts(Contact $primary, Contact $duplicate, array $options = []): Contact
    {
        // Validate same team
        if ($primary->team_id !== $duplicate->team_id) {
            throw new \Exception("Cannot merge contacts from different teams");
        }

        // Validate not same contact
        if ($primary->id === $duplicate->id) {
            throw new \Exception("Cannot merge contact with itself");
        }

        // Check for circular merges
        $this->detectCircularMerges($primary, $duplicate);

        return DB::transaction(function () use ($primary, $duplicate, $options) {
            // 1. Merge basic fields
            $this->mergeBasicFields($primary, $duplicate);

            // 2. Merge custom attributes
            $this->mergeCustomAttributes($primary, $duplicate);

            // 3. Merge tags
            $this->mergeTags($primary, $duplicate);

            // 4. Update message references
            $this->updateMessageReferences($primary, $duplicate);

            // 5. Update conversation references
            $this->updateConversationReferences($primary, $duplicate);

            // 6. Merge consent logs
            $this->mergeConsentLogs($primary, $duplicate);

            // 7. Create merge audit log
            $this->logMerge($primary, $duplicate, $options);

            // 8. Soft delete duplicate
            if ($options['hard_delete'] ?? false) {
                $duplicate->forceDelete();
            } else {
                $duplicate->delete(); // Soft delete
            }

            Log::info("Merged contacts", [
                'primary_id' => $primary->id,
                'duplicate_id' => $duplicate->id,
                'team_id' => $primary->team_id,
            ]);

            return $primary->fresh();
        });
    }

    /**
     * Detect circular merge attempts.
     */
    protected function detectCircularMerges(Contact $contact1, Contact $contact2): void
    {
        $contact1MergedInto = ContactMergeLog::where('duplicate_contact_id', $contact1->id)
            ->where('primary_contact_id', $contact2->id)
            ->exists();

        $contact2MergedInto = ContactMergeLog::where('duplicate_contact_id', $contact2->id)
            ->where('primary_contact_id', $contact1->id)
            ->exists();

        if ($contact1MergedInto || $contact2MergedInto) {
            throw new \Exception(
                "Circular merge detected between contacts #{$contact1->id} and #{$contact2->id}"
            );
        }
    }

    /**
     * Merge basic contact fields.
     */
    protected function mergeBasicFields(Contact $primary, Contact $duplicate): void
    {
        // Name: Use longest non-null
        if (!$primary->name || (strlen($duplicate->name ?? '') > strlen($primary->name))) {
            $primary->name = $duplicate->name;
        }

        // Email: Use first non-null
        if (!$primary->email && $duplicate->email) {
            $primary->email = $duplicate->email;
        }

        // Consent: Most restrictive (opt-out takes precedence)
        $this->resolveConsentConflict($primary, $duplicate);

        // Timestamps: Use earliest/latest
        if ($duplicate->opt_in_at && (!$primary->opt_in_at || $duplicate->opt_in_at < $primary->opt_in_at)) {
            $primary->opt_in_at = $duplicate->opt_in_at;
            if (!$primary->opt_in_source) {
                $primary->opt_in_source = $duplicate->opt_in_source;
            }
        }

        if ($duplicate->last_interaction_at && (!$primary->last_interaction_at || $duplicate->last_interaction_at > $primary->last_interaction_at)) {
            $primary->last_interaction_at = $duplicate->last_interaction_at;
        }

        if ($duplicate->last_customer_message_at && (!$primary->last_customer_message_at || $duplicate->last_customer_message_at > $primary->last_customer_message_at)) {
            $primary->last_customer_message_at = $duplicate->last_customer_message_at;
        }

        $primary->save();
    }

    /**
     * Resolve consent conflicts (GDPR compliance).
     */
    protected function resolveConsentConflict(Contact $primary, Contact $duplicate): void
    {
        // If either is opted-out, result is opted-out
        if ($primary->opt_in_status === 'opted_out' || $duplicate->opt_in_status === 'opted_out') {
            $primary->opt_in_status = 'opted_out';

            // Log the opt-out preservation
            ConsentLog::create([
                'team_id' => $primary->team_id,
                'contact_id' => $primary->id,
                'action' => 'OPT_OUT',
                'source' => 'MERGE_CONFLICT',
                'notes' => "Opted-out status preserved from merged contact #{$duplicate->id}",
            ]);
        } elseif ($duplicate->opt_in_status === 'opted_in' && $primary->opt_in_status !== 'opted_in') {
            $primary->opt_in_status = 'opted_in';
            $primary->opt_in_source = $duplicate->opt_in_source;
            $primary->opt_in_at = $duplicate->opt_in_at;
        }
    }

    /**
     * Merge custom attributes.
     */
    protected function mergeCustomAttributes(Contact $primary, Contact $duplicate): void
    {
        $primaryAttrs = $primary->custom_attributes ?? [];
        $duplicateAttrs = $duplicate->custom_attributes ?? [];

        // Deep merge: duplicate values override primary only if primary is null
        foreach ($duplicateAttrs as $key => $value) {
            if (!isset($primaryAttrs[$key]) || $primaryAttrs[$key] === null) {
                $primaryAttrs[$key] = $value;
            }
        }

        $primary->custom_attributes = $primaryAttrs;
        $primary->save();
    }

    /**
     * Merge tags (union).
     */
    protected function mergeTags(Contact $primary, Contact $duplicate): void
    {
        $duplicateTagIds = $duplicate->tags->pluck('id')->toArray();
        $primary->tags()->syncWithoutDetaching($duplicateTagIds);
    }

    /**
     * Update message references.
     */
    protected function updateMessageReferences(Contact $primary, Contact $duplicate): void
    {
        Message::where('contact_id', $duplicate->id)
            ->update(['contact_id' => $primary->id]);
    }

    /**
     * Update conversation references.
     */
    protected function updateConversationReferences(Contact $primary, Contact $duplicate): void
    {
        Conversation::where('contact_id', $duplicate->id)
            ->update(['contact_id' => $primary->id]);
    }

    /**
     * Merge consent logs.
     */
    protected function mergeConsentLogs(Contact $primary, Contact $duplicate): void
    {
        ConsentLog::where('contact_id', $duplicate->id)
            ->update(['contact_id' => $primary->id]);
    }

    /**
     * Log merge for audit trail.
     */
    protected function logMerge(Contact $primary, Contact $duplicate, array $options): void
    {
        ContactMergeLog::create([
            'team_id' => $primary->team_id,
            'primary_contact_id' => $primary->id,
            'duplicate_contact_id' => $duplicate->id,
            'duplicate_data' => $duplicate->toArray(),
            'merged_by' => auth()->id(),
            'merge_strategy' => $options['strategy'] ?? 'manual',
            'confidence_score' => $options['confidence_score'] ?? null,
            'merged_at' => now(),
        ]);
    }

    /**
     * Queue duplicate for manual review.
     */
    public function queueForReview(Contact $contact, Contact $potentialDuplicate, array $score): void
    {
        // Check if already queued
        $existing = DuplicateDetectionQueue::where('team_id', $contact->team_id)
            ->where(function ($query) use ($contact, $potentialDuplicate) {
                $query->where(function ($q) use ($contact, $potentialDuplicate) {
                    $q->where('contact_id', $contact->id)
                        ->where('potential_duplicate_id', $potentialDuplicate->id);
                })->orWhere(function ($q) use ($contact, $potentialDuplicate) {
                    $q->where('contact_id', $potentialDuplicate->id)
                        ->where('potential_duplicate_id', $contact->id);
                });
            })
            ->where('status', 'pending')
            ->exists();

        if (!$existing) {
            DuplicateDetectionQueue::create([
                'team_id' => $contact->team_id,
                'contact_id' => $contact->id,
                'potential_duplicate_id' => $potentialDuplicate->id,
                'confidence_score' => $score['score'],
                'confidence_level' => $score['confidence'],
                'match_reasons' => $score['reasons'],
                'status' => 'pending',
            ]);
        }
    }
}
