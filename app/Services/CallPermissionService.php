<?php

namespace App\Services;

use App\Models\CallPermission;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CallPermissionService
{
    /**
     * Countries where Meta restricts business-initiated calls.
     * Per Meta Calling API docs: US, Canada, Egypt, Vietnam, Nigeria.
     */
    protected const RESTRICTED_COUNTRIES = ['US', 'CA', 'EG', 'VN', 'NG'];

    /**
     * Minimum messaging tier required for outbound calling
     */
    protected const MINIMUM_TIER = 2000;

    /**
     * Validate if a permission request can be made
     *
     * @throws \Exception
     */
    public function validatePermissionRequest(Contact $contact, Team $team): array
    {
        // 1. Check tier eligibility
        if (! $this->checkTierEligibility($team)) {
            return [
                'allowed' => false,
                'reason' => 'tier_requirement',
                'message' => 'Your messaging tier must be 2000 or higher to initiate outbound calls.',
            ];
        }

        // 2. Check geographic restrictions
        if (! $this->validateGeographicRestrictions($contact)) {
            return [
                'allowed' => false,
                'reason' => 'geographic_restriction',
                'message' => 'Business-initiated calls are not allowed in this country.',
            ];
        }

        // 3. Check if there's an existing permission
        $existingPermission = CallPermission::where('contact_id', $contact->id)
            ->where('team_id', $team->id)
            ->latest()
            ->first();

        if ($existingPermission) {
            // Check if within calling window
            if ($existingPermission->isWithinCallingWindow()) {
                return [
                    'allowed' => false,
                    'reason' => 'already_granted',
                    'message' => 'Permission already granted. You can initiate a call.',
                    'permission' => $existingPermission,
                ];
            }

            // Check rate limits
            if (! $existingPermission->canRequestPermission()) {
                return [
                    'allowed' => false,
                    'reason' => 'rate_limit',
                    'message' => 'Permission request limit exceeded. Max 1 request per 24 hours, 2 per 7 days.',
                ];
            }
        }

        // 4. Check if there's an active conversation
        if (! $this->hasActiveConversation($contact, $team)) {
            return [
                'allowed' => false,
                'reason' => 'no_active_conversation',
                'message' => 'An active conversation is required to request call permission.',
            ];
        }

        return [
            'allowed' => true,
            'existing_permission' => $existingPermission,
        ];
    }

    /**
     * Track a permission request
     */
    public function trackPermissionRequest(Contact $contact, Team $team, string $phoneNumberId): CallPermission
    {
        $permission = CallPermission::where('contact_id', $contact->id)
            ->where('team_id', $team->id)
            ->where('phone_number_id', $phoneNumberId)
            ->latest()
            ->first();

        if (! $permission) {
            $permission = CallPermission::create([
                'team_id' => $team->id,
                'phone_number_id' => $phoneNumberId,
                'contact_id' => $contact->id,
                'permission_status' => 'requested',
                'permission_requested_at' => now(),
                'requests_in_24h' => 1,
                'requests_in_7d' => 1,
                'first_request_in_24h' => now(),
                'first_request_in_7d' => now(),
            ]);
        } else {
            $permission->trackRequest();
        }

        Log::info('Call permission requested', [
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'phone_number_id' => $phoneNumberId,
        ]);

        return $permission;
    }

    /**
     * Validate calling window (Meta temporary permission: 7 days / 168h)
     */
    public function validateCallingWindow(CallPermission $permission): bool
    {
        return $permission->isWithinCallingWindow();
    }

    /**
     * Check tier eligibility (≥2000 messages)
     */
    public function checkTierEligibility(Team $team): bool
    {
        $messagingTier = $team->whatsapp_settings['messaging_tier'] ?? 0;

        return $messagingTier >= self::MINIMUM_TIER;
    }

    /**
     * Validate geographic restrictions
     */
    public function validateGeographicRestrictions(Contact $contact): bool
    {
        $countryCode = $this->extractCountryCode($contact->phone_number ?? '');

        if (! $countryCode) {
            return true; // Allow if country code cannot be determined
        }

        return ! in_array($countryCode, self::RESTRICTED_COUNTRIES);
    }

    /**
     * Check if there's an active conversation
     */
    protected function hasActiveConversation(Contact $contact, Team $team): bool
    {
        // Check for recent messages (within 24 hours)
        $recentMessage = Message::where('contact_id', $contact->id)
            ->where('team_id', $team->id)
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($recentMessage) {
            return true;
        }

        // Check for active conversation
        $activeConversation = Conversation::where('contact_id', $contact->id)
            ->where('team_id', $team->id)
            ->where('status', 'open')
            ->exists();

        return $activeConversation;
    }

    /**
     * Extract country code from phone number
     */
    protected function extractCountryCode(string $phone): ?string
    {
        // Remove + and spaces
        $phone = str_replace(['+', ' ', '-'], '', $phone);

        // Dialing codes for the restricted markets. Check longer codes first so
        // e.g. Nigeria (234) isn't shadowed by a shorter prefix.
        $countryCodeMap = [
            '234' => 'NG', // Nigeria
            '20' => 'EG',  // Egypt
            '84' => 'VN',  // Vietnam
            '1' => 'US',   // US/Canada (both restricted)
        ];

        foreach ($countryCodeMap as $code => $country) {
            if (str_starts_with($phone, $code)) {
                return $country;
            }
        }

        return null;
    }

    /**
     * Grant permission (called when user accepts).
     * Pass Meta's expiration_timestamp / is_permanent from the
     * call_permission_reply webhook when available.
     */
    public function grantPermission(CallPermission $permission, ?Carbon $expiresAt = null, bool $isPermanent = false): void
    {
        $permission->grantPermission($expiresAt, $isPermanent);

        Log::info('Call permission granted', [
            'permission_id' => $permission->id,
            'expires_at' => $permission->permission_expires_at,
            'permanent' => $isPermanent,
        ]);
    }

    /**
     * Record a successful call
     */
    public function recordCall(CallPermission $permission): void
    {
        $permission->recordCall();

        Log::info('Call recorded', [
            'permission_id' => $permission->id,
            'total_calls' => $permission->calls_made_count,
        ]);
    }
}
