<?php

namespace App\Observers;

use App\Models\OnboardingStatus;

class OnboardingObserver
{
    public function saving(OnboardingStatus $onboardingStatus): void
    {
        // Automatically mark onboarding as completed if all steps are done
        if (
            $onboardingStatus->whatsapp_connected &&
            $onboardingStatus->business_profile_completed &&
            $onboardingStatus->first_template_created &&
            $onboardingStatus->first_campaign_created &&
            $onboardingStatus->ai_training_completed
        ) {

            if (! $onboardingStatus->onboarding_completed) {
                $onboardingStatus->onboarding_completed = true;

                // If they completed it AFTER at least one reminder, mark as recovered
                if ($onboardingStatus->reminders_sent_count > 0) {
                    $onboardingStatus->was_recovered = true;
                    $onboardingStatus->recovered_at = now();
                }

                \Illuminate\Support\Facades\Log::info("Onboarding completed for user: {$onboardingStatus->user_id}");
            }
        }

        // Update activity timestamp on any change
        if ($onboardingStatus->isDirty()) {
            $onboardingStatus->last_activity_at = now();
        }
    }

    public function updated(OnboardingStatus $onboardingStatus): void
    {
        $engine = app(\App\Services\WorkflowEngine::class);
        $user = $onboardingStatus->user;
        if (! $user) {
            return;
        }

        if ($onboardingStatus->wasChanged('whatsapp_connected') && $onboardingStatus->whatsapp_connected) {
            $engine->trigger('whatsapp_connected', $user);
        }

        if ($onboardingStatus->wasChanged('business_profile_completed') && $onboardingStatus->business_profile_completed) {
            $engine->trigger('profile_completed', $user);
        }

        if ($onboardingStatus->wasChanged('onboarding_completed') && $onboardingStatus->onboarding_completed) {
            $engine->trigger('onboarding_completed', $user);
        }
    }
}
