<?php

namespace App\Jobs\Alerts;

use App\Models\AlertLog;
use App\Services\Email\CentralEmailService;
use App\Services\Email\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAlertEscalation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected AlertLog $log,
        protected int $level = 2
    ) {
    }

    public function handle(
        CentralEmailService $emailService,
        EmailTemplateService $templateService
    ): void {
        // 1. Check if alert is already resolved
        if ($this->log->resolved_at || $this->log->status === 'resolved') {
            return;
        }

        $rule = $this->log->rule;
        $path = $rule->escalation_path ?? [];

        // 2. Find the current level in the path
        $currentPath = collect($path)->firstWhere('level', $this->level);

        if (!$currentPath) {
            return;
        }

        try {
            // 3. Determine recipients for this level
            // For now, we'll assume the path contains specific emails or roles
            $recipients = $currentPath['emails'] ?? [config('mail.admin_address')];

            foreach ($recipients as $recipient) {
                $emailService->sendSystemEmail(
                    $recipient,
                    "[ESCALATION LEVEL {$this->level}] " . ($this->log->payload['subject'] ?? $rule->name),
                    "This is an escalated alert for rule: {$rule->name}. Initial trigger: {$this->log->triggered_at}",
                    "Alert Escalation: {$rule->name}"
                );
            }

            // 4. Update log status
            $this->log->update(['status' => 'escalated']);

            // 5. Schedule next level if exists
            $nextLevel = collect($path)->firstWhere('level', $this->level + 1);
            if ($nextLevel) {
                $delay = $nextLevel['delay_mins'] ?? 60;
                self::dispatch($this->log, $this->level + 1)->delay(now()->addMinutes($delay));
            }

        } catch (\Exception $e) {
            Log::error("Escalation failed for AlertLog #{$this->log->id} at level {$this->level}: " . $e->getMessage());
        }
    }
}
