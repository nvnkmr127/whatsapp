<?php

namespace App\Services;

use App\Models\Message;
use App\Services\AI\AIProviderManager;
use App\Services\AI\Providers\OpenAIProvider;
use Illuminate\Support\Facades\Log;

class VoiceTranscriptionService
{
    /**
     * Transcribe an audio message using OpenAI Whisper.
     */
    public function transcribe(Message $message): bool
    {
        if ($message->type !== 'audio' || ! $message->media_url) {
            return false;
        }

        // 1. Get OpenAI Provider (Whisper is OpenAI-only; team-specific key respected)
        $provider = AIProviderManager::getProvider($message->team, 'openai');
        if (! $provider instanceof OpenAIProvider) {
            Log::warning("[Transcription] OpenAI provider not configured. Skipping transcription for Message #{$message->id}");

            return false;
        }

        // 2. Prepare File Path
        // Assuming media_url is a path relative to storage/app/public or similar
        // We need the absolute path for the AI provider
        $relativePath = str_replace('/storage/', 'public/', $message->media_url);
        $absolutePath = storage_path('app/'.$relativePath);

        if (! file_exists($absolutePath)) {
            Log::error("[Transcription] File not found for Message #{$message->id} at {$absolutePath}");

            return false;
        }

        // 3. Request Transcription
        try {
            Log::info("[Transcription] Requesting Whisper transcription for Message #{$message->id}");

            $text = $provider->transcribe($absolutePath, [
                'prompt' => 'This is a WhatsApp voice message from a customer. It might be in Hindi, English, or Hinglish.',
            ]);

            if ($text) {
                // 4. Save transcription to message metadata
                $metadata = $message->metadata ?? [];
                $metadata['transcription'] = $text;

                $message->update([
                    'metadata' => $metadata,
                ]);

                Log::info("[Transcription] SUCCESS for Message #{$message->id}");

                // DownloadMediaJob dispatches MessageReceived right after this returns,
                // which is what re-triggers the UI broadcast and the AI reply. Dispatching
                // here too would fire the AI listener twice → duplicate reply.

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("[Transcription] FAILED for Message #{$message->id}: ".$e->getMessage());

            return false;
        }
    }
}
