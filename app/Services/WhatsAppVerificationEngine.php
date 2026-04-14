<?php

namespace App\Services;

use App\Enums\IntegrationState;
use App\Models\Team;
use App\Traits\WhatsApp;
use Illuminate\Support\Facades\Log;

class WhatsAppVerificationEngine
{
    use WhatsApp;

    protected Team $team;

    public function __construct(?Team $team = null)
    {
        if ($team) {
            $this->team = $team;
        }
    }

    public function setTeam(Team $team): self
    {
        $this->team = $team;

        return $this;
    }

    /**
     * Run the full verification checklist.
     */
    public function verify(): array
    {
        if (! $this->team->whatsapp_access_token) {
            return $this->updateState(IntegrationState::DISCONNECTED, ['error' => 'No token provided']);
        }

        // Tier 1: Identity & Token Validity
        $results['tier1'] = $this->verifyTier1Identity();
        if (! $results['tier1']['status']) {
            $finalState = $this->determineFinalState($results);

            return $this->updateState($finalState, $results);
        }

        // Tier 2: Entity Verification (Phone Number)
        $results['tier2'] = $this->verifyTier2Entity();
        if (! $results['tier2']['status']) {
            $finalState = $this->determineFinalState($results);

            return $this->updateState($finalState, $results);
        }

        // Tier 3: Readiness (Webhooks & Templates)
        $results['tier3'] = $this->verifyTier3Readiness();

        // Tier 4: Health (Quality Rating)
        $results['tier4'] = $this->verifyTier4Health();

        // Tier 5: Connectivity (Real-world Webhook Pulse)
        $results['tier5'] = $this->verifyTier5Connectivity();

        // Determine final state based on results
        $finalState = $this->determineFinalState($results);

        return $this->updateState($finalState, $results);
    }

    protected function verifyTier1Identity(): array
    {
        $token = $this->team->whatsapp_access_token;
        $wabaId = $this->team->whatsapp_business_account_id;

        $debug = $this->debugToken($token);
        if (! $debug['status']) {
            // Fallback for Manual Tokens: Try a direct API call to verify functionality
            Log::info("debugToken failed for team {$this->team->id}, attempting direct API fallback.");

            // If debug failed due to mismatch, disable proof for the fallback call
            if (str_contains($debug['message'] ?? '', 'Configuration Error')) {
                $this->setSkipAppSecretProof(true);
            }

            $fallback = $this->loadTemplatesFromWhatsApp(); // Reuse loadTemplates as a functional test

            if ($fallback['status']) {
                return [
                    'status' => true,
                    'is_expiring_soon' => false,
                    'is_manual_fallback' => true,
                    'note' => 'Verified via functional API call (Manual token)',
                ];
            }

            return ['status' => false, 'error' => $debug['message'], 'category' => 'AUTH_EXPIRED'];
        }

        $data = $debug['data'];

        // Persist token details for Health Dashboard
        $expiresAt = $data['expires_at'] ?? null;
        $this->team->update([
            'whatsapp_token_last_validated' => now(),
            'whatsapp_token_expires_at' => $expiresAt ? \Carbon\Carbon::createFromTimestamp($expiresAt) : null,
        ]);

        // Scope check
        $scopes = $data['scopes'] ?? [];
        $required = ['whatsapp_business_messaging', 'whatsapp_business_management'];
        $missing = array_diff($required, $scopes);
        
        $results['scopes'] = [
            'has_messaging' => in_array('whatsapp_business_messaging', $scopes),
            'has_management' => in_array('whatsapp_business_management', $scopes),
            'all' => $scopes
        ];

        if (! empty($missing)) {
            // Log missing scopes but don't hard-fail if there's no scope metadata (common in some manual system tokens)
            Log::warning("Token missing recommended scopes for team {$this->team->id}: ".implode(', ', $missing));
        }

        // Rule: App Mode Check (Production Hardening)
        $isLive = $data['app_mode'] ?? true; // Default to true if not present in legacy debug
        if (!$isLive) {
            Log::warning("WhatsApp Verification Warning: Team {$this->team->id} is connected via an App in DEVELOPMENT MODE. Webhooks will ONLY work for registered App Developers.");
            $results['tier1']['app_mode_warning'] = true;
        }

        // Rule 2: Token Grace Period check
        $isExpiringSoon = false;
        if ($expiresAt) {
            $expiry = \Carbon\Carbon::createFromTimestamp($expiresAt);
            if ($expiry->isPast()) {
                return ['status' => false, 'error' => 'Token expired', 'category' => 'AUTH_EXPIRED'];
            }
            if ($expiry->diffInHours(now()) < 48) {
                $isExpiringSoon = true;
            }
        }

        return ['status' => true, 'is_expiring_soon' => $isExpiringSoon];
    }

