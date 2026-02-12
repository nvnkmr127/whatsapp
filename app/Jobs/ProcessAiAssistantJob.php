<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\Contact;
use App\Services\AiCommerceService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessAiAssistantJob implements ShouldQueue
{
    use Queueable;

    public $messageId;
    public $tries = 3;
    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct($messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $message = Message::with(['contact', 'team'])->find($this->messageId);
        if (!$message) {
            return;
        }

        $contact = $message->contact;
        $team = $message->team;
        $waService = new WhatsAppService();
        $waService->setTeam($team);

        try {
            $aiService = new AiCommerceService($waService);
            $handled = $aiService->handle($contact, $message->content);

            if ($handled) {
                Log::info("ProcessAiAssistantJob: AI Assistant handled message {$this->messageId}");
            } else {
                Log::info("ProcessAiAssistantJob: AI Assistant skipped message {$this->messageId} (No match or blocked)");
            }
        } catch (\Exception $e) {
            Log::error("ProcessAiAssistantJob Failed: " . $e->getMessage());
            // Optionally retry via throw $e; 
            // Here we just log to avoid crash loops for AI errors.
        }
    }
}
