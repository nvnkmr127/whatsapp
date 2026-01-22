<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\QualityRatingHistory;
use App\Traits\WhatsApp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorWhatsAppPhoneStatus extends Command
{
    use WhatsApp;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:monitor-phones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor WhatsApp phone number status and quality ratings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting WhatsApp phone status monitoring...');

        $teamsChecked = 0;
        $teamsWithIssues = 0;

        Team::whereNotNull('whatsapp_phone_number_id')
            ->whereNotNull('whatsapp_access_token')
            ->chunk(50, function ($teams) use (&$teamsChecked, &$teamsWithIssues) {
                foreach ($teams as $team) {
                    try {
                        $hasIssues = $this->checkPhoneStatus($team);

                        if ($hasIssues) {
                            $teamsWithIssues++;
                        }

                        $teamsChecked++;

                        // Rate limit: 5 req/sec
                        usleep(200000);

                    } catch (\Exception $e) {
                        $this->error("Failed to check team {$team->id}: {$e->getMessage()}");
                        Log::error("Phone status check failed for team {$team->id}", [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

        $this->info("Checked {$teamsChecked} teams. {$teamsWithIssues} teams have issues.");

        return Command::SUCCESS;
    }

    /**
     * Check phone status for a specific team
     */
    private function checkPhoneStatus(Team $team): bool
    {
        $hasIssues = false;

        try {
            $result = $this->getPhoneNumberDetails($team->whatsapp_phone_number_id);

            if (!$result['status']) {
                $this->handlePhoneError($team, $result['message']);
                return true;
            }

            $data = $result['data'];
            $previousStatus = $team->whatsapp_phone_status;
            $previousRating = $team->wm_quality_rating;

            // Determine status from quality rating
            $newStatus = $this->determinePhoneStatus($data);
            $newRating = strtoupper($data['quality_rating'] ?? 'UNKNOWN');

            // Update team
            $team->update([
                'whatsapp_phone_status' => $newStatus,
                'whatsapp_phone_status_checked_at' => now(),
                'wm_quality_rating' => $newRating,
                'wm_messaging_limit' => $data['messaging_limit_tier'] ?? null,
                'whatsapp_token_last_validated' => now(),
            ]);

            // Check for status degradation
            if ($previousStatus !== $newStatus && $newStatus !== 'active') {
                $this->warn("Team {$team->id}: Phone status changed from {$previousStatus} to {$newStatus}");
                $this->handleStatusChange($team, $previousStatus, $newStatus, $data);
                $hasIssues = true;
            }

            // Check for quality rating changes
            if ($previousRating !== $newRating) {
                $this->handleQualityRatingChange($team, $previousRating, $newRating, $data);

                if ($newRating === 'RED' || $newRating === 'YELLOW') {
                    $hasIssues = true;
                }
            }

        } catch (\Exception $e) {
            Log::error("Phone status check failed for team {$team->id}: " . $e->getMessage());
            throw $e;
        }

        return $hasIssues;
    }

    /**
     * Determine phone status from API data
     */
    private function determinePhoneStatus(array $data): string
    {
        $quality = strtoupper($data['quality_rating'] ?? 'UNKNOWN');

        return match ($quality) {
            'GREEN' => 'active',
            'YELLOW' => 'flagged',
            'RED' => 'restricted',
            'UNKNOWN' => 'unknown',
            default => 'active'
        };
    }

    /**
     * Handle phone status change
     */
    private function handleStatusChange(Team $team, ?string $previousStatus, string $newStatus, array $data): void
    {
        Log::warning("Phone status changed for team {$team->id}", [
            'previous' => $previousStatus,
            'new' => $newStatus,
            'quality_rating' => $data['quality_rating'] ?? null,
        ]);

        // TODO: Send notification to team owner
        // $team->owner->notify(new WhatsAppPhoneStatusAlert($team, $newStatus, $data));
    }

    /**
     * Handle quality rating change
     */
    private function handleQualityRatingChange(Team $team, ?string $oldRating, string $newRating, array $data): void
    {
        $severity = match ($newRating) {
            'RED' => 'critical',
            'YELLOW' => 'warning',
            'GREEN' => 'info',
            default => 'unknown'
        };

        // Log to history
        QualityRatingHistory::create([
            'team_id' => $team->id,
            'previous_rating' => $oldRating,
            'new_rating' => $newRating,
            'severity' => $severity,
            'metadata' => [
                'messaging_limit_tier' => $data['messaging_limit_tier'] ?? null,
                'display_phone_number' => $data['display_phone_number'] ?? null,
                'verified_name' => $data['verified_name'] ?? null,
            ],
        ]);

        if ($severity === 'critical') {
            $this->error("CRITICAL: Team {$team->id} quality rating is RED!");

            Log::critical("Quality rating RED for team {$team->id}", [
                'previous' => $oldRating,
                'new' => $newRating,
                'data' => $data,
            ]);

            // Pause running campaigns
            $pausedCount = $team->campaigns()->where('status', 'running')->update(['status' => 'paused']);

            if ($pausedCount > 0) {
                $this->warn("Paused {$pausedCount} campaigns for team {$team->id}");
            }

            // TODO: Send critical notification
            // $team->owner->notify(new CriticalQualityRatingAlert($team, $newRating));

        } elseif ($severity === 'warning') {
            $this->warn("WARNING: Team {$team->id} quality rating is YELLOW");

            Log::warning("Quality rating YELLOW for team {$team->id}", [
                'previous' => $oldRating,
                'new' => $newRating,
            ]);

            // TODO: Send warning notification
            // $team->owner->notify(new QualityRatingWarning($team, $newRating));
        } else {
            $this->info("Team {$team->id}: Quality rating changed from {$oldRating} to {$newRating}");
        }
    }

    /**
     * Handle phone error
     */
    private function handlePhoneError(Team $team, string $error): void
    {
        Log::error("Failed to fetch phone details for team {$team->id}", [
            'error' => $error
        ]);

        // Check if it's a token error
        if (str_contains(strtolower($error), 'token') || str_contains($error, '190')) {
            $team->update([
                'whatsapp_phone_status' => 'error',
                'whatsapp_phone_status_checked_at' => now(),
            ]);

            // TODO: Notify about token issue
            $this->error("Team {$team->id}: Token error - {$error}");
        }
    }
}
