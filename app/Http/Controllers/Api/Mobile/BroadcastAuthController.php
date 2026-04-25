<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class BroadcastAuthController extends Controller
{
    public function authenticate(Request $request)
    {
        if (app()->environment('local')) {
            \Illuminate\Support\Facades\Log::debug('[DEBUG] [BROADCAST_AUTH] Attempting authentication', [
                'user_id' => $request->user()?->id,
                'channel_name' => $request->channel_name,
                'socket_id' => $request->socket_id,
            ]);
        }
        return Broadcast::auth($request);
    }
}

