<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Upload a file and return the path.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'type' => 'required|in:image,video,audio,document',
        ]);

        $team = $request->user()->currentTeam;
        $path = $request->file('file')->store("teams/{$team->id}/mobile_uploads", 'public');

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'type' => $request->type,
        ]);
    }
}
