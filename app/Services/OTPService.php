<?php

namespace App\Services;

use App\Notifications\OtpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use App\Models\Team;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class OTPService
{
    protected $ttl = 300; // 5 minutes
    protected $maxAttempts = 5;
    protected $maxRequestsPer24h = 10; // "Dead drop" threshold

    protected function persistOtp(string $identifier, string $code, string $type, ?int $teamId = null): void
    {
        Cache::put($this->getCacheKey($identifier), [
            'hash' => Hash::make($code),
            'attempts' => 0,
            'team_id' => $teamId,
            'type' => $type,
        ], $this->ttl);
    }

    /**
     * Send OTP to email or phone.
     */
    public function send(string $identifier, string $type = 'email', ?int $teamId = null): bool
    {
        // Auto-detect if type matches content (safety for mixed inputs)
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $type = 'email';
        } elseif (preg_match('/^\+?[0-9]{10,15}$/', preg_replace('/[^0-9+]/', '', $identifier))) {
            $type = 'phone';
        }

        // Abuse Prevention: Dead drop check
        if ($this->isBlacklisted($identifier)) {
            Log::warning("OTP Request blocked for blacklisted identifier: {$identifier}");
            AuditService::log('Auth.Abuse.Blocked', null, $identifier, $type . '_otp', ['reason' => 'Too many requests in 24h']);
            return false;
        }

        $code = (string) random_int(100000, 999999);

        // Increment total requests in 24h for this identifier
        $this->incrementRequestCount($identifier);

        $sent = false;
        if ($type === 'email') {
            $sent = $this->sendEmail($identifier, $code);
        } elseif ($type === 'phone') {
            $sent = $this->sendWhatsApp($identifier, $code);
        }

        if ($sent) {
            if ($type === 'email') {
                $this->persistOtp($identifier, $code, $type, $teamId);
            }

            // Log for CRM tracking of new leads
            if (!$teamId) {
                $metadata = [
                    'is_new_user' => !\App\Models\User::where($type === 'email' ? 'email' : 'phone', $identifier)->exists()
                ];

                // Capture UTMs from request if available
                $utms = ['utm_source', 'utm_medium', 'utm_campaign'];
                foreach ($utms as $utm) {
                    if (request()->has($utm)) {
                        $metadata[$utm] = request()->query($utm);
                    } elseif (request()->hasCookie($utm)) {
                        $metadata[$utm] = request()->cookie($utm);
                    }
                }

                AuditService::log('auth.otp.requested', null, $identifier, $type, $metadata);
            }

            $webhookService = app(\App\Services\WebhookService::class);
            $eventData = [
                'identifier' => $identifier,
                'type' => $type,
                'is_new_user' => !\App\Models\User::where($type === 'email' ? 'email' : 'phone', $identifier)->exists(),
                'timestamp' => now()->toIso8601String(),
            ];

            // Dispatch system-wide webhook for Login OTP if no teamId
            if (!$teamId) {
                try {
                    $webhookService->dispatch(null, 'auth.otp.login', $eventData);
                } catch (\Exception $e) {
                    Log::error("Failed to dispatch auth.otp.login webhook: " . $e->getMessage());
                }
            }

            // Dispatch general otp.sent event
            try {
                $webhookService->dispatch($teamId, 'otp.sent', $eventData);
            } catch (\Exception $e) {
                Log::error("Failed to dispatch otp.sent webhook: " . $e->getMessage());
            }
        }

        return $sent;
    }

    /**
     * Verify the OTP code with retry protection and logging.
     */
    public function verify(string $identifier, string $code, bool $consume = true): bool
    {
        $data = Cache::get($this->getCacheKey($identifier));

        if (!$data) {
            return false;
        }

        $teamId = $data['team_id'] ?? null;
        $type = $data['type'] ?? 'unknown';

        // Increment attempts
        $data['attempts']++;
        Cache::put($this->getCacheKey($identifier), $data, $this->ttl);

        if ($data['attempts'] > $this->maxAttempts) {
            Cache::forget($this->getCacheKey($identifier));
            Log::warning("OTP brute force attempt detected for: {$identifier}");
            AuditService::log('Auth.Abuse.Flag', null, $identifier, null, ['reason' => 'Max attempts reached']);

            app(\App\Services\WebhookService::class)->dispatch($teamId, 'otp.failed', [
                'identifier' => $identifier,
                'type' => $type,
                'reason' => 'max_attempts_reached',
                'timestamp' => now()->toIso8601String(),
            ]);

            return false;
        }

        if (Hash::check($code, $data['hash'])) {
            if ($consume) {
                Cache::forget($this->getCacheKey($identifier));
                Cache::forget($this->getDailyCountKey($identifier));
            }

            app(\App\Services\WebhookService::class)->dispatch($teamId, 'otp.verified', [
                'identifier' => $identifier,
                'type' => $type,
                'timestamp' => now()->toIso8601String(),
            ]);

            return true;
        }

        // Generic failure (wrong code)
        app(\App\Services\WebhookService::class)->dispatch($teamId, 'otp.failed', [
            'identifier' => $identifier,
            'type' => $type,
            'reason' => 'invalid_code',
            'timestamp' => now()->toIso8601String(),
        ]);

        return false;
    }

    protected function getCacheKey(string $identifier): string
    {
        return 'otp_secure_' . md5($identifier);
    }

    protected function getDailyCountKey(string $identifier): string
    {
        return 'otp_daily_count_' . md5($identifier);
    }

    /**
     * Check if identifier is temporarily blacklisted (SaaS Dead Drop).
     */
    protected function isBlacklisted(string $identifier): bool
    {
        $count = Cache::get($this->getDailyCountKey($identifier), 0);
        return $count >= $this->maxRequestsPer24h;
    }

    protected function incrementRequestCount(string $identifier): void
    {
        $key = $this->getDailyCountKey($identifier);
        $count = Cache::get($key, 0);
        Cache::put($key, $count + 1, 86400); // 24 hours
    }

    protected function sendEmail(string $email, string $code): bool
    {
        try {
            $user = \App\Models\User::where('email', $email)->first();
            $name = $user ? $user->name : explode('@', $email)[0];

            app(\App\Services\Email\CentralEmailService::class)->sendOtp($email, [
                'name' => $name,
                'code' => $code,
                'expiry' => '5 minutes'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send Email OTP to {$email}: " . $e->getMessage());
            return false;
        }
    }

    public function sendWhatsApp(string $phone, string $code): bool
    {
        try {
            $team = $this->findSendingTeam();
            if (!$team) {
                Log::error("No eligible team or system credentials found for sending WhatsApp OTP.");
                return false;
            }

            // Find an available template (priority to AUTHENTICATION category)
            $tpl = $this->findOtpTemplate($team);

            if (!$tpl) {
                Log::error("No valid WhatsApp OTP template found for team {$team->id}. Please ensure a template named 'verification_code' or an AUTHENTICATION category template exists and is synced.");
                return false;
            }

            Log::info("Using WhatsApp template '{$tpl->name}' ({$tpl->language}) for OTP to {$phone}");

            return $this->sendCustomWhatsAppOtp($phone, $code, $tpl->name, $tpl->language, [$code], $team);
        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp OTP to {$phone}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Finds a team or system-level configuration to send WhatsApp messages.
     */
    protected function findSendingTeam(): ?Team
    {
        $systemToken = env('WHATSAPP_SYSTEM_ACCESS_TOKEN');
        $systemPhoneId = env('WHATSAPP_SYSTEM_PHONE_NUMBER_ID');

        if ($systemToken && $systemPhoneId) {
            // Find the best real DB team that has approved OTP templates so that
            // template lookups, contact creation, and billing all use a valid team_id.
            // Then override only the WhatsApp credentials with the system values.
            //
            // Previously this created a synthetic team with id=0, which caused:
            //  - sendTemplate() querying WhatsappTemplate::where('team_id', 0) → 0 rows → failure
            //  - getOrCreateContact() creating orphaned contacts with team_id=0
            //  - BillingService creating an orphaned TeamWallet(team_id=0)
            //  - verifyReadyToSend() calling $team->save() attempting to INSERT team id=0
            $realTeam = Team::whereNotNull('whatsapp_access_token')
                ->where('whatsapp_access_token', '!=', '')
                ->whereHas('whatsappTemplates', function ($q) {
                    $q->where('status', 'APPROVED')
                      ->where(function ($q2) {
                          $q2->where('category', 'AUTHENTICATION')
                             ->orWhere('name', 'like', '%otp%')
                             ->orWhere('name', 'like', '%verification%')
                             ->orWhere('name', 'like', '%code%');
                      });
                })
                ->first();

            // Fallback: any team with any approved template
            if (!$realTeam) {
                $realTeam = Team::whereNotNull('whatsapp_access_token')
                    ->where('whatsapp_access_token', '!=', '')
                    ->whereHas('whatsappTemplates', function ($q) {
                        $q->where('status', 'APPROVED');
                    })
                    ->first();
            }

            if ($realTeam) {
                // Override only the sending credentials with system values.
                // The real team_id is preserved so template/contact/billing resolve correctly.
                $realTeam->whatsapp_access_token = $systemToken;
                $realTeam->whatsapp_phone_number_id = $systemPhoneId;
                if (env('WHATSAPP_SYSTEM_WABA_ID')) {
                    $realTeam->whatsapp_business_account_id = env('WHATSAPP_SYSTEM_WABA_ID');
                }
                $realTeam->whatsapp_setup_state = \App\Enums\IntegrationState::READY;
                return $realTeam;
            }
            // System credentials set but no DB team has templates — fall through
        }

        // 1. Prioritize teams with a token AND at least one approved OTP-style template
        $activeTeam = Team::whereNotNull('whatsapp_access_token')
            ->where('whatsapp_access_token', '!=', '')
            ->whereNotNull('whatsapp_phone_number_id')
            ->whereHas('whatsappTemplates', function ($q) {
                $q->where('status', 'APPROVED');
            })
            ->get()
            ->first(fn(Team $team) => $team->canAccess('send_message'));

        if ($activeTeam) {
            return $activeTeam;
        }

        // 2. Fallback to any team with a token
        return Team::whereNotNull('whatsapp_access_token')
            ->where('whatsapp_access_token', '!=', '')
            ->whereNotNull('whatsapp_phone_number_id')
            ->get()
            ->first(fn(Team $team) => $team->canAccess('send_message'));
    }

    /**
     * Finds the best available OTP template for a team.
     */
    protected function findOtpTemplate(Team $team)
    {
        // 1. Look for explicit AUTHENTICATION templates
        $tpl = \App\Models\WhatsappTemplate::where('team_id', $team->id)
            ->where('category', 'AUTHENTICATION')
            ->where('status', 'APPROVED')
            ->first();

        if ($tpl)
            return $tpl;

        // 2. Look for templates with "otp" or "verification" in the name
        $tpl = \App\Models\WhatsappTemplate::where('team_id', $team->id)
            ->where('status', 'APPROVED')
            ->where(function ($q) {
                $q->where('name', 'like', '%otp%')
                    ->orWhere('name', 'like', '%verification%')
                    ->orWhere('name', 'like', '%code%');
            })
            ->orderByRaw("FIELD(name, 'verification_code', 'otp', 'verification', 'code') DESC")
            ->first();

        if ($tpl)
            return $tpl;

        // 3. Last resort: Any approved UTILITY template (might be dangerous, but better than failing)
        return \App\Models\WhatsappTemplate::where('team_id', $team->id)
            ->where('category', 'UTILITY')
            ->where('status', 'APPROVED')
            ->first();
    }

    /**
     * Send OTP using a custom WhatsApp template.
     */
    public function sendCustomWhatsAppOtp(
        string $phone,
        string $code,
        string $templateName,
        string $language,
        array $parameters,
        Team $team,
        int $otpPosition = 0
    ): bool {
        try {
            $whatsappService = new WhatsAppService($team);

            // Replace the specific position with the OTP code
            if (isset($parameters[$otpPosition])) {
                $parameters[$otpPosition] = $code;
            }

            $response = $whatsappService->sendTemplate(
                $phone,
                $templateName,
                $language,
                $parameters
            );

            if ($response['success'] ?? false) {
                $this->persistOtp($phone, $code, 'phone', $team->id);

                // Dispatch general otp.sent event
                try {
                    app(\App\Services\WebhookService::class)->dispatch($team->id, 'otp.sent', [
                        'identifier' => $phone,
                        'type' => 'phone',
                        'template' => $templateName,
                        'timestamp' => now()->toIso8601String(),
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to dispatch otp.sent webhook: " . $e->getMessage());
                }

                return true;
            }

            Log::warning("WhatsApp template send failed for custom OTP: " . json_encode($response));
            return false;
        } catch (\Exception $e) {
            Log::error("Failed to send Custom WhatsApp OTP to {$phone}: " . $e->getMessage());
            return false;
        }
    }
}
