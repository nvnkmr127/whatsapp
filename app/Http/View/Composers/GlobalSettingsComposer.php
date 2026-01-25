<?php

namespace App\Http\View\Composers;

use App\Models\Setting;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;

class GlobalSettingsComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $settings = Cache::remember('global_settings', 3600, function () {
            return Setting::all()->pluck('value', 'key');
        });

        $view->with('globalSettings', $settings);
        $view->with('brandPrimaryColor', $settings['brand_primary_color'] ?? '#4F46E5');
        $view->with('appName', $settings['app_name'] ?? config('app.name', 'Laravel'));

        // Inject Billing Warnings
        $user = auth()->user();
        if ($user && $user->currentTeam) {
            $billingService = app(\App\Services\BillingService::class);
            $view->with('billingWarnings', $billingService->getWarningStatus($user->currentTeam));
        } else {
            $view->with('billingWarnings', []);
        }
    }
}
