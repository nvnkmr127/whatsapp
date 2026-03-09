<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckOnboardingInactivityJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = now();

        // 1. Account Created but WhatsApp not connected (> 1 hour)
        $this->processReminders(
            \App\Models\OnboardingStatus::where('account_created', true)
                ->where('whatsapp_connected', false)
                ->where('last_activity_at', '<=', $now->subHour())
                ->with('user')
                ->get(),
            'onboarding_remote_reminder'
        );

        // 2. WhatsApp connected but Business Profile incomplete (> 2 hours)
        $this->processReminders(
            \App\Models\OnboardingStatus::where('whatsapp_connected', true)
                ->where('business_profile_completed', false)
                ->where('last_activity_at', '<=', $now->subHours(2))
                ->with('user')
                ->get(),
            'onboarding_profile_reminder'
        );

        // 3. Profile completed but first Campaign/Template not created (> 24 hours)
        $this->processReminders(
            \App\Models\OnboardingStatus::where('business_profile_completed', true)
                ->where('first_campaign_created', false)
                ->where('last_activity_at', '<=', $now->subHours(24))
                ->with('user')
                ->get(),
            'onboarding_campaign_tutorial'
        );
    }

    protected function processReminders($statuses, $templateName)
    {
        foreach ($statuses as $status) {
            $user = $status->user;
            if (!$user || !$user->phone)
                continue;

            // Use the first team this user belongs to for context
            $team = $user->ownedTeams()->first() ?? $user->teams()->first();
            if (!$team)
                continue;

            try {
                // Send WhatsApp Template Reminder
                // Note: This assumes the user's phone is WhatsApp-active
                (new \App\Services\WhatsAppService($team))->sendTemplate(
                    $user->phone,
                    $templateName,
                    'en_US',
                    [$user->name] // Variable 1: User Name
                );

                \Illuminate\Support\Facades\Log::info("Onboarding reminder ({$templateName}) sent to user: {$user->id}");

                // Update activity to prevent duplicate reminders in the next 10m run
                $status->increment('last_activity_at', 0); // Trigger saving observer
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send onboarding reminder to user {$user->id}: " . $e->getMessage());
            }
        }
    }
}
