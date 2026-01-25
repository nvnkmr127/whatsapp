<?php

namespace App\Listeners;

use App\Events\UsageThresholdReached;
use App\Mail\BillingThresholdAlert;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotifyTeamOfBillingAlert implements ShouldQueue
{
    use InteractsWithQueue;

    protected $webhookService;
    protected $emailDispatcher;

    public function __construct(WebhookService $webhookService, \App\Services\Email\EmailDispatcher $emailDispatcher)
    {
        $this->webhookService = $webhookService;
        $this->emailDispatcher = $emailDispatcher;
    }

    /**
     * Handle the event.
     */
    public function handle(UsageThresholdReached $event): void
    {
        $team = $event->team;

        // 1. Send Email to Team Owner and Admins
        $recipients = $team->users()
            ->wherePivotIn('role', ['admin'])
            ->get();

        if ($team->user_id) {
            $owner = \App\Models\User::find($team->user_id);
            if ($owner && !$recipients->contains($owner)) {
                $recipients->push($owner);
            }
        }

        foreach ($recipients as $recipient) {
            try {
                $this->emailDispatcher->send(
                    $recipient,
                    \App\Enums\EmailUseCase::ALERT,
                    new BillingThresholdAlert(
                        $team,
                        $event->metric,
                        $event->level,
                        $event->percent,
                        $event->message
                    )
                );
            } catch (\Exception $e) {
                Log::error("Failed to send billing alert email to {$recipient->email}: " . $e->getMessage());
            }
        }

        // 2. Dispatch Webhook
        $webhookData = [
            'team_id' => $team->id,
            'metric' => $event->metric,
            'level' => $event->level,
            'percent' => round($event->percent, 2),
            'message' => $event->message,
            'threshold_status' => $event->level === 'danger' ? 'exhausted' : 'approaching',
        ];

        $this->webhookService->dispatch($team->id, 'billing.threshold_reached', $webhookData);
    }
}
