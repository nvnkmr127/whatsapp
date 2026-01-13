<?php

namespace App\Jobs;

use App\Models\Team;
use App\Models\Contact;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $teamId;
    public $phone;
    public $type;
    public $content; // Message body or Array for template
    public $templateName;
    public $language;

    /**
     * Create a new job instance.
     */
    public function __construct($teamId, $phone, $type, $content, $templateName = null, $language = 'en_US')
    {
        $this->teamId = $teamId;
        $this->phone = $phone;
        $this->type = $type;
        $this->content = $content;
        $this->templateName = $templateName;
        $this->language = $language;
    }

    public $tries = 3;
    public $backoff = [10, 30, 60];

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $waService): void
    {
        $team = Team::find($this->teamId);
        if (!$team) {
            Log::error("SendMessageJob: Team not found {$this->teamId}");
            return;
        }

        $waService->setTeam($team);

        try {
            $response = null;

            if ($this->type === 'text') {
                $response = $waService->sendText($this->phone, $this->content);
            } elseif ($this->type === 'template') {
                $response = $waService->sendTemplate(
                    $this->phone,
                    $this->templateName,
                    $this->language,
                    $this->content // Variables array
                );
            }

            if (!empty($response['error'])) {
                throw new \Exception(json_encode($response['error']));
            }

        } catch (\Exception $e) {
            Log::error("Failed to send message to {$this->phone}: " . $e->getMessage());
            // Throw to trigger retry
            throw $e;
        }
    }
}
