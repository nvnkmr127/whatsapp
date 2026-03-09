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

            if (!$onboardingStatus->onboarding_completed) {
                $onboardingStatus->onboarding_completed = true;
                \Illuminate\Support\Facades\Log::info("Onboarding completed for user: {$onboardingStatus->user_id}");
            }
        }

        // Update activity timestamp on any change
        if ($onboardingStatus->isDirty()) {
            $onboardingStatus->last_activity_at = now();
        }
    }
}
