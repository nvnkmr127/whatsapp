<?php

namespace App\Services;

use App\Models\Segment;
use App\Models\Contact;
use App\Jobs\PrecomputeSegmentMembership;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SegmentService
{
    /**
     * Get segment members with hybrid caching strategy.
     */
    public function getMembers(Segment $segment, int $page = 1, int $perPage = 100): Collection
    {
        // Large segments: Use materialized view
        if ($segment->isLargeSegment()) {
            return $this->getMembersFromMaterializedView($segment, $page, $perPage);
        }

        // Medium segments: Use database cache
        if ($segment->isMediumSegment()) {
            return $this->getMembersFromCache($segment, $page, $perPage);
        }

        // Small segments: Realtime query
        return $this->getMembersRealtime($segment, $page, $perPage);
    }

    /**
     * Get members from materialized view (large segments).
     */
    protected function getMembersFromMaterializedView(Segment $segment, int $page, int $perPage): Collection
    {
        // Check if needs recomputation
        if ($segment->needsRecomputation()) {
            PrecomputeSegmentMembership::dispatch($segment);
        }

        return DB::table('segment_memberships as sm')
            ->join('contacts as c', 'sm.contact_id', '=', 'c.id')
            ->where('sm.segment_id', $segment->id)
            ->select('c.*')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn($contact) => Contact::hydrate([(array) $contact])->first());
    }

    /**
     * Get members from database cache (medium segments).
     */
    protected function getMembersFromCache(Segment $segment, int $page, int $perPage): Collection
    {
        // Check database cache
        $cache = DB::table('segment_cache')
            ->where('segment_id', $segment->id)
            ->where('expires_at', '>', now())
            ->first();

        if ($cache) {
            // Cache hit
            $contactIds = explode(',', $cache->contact_ids);

            // Paginate
            $offset = ($page - 1) * $perPage;
            $paginatedIds = array_slice($contactIds, $offset, $perPage);

            return Contact::whereIn('id', $paginatedIds)->get();
        }

        // Cache miss - compute and cache
        $contacts = $this->computeSegment($segment);
        $contactIds = $contacts->pluck('id')->toArray();

        // Store in cache (1 hour expiry)
        DB::table('segment_cache')->updateOrInsert(
            ['segment_id' => $segment->id],
            [
                'contact_ids' => implode(',', $contactIds),
                'member_count' => count($contactIds),
                'cached_at' => now(),
                'expires_at' => now()->addHour(),
            ]
        );

        return $contacts->forPage($page, $perPage);
    }

    /**
     * Get members with realtime query (small segments).
     */
    protected function getMembersRealtime(Segment $segment, int $page, int $perPage): Collection
    {
        $query = SegmentBuilder::buildQuery($segment->rules, $segment->team_id);

        return $query->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();
    }

    /**
     * Compute segment members.
     */
    public function computeSegment(Segment $segment): Collection
    {
        $query = SegmentBuilder::buildQuery($segment->rules, $segment->team_id);

        return $query->get();
    }

    /**
     * Get eligible contacts for broadcast (with consent filtering).
     */
    public function getEligibleContacts(Segment $segment): Collection
    {
        if ($segment->isLargeSegment()) {
            // Use materialized view with consent filter
            return DB::table('segment_memberships as sm')
                ->join('contacts as c', 'sm.contact_id', '=', 'c.id')
                ->where('sm.segment_id', $segment->id)
                ->where('c.opt_in_status', 'opted_in')
                ->where(function ($query) {
                    $query->whereNull('c.opt_in_expires_at')
                        ->orWhere('c.opt_in_expires_at', '>', now());
                })
                ->select('c.*')
                ->get()
                ->map(fn($contact) => Contact::hydrate([(array) $contact])->first());
        }

        // For small/medium segments, compute with consent filter
        $query = SegmentBuilder::buildQuery($segment->rules, $segment->team_id);
        $query->where('opt_in_status', 'opted_in')
            ->where(function ($q) {
                $q->whereNull('opt_in_expires_at')
                    ->orWhere('opt_in_expires_at', '>', now());
            });

        return $query->get();
    }

    /**
     * Clean expired cache entries.
     */
    public function cleanExpiredCache(): void
    {
        DB::table('segment_cache')
            ->where('expires_at', '<', now())
            ->delete();

        Log::info('Cleaned expired segment cache');
    }

    /**
     * Invalidate segment cache.
     */
    public function invalidateCache(Segment $segment): void
    {
        // Clear database cache
        DB::table('segment_cache')
            ->where('segment_id', $segment->id)
            ->delete();

        // Clear Laravel cache
        Cache::forget("segment:{$segment->id}:members");
    }
}
