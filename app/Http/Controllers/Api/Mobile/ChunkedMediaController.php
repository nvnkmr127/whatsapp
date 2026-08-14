<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkedMediaController extends Controller
{
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'upload_id'    => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'chunk_index'  => 'required|integer|min:0|max:9999',
            'total_chunks' => 'required|integer|min:1|max:9999',
            'filename'     => 'required|string|max:255',
            'mime'         => 'required|string|max:100',
            'chunk'        => 'required|file|max:1500',
        ]);

        $uploadId    = $request->input('upload_id');
        $chunkIndex  = (int) $request->input('chunk_index');
        $totalChunks = (int) $request->input('total_chunks');
        $filename    = basename($request->input('filename'));
        $mime        = strtolower($request->input('mime'));

        $tempDir = sys_get_temp_dir() . '/chunks_' . $uploadId;

        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0755, true)) {
                \Log::error('[CHUNKED_UPLOAD] Failed to create temp dir', ['dir' => $tempDir]);
                return response()->json(['error' => 'Server could not create temp directory'], 500);
            }
        }

        $chunkName = sprintf('chunk_%05d', $chunkIndex);
        $request->file('chunk')->move($tempDir, $chunkName);

        $chunkFiles = glob($tempDir . '/chunk_*');
        $received   = count($chunkFiles);

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

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = ['mp4','mov','avi','mkv','webm','3gp','mp3','ogg','wav','m4a','aac','opus','jpg','jpeg','png','gif','webp','heic','heif','pdf','doc','docx','xls','xlsx','csv','txt'];

        if (!in_array($ext, $allowed)) {
            $this->cleanupTempDir($tempDir);
            return response()->json(['error' => "Invalid file type: .{$ext}"], 422);
        }

        $type = 'document';
        if (str_contains($mime, 'audio') || in_array($ext, ['mp3','ogg','wav','m4a','aac','opus'])) {
            $type = 'audio';
        } elseif (str_contains($mime, 'image') || in_array($ext, ['jpg','jpeg','png','gif','webp','heic','heif'])) {
            $type = 'image';
        } elseif (str_contains($mime, 'video') || in_array($ext, ['mp4','mov','avi','mkv','webm','3gp'])) {
            $type = 'video';
        }

        $finalFileName = Str::uuid() . '.' . $ext;
        $assembledPath = $tempDir . '/assembled.' . $ext;

        sort($chunkFiles);

        try {
            $out = fopen($assembledPath, 'wb');
            if ($out === false) {
                throw new \RuntimeException("Cannot open assembled file: {$assembledPath}");
            }
            foreach ($chunkFiles as $chunkFile) {
                $in = fopen($chunkFile, 'rb');
                if ($in === false) {
                    throw new \RuntimeException("Cannot open chunk: {$chunkFile}");
                }
                while (!feof($in)) {
                    fwrite($out, fread($in, 65536));
                }
                fclose($in);
            }
            fclose($out);
        } catch (\Throwable $e) {
            \Log::error('[CHUNKED_UPLOAD] Assembly failed', ['error' => $e->getMessage()]);
            $this->cleanupTempDir($tempDir);
            return response()->json(['error' => 'Failed to assemble chunks: ' . $e->getMessage()], 500);
        }

        $configuredDisk = config('filesystems.default', 'public');
        $disk           = ($configuredDisk === 'local') ? 'public' : $configuredDisk;
        $destPath       = "mobile/uploads/{$type}/{$finalFileName}";

        try {
            $stream = fopen($assembledPath, 'rb');
            Storage::disk($disk)->put($destPath, $stream);
            fclose($stream);
        } catch (\Throwable $e) {
            \Log::error('[CHUNKED_UPLOAD] Storage put failed', ['error' => $e->getMessage(), 'disk' => $disk]);
            $this->cleanupTempDir($tempDir);
            return response()->json(['error' => 'Failed to store file: ' . $e->getMessage()], 500);
        }

        $this->cleanupTempDir($tempDir);

        try {
            $fullUrl = Storage::disk($disk)->url($destPath);
        } catch (\Throwable $e) {
            $origin  = request()->getSchemeAndHttpHost();
            $fullUrl = rtrim($origin, '/') . '/storage/' . ltrim($destPath, '/');
        }

        \Log::info('[CHUNKED_UPLOAD] File stored successfully', [
            'filename' => $filename, 'type' => $type, 'url' => $fullUrl,
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

    private function cleanupTempDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (glob($dir . '/*') as $file) {
            if (is_file($file)) @unlink($file);
        }
        @rmdir($dir);
    }
}
