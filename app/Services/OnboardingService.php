<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;

class OnboardingService
{
    /**
     * Users who registered but haven't started WhatsApp connection (Requesting for new WhatsApp)
     */
    public function getSignupsNotStarted()
    {
        return Team::with('owner')
            ->whereNull('whatsapp_access_token')
            ->whereNull('whatsapp_business_account_id')
            ->orderByDesc('created_at');
    }

    /**
     * Users who started but didn't finish (Didn't connect WhatsApp fully)
     * - Has Token but no WABA
     * - OR Has WABA but no Phone Number
     */
    public function getAbandonedSetup()
    {
        return Team::with('owner')
            ->whereNotNull('whatsapp_access_token')
            ->where(function ($query) {
                $query->whereNull('whatsapp_business_account_id')
                      ->orWhereNull('whatsapp_phone_number_id');
            })
            ->orderByDesc('updated_at');
    }

    /**
     * Users who finished setup (Signed up for WhatsApp)
     */
    public function getSetupCompleted()
    {
        return Team::with('owner')
            ->whereNotNull('whatsapp_phone_number_id')
            ->orderByDesc('updated_at');
    }

    /**
     * Users who finished setup but haven't sent a message (Didn't sent 1st message)
     * Assuming we track message counts or last activity on Team
     */
    public function getSetupCompletedNoActivity()
    {
        return Team::with('owner')
            ->whereNotNull('whatsapp_phone_number_id')
            ->where(function ($query) {
                $query->whereNull('last_webhook_received_at') // No inbound
                      ->whereDoesntHave('messages', function (Builder $q) {
                          $q->where('direction', 'outbound');
                      }); // No outbound
            })
            ->orderByDesc('updated_at');
    }

    /**
     * Get counts for all stages
     */
    public function getFunnelCounts()
    {
        return [
            'not_started' => $this->getSignupsNotStarted()->count(),
            'abandoned' => $this->getAbandonedSetup()->count(),
            'completed_no_activity' => $this->getSetupCompletedNoActivity()->count(),
            'active' => Team::whereNotNull('last_webhook_received_at')->count(),
        ];
    }
}
