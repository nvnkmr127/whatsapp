<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Contact;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CallConsentService
{
    protected $team;

    public function __construct(Team $team)
    {
        $this->team = $team;
    }

    /**
     * Validate if a call can be initiated based on trigger and consent rules.
     */
    public function validateCallTrigger(Contact $contact, string $triggerType, array $context = []): array
    {
        $checks = [];

        // 1. Check trigger type validity
        $checks['trigger_valid'] = $this->validateTriggerType($triggerType, $context);
        if (!$checks['trigger_valid']['passed']) {
            return $this->buildBlockedResponse('trigger', $checks);
        }

        // 2. Check consent based on trigger type
        if ($triggerType === 'user_initiated') {
            $checks['consent'] = $this->validateUserInitiatedConsent($contact, $context);
        } else {
            $checks['consent'] = $this->validateAgentOfferedConsent($contact, $context);
        }

        if (!$checks['consent']['passed']) {
            return $this->buildBlockedResponse('consent', $checks);
        }

        // 3. Check conversation context
        $checks['context'] = $this->validateConversationContext($contact);
        if (!$checks['context']['passed']) {
            return $this->buildBlockedResponse('context', $checks);
        }

        // 4. Check 24-hour window
        $checks['window'] = $this->check24HourWindow($contact);

        // 5. Check automation state
        $checks['automation'] = $this->checkAutomationState($contact, $context);
        if (!$checks['automation']['passed']) {
            return $this->buildBlockedResponse('automation', $checks);
        }

        // All checks passed
        return [
            'allowed' => true,
            'blocked' => false,
            'block_reason' => null,
            'consent_type' => $checks['consent']['consent_type'] ?? 'implicit',
            'checks' => $checks,
        ];
    }

    /**
     * Validate trigger type.
     */
    protected function validateTriggerType(string $triggerType, array $context): array
    {
        $validTriggers = ['user_initiated', 'agent_offered'];

        if (!in_array($triggerType, $validTriggers)) {
            return [
                'passed' => false,
                'block_code' => 'INVALID_TRIGGER_TYPE',
                'details' => [
                    'trigger_type' => $triggerType,
                    'valid_triggers' => $validTriggers,
                ],
            ];
        }

        // Validate trigger source for user-initiated
        if ($triggerType === 'user_initiated') {
            $validSources = ['message_keyword', 'button_click', 'flow_completion', 'in_app_action'];
            $source = $context['trigger_source'] ?? null;

            if (!$source || !in_array($source, $validSources)) {
                return [
                    'passed' => false,
                    'block_code' => 'INVALID_TRIGGER_SOURCE',
                    'details' => [
                        'trigger_source' => $source,
                        'valid_sources' => $validSources,
                    ],
                ];
            }

            // If source is message_keyword, we need to ensure the message actually contains keywords
            if ($source === 'message_keyword') {
                $message = $context['trigger_message'] ?? '';
                if (!static::detectCallRequest($message)) {
                    return [
                        'passed' => false,
                        'block_code' => 'NO_CALL_KEYWORD_DETECTED',
                        'details' => [
                            'message' => $message,
                            'reason' => 'No call request keywords found in message',
                        ],
                    ];
                }
            }
        }

        return [
            'passed' => true,
            'block_code' => null,
            'details' => [
                'trigger_type' => $triggerType,
                'trigger_source' => $context['trigger_source'] ?? null,
            ],
        ];
    }

    /**
     * Validate consent for user-initiated calls.
     */
    protected function validateUserInitiatedConsent(Contact $contact, array $context): array
    {
        // User-initiated calls have implicit consent through the action

        // Check opt-in status
        if ($contact->opt_in_status !== 'opted_in') {
            return [
                'passed' => false,
                'block_code' => 'CONTACT_OPTED_OUT',
                'consent_type' => null,
                'details' => [
                    'opt_in_status' => $contact->opt_in_status,
                    'reason' => 'Contact has opted out of communications',
                ],
            ];
        }

        // Check for explicit "don't call" preference
        if ($contact->custom_attributes['calling_declined'] ?? false) {
            return [
                'passed' => false,
                'block_code' => 'CALLING_EXPLICITLY_DECLINED',
                'consent_type' => null,
                'details' => [
                    'reason' => 'Contact previously declined calling',
                ],
            ];
        }

        return [
            'passed' => true,
            'block_code' => null,
            'consent_type' => 'implicit',
            'details' => [
                'trigger_message' => $context['trigger_message'] ?? null,
                'implicit_consent' => true,
                'opt_in_status' => 'opted_in',
            ],
        ];
    }

    /**
     * Validate consent for agent-offered calls.
     */
    protected function validateAgentOfferedConsent(Contact $contact, array $context): array
    {
        // Agent-offered calls require explicit consent

        // Check if offer was sent
        $offerSentAt = $context['offer_sent_at'] ?? null;
        if (!$offerSentAt) {
            return [
                'passed' => false,
                'block_code' => 'NO_OFFER_SENT',
                'consent_type' => null,
                'details' => [
                    'reason' => 'Agent must send call offer first',
                ],
            ];
        }

        // Check if user responded affirmatively
        $userResponse = $context['user_response'] ?? null;
        if (!$userResponse || !static::isAffirmative($userResponse)) {
            return [
                'passed' => false,
                'block_code' => 'NO_EXPLICIT_CONSENT',
                'consent_type' => null,
                'details' => [
                    'user_response' => $userResponse,
                    'reason' => 'User did not provide explicit affirmative consent',
                ],
            ];
        }

        // Check consent validity (1 hour)
        $userResponseAt = Carbon::parse($context['user_response_at'] ?? now());
        $consentValidUntil = $userResponseAt->addHour();

        if (now()->isAfter($consentValidUntil)) {
            return [
                'passed' => false,
                'block_code' => 'CONSENT_EXPIRED',
                'consent_type' => 'explicit',
                'details' => [
                    'consent_given_at' => $userResponseAt,
                    'consent_expired_at' => $consentValidUntil,
                    'reason' => 'Consent expired (valid for 1 hour after user response)',
                ],
            ];
        }

        return [
            'passed' => true,
            'block_code' => null,
            'consent_type' => 'explicit',
            'details' => [
                'offer_sent_at' => $offerSentAt,
                'user_response' => $userResponse,
                'user_response_at' => $userResponseAt,
                'consent_valid_until' => $consentValidUntil,
            ],
        ];
    }

    /**
     * Validate conversation context.
     */
    protected function validateConversationContext(Contact $contact): array
    {
        $conversation = $contact->conversations()
            ->where('team_id', $this->team->id)
            ->latest()
            ->first();

        if (!$conversation) {
            return [
                'passed' => false,
                'block_code' => 'NO_CONVERSATION_HISTORY',
                'details' => [
                    'reason' => 'No conversation history with this contact',
                ],
            ];
        }

        // Check if conversation is closed
        if ($conversation->status === 'closed') {
            return [
                'passed' => false,
                'block_code' => 'CONVERSATION_CLOSED',
                'details' => [
                    'conversation_id' => $conversation->id,
                    'status' => 'closed',
                    'reason' => 'Conversation is closed',
                ],
            ];
        }

        // Check if user has sent at least one message
        $hasInboundMessage = $conversation->messages()
            ->where('direction', 'inbound')
            ->exists();

        if (!$hasInboundMessage) {
            return [
                'passed' => false,
                'block_code' => 'NO_USER_ENGAGEMENT',
                'details' => [
                    'reason' => 'User has not sent any messages',
                ],
            ];
        }

        // Check if conversation is active (last message < 5 min = highly active)
        $lastMessageAt = $conversation->last_message_at;
        $isHighlyActive = $lastMessageAt && $lastMessageAt >= now()->subMinutes(5);

        return [
            'passed' => true,
            'block_code' => null,
            'details' => [
                'conversation_id' => $conversation->id,
                'status' => $conversation->status,
                'last_message_at' => $lastMessageAt,
                'is_highly_active' => $isHighlyActive,
                'has_user_engagement' => true,
            ],
        ];
    }

    /**
     * Check 24-hour service window.
     */
    protected function check24HourWindow(Contact $contact): array
    {
        $conversation = $contact->conversations()
            ->where('team_id', $this->team->id)
            ->latest()
            ->first();

        if (!$conversation) {
            return [
                'passed' => false,
                'within_window' => false,
                'details' => [
                    'reason' => 'No conversation found',
                ],
            ];
        }

        $lastInboundMessage = $conversation->messages()
            ->where('direction', 'inbound')
            ->latest()
            ->first();

        if (!$lastInboundMessage) {
            return [
                'passed' => false,
                'within_window' => false,
                'details' => [
                    'reason' => 'No inbound messages found',
                ],
            ];
        }

        $withinWindow = $lastInboundMessage->created_at >= now()->subHours(24);
        $hoursRemaining = $withinWindow
            ? round(24 - now()->diffInHours($lastInboundMessage->created_at), 1)
            : 0;

        return [
            'passed' => $withinWindow,
            'within_window' => $withinWindow,
            'details' => [
                'last_inbound_message_at' => $lastInboundMessage->created_at,
                'hours_since_last_message' => now()->diffInHours($lastInboundMessage->created_at),
                'hours_remaining' => $hoursRemaining,
                'window_closes_at' => $lastInboundMessage->created_at->addHours(24),
            ],
        ];
    }

    /**
     * Check automation state.
     */
    protected function checkAutomationState(Contact $contact, array $context): array
    {
        $automationId = $context['automation_id'] ?? null;

        if (!$automationId) {
            return [
                'passed' => true,
                'automation_active' => false,
                'details' => [
                    'no_automation' => true,
                ],
            ];
        }

        // Check if automation allows calling
        $automationType = $context['automation_type'] ?? null;
        $allowedTypes = ['support_flow', 'callback_request', 'escalation'];

        if (!in_array($automationType, $allowedTypes)) {
            return [
                'passed' => false,
                'automation_active' => true,
                'block_code' => 'AUTOMATION_BLOCKS_CALLING',
                'details' => [
                    'automation_id' => $automationId,
                    'automation_type' => $automationType,
                    'reason' => 'This automation type does not allow calling',
                ],
            ];
        }

        return [
            'passed' => true,
            'automation_active' => true,
            'details' => [
                'automation_id' => $automationId,
                'automation_type' => $automationType,
                'automation_allows_calling' => true,
            ],
        ];
    }

    /**
     * Log consent for audit trail.
     */
    public function logConsent(Contact $contact, string $triggerType, array $context, array $validationResult): void
    {
        $consentType = $validationResult['consent_type'] ?? 'unknown';
        $consentDetails = $validationResult['checks']['consent']['details'] ?? [];
        $windowDetails = $validationResult['checks']['window']['details'] ?? [];
        $contextDetails = $validationResult['checks']['context']['details'] ?? [];

        try {
            DB::table('calling_consent_log')->insert([
                'team_id' => $this->team->id,
                'contact_id' => $contact->id,
                'consent_type' => $consentType,
                'consent_signal' => $context['trigger_source'] ?? $triggerType,
                'consent_message' => $context['trigger_message'] ?? ($context['user_response'] ?? null),
                'consent_given_at' => now(),
                'consent_expires_at' => $consentDetails['consent_valid_until'] ?? now()->addHours(24),
                'trigger_type' => $triggerType,
                'conversation_id' => $context['conversation_id'] ?? ($contextDetails['conversation_id'] ?? null),
                'automation_id' => $context['automation_id'] ?? null,
                'agent_id' => $context['agent_id'] ?? auth()->id(),
                'within_24h_window' => $validationResult['checks']['window']['within_window'] ?? false,
                'active_chat' => $contextDetails['is_highly_active'] ?? false,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("Call consent logged", [
                'team_id' => $this->team->id,
                'contact_id' => $contact->id,
                'consent_type' => $consentType,
                'trigger_type' => $triggerType,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log call consent: " . $e->getMessage(), [
                'team_id' => $this->team->id,
                'contact_id' => $contact->id,
            ]);
        }
    }

    /**
     * Build blocked response.
     */
    protected function buildBlockedResponse(string $category, array $checks): array
    {
        $failedCheck = $checks[array_key_last($checks)];
        $blockCode = $failedCheck['block_code'];

        $messages = $this->getBlockMessages($blockCode);

        return [
            'allowed' => false,
            'blocked' => true,
            'block_reason' => $blockCode,
            'block_category' => $category,
            'user_message' => $messages['user'],
            'admin_message' => $messages['admin'],
            'checks' => $checks,
        ];
    }

    /**
     * Get user-friendly block messages.
     */
    protected function getBlockMessages(string $blockCode): array
    {
        $messages = [
            'INVALID_TRIGGER_TYPE' => [
                'user' => 'Invalid call request.',
                'admin' => 'Invalid trigger type provided.',
            ],
            'INVALID_TRIGGER_SOURCE' => [
                'user' => 'Invalid call request source.',
                'admin' => 'Trigger source is not valid for user-initiated calls.',
            ],
            'CONTACT_OPTED_OUT' => [
                'user' => 'This contact has opted out of communications.',
                'admin' => 'Contact opt-in status is not "opted_in".',
            ],
            'CALLING_EXPLICITLY_DECLINED' => [
                'user' => 'This contact has declined calling.',
                'admin' => 'Contact previously declined calling feature.',
            ],
            'NO_OFFER_SENT' => [
                'user' => 'Call offer must be sent first.',
                'admin' => 'Agent must send call offer before initiating call.',
            ],
            'NO_EXPLICIT_CONSENT' => [
                'user' => 'User did not consent to the call.',
                'admin' => 'User did not provide explicit consent for agent-offered call.',
            ],
            'CONSENT_EXPIRED' => [
                'user' => 'Call consent has expired. Please request again.',
                'admin' => 'Consent expired (valid for 1 hour after user acceptance).',
            ],
            'NO_CONVERSATION_HISTORY' => [
                'user' => 'No conversation history with this contact.',
                'admin' => 'Cannot call contact without prior conversation.',
            ],
            'CONVERSATION_CLOSED' => [
                'user' => 'Conversation is closed. Start a new conversation first.',
                'admin' => 'Conversation status is closed.',
            ],
            'NO_USER_ENGAGEMENT' => [
                'user' => 'User has not engaged in conversation.',
                'admin' => 'User has not sent any messages in the conversation.',
            ],
            'AUTOMATION_BLOCKS_CALLING' => [
                'user' => 'Calling is not available in this automation.',
                'admin' => 'Automation type does not allow calling.',
            ],
        ];

        return $messages[$blockCode] ?? [
            'user' => 'Calling is currently unavailable.',
            'admin' => 'Unknown block reason: ' . $blockCode,
        ];
    }

    /**
     * Detect call request keywords in message.
     */
    public static function detectCallRequest(string $message): bool
    {
        $keywords = [
            'call me',
            'please call',
            'can you call',
            'i need a call',
            'give me a call',
            'phone me',
            'ring me',
            'call now',
            'want a call',
            'request call',
        ];

        $messageLower = strtolower($message);

        foreach ($keywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user response is affirmative.
     */
    public static function isAffirmative(string $message): bool
    {
        $affirmatives = ['yes', 'sure', 'ok', 'okay', 'go ahead', 'call me', 'definitely', 'yep', 'yup', 'yeah'];
        $messageLower = trim(strtolower($message));

        foreach ($affirmatives as $affirmative) {
            if ($messageLower === $affirmative || str_starts_with($messageLower, $affirmative . ' ') || str_ends_with($messageLower, ' ' . $affirmative)) {
                return true;
            }
        }

        return false;
    }
}
