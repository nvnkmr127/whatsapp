<?php

namespace App\Services;

use App\Models\Contact;
use App\Helpers\PhoneNumberHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ContactResolver
{
    /**
     * Resolve contact from phone number with caching.
     */
    public function resolve(string $phone, int $teamId): ?Contact
    {
        // Normalize phone first
        $normalized = PhoneNumberHelper::normalize($phone);

        // Check cache
        $cacheKey = "contact:{$teamId}:{$normalized}";

        return Cache::remember($cacheKey, 300, function () use ($normalized, $teamId) {
            return Contact::where('team_id', $teamId)
                ->where('phone_number', $normalized)
                ->with(['tags', 'assignedTo'])
                ->select([
                    'id',
                    'name',
                    'phone_number',
                    'email',
                    'opt_in_status',
                    'engagement_score',
                    'lifecycle_state',
                    'assigned_to',
                    'custom_attributes',
                    'last_interaction_at',
                    'has_pending_reply',
                    'is_within_24h_window',
                    'version'
                ])
                ->first();
        });
    }

    /**
     * Resolve multiple contacts in batch.
     */
    public function resolveBatch(array $phones, int $teamId): Collection
    {
        $normalized = array_map(
            fn($phone) => PhoneNumberHelper::normalize($phone),
            $phones
        );

        return Contact::where('team_id', $teamId)
            ->whereIn('phone_number', $normalized)
            ->with(['tags', 'assignedTo'])
            ->select([
                'id',
                'name',
                'phone_number',
                'email',
                'opt_in_status',
                'engagement_score',
                'lifecycle_state',
                'assigned_to',
                'custom_attributes',
                'last_interaction_at',
                'has_pending_reply',
                'is_within_24h_window',
                'version'
            ])
            ->get()
            ->keyBy('phone_number');
    }

    /**
     * Invalidate contact cache.
     */
    public function invalidateCache(Contact $contact): void
    {
        $cacheKey = "contact:{$contact->team_id}:{$contact->phone_number}";
        Cache::forget($cacheKey);
    }

    /**
     * Get or create contact (for inbox).
     */
    public function getOrCreate(string $phone, int $teamId, array $attributes = []): Contact
    {
        $normalized = PhoneNumberHelper::normalize($phone);

        $contact = $this->resolve($normalized, $teamId);

        if (!$contact) {
            $contact = Contact::create(array_merge([
                'team_id' => $teamId,
                'phone_number' => $normalized,
            ], $attributes));

            // Cache the new contact
            $cacheKey = "contact:{$teamId}:{$normalized}";
            Cache::put($cacheKey, $contact, 300);
        }

        return $contact;
    }
}
