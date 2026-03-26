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
            ]
        ]);

        // Increment Click Count
        $link->increment('click_count');

        return redirect($link->destination_url);
    }
}
