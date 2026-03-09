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
        $firstDelay = (int) get_setting('onboarding_first_delay', 1);
        $secondDelay = (int) get_setting('onboarding_second_delay', 2);
        $thirdDelay = (int) get_setting('onboarding_third_delay', 24);

        // 1. Account Created but WhatsApp not connected (> firstDelay)
        $this->processReminders(
            \App\Models\OnboardingStatus::where('account_created', true)
                ->where('whatsapp_connected', false)
                ->where('last_activity_at', '<=', $now->copy()->subHours($firstDelay))
                ->with('user')
                ->get(),
            get_setting('onboarding_remote_reminder_template', 'onboarding_remote_reminder'),
            get_setting('onboarding_remote_reminder_variables', '{name}, {setup_link}')
        );

        // 2. WhatsApp connected but Business Profile incomplete (> secondDelay)
        $this->processReminders(
            \App\Models\OnboardingStatus::where('whatsapp_connected', true)
                ->where('business_profile_completed', false)
                ->where('last_activity_at', '<=', $now->copy()->subHours($secondDelay))
                ->with('user')
                ->get(),
            get_setting('onboarding_profile_reminder_template', 'onboarding_profile_reminder'),
            get_setting('onboarding_profile_reminder_variables', '{name}, {setup_link}')
        );

        // 3. Profile completed but first Campaign/Template not created (> thirdDelay)
        $this->processReminders(
            \App\Models\OnboardingStatus::where('business_profile_completed', true)
                ->where('first_campaign_created', false)
                ->where('last_activity_at', '<=', $now->copy()->subHours($thirdDelay))
                ->with('user')
                ->get(),
            get_setting('onboarding_campaign_tutorial_template', 'onboarding_campaign_tutorial'),
            get_setting('onboarding_campaign_tutorial_variables', '{name}')
        );
    }

    protected function processReminders($statuses, $templateName, $variableMapping)
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
                // Parse Variables
                $variables = [];
                $mappingParts = explode(',', $variableMapping);

                foreach ($mappingParts as $placeholder) {
                    $placeholder = trim($placeholder);

                    if ($placeholder === '{name}') {
                        $variables[] = $user->name;
                    } elseif ($placeholder === '{setup_link}' || $placeholder === '{onboarding_url}') {
                        $variables[] = url('/whatsapp/setup'); // Or your specific landing page
                    } elseif ($placeholder === '{email}') {
                        $variables[] = $user->email;
                    } else {
                        // If it's just plain text or unknown, keep as is or empty
                        $variables[] = str_replace(['{', '}'], '', $placeholder);
                    }
                }

                // Send WhatsApp Template Reminder
                (new \App\Services\WhatsAppService($team))->sendTemplate(
                    $user->phone,
                    $templateName,
                    'en_US',
                    $variables
                );

                \Illuminate\Support\Facades\Log::info("Onboarding reminder ({$templateName}) sent to user: {$user->id} with vars: " . implode(',', $variables));

                // Update activity to prevent duplicate reminders in the next 10m run
                // and increment internal reminder counter
                $status->increment('reminders_sent_count');
                $status->update(['last_activity_at' => now()]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send onboarding reminder to user {$user->id}: " . $e->getMessage());
            }
        }
    }
}
