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
        
        $isVoiceNote = filter_var($request->input('is_voice_note'), FILTER_VALIDATE_BOOLEAN);
        if ($isVoiceNote) {
            // Skip transcoding if file is already an ogg Opus file
            if ($extension !== 'ogg') {
                $ffmpegBin = env('FFMPEG_PATH');

                if (!$ffmpegBin) {
                    $extraDirs = [
                        '/opt/homebrew/bin', '/usr/local/bin', '/usr/bin', '/bin',
                        'C:/ffmpeg/bin', 'C:/ProgramData/chocolatey/bin',
                        'C:/Program Files/ffmpeg/bin', 'C:/tools/ffmpeg/bin',
                    ];
                    $userProfile = getenv('USERPROFILE') ?: getenv('HOME');
                    if ($userProfile && is_dir($userProfile . '/AppData/Local/Microsoft/WinGet/Packages')) {
                        $winGetPkgs = glob($userProfile . '/AppData/Local/Microsoft/WinGet/Packages/Gyan.FFmpeg*');
                        foreach ($winGetPkgs as $pkg) {
                            $subDirs = glob($pkg . '/ffmpeg-*');
                            foreach ($subDirs as $sd) {
                                if (is_dir($sd . '/bin')) {
                                    $extraDirs[] = $sd . '/bin';
                                }
                            }
                        }
                    }

                    $ffmpegBin = (new \Symfony\Component\Process\ExecutableFinder)
                        ->find('ffmpeg', null, array_values(array_unique($extraDirs)));
                }

                if ($ffmpegBin) {
                    $tmpOgg = sys_get_temp_dir() . '/' . \Illuminate\Support\Str::random(20) . '.ogg';
                    $srcTmp = sys_get_temp_dir() . '/' . \Illuminate\Support\Str::random(20) . '.' . $extension;
                    copy($file->getRealPath(), $srcTmp);

                    $process = new \Symfony\Component\Process\Process([
                        $ffmpegBin,
                        '-y',
                        '-i', $srcTmp,
                        '-vn',
                        '-c:a', 'libopus',
                        '-b:a', '32k',
                        '-ar', '16000',
                        '-f', 'ogg',
                        $tmpOgg
                    ]);
                    $process->setTimeout(30);
                    $process->run();
                    @unlink($srcTmp);

                    if ($process->isSuccessful() && file_exists($tmpOgg) && filesize($tmpOgg) > 100) {
                        $file = new \Illuminate\Http\UploadedFile($tmpOgg, 'voice.ogg', 'audio/ogg', 0, true);
                        $extension = 'ogg';
                        $fileName = Str::uuid() . '.' . $extension;
                    } else {
                        \Log::error('[MOBILE_MEDIA_UPLOAD] FFmpeg transcoding failed', ['error' => $process->getErrorOutput()]);
                        return response()->json(['error' => 'Could not convert voice note audio format. Please try again.'], 422);
                    }
                } else {
                    \Log::error('[MOBILE_MEDIA_UPLOAD] ffmpeg not found — cannot convert voice note.');
                    return response()->json(['error' => 'Voice notes require server audio conversion support (FFmpeg). Please contact support.'], 422);
                }
            }
        }
        
        // Determine type based on mime or extension
        $mime = strtolower((string) $file->getMimeType());
        if ($extension === 'ogg' || str_contains($mime, 'ogg')) {
            $mime = 'audio/ogg';
        }
        $type = 'document';
        if (str_contains($mime, 'audio') || in_array($extension, ['mp3','ogg','wav','m4a','aac','opus','3gp'])) {
            $type = 'audio';
            if ($extension === 'ogg') {
                $mime = 'audio/ogg';
            }
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

        \Log::info('[MOBILE_MEDIA_UPLOAD] Media uploaded successfully', [
            'original_name' => $file->getClientOriginalName(),
            'extension' => $extension,
            'mime' => $mime,
            'detected_type' => $type,
            'disk' => $disk,
            'path' => $path,
            'full_url' => $fullUrl,
            'size_bytes' => $file->getSize(),
        ]);

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
