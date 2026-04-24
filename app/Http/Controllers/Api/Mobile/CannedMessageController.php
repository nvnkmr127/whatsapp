<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CannedMessage;
use Illuminate\Http\Request;

class CannedMessageController extends Controller
{
    public function index(Request $request)
    {
        $messages = $request->user()->currentTeam->cannedMessages()
            ->latest()
            ->get();

        return response()->json($messages->map(fn($m) => [
            'id' => $m->id,
            'title' => $m->title,
            'content' => $m->content,
            'shortcut' => $m->shortcut,
        ]));
    }
}
