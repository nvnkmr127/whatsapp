<?php

namespace App\Services;

use App\DTOs\ValidationResult;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Team;
use App\Models\WhatsappTemplate;
use App\Validators\OptInValidator;
use App\Validators\QualityProtectionValidator;
use App\Validators\RateLimitValidator;
use App\Validators\TemplateValidator;

class WhatsAppSendGuardrails
{
    public function __construct(
        protected TemplateValidator $templateValidator,
        protected OptInValidator $optInValidator,
        protected QualityProtectionValidator $qualityValidator,
        protected RateLimitValidator $rateLimitValidator,
    ) {
    }

    /**
     * Validate entire campaign before sending
     */
    public function validateCampaign(Campaign $campaign): ValidationResult
    {
        $result = new ValidationResult();

        // Layer 1: Template validation
        if ($campaign->template) {
            $templateResult = $this->templateValidator->validate(
                $campaign->template,
                $campaign->template_parameters ?? []
            );

            foreach ($templateResult->errors as $error) {
                $result->addError($error);
            }

            foreach ($templateResult->warnings as $warning) {
                $result->addError($warning);
            }
        }

        // Layer 2: Quality protection
        $qualityResult = $this->qualityValidator->validate(
            $campaign->team,
            $campaign->recipients()->count()
        );

        foreach ($qualityResult->errors as $error) {
            $result->addError($error);
        }

        foreach ($qualityResult->warnings as $warning) {
            $result->addError($warning);
        }

        // Layer 3: Rate limiting
        $rateLimitResult = $this->rateLimitValidator->validate(
            $campaign->team,
            $campaign->recipients()->count()
        );

        foreach ($rateLimitResult->errors as $error) {
            $result->addError($error);
        }

        foreach ($rateLimitResult->warnings as $warning) {
            $result->addError($warning);
        }

        // Layer 4: Per-recipient validation
        if ($result->canSend()) {
            $result->recipientResults = $this->validateRecipients($campaign);
        }

        return $result;
    }

    /**
     * Validate individual message send
     */
    public function validateMessage(
        Team $team,
        Contact $contact,
        WhatsappTemplate $template,
        array $parameters = [],
        string $messageType = 'transactional'
    ): ValidationResult {
        $result = new ValidationResult();

        // Template validation
        $templateResult = $this->templateValidator->validate($template, $parameters);
        foreach ($templateResult->errors as $error) {
            $result->addError($error);
        }

        // Opt-in validation
        $optInResult = $this->optInValidator->validate($contact, $messageType);
        foreach ($optInResult->errors as $error) {
            $result->addError($error);
        }

        // Quality validation
        $qualityResult = $this->qualityValidator->validate($team, 1);
        foreach ($qualityResult->errors as $error) {
            $result->addError($error);
        }

        // Rate limit validation
        $rateLimitResult = $this->rateLimitValidator->validate($team, 1);
        foreach ($rateLimitResult->errors as $error) {
            $result->addError($error);
        }

        return $result;
    }

    /**
     * Validate all recipients in campaign
     */
    protected function validateRecipients(Campaign $campaign): array
    {
        $results = [];
        $messageType = $campaign->message_type ?? 'transactional';

        foreach ($campaign->recipients as $contact) {
            $optInResult = $this->optInValidator->validate($contact, $messageType);

            $results[$contact->id] = [
                'valid' => $optInResult->isValid(),
                'errors' => $optInResult->getErrors(),
                'warnings' => $optInResult->getWarnings(),
            ];
        }

        return $results;
    }

    /**
     * Quick check if team can send messages
     */
    public function canSend(Team $team): bool
    {
        // Check quality rating
        if ($team->wm_quality_rating === 'RED') {
            return false;
        }

        // Check token validity
        if (
            !$team->whatsapp_access_token ||
            ($team->whatsapp_token_expires_at && $team->whatsapp_token_expires_at->isPast())
        ) {
            return false;
        }

        // Check rate limit
        $rateLimitResult = $this->rateLimitValidator->validate($team, 1);
        if (!$rateLimitResult->isValid()) {
            return false;
        }

        return true;
    }

    /**
     * Get blocking issues preventing sends
     */
    public function getBlockingIssues(Team $team): array
    {
        $issues = [];

        if ($team->wm_quality_rating === 'RED') {
            $issues[] = 'Quality rating is RED';
        }

        if (!$team->whatsapp_access_token) {
            $issues[] = 'No access token configured';
        } elseif ($team->whatsapp_token_expires_at && $team->whatsapp_token_expires_at->isPast()) {
            $issues[] = 'Access token expired';
        }

        $rateLimitResult = $this->rateLimitValidator->validate($team, 1);
        if (!$rateLimitResult->isValid()) {
            $issues[] = $rateLimitResult->getBlockingReason();
        }

        return $issues;
    }
}
