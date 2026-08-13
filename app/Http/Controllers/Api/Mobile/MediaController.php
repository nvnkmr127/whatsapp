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
            'file' => [
                'required', 'file', 'max:65536',
            ],
        ]);

        $file = $request->file('file');
        
        // Derive extension from MIME type or original filename
        $clientExt = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['jpg','jpeg','png','gif','webp','heic','heif','mp4','mov','avi','mkv','webm','3gp','mp3','ogg','wav','m4a','aac','opus','pdf','doc','docx','xls','xlsx','csv','txt'];
        
        $extension = $file->extension() ?: $clientExt;
        if (!in_array($extension, $allowedExtensions)) {
            if (in_array($clientExt, $allowedExtensions)) {
                $extension = $clientExt;
            } else {
                return response()->json(['error' => "Invalid file type (.$extension)"], 422);
            }
        }
        
        $fileName = Str::uuid() . '.' . $extension;
        
        // Determine type based on mime or extension
        $mime = strtolower((string) $file->getMimeType());
        $type = 'document';
        if (str_contains($mime, 'audio') || in_array($extension, ['mp3','ogg','wav','m4a','aac','opus','3gp'])) {
            $type = 'audio';
        } elseif (str_contains($mime, 'image') || in_array($extension, ['jpg','jpeg','png','gif','webp','heic','heif'])) {
            $type = 'image';
        } elseif (str_contains($mime, 'video') || in_array($extension, ['mp4','mov','avi','mkv','webm'])) {
            $type = 'video';
        }

        // Resolve target disk based on default configuration (S3/R2 in production, public locally)
        $configuredDisk = config('filesystems.default', 'public');
        $disk = ($configuredDisk === 'local') ? 'public' : $configuredDisk;

        // Store in resolved disk
        $path = $file->storeAs('mobile/uploads/' . $type, $fileName, $disk);

        try {
            $fullUrl = Storage::disk($disk)->url($path);
        } catch (\Throwable $e) {
            $origin = request()->getSchemeAndHttpHost();
            $fullUrl = rtrim($origin, '/') . '/storage/' . ltrim($path, '/');
        }

        return response()->json([
            'success' => true,
            'url' => $fullUrl,
            'path' => $path,
            'fileName' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size' => $file->getSize(),
            'type' => $type
        ]);
    }
}
