<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Handle mobile media uploads.
     * Supports images, videos, documents, and audio.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // 20MB limit
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        
        // Determine type based on mime
        $mime = $file->getMimeType();
        $type = 'document';
        if (str_contains($mime, 'image')) $type = 'image';
        elseif (str_contains($mime, 'video')) $type = 'video';
        elseif (str_contains($mime, 'audio')) $type = 'audio';

        // Store in public disk for easy access (or s3 in production)
        $path = $file->storeAs('mobile/uploads/' . $type, $fileName, 'public');

        return response()->json([
            'success' => true,
            'url' => Storage::disk('public')->url($path),
            'fileName' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size' => $file->getSize(),
            'type' => $type
        ]);
    }
}
