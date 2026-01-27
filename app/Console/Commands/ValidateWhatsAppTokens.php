<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ValidateWhatsAppTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:validate-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate WhatsApp access tokens and check for expiration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Validating WhatsApp tokens...');

        $teamsChecked = 0;
        $tokensExpired = 0;
        $tokensExpiringSoon = 0;
        $tokensInvalid = 0;

        Team::whereNotNull('whatsapp_access_token')->chunk(100, function ($teams) use (&$teamsChecked, &$tokensExpired, &$tokensExpiringSoon, &$tokensInvalid) {
            foreach ($teams as $team) {
                try {
                    // Check expiration first
                    if ($team->whatsapp_token_expires_at && $team->whatsapp_token_expires_at->isPast()) {
                        $this->warn("Team {$team->id}: Token expired on {$team->whatsapp_token_expires_at}");
                        $tokensExpired++;
                        $this->handleTokenExpired($team);
                        continue;
                    }

                    // Check if expiring soon (7 days)
                    if ($team->whatsapp_token_expires_at && $team->whatsapp_token_expires_at->diffInDays() < 7) {
                        $daysRemaining = $team->whatsapp_token_expires_at->diffInDays();
                        $this->warn("Team {$team->id}: Token expires in {$daysRemaining} days");
                        $tokensExpiringSoon++;
                        // TODO: Send notification
                    }

                    // Validate token with API call
                    $service = new WhatsAppService($team);
                    $result = $service->getBusinessProfile();

                    if (isset($result['error'])) {
                        $isPermanent = $this->handleTokenError($team, $result['error']);
                        if ($isPermanent) {
                            $team->update([
                                'whatsapp_connected' => false,
                                'whatsapp_setup_state' => \App\Enums\IntegrationState::DISCONNECTED,
                                'whatsapp_token_expires_at' => null
                            ]);
                            $this->error("Auto-Disconnected Team {$team->id} due to permanent auth failure.");
                        }
                        $tokensInvalid++;
                    } else {
                        $team->update(['whatsapp_token_last_validated' => now()]);
                        $teamsChecked++;
                    }

                    // Rate limit
                    usleep(100000); // 10 req/sec

                } catch (\Exception $e) {
                    $this->error("Failed to validate team {$team->id}: {$e->getMessage()}");
                    $this->handleTokenError($team, ['message' => $e->getMessage()]);
                    $tokensInvalid++;
                }
            }
        });

        $this->info("Validation complete:");
        $this->info("  - Checked: {$teamsChecked}");
        $this->info("  - Expired: {$tokensExpired}");
        $this->info("  - Expiring soon: {$tokensExpiringSoon}");
        $this->info("  - Invalid: {$tokensInvalid}");

        return Command::SUCCESS;
    }

    /**
     * Handle expired token
     */
    private function handleTokenExpired(Team $team): void
    {
        Log::warning("WhatsApp token expired for team {$team->id}");

        // TODO: Send notification to team owner
        // $team->owner->notify(new WhatsAppTokenExpired($team));
    }

    /**
     * Handle token error
     * @return bool True if permanent failure
     */
    private function handleTokenError(Team $team, array $error): bool
    {
        $message = $error['message'] ?? 'Unknown error';
        $code = $error['code'] ?? null;

        // Check if it's an auth error
        if (str_contains(strtolower($message), 'token') || $code == 190 || $code == 102) {
            Log::error("WhatsApp token invalid for team {$team->id}", [
                'error' => $error
            ]);

            // Return true to trigger auto-disconnect
            return true;
        } else {
            Log::warning("WhatsApp API error for team {$team->id}", [
                'error' => $error
            ]);
        }
        return false;
    }
}
