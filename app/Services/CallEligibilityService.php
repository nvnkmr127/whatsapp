<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Contact;
use App\Services\CallConsentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CallEligibilityService
{
    protected $team;

    public function __construct(Team $team)
    {
        $this->team = $team;
    }

    /**
     * Comprehensive eligibility check for making a call.
     */
    public function checkEligibility(Contact $contact, string $triggerType = 'user_initiated', array $context = []): array
    {
        $checks = [];

        // 0. Validate trigger and consent (NEW)
        $consentService = new CallConsentService($this->team);
        $consentCheck = $consentService->validateCallTrigger($contact, $triggerType, $context);

        if (!$consentCheck['allowed']) {
            return $consentCheck; // Return consent block immediately
        }

        $checks['trigger_consent'] = $consentCheck['checks'];

        // 1. Phone Number Readiness (Critical)
        $checks['phone_readiness'] = $this->checkPhoneReadiness();
        if (!$checks['phone_readiness']['passed']) {
            return $this->buildBlockedResponse('phone_readiness', $checks);
        }

        // 2. Quality Rating (Critical)
        $checks['quality_rating'] = $this->checkQualityRating();
        if (!$checks['quality_rating']['passed']) {
            return $this->buildBlockedResponse('quality', $checks);
        }

        // 3. Consent & Opt-In (Legal) - Already checked above
        $checks['consent'] = $this->checkConsent($contact);
        if (!$checks['consent']['passed']) {
            return $this->buildBlockedResponse('consent', $checks);
        }

        // 4. Agent Availability (Operational)
        $checks['agent_availability'] = $this->checkAgentAvailability();
        if (!$checks['agent_availability']['passed']) {
            return $this->buildBlockedResponse('agent', $checks);
        }

        // 5. Safeguards (Reliability)
        $checks['safeguards'] = $this->checkSafeguards();
        if (!$checks['safeguards']['passed']) {
            return $this->buildBlockedResponse('safeguards', $checks);
        }

        // 6. Usage Limits (Billing)
        $checks['usage_limits'] = $this->checkUsageLimits();
        if (!$checks['usage_limits']['passed']) {
            return $this->buildBlockedResponse('limits', $checks);
        }

        // Log consent for audit trail
        $consentService->logConsent($contact, $triggerType, $context, $consentCheck);

        // All checks passed
        return [
            'eligible' => true,
            'blocked' => false,
            'block_reason' => null,
            'block_category' => null,
            'user_message' => 'Ready to call',
            'admin_message' => null,
            'can_retry_at' => null,
            'consent_type' => $consentCheck['consent_type'] ?? 'implicit',
            'checks' => $checks,
        ];
    }

    /**
     * Check if phone number is ready for calling.
     */
    protected function checkPhoneReadiness(): array
    {
        // Cache for 1 hour (rarely changes)
        return Cache::remember("phone_readiness_{$this->team->id}", 3600, function () {
            $phoneNumberId = $this->team->phone_number_id;

            // Check if calling is enabled for team
            if (!$this->team->calling_enabled) {
                return [
                    'passed' => false,
                    'block_code' => 'CALLING_NOT_ENABLED_FOR_NUMBER',
                    'details' => [
                        'verified' => true,
                        'status' => 'CONNECTED',
                        'calling_enabled' => false,
                        'reason' => 'Calling feature not enabled for this account',
                    ],
                ];
            }

            // Check phone number status (would integrate with Meta API)
            $phoneStatus = $this->getPhoneNumberStatus($phoneNumberId);

            if ($phoneStatus['status'] !== 'CONNECTED') {
                return [
                    'passed' => false,
                    'block_code' => 'PHONE_NUMBER_INACTIVE',
                    'details' => $phoneStatus,
                ];
            }

            return [
                'passed' => true,
                'block_code' => null,
                'details' => [
                    'verified' => true,
                    'status' => 'CONNECTED',
                    'calling_enabled' => true,
                    'display_name_approved' => true,
                ],
            ];
        });
    }

    /**
     * Check quality rating from Meta.
     */
    protected function checkQualityRating(): array
    {
        // Cache for 15 minutes (updated periodically)
        return Cache::remember("quality_rating_{$this->team->id}", 900, function () {
            // Get quality metrics (would integrate with Meta API)
            $qualityData = $this->getQualityMetrics();

            $rating = $qualityData['rating'] ?? 'GREEN';
            $blockRate = $qualityData['block_rate'] ?? 0;
            $reportRate = $qualityData['report_rate'] ?? 0;

            // Block if rating is RED or FLAGGED
            if (in_array($rating, ['RED', 'FLAGGED'])) {
                return [
                    'passed' => false,
                    'block_code' => $rating === 'FLAGGED' ? 'ACCOUNT_FLAGGED_BY_META' : 'QUALITY_RATING_TOO_LOW',
                    'details' => [
                        'rating' => $rating,
                        'block_rate' => $blockRate,
                        'report_rate' => $reportRate,
                        'reason' => 'Quality rating below acceptable threshold',
                    ],
                ];
            }

            // Warn if YELLOW
            $warning = $rating === 'YELLOW' ? 'Quality rating is medium. Monitor closely.' : null;

            return [
                'passed' => true,
                'block_code' => null,
                'warning' => $warning,
                'details' => [
                    'rating' => $rating,
                    'block_rate' => $blockRate,
                    'report_rate' => $reportRate,
                ],
            ];
        });
    }

    /**
     * Check if contact has given consent for calls.
     */
    protected function checkConsent(Contact $contact): array
    {
        // Check opt-in status
        if ($contact->opt_in_status !== 'opted_in') {
            return [
                'passed' => false,
                'block_code' => 'CONTACT_OPTED_OUT',
                'details' => [
                    'has_consent' => false,
                    'opt_in_status' => $contact->opt_in_status,
                    'reason' => 'Contact has opted out of communications',
                ],
            ];
        }

        // Check for explicit calling consent (custom field)
        $callingConsent = $contact->custom_attributes['calling_consent'] ?? null;

        if (!$callingConsent) {
            return [
                'passed' => false,
                'block_code' => 'NO_CALLING_CONSENT',
                'details' => [
                    'has_consent' => false,
                    'opt_in_status' => 'opted_in',
                    'calling_consent' => false,
                    'reason' => 'No explicit consent for calling',
                ],
            ];
        }

        // Check consent expiration (if applicable)
        $consentDate = $contact->custom_attributes['calling_consent_date'] ?? null;
        if ($consentDate) {
            $consentAge = now()->diffInMonths($consentDate);
            if ($consentAge > 12) {
                return [
                    'passed' => false,
                    'block_code' => 'CONSENT_EXPIRED',
                    'details' => [
                        'has_consent' => true,
                        'consent_date' => $consentDate,
                        'consent_age_months' => $consentAge,
                        'reason' => 'Consent expired (>12 months old)',
                    ],
                ];
            }
        }

        return [
            'passed' => true,
            'block_code' => null,
            'details' => [
                'has_consent' => true,
                'consent_date' => $consentDate,
                'opt_in_status' => 'opted_in',
                'calling_consent' => true,
            ],
        ];
    }

    /**
     * Check if agents are available to handle the call.
     */
    protected function checkAgentAvailability(): array
    {
        // Get online agents
        $availableAgents = $this->team->users()
            ->where('is_online', true)
            ->where('status', 'available')
            ->count();

        $onlineAgents = $this->team->users()
            ->where('is_online', true)
            ->count();

        if ($availableAgents === 0) {
            return [
                'passed' => false,
                'block_code' => $onlineAgents > 0 ? 'ALL_AGENTS_BUSY' : 'NO_AGENTS_AVAILABLE',
                'can_retry_at' => now()->addMinutes(5),
                'details' => [
                    'available_agents' => 0,
                    'online_agents' => $onlineAgents,
                    'reason' => $onlineAgents > 0 ? 'All agents are currently busy' : 'No agents are online',
                ],
            ];
        }

        return [
            'passed' => true,
            'block_code' => null,
            'details' => [
                'available_agents' => $availableAgents,
                'online_agents' => $onlineAgents,
                'queue_size' => 0,
            ],
        ];
    }

    /**
     * Check usage limits (monthly minutes).
     */
    protected function checkUsageLimits(): array
    {
        if (!$this->team->max_call_minutes_per_month) {
            return [
                'passed' => true,
                'block_code' => null,
                'details' => [
                    'has_limit' => false,
                    'unlimited' => true,
                ],
            ];
        }

        $currentMonth = now()->format('Y-m');
        $minutesUsed = \App\Models\WhatsAppCall::where('team_id', $this->team->id)
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])
            ->sum('duration_seconds') / 60;

        $limit = $this->team->max_call_minutes_per_month;

        if ($minutesUsed >= $limit) {
            return [
                'passed' => false,
                'block_code' => 'MONTHLY_LIMIT_REACHED',
                'can_retry_at' => now()->addMonth()->startOfMonth(),
                'details' => [
                    'has_limit' => true,
                    'minutes_used' => round($minutesUsed, 2),
                    'minutes_limit' => $limit,
                    'minutes_remaining' => 0,
                    'reason' => 'Monthly call limit has been reached',
                ],
            ];
        }

        return [
            'passed' => true,
            'block_code' => null,
            'details' => [
                'has_limit' => true,
                'minutes_used' => round($minutesUsed, 2),
                'minutes_limit' => $limit,
                'minutes_remaining' => round($limit - $minutesUsed, 2),
            ],
        ];
    }

    /**
     * Check calling safeguards (rate limits, suspension).
     */
    protected function checkSafeguards(): array
    {
        $safeguardService = new CallSafeguardService();
        $result = $safeguardService->evaluateOutboundEligibility($this->team);

        if (!$result['allowed']) {
            return [
                'passed' => false,
                'block_code' => $result['reason'],
                'can_retry_at' => $result['retry_after'] ?? null,
                'details' => $result,
            ];
        }

        return [
            'passed' => true,
            'block_code' => null,
        ];
    }

    /**
     * Build blocked response with user-friendly messages.
     */
    protected function buildBlockedResponse(string $category, array $checks): array
    {
        $failedCheck = $checks[array_key_last($checks)];
        $blockCode = $failedCheck['block_code'];

        $messages = $this->getBlockMessages($blockCode);

        return [
            'eligible' => false,
            'blocked' => true,
            'block_reason' => $blockCode,
            'block_category' => $category,
            'user_message' => $messages['user'],
            'admin_message' => $messages['admin'],
            'can_retry_at' => $failedCheck['can_retry_at'] ?? null,
            'checks' => $checks,
        ];
    }

    /**
     * Get user-friendly block messages.
     */
    protected function getBlockMessages(string $blockCode): array
    {
        $messages = [
            'CALLING_NOT_ENABLED_FOR_NUMBER' => [
                'user' => 'Calling is not enabled for your account. Please contact support.',
                'admin' => 'Enable calling feature in WhatsApp Business settings or contact Meta support.',
            ],
            'PHONE_NUMBER_INACTIVE' => [
                'user' => 'Your phone number is not active. Please verify your number.',
                'admin' => 'Phone number status is not CONNECTED. Check Meta Business Manager.',
            ],
            'ACCOUNT_FLAGGED_BY_META' => [
                'user' => 'Your account is under review. Calling is temporarily disabled.',
                'admin' => 'Account flagged by Meta. Contact Meta support immediately.',
            ],
            'QUALITY_RATING_TOO_LOW' => [
                'user' => 'Calling is temporarily disabled. We\'re working to improve service quality.',
                'admin' => 'Quality rating is RED. Reduce block/report rates to restore calling.',
            ],
            'CONTACT_OPTED_OUT' => [
                'user' => 'This contact has opted out of calls.',
                'admin' => 'Respect opt-out preference. Do not attempt to call.',
            ],
            'NO_EXPLICIT_CONSENT' => [
                'user' => 'User did not consent to the call.',
                'admin' => 'User did not provide explicit affirmative consent for agent-offered call.',
            ],
            'NO_CALL_KEYWORD_DETECTED' => [
                'user' => 'No call request was detected in your message.',
                'admin' => 'Trigger source was message_keyword but no keywords were found.',
            ],
            'AUTOMATION_BLOCKS_CALLING' => [
                'user' => 'Calling is not available in this automation.',
                'admin' => 'Automation type does not allow calling.',
            ],
            'NO_CALLING_CONSENT' => [
                'user' => 'This contact has not consented to receive calls.',
                'admin' => 'Obtain explicit calling consent before attempting to call.',
            ],
            'CONSENT_EXPIRED' => [
                'user' => 'Calling consent has expired for this contact.',
                'admin' => 'Renew calling consent (consent is >12 months old).',
            ],
            'NO_AGENTS_AVAILABLE' => [
                'user' => 'No agents are currently available. Please try again later.',
                'admin' => 'No agents online. Ensure agents are logged in and available.',
            ],
            'ALL_AGENTS_BUSY' => [
                'user' => 'All agents are currently busy. Estimated wait time: 5 minutes.',
                'admin' => 'All agents at capacity. Consider adding more agents or queue management.',
            ],
            'MONTHLY_LIMIT_REACHED' => [
                'user' => 'Monthly call limit has been reached. Upgrade your plan for more minutes.',
                'admin' => 'Monthly call limit exceeded. Upgrade plan or wait until next month.',
            ],
            'TEAM_CALLING_SUSPENDED' => [
                'user' => 'Calling is temporarily suspended for your team due to high missed call volume.',
                'admin' => 'Calling suspended. Threshold for missed calls hit.',
            ],
            'RATE_LIMIT_MINUTE_EXCEEDED' => [
                'user' => 'Too many calls initiated in a short time. Please wait a minute.',
                'admin' => 'Minutely rate limit hit.',
            ],
            'RATE_LIMIT_HOUR_EXCEEDED' => [
                'user' => 'Hourly call limit reached. Please try again later.',
                'admin' => 'Hourly rate limit hit.',
            ],
        ];

        return $messages[$blockCode] ?? [
            'user' => 'Calling is currently unavailable.',
            'admin' => 'Unknown block reason: ' . $blockCode,
        ];
    }

    /**
     * Get phone number status (mock - would integrate with Meta API).
     */
    protected function getPhoneNumberStatus(string $phoneNumberId): array
    {
        // Mock implementation - replace with actual Meta API call
        return [
            'status' => 'CONNECTED',
            'verified' => true,
            'display_name_approved' => true,
        ];
    }

    /**
     * Get quality metrics (mock - would integrate with Meta API).
     */
    protected function getQualityMetrics(): array
    {
        // Mock implementation - replace with actual Meta API call
        return [
            'rating' => 'GREEN',
            'block_rate' => 1.2,
            'report_rate' => 0.3,
            'delivery_rate' => 98.5,
        ];
    }
}
