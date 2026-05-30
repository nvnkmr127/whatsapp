<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use App\Models\ShortLinkClick;
use Illuminate\Http\Request;

class ShortLinkController extends Controller
{
    public function redirect(Request $request, $code)
    {
        $link = ShortLink::where('short_code', $code)->firstOrFail();

        // Check expiry
        if ($link->expires_at && $link->expires_at->isPast()) {
            abort(404, 'Link expired.');
        }

        // Log Click
        ShortLinkClick::create([
            'short_link_id' => $link->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'referer' => $request->header('referer'),
            ],
        ]);

        // Increment Click Count
        $link->increment('click_count');

        $destinationUrl = $link->destination_url;
        $destinationHost = parse_url($destinationUrl, PHP_URL_HOST);

        $trustedDomains = [
            'watxio.com',
            'wa.me',
            'whatsapp.com',
            'facebook.com',
            'meta.com',
            'instagram.com',
        ];

        // Dynamically add current application domain
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if ($appHost) {
            $trustedDomains[] = $appHost;
        }

        $isTrusted = false;
        if ($destinationHost) {
            $destinationHost = strtolower($destinationHost);
            foreach ($trustedDomains as $trusted) {
                if ($trusted && ($destinationHost === $trusted || str_ends_with($destinationHost, '.' . $trusted))) {
                    $isTrusted = true;
                    break;
                }
            }
        } else {
            // Direct path / local redirects are safe
            if (str_starts_with($destinationUrl, '/') || str_starts_with($destinationUrl, '#')) {
                $isTrusted = true;
            }
        }

        if ($isTrusted) {
            return redirect($destinationUrl);
        }

        return response()->view('short-link.redirect', [
            'url' => $destinationUrl,
            'host' => $destinationHost ?: $destinationUrl
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
