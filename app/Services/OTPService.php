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
    protected $ttl;
    protected $maxAttempts;
    protected $maxRequestsPer24h;

    public function __construct()
    {
        $this->ttl = config('otp.ttl', 300);
        $this->maxAttempts = config('otp.max_attempts', 5);
        $this->maxRequestsPer24h = config('otp.daily_request_limit_per_user', 10);
    }

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

            $credentialSource = $this->getOtpCredentialSource($team);

            // Find an available template (priority to AUTHENTICATION category)
            $tpl = $this->findOtpTemplate($team);

            if (!$tpl) {
                Log::error("No synced WhatsApp OTP template found for team {$team->id}. Sync templates from Meta and ensure WHATSAPP_OTP_TEMPLATE_NAME matches an approved template + language in your WABA.", [
                    'configured_template_name' => config('otp.whatsapp_template_name'),
                    'app_locale' => app()->getLocale(),
                ]);
                return false;
            }

            Log::info("Using WhatsApp template '{$tpl->name}' ({$tpl->language}) for OTP to {$phone}", [
                'team_id' => $team->id,
                'credential_source' => $credentialSource,
                'phone_number_id' => $team->whatsapp_phone_number_id,
                'token_fingerprint' => $this->tokenFingerprint($team->whatsapp_access_token),
            ]);

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
        $systemToken = config('whatsapp.system_access_token');
        $systemPhoneId = config('whatsapp.system_phone_number_id');

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
                if (config('whatsapp.system_waba_id')) {
                    $realTeam->whatsapp_business_account_id = config('whatsapp.system_waba_id');
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
        $configName = config('otp.whatsapp_template_name', 'verification_code');
        $allowUnsyncedFallback = (bool) config('otp.allow_unsynced_template_fallback', false);
        $appLocale = app()->getLocale();

        // Prefer exact locale, then en_US, then en.
        $languageOrderSql = "CASE
            WHEN language = ? THEN 0
            WHEN language = 'en_US' THEN 1
            WHEN language = 'en' THEN 2
            ELSE 3
        END";

        // 1. Look for explicit synced template by name from config.
        $tpl = \App\Models\WhatsappTemplate::where('team_id', $team->id)
            ->where('name', $configName)
            ->where('status', 'APPROVED')
            ->whereNotNull('whatsapp_template_id')
            ->orderByRaw($languageOrderSql, [$appLocale])
            ->first();

        if ($tpl) {
            return $tpl;
        }

        // 2. Look for any synced AUTHENTICATION templates.
        $tpl = \App\Models\WhatsappTemplate::where('team_id', $team->id)
            ->where('category', 'AUTHENTICATION')
            ->where('status', 'APPROVED')
            ->whereNotNull('whatsapp_template_id')
            ->orderByRaw($languageOrderSql, [$appLocale])
            ->first();

        if ($tpl)
            return $tpl;

        // 3. Look for synced templates with "otp"/"verification"/"code" in the name.
        $tpl = \App\Models\WhatsappTemplate::where('team_id', $team->id)
            ->where('status', 'APPROVED')
            ->whereNotNull('whatsapp_template_id')
            ->where(function ($q) {
                $q->where('name', 'like', '%otp%')
                    ->orWhere('name', 'like', '%verification%')
                    ->orWhere('name', 'like', '%code%');
            })
            ->orderByRaw($languageOrderSql, [$appLocale])
            ->first();

        if ($tpl)
            return $tpl;

        // 4. Last resort: Any synced approved UTILITY template.
        $tpl = \App\Models\WhatsappTemplate::where('team_id', $team->id)
            ->where('category', 'UTILITY')
            ->where('status', 'APPROVED')
            ->whereNotNull('whatsapp_template_id')
            ->orderByRaw($languageOrderSql, [$appLocale])
            ->first();

        if ($tpl) {
            return $tpl;
        }

        // Optional, unsafe fallback for legacy environments with seeded placeholders.
        if ($allowUnsyncedFallback) {
            $fallback = \App\Models\WhatsappTemplate::where('team_id', $team->id)
                ->where('status', 'APPROVED')
                ->where(function ($q) use ($configName) {
                    $q->where('name', $configName)
                        ->orWhere('category', 'AUTHENTICATION')
                        ->orWhere('name', 'like', '%otp%')
                        ->orWhere('name', 'like', '%verification%')
                        ->orWhere('name', 'like', '%code%');
                })
                ->orderByRaw($languageOrderSql, [$appLocale])
                ->first();

            if ($fallback) {
                Log::warning('Using unsynced WhatsApp OTP template fallback. This may fail with Meta 132001.', [
                    'team_id' => $team->id,
                    'template_name' => $fallback->name,
                    'template_language' => $fallback->language,
                ]);
                return $fallback;
            }
        }

        return null;
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

            // If current credentials are denied by Meta permissions,
            // retry once with the team's stored credentials.
            if ($this->isMetaPermissionDenied($response)) {
                $teamFromDb = Team::query()->find($team->id);

                if (
                    $teamFromDb
                    && !empty($teamFromDb->whatsapp_access_token)
                    && !empty($teamFromDb->whatsapp_phone_number_id)
                ) {
                    Log::warning('OTP send got Meta permission denial with current credentials; retrying with team stored credentials', [
                        'team_id' => $team->id,
                        'credential_source' => $this->getOtpCredentialSource($team),
                        'current_phone_id' => $team->whatsapp_phone_number_id,
                        'current_token_fingerprint' => $this->tokenFingerprint($team->whatsapp_access_token),
                        'fallback_phone_id' => $teamFromDb->whatsapp_phone_number_id,
                        'fallback_token_fingerprint' => $this->tokenFingerprint($teamFromDb->whatsapp_access_token),
                    ]);

                    $fallbackService = new WhatsAppService($teamFromDb);
                    $response = $fallbackService->sendTemplate(
                        $phone,
                        $templateName,
                        $language,
                        $parameters
                    );
                }
            }

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

            Log::warning("WhatsApp template send failed for custom OTP", [
                'team_id' => $team->id,
                'credential_source' => $this->getOtpCredentialSource($team),
                'phone_number_id' => $team->whatsapp_phone_number_id,
                'token_fingerprint' => $this->tokenFingerprint($team->whatsapp_access_token),
                'response' => $response,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Failed to send Custom WhatsApp OTP to {$phone}: " . $e->getMessage());
            return false;
        }
    }

    protected function isMetaPermissionDenied(array $response): bool
    {
        $status = (int) ($response['status_code'] ?? 0);
        $code = (int) ($response['error']['error']['code'] ?? 0);
        $message = strtolower((string) ($response['message'] ?? ($response['error']['error']['message'] ?? '')));

        return ($status === 403 || $code === 200)
            && str_contains($message, 'necessary permissions to send messages');
    }

    protected function getOtpCredentialSource(Team $team): string
    {
        $systemToken = (string) config('whatsapp.system_access_token', '');
        $systemPhoneId = (string) config('whatsapp.system_phone_number_id', '');

        if (
            $systemToken !== ''
            && $systemPhoneId !== ''
            && (string) $team->whatsapp_phone_number_id === $systemPhoneId
            && (string) $team->whatsapp_access_token === $systemToken
        ) {
            return 'system';
        }

        return 'team';
    }

    protected function tokenFingerprint(?string $token): ?string
    {
        if (!$token) {
            return null;
        }

        return substr(hash('sha256', $token), 0, 12);
    }
}
