<?php

namespace App\Jobs;

use App\Models\Team;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MarkAsReadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $teamId,
        public array $whatsappMessageIds
    ) {
        $this->onQueue('messages');
    }

    public function handle(): void
    {
        $team = Team::find($this->teamId);
        if (!$team) return;

        $wa = new WhatsAppService($team);

        foreach ($this->whatsappMessageIds as $msgId) {
            try {
                $wa->markRead($msgId);
            } catch (\Exception $e) {
                Log::warning("Failed to mark message {$msgId} as read on WhatsApp: " . $e->getMessage());
            }
        }
    }
}
