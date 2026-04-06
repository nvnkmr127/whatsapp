<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ShortLink;
use App\Models\Team;
use Illuminate\Support\Str;

class LinkTrackerService
{
    /**
     * Search and replace URLs in a text with tracked short links.
     */
    public function trackUrls(string $content, Team $team, ?Contact $contact = null, ?Campaign $campaign = null, ?int $expiresInDays = null)
    {
        // URL Matching Regex
        $regex = '/(https?:\/\/[^\s]+)/';

        return preg_replace_callback($regex, function ($matches) use ($team, $contact, $campaign, $expiresInDays) {
            $url = $matches[0];

            // Avoid tracking internal domain or already shortened links
            $internal = parse_url(config('app.url'), PHP_URL_HOST);
            if (Str::contains($url, $internal)) {
                return $url;
            }

            // Create Short Link
            $shortLink = ShortLink::create([
                'team_id' => $team->id,
                'contact_id' => $contact?->id,
                'campaign_id' => $campaign?->id,
                'destination_url' => $url,
                'short_code' => ShortLink::generateUniqueCode(),
                'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
            ]);

            return route('short-link.redirect', ['code' => $shortLink->short_code]);
        }, $content);
    }
}
