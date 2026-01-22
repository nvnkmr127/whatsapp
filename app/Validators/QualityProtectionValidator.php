<?php

namespace App\Validators;

use App\DTOs\ValidationError;
use App\DTOs\ValidationResult;
use App\Models\Campaign;
use App\Models\Team;
use App\Services\WhatsAppHealthMonitor;

class QualityProtectionValidator
{
    public function __construct(
        protected WhatsAppHealthMonitor $healthMonitor
    ) {
    }

    /**
     * Validate quality rating and campaign size
     */
    public function validate(Team $team, int $recipientCount): ValidationResult
    {
        $result = new ValidationResult();
        $rating = $team->wm_quality_rating ?? 'UNKNOWN';

        // Block if RED
        if ($rating === 'RED') {
            $result->addError(new ValidationError(
                code: 'QUALITY_RATING_RED',
                message: 'Account quality rating is RED - sending disabled',
                severity: 'error',
                field: 'quality_rating',
                suggestion: 'Improve message quality and wait for rating to improve to YELLOW or GREEN',
                metadata: [
                    'rating' => 'RED',
                    'action_required' => 'Review WhatsApp quality guidelines',
                    'support_url' => 'https://business.whatsapp.com/policy',
                ]
            ));

            return $result; // No point checking further
        }

        // Limit if YELLOW
        if ($rating === 'YELLOW' && $recipientCount > 100) {
            $result->addError(new ValidationError(
                code: 'CAMPAIGN_SIZE_EXCEEDED',
                message: 'Campaign size limited to 100 recipients due to YELLOW quality rating',
                severity: 'error',
                field: 'recipient_count',
                suggestion: 'Split campaign into batches of 100 or improve quality rating to GREEN',
                metadata: [
                    'current_size' => $recipientCount,
                    'max_allowed' => 100,
                    'rating' => 'YELLOW',
                    'batches_needed' => ceil($recipientCount / 100),
                ]
            ));
        }

        // Check quality trend
        $health = $this->healthMonitor->checkHealth($team);
        $trend = $health['quality']['trend'] ?? 'stable';

        if ($trend === 'degrading') {
            $result->addError(new ValidationError(
                code: 'QUALITY_DEGRADING',
                message: 'Quality rating is degrading - exercise caution',
                severity: 'warning',
                field: 'quality_rating',
                suggestion: 'Review recent message quality and reduce sending volume',
                metadata: [
                    'trend' => 'degrading',
                    'current_rating' => $rating,
                ]
            ));
        }

        // Warn if YELLOW
        if ($rating === 'YELLOW' && $recipientCount <= 100) {
            $result->addError(new ValidationError(
                code: 'QUALITY_RATING_YELLOW',
                message: 'Quality rating is YELLOW - sending is limited',
                severity: 'warning',
                field: 'quality_rating',
                suggestion: 'Improve message quality to unlock full sending capacity',
                metadata: [
                    'rating' => 'YELLOW',
                    'current_limit' => 100,
                ]
            ));
        }

        return $result;
    }
}
