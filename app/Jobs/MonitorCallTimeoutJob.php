<?php

namespace App\Jobs;

use App\Models\WhatsAppCall;
use App\Services\CallRoutingService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MonitorCallTimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $callId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $callId)
    {
        $this->callId = $callId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $call = WhatsAppCall::find($this->callId);

        if (! $call || ! in_array($call->status, ['initiated', 'ringing'])) {
            return;
        }

        $team = $call->team;
        $config = $team->getCallRoutingConfig();
        $timeout = $config['ring_timeout_seconds'] ?? 30;

        // Check if the call is still in 'initiated' or 'ringing' status after the timeout period
        // The job should be delayed by $timeout seconds.

        Log::info('Call timeout reached, triggering fallback', [
            'call_id' => $call->call_id,
            'team_id' => $team->id,
            'status' => $call->status,
        ]);

        $routingService = new CallRoutingService($team);
        $fallback = $routingService->findAgent($call->contact); // findAgent will handle fallback if no one available

        if ($fallback['method'] === 'fallback') {
            $this->executeFallbackAction($call, $fallback['action']);
        } elseif ($fallback['agent']) {
            // Reroute to next agent?
            // In a complex system we might loop, but for now we follow the "Timeout fallback triggered" requirement.
            $call->update(['agent_id' => $fallback['agent']->id]);
            Log::info('Call rerouted due to timeout', ['call_id' => $call->call_id, 'new_agent' => $fallback['agent']->id]);
        }
    }

    protected function executeFallbackAction(WhatsAppCall $call, string $action): void
    {
        $call->markAsFailed('TIMEOUT_FALLBACK_'.strtoupper($action));

        if ($action === 'auto_reply') {
            $team = $call->team;
            $waService = new WhatsAppService($team);

            $message = $team->away_message ?? 'All our agents are currently busy. We will get back to you shortly.';

            try {
                $waService->sendText($call->contact->phone_number, $message);
            } catch (\Exception $e) {
                Log::error('Failed to send fallback auto-reply', ['error' => $e->getMessage()]);
            }
        }
    }
}