    protected function verifyTier2Entity(): array
    {
        $phoneId = $this->team->whatsapp_phone_number_id;
        if (! $phoneId) {
            return ['status' => false, 'error' => 'No phone number configured', 'category' => 'ENTITY_MISMATCH'];
        }

        $details = $this->getPhoneNumberDetails($phoneId);
        if (! $details['status']) {
            return ['status' => false, 'error' => $details['message'], 'category' => 'ENTITY_MISMATCH'];
        }

        // Potential check if phone belongs to WABA?
        // Usually if getPhoneNumberDetails works with the WABA's token, it belongs.

        $data = $details['data'];

        // Persist these details to the team immediately as they are source-of-truth
        $this->team->update([
            'whatsapp_messaging_limit' => $data['messaging_limit_tier'] ?? $this->team->whatsapp_messaging_limit,
            'whatsapp_quality_rating' => $data['quality_rating'] ?? $this->team->whatsapp_quality_rating,
            'whatsapp_phone_display' => $data['display_phone_number'] ?? $this->team->whatsapp_phone_display,
            'whatsapp_verified_name' => $data['verified_name'] ?? $this->team->whatsapp_verified_name,
        ]);

        return ['status' => true, 'data' => $data];
    }

    protected function verifyTier3Readiness(): array
    {
        $wabaId = $this->team->whatsapp_business_account_id;
        $token  = $this->team->whatsapp_access_token;

        if (! $wabaId) {
            return ['status' => false, 'error' => 'No WABA ID'];
        }

        // Advisory scope check (warning-only).
        // Meta does not always return granular scopes for USER tokens, so we cannot
        // hard-fail here — we simply log and continue to the webhook check.
        $debug  = $this->debugToken($token);
        $scopes = $debug['data']['scopes'] ?? [];
        $scopeWarning = null;
        if (! empty($scopes) && ! in_array('whatsapp_business_management', $scopes)) {
            $scopeWarning = 'Token may be missing "whatsapp_business_management" scope — webhook verification may be limited.';
            Log::warning("WhatsApp VerificationEngine [Tier3]: {$scopeWarning}", ['team_id' => $this->team->id]);
        }

        // Webhook subscription check.
        // The trait returns `unverifiable: true` when the token cannot list subscriptions
        // (e.g. USER token without management scope). We treat that as passing Tier 3 since
        // the subscription POST succeeded during setup.
        $webhook = $this->checkWebhookSubscription($wabaId, $token);
        $isSubscribed = ($webhook['is_subscribed'] ?? false) || ($webhook['unverifiable'] ?? false);

        // Template baseline
        $templates = $this->team->whatsappTemplates()->count();

        return [
            'status'            => $isSubscribed,
            'webhook_subscribed'=> $isSubscribed,
            'unverifiable'      => $webhook['unverifiable'] ?? false,
            'scope_warning'     => $scopeWarning,
            'template_count'    => $templates,
        ];
    }

    protected function verifyTier4Health(): array
    {
        $quality = $this->team->whatsapp_quality_rating;

        if ($quality === 'RED') {
            return ['status' => false, 'error' => 'Quality rating is RED'];
        }

        return ['status' => true];
    }

    protected function verifyTier5Connectivity(): array
    {
        // Check for recent pulse (within last 48 hours is healthy for active teams)
        $lastPulse = $this->team->last_webhook_received_at;
        
        if (!$lastPulse) {
             return [
                 'status' => true, 
                 'is_verified' => false,
                 'note' => 'No webhook signal received yet. Connection is API-only until first message or status update arrives.'
             ];
        }

        $isHealthy = $lastPulse->diffInHours(now()) < 48;

        return [
            'status' => true,
            'is_verified' => $isHealthy,
            'last_pulse' => $lastPulse->toDateTimeString()
        ];
    }

    protected function determineFinalState(array $results): IntegrationState
    {
        if (! ($results['tier1']['status'] ?? false)) {
            return IntegrationState::SUSPENDED;
        }
        if (! ($results['tier2']['status'] ?? false)) {
            return IntegrationState::AUTHENTICATED;
        }
        if (! ($results['tier3']['status'] ?? false) || ! ($results['tier3']['webhook_subscribed'] ?? false)) {
            return IntegrationState::PROVISIONED;
        }
        if (! ($results['tier4']['status'] ?? false)) {
            return IntegrationState::RESTRICTED;
        }

        // Check for READY_WARNING (Rule 2)
        if ($results['tier1']['is_expiring_soon'] ?? false) {
            return IntegrationState::READY_WARNING;
        }

        return IntegrationState::READY;
    }

    protected function updateState(IntegrationState $state, array $results): array
    {
        $this->team->update([
            'whatsapp_setup_state' => $state->value,
            // 'whatsapp_setup_progress' => json_encode($results), // Optional: store details
        ]);

        return [
            'state' => $state,
            'results' => $results,
        ];
    }
}
