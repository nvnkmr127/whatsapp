<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogLivewireUpdateFailures
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('livewire/update')) {
            return $response;
        }

        $contentType = (string) ($response->headers->get('content-type') ?? '');
        $status = $response->getStatusCode();

        $isJson = str_contains(strtolower($contentType), 'application/json');
        $hasErrorStatus = $status >= 400;

        if ($isJson && ! $hasErrorStatus) {
            return $response;
        }

        $bodySnippet = '';
        try {
            $body = $response->getContent();
            if (is_string($body) && $body !== '') {
                $bodySnippet = substr($body, 0, 800);
            }
        } catch (\Throwable $e) {
            $bodySnippet = '<<non-string/streamed response>>';
        }

        $userId = Auth::id();
        $teamId = Auth::check() ? (Auth::user()->currentTeam?->id) : null;

        try {
            $line = json_encode([
                'at' => now()->toIso8601String(),
                'status' => $status,
                'content_type' => $contentType,
                'path' => $request->path(),
                'user_id' => $userId,
                'team_id' => $teamId,
                'snippet' => $bodySnippet,
            ], JSON_UNESCAPED_SLASHES);
            if (is_string($line)) {
                @file_put_contents(storage_path('logs/livewire_update_debug.log'), $line.PHP_EOL, FILE_APPEND);
            }
        } catch (\Throwable $e) {
        }

        Log::warning('Livewire update returned non-JSON or error status', [
            'status' => $status,
            'content_type' => $contentType,
            'path' => $request->path(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'snippet' => $bodySnippet,
        ]);

        return $response;
    }
}
