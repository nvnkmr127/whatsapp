<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkedMediaController extends Controller
{
    /**
     * Receive a single chunk of a chunked file upload.
     *
     * Mobile client splits large files into ~700 KB chunks (each safely under
     * nginx's default 1 MB client_max_body_size) and calls this endpoint once
     * per chunk.  When the final chunk arrives the controller assembles the
     * complete file and stores it to the configured disk.
     */
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'upload_id'    => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'chunk_index'  => 'required|integer|min:0|max:9999',
            'total_chunks' => 'required|integer|min:1|max:9999',
            'filename'     => 'required|string|max:255',
            'mime'         => 'required|string|max:100',
            'chunk'        => 'required|file|max:1500',  // 1.5 MB max per chunk
        ]);

        $uploadId    = $request->input('upload_id');
        $chunkIndex  = (int) $request->input('chunk_index');
        $totalChunks = (int) $request->input('total_chunks');
        $filename    = basename($request->input('filename'));
        $mime        = strtolower($request->input('mime'));

        // ── 1. Persist this chunk ────────────────────────────────────────────
        $tempDir   = "chunks/{$uploadId}";
        $chunkName = sprintf('chunk_%05d', $chunkIndex); // zero-padded for correct sort

        $request->file('chunk')->storeAs($tempDir, $chunkName, 'local');

        // ── 2. Count how many chunks we have so far ──────────────────────────
        $received = count(Storage::disk('local')->files($tempDir));

        \Log::info('[CHUNKED_UPLOAD] Chunk received', [
            'upload_id' => $uploadId,
            'chunk'     => $chunkIndex,
            'received'  => $received,
            'total'     => $totalChunks,
        ]);

        if ($received < $totalChunks) {
            return response()->json([
                'received' => true,
                'chunk'    => $chunkIndex,
                'progress' => (int) round(($received / $totalChunks) * 100),
            ]);
        }

        // ── 3. All chunks received → assemble ────────────────────────────────
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowedExtensions = [
            'mp4','mov','avi','mkv','webm','3gp',
            'mp3','ogg','wav','m4a','aac','opus',
            'jpg','jpeg','png','gif','webp','heic','heif',
            'pdf','doc','docx','xls','xlsx','csv','txt',
        ];

        if (!in_array($ext, $allowedExtensions)) {
            Storage::disk('local')->deleteDirectory($tempDir);
            return response()->json(['error' => "Invalid file type: .{$ext}"], 422);
        }

        // Determine media type
        $type = 'document';
        if (str_contains($mime, 'audio') || in_array($ext, ['mp3','ogg','wav','m4a','aac','opus'])) {
            $type = 'audio';
        } elseif (str_contains($mime, 'image') || in_array($ext, ['jpg','jpeg','png','gif','webp','heic','heif'])) {
            $type = 'image';
        } elseif (str_contains($mime, 'video') || in_array($ext, ['mp4','mov','avi','mkv','webm','3gp'])) {
            $type = 'video';
        }

        $finalFileName = Str::uuid() . '.' . $ext;
        $assembledPath = storage_path("app/{$tempDir}/assembled.{$ext}");

        // Merge chunks in order (zero-padded names → sort correctly)
        $chunkFiles = Storage::disk('local')->files($tempDir);
        sort($chunkFiles);

        $out = fopen($assembledPath, 'wb');
        foreach ($chunkFiles as $chunkFile) {
            if (str_contains($chunkFile, 'assembled')) continue;
            $chunkFullPath = Storage::disk('local')->path($chunkFile);
            $in = fopen($chunkFullPath, 'rb');
            while (!feof($in)) {
                fwrite($out, fread($in, 65536)); // 64 KB read buffer
            }
            fclose($in);
        }
        fclose($out);

        // ── 4. Store assembled file on the configured disk ───────────────────
        $configuredDisk = config('filesystems.default', 'public');
        $disk           = ($configuredDisk === 'local') ? 'public' : $configuredDisk;
        $destPath       = "mobile/uploads/{$type}/{$finalFileName}";

        $stream = fopen($assembledPath, 'rb');
        Storage::disk($disk)->put($destPath, $stream);
        fclose($stream);

        // ── 5. Clean up temp chunks ──────────────────────────────────────────
        Storage::disk('local')->deleteDirectory($tempDir);

        // ── 6. Build public URL ──────────────────────────────────────────────
        try {
            $fullUrl = Storage::disk($disk)->url($destPath);
        } catch (\Throwable $e) {
            $origin  = request()->getSchemeAndHttpHost();
            $fullUrl = rtrim($origin, '/') . '/storage/' . ltrim($destPath, '/');
        }

        \Log::info('[CHUNKED_UPLOAD] File assembled and stored', [
            'filename'     => $filename,
            'type'         => $type,
            'disk'         => $disk,
            'path'         => $destPath,
            'url'          => $fullUrl,
            'total_chunks' => $totalChunks,
        ]);

        return response()->json([
            'success'  => true,
            'url'      => $fullUrl,
            'path'     => $destPath,
            'fileName' => $filename,
            'mime'     => $mime,
            'type'     => $type,
        ]);
    }
}
