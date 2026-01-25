<?php

namespace App\Services\Email;

use App\Enums\EmailUseCase;
use App\Models\User;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AppMarketingService
{
    protected $centralEmailService;

    public function __construct(CentralEmailService $centralEmailService)
    {
        $this->centralEmailService = $centralEmailService;
    }

    /**
     * Dispatch a marketing campaign to all opted-in platform users.
     */
    public function sendCampaign(string $templateSlug, array $baseData = []): int
    {
        $template = EmailTemplate::where('slug', $templateSlug)
            ->where('type', EmailUseCase::MARKETING)
            ->firstOrFail();

        // 1. Fetch Audience (Opted-in platform users only)
        $users = User::where('marketing_opt_in', true)
            ->whereNull('unsubscribed_at')
            ->whereNotNull('email_verified_at')
            ->get();

        $count = 0;
        foreach ($users as $user) {
            // Ensure unsubscribe token exists
            if (!$user->unsubscribe_token) {
                $user->update(['unsubscribe_token' => Str::random(32)]);
            }

            $userData = array_merge($baseData, [
                'name' => $user->name,
                'unsubscribe_url' => route('marketing.unsubscribe', ['token' => $user->unsubscribe_token]),
            ]);

            // Dispatch via centralized service (which queues it)
            // We use the marketing use case which triggers the marketing SMTP if configured
            $this->centralEmailService->sendTemplatedEmail($user->email, $templateSlug, $userData, EmailUseCase::MARKETING);
            $count++;
        }

        Log::info("Marketing campaign '{$templateSlug}' dispatched to {$count} users.");
        return $count;
    }

    /**
     * Handle unsubscribe request.
     */
    public function unsubscribe(string $token): bool
    {
        $user = User::where('unsubscribe_token', $token)->first();

        if ($user) {
            $user->update([
                'marketing_opt_in' => false,
                'unsubscribed_at' => now(),
            ]);
            Log::info("User {$user->email} unsubscribed from marketing.");
            return true;
        }

        return false;
    }
}
