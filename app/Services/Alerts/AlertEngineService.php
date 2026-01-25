<?php

namespace App\Services\Alerts;

use App\Models\AlertRule;
use App\Models\AlertLog;
use App\Services\Email\CentralEmailService;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AlertEngineService
{
    public function __construct(
        protected CentralEmailService $emailService,
        protected EmailTemplateService $templateService
    ) {
    }

    /**
     * Trigger an alert by its slug.
     */
    public function trigger(string $slug, array $payload = [], ?int $teamId = null): void
    {
        $rule = AlertRule::where('slug', $slug)->where('is_active', true)->first();

        if (!$rule) {
            Log::warning("Alert trigger failed: Rule not found or inactive for slug '{$slug}'");
            return;
        }

        $suppressionKey = $this->generateSuppressionKey($rule, $payload, $teamId);

        if ($this->isThrottled($rule, $suppressionKey)) {
            $this->logAlert($rule, $payload, $teamId, $suppressionKey, 'throttled');
            return;
        }

        $this->processAlert($rule, $payload, $teamId, $suppressionKey);
    }

    /**
     * Process the alert: send email and log it.
     */
    protected function processAlert(AlertRule $rule, array $payload, ?int $teamId, string $suppressionKey): void
    {
        try {
            // 1. Determine recipients (can be from rule or payload)
            $recipient = $payload['recipient'] ?? config('mail.admin_address');

            // 2. Render content if template exists
            $content = null;
            if ($rule->template_slug) {
                $content = $this->templateService->render($rule->template_slug, $payload);
            }

            // 3. Send Email
            if ($content) {
                $this->emailService->sendSystemEmail(
                    $recipient,
                    $content['subject'],
                    $content['html'],
                    $content['text']
                );
            }

            // 4. Log the alert
            $log = $this->logAlert($rule, $payload, $teamId, $suppressionKey, 'processed');

            // 5. Handle Escalation if Critical
            if ($rule->isCritical()) {
                $this->scheduleEscalation($log);
            }

        } catch (\Exception $e) {
            Log::error("Alert processing failed for rule '{$rule->slug}': " . $e->getMessage());
            $this->logAlert($rule, $payload, $teamId, $suppressionKey, 'failed', $e->getMessage());
        }
    }

    /**
     * Generate a key for throttling.
     */
    protected function generateSuppressionKey(AlertRule $rule, array $payload, ?int $teamId): string
    {
        // Customizable key based on payload (e.g., target user id or error code)
        $resourceId = $payload['resource_id'] ?? $teamId ?? 'global';
        return md5($rule->slug . '_' . $resourceId);
    }

    /**
     * Check if the alert is currently throttled.
     */
    protected function isThrottled(AlertRule $rule, string $suppressionKey): bool
    {
        return Cache::has("alert_throttle_{$suppressionKey}");
    }

    /**
     * Log the alert attempt.
     */
    protected function logAlert(
        AlertRule $rule,
        array $payload,
        ?int $teamId,
        string $suppressionKey,
        string $status,
        ?string $error = null
    ): AlertLog {
        $log = AlertLog::create([
            'rule_id' => $rule->id,
            'team_id' => $teamId,
            'suppression_key' => $suppressionKey,
            'status' => $status,
            'severity' => $rule->severity,
            'payload' => $payload,
            'error_message' => $error,
            'triggered_at' => now(),
        ]);

        if ($status === 'processed' && $rule->throttle_seconds > 0) {
            Cache::put("alert_throttle_{$suppressionKey}", true, $rule->throttle_seconds);
        }

        return $log;
    }

    /**
     * Dispatch the escalation job if configured.
     */
    protected function scheduleEscalation(AlertLog $log): void
    {
        $rule = $log->rule;
        $path = $rule->escalation_path ?? [];

        $firstEscalation = collect($path)->firstWhere('level', 2);

        if ($firstEscalation) {
            $delay = $firstEscalation['delay_mins'] ?? 30;
            \App\Jobs\Alerts\ProcessAlertEscalation::dispatch($log, 2)
                ->delay(now()->addMinutes($delay));
        }
    }
}
