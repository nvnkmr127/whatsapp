<?php

namespace App\Services;

use App\Enums\AlertSeverity;
use App\Models\QualityRatingHistory;
use App\Models\Team;
use App\Models\WhatsAppHealthAlert;
use App\Models\WhatsAppHealthSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppHealthMonitor
{
    /**
     * Check complete health of a team's WhatsApp account
     */
    public function checkHealth(Team $team): array
    {
        $tokenHealth = $this->checkTokenHealth($team);
        $phoneHealth = $this->checkPhoneHealth($team);
        $qualityHealth = $this->checkQualityHealth($team);
        $messagingHealth = $this->checkMessagingHealth($team);

        $overallScore = $this->calculateOverallScore([
            'token' => $tokenHealth['score'],
            'phone' => $phoneHealth['score'],
            'quality' => $qualityHealth['score'],
            'messaging' => $messagingHealth['score'],
        ]);

        $status = $this->getHealthStatus($overallScore);

        $results = [
            'overall_score' => $overallScore,
            'status' => $status,
            'token' => $tokenHealth,
            'phone' => $phoneHealth,
            'quality' => $qualityHealth,
            'messaging' => $messagingHealth,
            'alerts' => $this->getActiveAlerts($team),
            'checked_at' => now(),
        ];

        // Sync Integration State (Lifecycle Management)
        try {
            $engine = app(\App\Services\WhatsAppVerificationEngine::class)->setTeam($team);
            $engine->verify(); // This updates $team->whatsapp_setup_state
        } catch (\Exception $e) {
            Log::error("Error syncing integration state in HealthMonitor: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Check token health
     */
    public function checkTokenHealth(Team $team): array
    {
        $score = 100;
        $issues = [];
        $valid = true;

        // Check if token exists
        if (empty($team->whatsapp_access_token)) {
            return [
                'score' => 0,
                'valid' => false,
                'issues' => ['No access token configured'],
                'expires_at' => null,
                'days_remaining' => null,
            ];
        }

        // Check expiration
        if ($team->whatsapp_token_expires_at) {
            if ($team->whatsapp_token_expires_at->isPast()) {
                $score = 0;
                $valid = false;
                $issues[] = 'Token expired';
            } else {
                $daysUntilExpiry = $team->whatsapp_token_expires_at->diffInDays();

                if ($daysUntilExpiry < 3) {
                    $score -= 60;
                    $issues[] = "Token expires in {$daysUntilExpiry} days (critical)";
                } elseif ($daysUntilExpiry < 7) {
                    $score -= 40;
                    $issues[] = "Token expires in {$daysUntilExpiry} days";
                } elseif ($daysUntilExpiry < 30) {
                    $score -= 10;
                    $issues[] = "Token expires in {$daysUntilExpiry} days";
                }
            }
        }

        // Check last validation
        if ($team->whatsapp_token_last_validated) {
            $hoursSinceValidation = $team->whatsapp_token_last_validated->diffInHours();

            if ($hoursSinceValidation > 48) {
                $score -= 20;
                $issues[] = 'Not validated in 48+ hours';
            } elseif ($hoursSinceValidation > 24) {
                $score -= 10;
                $issues[] = 'Not validated in 24+ hours';
            }
        } else {
            $score -= 15;
            $issues[] = 'Never validated';
        }

        $tokenResults = [
            'score' => max(0, $score),
            'valid' => $valid,
            'is_permanent' => is_null($team->whatsapp_token_expires_at),
            'expires_at' => $team->whatsapp_token_expires_at,
            'days_remaining' => $team->whatsapp_token_expires_at?->diffInDays(),
            'last_validated' => $team->whatsapp_token_last_validated,
            'issues' => $issues,
        ];

        // [AUTO-ALERT] Critical Token Health
        // Skip alert if it's a permanent token (no expiry) unless there's another non-expiry issue
        if ($score < 50 && $team && $team->owner && !is_null($team->whatsapp_token_expires_at)) {
            $msg = !empty($issues) ? $issues[0] : "Token health is critical ({$score}%)";
            $this->createAlert($team, AlertSeverity::CRITICAL, 'token', 'token_expiry', $msg);

            try {
                $team->owner->notify(new \App\Notifications\WhatsAppHealthNotification($team, 'token_expiry', $msg));
            } catch (\Exception $e) {
                Log::error("Failed to notify token alert: " . $e->getMessage());
            }
        }

        return $tokenResults;
    }

    /**
     * Check phone health
     */
    public function checkPhoneHealth(Team $team): array
    {
        $score = 100;
        $issues = [];

        if (empty($team->whatsapp_phone_number_id)) {
            return [
                'score' => 0,
                'verified' => false,
                'status' => 'not_configured',
                'issues' => ['No phone number configured'],
            ];
        }

        // Check verification status
        $verified = $team->whatsapp_phone_verification_status === 'verified';
        if (!$verified) {
            $score -= 40;
            $issues[] = 'Phone not verified';
        }

        // Check phone status
        $status = $team->whatsapp_phone_status ?? 'unknown';
        if ($status === 'restricted') {
            $score -= 60;
            $issues[] = 'Phone restricted';
        } elseif ($status === 'flagged') {
            $score -= 30;
            $issues[] = 'Phone flagged';
        } elseif ($status === 'unknown') {
            $score -= 10;
            $issues[] = 'Phone status unknown';
        }

        // Check last status check
        if ($team->whatsapp_phone_status_checked_at) {
            $hoursSinceCheck = $team->whatsapp_phone_status_checked_at->diffInHours();

            if ($hoursSinceCheck > 24) {
                $score -= 15;
                $issues[] = 'Status not checked in 24+ hours';
            } elseif ($hoursSinceCheck > 12) {
                $score -= 5;
            }
        } else {
            $score -= 20;
            $issues[] = 'Status never checked';
        }

        return [
            'score' => max(0, $score),
            'verified' => $verified,
            'status' => $status,
            'last_checked' => $team->whatsapp_phone_status_checked_at,
            'issues' => $issues,
        ];
    }

    /**
     * Check quality rating health
     */
    public function checkQualityHealth(Team $team): array
    {
        $rating = $team->whatsapp_quality_rating ?? 'UNKNOWN';

        $baseScore = match ($rating) {
            'GREEN' => 100,
            'YELLOW' => 60,
            'RED' => 0,
            default => 50,
        };

        $issues = [];
        $trend = $this->getQualityTrend($team);

        // Adjust for trend
        if ($trend === 'improving') {
            $baseScore = min(100, $baseScore + 10);
        } elseif ($trend === 'degrading') {
            $baseScore = max(0, $baseScore - 20);
            $issues[] = 'Quality rating degrading';
        }

        // Check for RED rating
        if ($rating === 'RED') {
            $issues[] = 'Quality rating is RED - sending disabled';
        } elseif ($rating === 'YELLOW') {
            $issues[] = 'Quality rating is YELLOW - sending limited';
        } elseif ($rating === 'UNKNOWN') {
            $issues[] = 'Quality rating unknown';
        }

        // [AUTO-ALERT] Quality Rating
        if ($rating === 'RED' && $team && $team->owner) {
            $this->createAlert($team, AlertSeverity::CRITICAL, 'quality', 'quality_red', "Your WhatsApp Quality has dropped to RED. Sending is disabled.");
        }

        return [
            'score' => $baseScore,
            'rating' => $rating,
            'trend' => $trend,
            'issues' => $issues,
        ];
    }

    /**
     * Check messaging tier health
     */
    public function checkMessagingHealth(Team $team): array
    {
        $tier = $team->whatsapp_messaging_limit ?? 'TIER_1K';
        $limit = $this->getTierLimit($tier);
        $usage = $this->get24HourUsage($team);

        $usagePercent = $limit > 0 ? ($usage / $limit) * 100 : 0;

        $score = 100;
        $issues = [];

        if ($usagePercent >= 100) {
            $score = 0;
            $issues[] = 'Daily limit exceeded';
        } elseif ($usagePercent >= 90) {
            $score = 30;
            $issues[] = 'At 90% of daily limit';
        } elseif ($usagePercent >= 70) {
            $score = 70;
            $issues[] = 'At 70% of daily limit';
        }

        return [
            'score' => $score,
            'tier' => $tier,
            'daily_limit' => $limit,
            'current_usage' => $usage,
            'usage_percent' => round($usagePercent, 2),
            'remaining' => max(0, $limit - $usage),
            'issues' => $issues,
        ];
    }

    /**
     * Calculate overall health score with weighted dimensions
     */
    protected function calculateOverallScore(array $scores): int
    {
        $weights = [
            'token' => 0.30,
            'phone' => 0.20,
            'quality' => 0.35,
            'messaging' => 0.15,
        ];

        $weightedScore = 0;
        foreach ($scores as $dimension => $score) {
            $weightedScore += $score * $weights[$dimension];
        }

        return round($weightedScore);
    }

    /**
     * Get health status from score
     */
    protected function getHealthStatus(int $score): string
    {
        if ($score >= 90) {
            return 'healthy';
        } elseif ($score >= 60) {
            return 'warning';
        } else {
            return 'critical';
        }
    }

    /**
     * Get quality rating trend
     */
    protected function getQualityTrend(Team $team): string
    {
        $history = QualityRatingHistory::where('team_id', $team->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        if ($history->count() < 2) {
            return 'stable';
        }

        $current = $history[0]->new_rating;
        $previous = $history[1]->new_rating;

        $ratingValues = ['RED' => 0, 'YELLOW' => 1, 'GREEN' => 2];

        $currentValue = $ratingValues[$current] ?? 1;
        $previousValue = $ratingValues[$previous] ?? 1;

        if ($currentValue > $previousValue) {
            return 'improving';
        } elseif ($currentValue < $previousValue) {
            return 'degrading';
        }

        return 'stable';
    }

    /**
     * Get tier limit
     */
    protected function getTierLimit(string $tier): int
    {
        return match ($tier) {
            'TIER_1K' => 1000,
            'TIER_10K' => 10000,
            'TIER_100K' => 100000,
            'TIER_UNLIMITED' => PHP_INT_MAX,
            default => 1000,
        };
    }

    /**
     * Get 24-hour message usage
     */
    protected function get24HourUsage(Team $team): int
    {
        // Count messages sent in last 24 hours
        return DB::table('messages')
            ->where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    /**
     * Get active alerts for team
     */
    public function getActiveAlerts(Team $team): array
    {
        return WhatsAppHealthAlert::where('team_id', $team->id)
            ->where('acknowledged', false)
            ->whereNull('resolved_at')
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Check if team can send messages
     */
    public function canSendMessages(Team $team): bool
    {
        // Check token validity
        if (
            !$team->whatsapp_access_token ||
            ($team->whatsapp_token_expires_at && $team->whatsapp_token_expires_at->isPast())
        ) {
            return false;
        }

        // Check quality rating
        if ($team->whatsapp_quality_rating === 'RED') {
            return false;
        }

        // Check messaging limit
        $tier = $team->whatsapp_messaging_limit ?? 'TIER_1K';
        $limit = $this->getTierLimit($tier);
        $usage = $this->get24HourUsage($team);

        if ($usage >= $limit) {
            return false;
        }

        return true;
    }

    /**
     * Get blocking issues preventing message sending
     */
    public function getBlockingIssues(Team $team): array
    {
        $issues = [];

        if (!$team->whatsapp_access_token) {
            $issues[] = 'No access token configured';
        } elseif ($team->whatsapp_token_expires_at && $team->whatsapp_token_expires_at->isPast()) {
            $issues[] = 'Access token expired';
        }

        if ($team->whatsapp_quality_rating === 'RED') {
            $issues[] = 'Quality rating is RED';
        }

        $tier = $team->whatsapp_messaging_limit ?? 'TIER_1K';
        $limit = $this->getTierLimit($tier);
        $usage = $this->get24HourUsage($team);

        if ($usage >= $limit) {
            $issues[] = 'Daily messaging limit exceeded';
        }

        if ($team->whatsapp_phone_status === 'restricted') {
            $issues[] = 'Phone number restricted';
        }

        return $issues;
    }

    /**
     * Create health snapshot
     */
    public function createSnapshot(Team $team): WhatsAppHealthSnapshot
    {
        $health = $this->checkHealth($team);

        return WhatsAppHealthSnapshot::create([
            'team_id' => $team->id,
            'health_score' => $health['overall_score'],
            'health_status' => $health['status'],
            'token_health_score' => $health['token']['score'],
            'phone_health_score' => $health['phone']['score'],
            'quality_health_score' => $health['quality']['score'],
            'messaging_health_score' => $health['messaging']['score'],
            'token_valid' => $health['token']['valid'],
            'token_expires_at' => $health['token']['expires_at'],
            'token_days_until_expiry' => $health['token']['days_remaining'],
            'phone_verified' => $health['phone']['verified'],
            'phone_status' => $health['phone']['status'],
            'quality_rating' => $health['quality']['rating'],
            'quality_trend' => $health['quality']['trend'],
            'messaging_tier' => $health['messaging']['tier'],
            'daily_limit' => $health['messaging']['daily_limit'],
            'current_usage' => $health['messaging']['current_usage'],
            'usage_percent' => $health['messaging']['usage_percent'],
            'snapshot_at' => now(),
        ]);
    }

    /**
     * Create alert
     */
    public function createAlert(Team $team, AlertSeverity $severity, string $dimension, string $alertType, string $message, array $metadata = []): WhatsAppHealthAlert
    {
        $alert = WhatsAppHealthAlert::create([
            'team_id' => $team->id,
            'severity' => $severity->value,
            'dimension' => $dimension,
            'alert_type' => $alertType,
            'message' => $message,
            'metadata' => $metadata,
        ]);

        // Bridge to general Alert Engine if a matching rule exists
        $slug = match ($alertType) {
            'quality_red' => 'whatsapp-quality-red',
            'webhook_pulse' => 'whatsapp-pulse-loss',
            default => null
        };

        if ($slug) {
            $rule = \App\Models\AlertRule::where('slug', $slug)->first();
            if ($rule) {
                \App\Models\AlertLog::create([
                    'rule_id' => $rule->id,
                    'team_id' => $team->id,
                    'suppression_key' => md5($slug . $team->id . floor(time() / $rule->throttle_seconds)),
                    'status' => 'processed',
                    'severity' => $severity,
                    'payload' => array_merge(['message' => $message], $metadata),
                    'triggered_at' => now(),
                ]);
            }
        }

        return $alert;
    }
}
