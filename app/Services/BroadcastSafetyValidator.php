<?php

namespace App\Services;

use App\Models\Segment;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

class BroadcastSafetyValidator
{
    /**
     * Validate broadcast before sending.
     */
    public function validate(Campaign $campaign, Segment $segment): array
    {
        $errors = [];

        // 1. Check segment size limits
        if ($segment->member_count > config('broadcast.max_recipients', 100000)) {
            $errors[] = "Segment too large ({$segment->member_count} contacts). Max: 100,000";
        }

        // 2. Check rate limits
        $recentBroadcasts = Campaign::where('team_id', $campaign->team_id)
            ->where('created_at', '>=', now()->subHour())
            ->sum('recipient_count');

        $hourlyLimit = config('broadcast.hourly_limit', 10000);
        if ($recentBroadcasts + $segment->member_count > $hourlyLimit) {
            $errors[] = "Hourly rate limit exceeded. Used: {$recentBroadcasts}/{$hourlyLimit}";
        }

        // 3. Verify consent
        $optedOutCount = $this->countOptedOut($segment);
        if ($optedOutCount > 0) {
            $errors[] = "{$optedOutCount} contacts have opted out";
        }

        // 4. Check for duplicates
        $duplicates = $this->findDuplicates($segment);
        if ($duplicates->isNotEmpty()) {
            $errors[] = "{$duplicates->count()} duplicate phone numbers detected";
        }

        // 5. Verify 24h window (if required)
        if ($campaign->requires_24h_window ?? false) {
            $outsideWindow = $this->countOutsideWindow($segment);
            if ($outsideWindow > 0) {
                $errors[] = "{$outsideWindow} contacts outside 24h window";
            }
        }

        // 6. Check consent expiry
        $expiredConsent = $this->countExpiredConsent($segment);
        if ($expiredConsent > 0) {
            $errors[] = "{$expiredConsent} contacts with expired consent";
        }

        return $errors;
    }

    /**
     * Count opted-out contacts in segment.
     */
    protected function countOptedOut(Segment $segment): int
    {
        if ($segment->isLargeSegment()) {
            return DB::table('segment_memberships as sm')
                ->join('contacts as c', 'sm.contact_id', '=', 'c.id')
                ->where('sm.segment_id', $segment->id)
                ->where('c.opt_in_status', 'opted_out')
                ->count();
        }

        $query = \App\Services\SegmentBuilder::buildQuery($segment->rules, $segment->team_id);
        return $query->where('opt_in_status', 'opted_out')->count();
    }

    /**
     * Find duplicate phone numbers in segment.
     */
    protected function findDuplicates(Segment $segment): \Illuminate\Support\Collection
    {
        if ($segment->isLargeSegment()) {
            return DB::table('segment_memberships as sm')
                ->join('contacts as c', 'sm.contact_id', '=', 'c.id')
                ->where('sm.segment_id', $segment->id)
                ->select('c.phone_number', DB::raw('COUNT(*) as count'))
                ->groupBy('c.phone_number')
                ->having('count', '>', 1)
                ->get();
        }

        $query = \App\Services\SegmentBuilder::buildQuery($segment->rules, $segment->team_id);
        return $query->select('phone_number', DB::raw('COUNT(*) as count'))
            ->groupBy('phone_number')
            ->having('count', '>', 1)
            ->get();
    }

    /**
     * Count contacts outside 24h window.
     */
    protected function countOutsideWindow(Segment $segment): int
    {
        if ($segment->isLargeSegment()) {
            return DB::table('segment_memberships as sm')
                ->join('contacts as c', 'sm.contact_id', '=', 'c.id')
                ->where('sm.segment_id', $segment->id)
                ->where('c.is_within_24h_window', false)
                ->count();
        }

        $query = \App\Services\SegmentBuilder::buildQuery($segment->rules, $segment->team_id);
        return $query->where('is_within_24h_window', false)->count();
    }

    /**
     * Count contacts with expired consent.
     */
    protected function countExpiredConsent(Segment $segment): int
    {
        if ($segment->isLargeSegment()) {
            return DB::table('segment_memberships as sm')
                ->join('contacts as c', 'sm.contact_id', '=', 'c.id')
                ->where('sm.segment_id', $segment->id)
                ->where('c.is_consent_expired', true)
                ->count();
        }

        $query = \App\Services\SegmentBuilder::buildQuery($segment->rules, $segment->team_id);
        return $query->where('is_consent_expired', true)->count();
    }

    /**
     * Deduplicate segment by phone number.
     */
    public function deduplicate(Segment $segment): void
    {
        if (!$segment->isLargeSegment()) {
            return; // Only needed for materialized views
        }

        // Find duplicate phone numbers
        $duplicates = DB::table('segment_memberships as sm')
            ->join('contacts as c', 'sm.contact_id', '=', 'c.id')
            ->where('sm.segment_id', $segment->id)
            ->select('c.phone_number', DB::raw('MIN(sm.id) as keep_id'))
            ->groupBy('c.phone_number')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            // Keep only the first membership, delete others
            DB::table('segment_memberships as sm')
                ->join('contacts as c', 'sm.contact_id', '=', 'c.id')
                ->where('sm.segment_id', $segment->id)
                ->where('c.phone_number', $duplicate->phone_number)
                ->where('sm.id', '!=', $duplicate->keep_id)
                ->delete();
        }
    }
}
