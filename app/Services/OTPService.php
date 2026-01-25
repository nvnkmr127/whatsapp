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

    /**
     * Send OTP to email or phone.
     */
    public function send(string $identifier, string $type = 'email'): bool
    {
        // Abuse Prevention: Dead drop check
        if ($this->isBlacklisted($identifier)) {
            Log::warning("OTP Request blocked for blacklisted identifier: {$identifier}");
            AuditService::log('Auth.Abuse.Blocked', null, $identifier, $type . '_otp', ['reason' => 'Too many requests in 24h']);
            return false;
        }

        $code = (string) rand(100000, 999999);

        // Securely store hashed code and attempt count
        Cache::put($this->getCacheKey($identifier), [
            'hash' => Hash::make($code),
            'attempts' => 0,
        ], $this->ttl);

        // Increment total requests in 24h for this identifier
        $this->incrementRequestCount($identifier);

        if ($type === 'email') {
            $sent = $this->sendEmail($identifier, $code);
        } elseif ($type === 'phone') {
            $sent = $this->sendWhatsApp($identifier, $code);
        }

        if ($sent) {
            // Dispatch system-wide webhook for Login OTP
            try {
                app(\App\Services\WebhookService::class)->dispatch(null, 'auth.otp.login', [
                    'identifier' => $identifier,
                    'type' => $type,
                    'is_new_user' => !\App\Models\User::where($type === 'email' ? 'email' : 'phone', $identifier)->exists(),
                    'timestamp' => now()->toIso8601String(),
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to dispatch auth.otp.login webhook: " . $e->getMessage());
            }
        }

        return $sent;
    }

    /**
     * Verify the OTP code with retry protection and logging.
     */
    public function verify(string $identifier, string $code): bool
    {
        $data = Cache::get($this->getCacheKey($identifier));

        if (!$data) {
            return false;
        }

        // Increment attempts
        $data['attempts']++;
        Cache::put($this->getCacheKey($identifier), $data, $this->ttl);

        if ($data['attempts'] > $this->maxAttempts) {
            Cache::forget($this->getCacheKey($identifier));
            Log::warning("OTP brute force attempt detected for: {$identifier}");
            AuditService::log('Auth.Abuse.Flag', null, $identifier, null, ['reason' => 'Max attempts reached']);
            return false;
        }

        if (Hash::check($code, $data['hash'])) {
            Cache::forget($this->getCacheKey($identifier));
            // Reset daily request count upon successful login? 
            // Better to keep it to prevent "churn and burn" strategies, but we'll reset for UX.
            Cache::forget($this->getDailyCountKey($identifier));
            return true;
        }

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
            $team = Team::whereNotNull('whatsapp_access_token')
                ->whereNotNull('whatsapp_phone_number_id')
                ->first();

            if (!$team) {
                Log::error("No team found with WhatsApp credentials for sending OTP.");
                return false;
            }

            return $this->sendCustomWhatsAppOtp($phone, $code, 'verification_code', 'en_US', [$code], $team);
        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp OTP to {$phone}: " . $e->getMessage());
            return false;
        }
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
            // Securely store hashed code if not already stored
            if (!Cache::has($this->getCacheKey($phone))) {
                Cache::put($this->getCacheKey($phone), [
                    'hash' => Hash::make($code),
                    'attempts' => 0,
                ], $this->ttl);
            }

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
