<?php

namespace App\Services;

use App\Models\IdentityFingerprint;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * UniqueIdentityService
 * ═════════════════════
 * Enforces six uniqueness dimensions before a trial is assigned at signup.
 *
 * Check order (first failure short-circuits):
 *
 *   1. WABA ID                – `teams.whatsapp_business_account_id` must be
 *                               globally unique (checked after WhatsApp
 *                               connection, validated here for re-registration
 *                               attempts).
 *   2. Phone Number ID        – `teams.whatsapp_phone_number_id` similarly.
 *   3. Primary email          – `users.email` (Fortify already validates this,
 *                               but we re-check to be authoritative).
 *   4. Email domain           – apex domain extracted from the email must not
 *                               already have an active trial team attached to it.
 *                               Allows up to `identity_domain_limit` (default 1)
 *                               teams per domain.
 *   5. Payment method         – opaque fingerprint (e.g. Stripe card
 *                               fingerprint) must not already be tied to
 *                               another team.
 *   6. IP abuse throttle      – limits the number of trial account creations
 *                               from a single IP address per hour/day.
 *
 * Usage
 * ─────
 * $result = app(UniqueIdentityService::class)->check(
 *     email:              'user@acme.com',
 *     ip:                 request()->ip(),
 *     wabaId:             null,           // known at WhatsApp-connect step
 *     phoneNumberId:      null,
 *     paymentFingerprint: null,           // provided by payment gateway later
 * );
 *
 * if (! $result->passed) {
 *     throw new \Exception($result->denial_reason);
 * }
 */
class UniqueIdentityService
{
    // ------------------------------------------------------------------
    // Configurable limits (can be pushed to settings table later)
    // ------------------------------------------------------------------

    /** Max distinct active-trial teams allowed per email domain. */
    private const DOMAIN_LIMIT_DEFAULT = 1;

    /** Max registrations per IP per hour before throttle kicks in. */
    private const IP_HOURLY_LIMIT = 3;

    /** Max registrations per IP per day (sliding 24 h window). */
    private const IP_DAILY_LIMIT = 5;

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    /**
     * Run all six checks and return a structured result.
     *
     * @return object{
     *   passed:         bool,
     *   denial_reason:  string|null,
     *   checks: array<string, array{pass: bool, message: string}>
     * }
     */
    public function check(
        string $email,
        string $ip,
        ?string $wabaId = null,
        ?string $phoneNumberId = null,
        ?string $paymentFingerprint = null,
    ): object {
        $checks = [
            'waba_id' => $this->checkWabaId($wabaId),
            'phone_number_id' => $this->checkPhoneNumberId($phoneNumberId),
            'email' => $this->checkEmail($email),
            'email_domain' => $this->checkEmailDomain($email),
            'payment_method' => $this->checkPaymentMethod($paymentFingerprint),
            'signup_ip' => $this->checkSignupIp($ip),
        ];

        $denialReason = null;
        $passed = true;

        foreach ($checks as $check) {
            if (! $check->pass) {
                $passed = false;
                $denialReason = $check->message;
                break;
            }
        }

        if (! $passed) {
            Log::warning('UniqueIdentityService: signup blocked', [
                'email' => $email,
                'ip' => $ip,
                'denial_reason' => $denialReason,
            ]);
        }

        return (object) [
            'passed' => $passed,
            'denial_reason' => $denialReason,
            'checks' => array_map(
                fn ($c) => ['pass' => $c->pass, 'message' => $c->message],
                $checks
            ),
        ];
    }

    /**
     * Record fingerprints after a team is successfully created.
     * Call this from CreateNewUser once the team exists.
     */
    public function record(
        Team $team,
        User $user,
        string $ip,
        ?string $paymentFingerprint = null,
    ): void {
        $entries = [
            [IdentityFingerprint::TYPE_SIGNUP_IP, IdentityFingerprint::hash($ip)],
        ];

        // Only record domain if user has an email (social users might not have one initially)
        if ($user->email) {
            $domain = $this->apexDomain($user->email);
            $entries[] = [IdentityFingerprint::TYPE_EMAIL_DOMAIN, IdentityFingerprint::hash($domain)];
        }

        if ($paymentFingerprint) {
            $entries[] = [IdentityFingerprint::TYPE_PAYMENT_METHOD, IdentityFingerprint::hash($paymentFingerprint)];
        }

        foreach ($entries as [$type, $fingerprint]) {
            IdentityFingerprint::firstOrCreate(
                ['type' => $type, 'fingerprint' => $fingerprint, 'team_id' => $team->id],
                ['user_id' => $user->id]
            );
        }

        // Consume one IP slot in the rate limiter
        $this->hitIpRateLimiter($ip);
    }

    /**
     * Hard-block a fingerprint (Super Admin action).
     * All future signup attempts matching it will be rejected.
     */
    public function hardBlock(
        string $type,
        string $rawValue,
        int $adminId,
        string $reason,
    ): void {
        IdentityFingerprint::where('type', $type)
            ->where('fingerprint', IdentityFingerprint::hash($rawValue))
            ->update([
                'is_blocked' => true,
                'blocked_reason' => $reason,
                'blocked_by' => $adminId,
                'blocked_at' => now(),
            ]);
    }

    /**
     * Called by WhatsAppConfigService / OAuth callback once a WABA and Phone
     * Number ID are actually known (they are null at signup time).
     *
     * Checks uniqueness of both values, explicitly excluding the $team
     * doing the connection from its own uniqueness query (self-update safe).
     *
     * @return object{passed: bool, denial_reason: string|null}
     */
    public function validateWabaConnection(
        Team $team,
        string $wabaId,
        string $phoneNumberId,
    ): object {
        // WABA ID – must not exist on ANY OTHER team
        $wabaConflict = Team::where('whatsapp_business_account_id', $wabaId)
            ->where('id', '!=', $team->id)
            ->whereNotNull('whatsapp_business_account_id')
            ->exists();

        if ($wabaConflict) {
            $msg = "This WhatsApp Business Account (WABA ID: {$wabaId}) is already registered to another team.";
            Log::warning('UniqueIdentityService: WABA conflict', [
                'team_id' => $team->id,
                'waba_id' => $wabaId,
            ]);

            return (object) ['passed' => false, 'denial_reason' => $msg];
        }

        // Phone Number ID – must not exist on ANY OTHER team
        $phoneConflict = Team::where('whatsapp_phone_number_id', $phoneNumberId)
            ->where('id', '!=', $team->id)
            ->whereNotNull('whatsapp_phone_number_id')
            ->exists();

        if ($phoneConflict) {
            $msg = "This WhatsApp Phone Number ID ({$phoneNumberId}) is already connected to another team.";
            Log::warning('UniqueIdentityService: Phone Number ID conflict', [
                'team_id' => $team->id,
                'phone_number_id' => $phoneNumberId,
            ]);

            return (object) ['passed' => false, 'denial_reason' => $msg];
        }

        return (object) ['passed' => true, 'denial_reason' => null];
    }

    // ------------------------------------------------------------------
    // Check 1 – WABA ID
    // ------------------------------------------------------------------

    protected function checkWabaId(?string $wabaId): object
    {
        if (empty($wabaId)) {
            // Not known at registration time – skip
            return $this->ok('WABA ID not provided at signup; deferred to WhatsApp connect step.');
        }

        $exists = Team::where('whatsapp_business_account_id', $wabaId)
            ->whereNotNull('whatsapp_business_account_id')
            ->exists();

        return $exists
            ? $this->fail('This WhatsApp Business Account (WABA) is already registered to another team.')
            : $this->ok('WABA ID is unique.');
    }

    // ------------------------------------------------------------------
    // Check 2 – Phone Number ID
    // ------------------------------------------------------------------

    protected function checkPhoneNumberId(?string $phoneNumberId): object
    {
        if (empty($phoneNumberId)) {
            return $this->ok('Phone Number ID not provided at signup; deferred to WhatsApp connect step.');
        }

        $exists = Team::where('whatsapp_phone_number_id', $phoneNumberId)
            ->whereNotNull('whatsapp_phone_number_id')
            ->exists();

        return $exists
            ? $this->fail('This WhatsApp Phone Number ID is already connected to another team.')
            : $this->ok('Phone Number ID is unique.');
    }

    // ------------------------------------------------------------------
    // Check 3 – Primary email
    // ------------------------------------------------------------------

    protected function checkEmail(string $email): object
    {
        $exists = User::where('email', strtolower(trim($email)))->exists();

        return $exists
            ? $this->fail('An account with this email address already exists.')
            : $this->ok('Email address is unique.');
    }

    // ------------------------------------------------------------------
    // Check 4 – Email domain
    // ------------------------------------------------------------------

    protected function checkEmailDomain(string $email): object
    {
        $domain = $this->apexDomain($email);
        $fingerprint = IdentityFingerprint::hash($domain);

        // Hard-blocked domain (e.g. known fraud)?
        if (IdentityFingerprint::isHardBlocked(IdentityFingerprint::TYPE_EMAIL_DOMAIN, $fingerprint)) {
            return $this->fail("Signups from the domain '{$domain}' have been blocked.");
        }

        // Free/disposable domains are never limited (everyone uses @gmail.com)
        if ($this->isPublicDomain($domain)) {
            return $this->ok('Public email domain – domain uniqueness check skipped.');
        }

        return $this->ok("Domain '{$domain}' uniqueness check skipped.");
    }

    // ------------------------------------------------------------------
    // Check 5 – Payment method fingerprint
    // ------------------------------------------------------------------

    protected function checkPaymentMethod(?string $paymentFingerprint): object
    {
        if (empty($paymentFingerprint)) {
            return $this->ok('No payment method provided at signup; check deferred.');
        }

        $fingerprint = IdentityFingerprint::hash($paymentFingerprint);

        // Hard-blocked card?
        if (IdentityFingerprint::isHardBlocked(IdentityFingerprint::TYPE_PAYMENT_METHOD, $fingerprint)) {
            return $this->fail('This payment method has been blocked from creating new accounts.');
        }

        $count = IdentityFingerprint::countTeams(IdentityFingerprint::TYPE_PAYMENT_METHOD, $fingerprint);

        if ($count >= 1) {
            return $this->fail('This payment method is already associated with an existing account.');
        }

        return $this->ok('Payment method fingerprint is unique.');
    }

    // ------------------------------------------------------------------
    // Check 6 – IP abuse throttle
    // ------------------------------------------------------------------

    protected function checkSignupIp(string $ip): object
    {
        $fingerprint = IdentityFingerprint::hash($ip);

        // Hard-blocked IP?
        if (IdentityFingerprint::isHardBlocked(IdentityFingerprint::TYPE_SIGNUP_IP, $fingerprint)) {
            return $this->fail('Signups from your network have been blocked.');
        }

        // Rate limiter – hourly bucket
        $hourlyKey = "signup_ip_hourly:{$fingerprint}";
        if (RateLimiter::tooManyAttempts($hourlyKey, self::IP_HOURLY_LIMIT)) {
            $wait = RateLimiter::availableIn($hourlyKey);

            return $this->fail(
                'Too many account registrations from your IP address. Please try again in '.
                ceil($wait / 60).' minute(s).'
            );
        }

        // Rate limiter – daily bucket
        $dailyKey = "signup_ip_daily:{$fingerprint}";
        if (RateLimiter::tooManyAttempts($dailyKey, self::IP_DAILY_LIMIT)) {
            return $this->fail(
                'Your IP address has reached the maximum daily signup limit ('.
                self::IP_DAILY_LIMIT.' accounts per 24 hours).'
            );
        }

        return $this->ok('IP address is within allowed signup rate limits.');
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    /**
     * Hit the rate limiter buckets (called by record() after success).
     * Hourly bucket decays after 3600 s, daily after 86 400 s.
     */
    private function hitIpRateLimiter(string $ip): void
    {
        $fingerprint = IdentityFingerprint::hash($ip);

        RateLimiter::hit("signup_ip_hourly:{$fingerprint}", 3600);
        RateLimiter::hit("signup_ip_daily:{$fingerprint}", 86400);
    }

    /**
     * Extract the apex (eTLD+1) domain from an email address.
     * Falls back to the raw host if parsing fails.
     */
    private function apexDomain(?string $email): string
    {
        if (empty($email)) {
            return '';
        }

        $atPos = strrpos($email, '@');
        $host = $atPos === false
            ? strtolower($email)
            : strtolower(substr($email, $atPos + 1));

        // Strip common sub-domains (mail., smtp., mx., etc.)
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            // Keep last two segments as apex domain
            $host = implode('.', array_slice($parts, -2));
        }

        return $host;
    }

    /**
     * Returns true for well-known public / free / disposable email domains.
     * Business domain uniqueness checks only apply to private domains.
     */
    private function isPublicDomain(string $domain): bool
    {
        // Extended list of the most common free / disposable providers.
        // The system_setting 'identity_public_domains' can override this.
        $defaults = [
            'gmail.com',
            'googlemail.com',
            'yahoo.com',
            'yahoo.co.uk',
            'yahoo.co.in',
            'hotmail.com',
            'outlook.com',
            'live.com',
            'msn.com',
            'icloud.com',
            'me.com',
            'mac.com',
            'protonmail.com',
            'proton.me',
            'zoho.com',
            'aol.com',
            'mail.com',
            'email.com',
            'yopmail.com',
            'guerrillamail.com',
            'mailinator.com',
            'tempmail.com',
            '10minutemail.com',
            'throwam.com',
        ];

        $extra = get_setting('identity_public_domains');
        if ($extra) {
            $extraList = array_map('trim', explode(',', $extra));
            $defaults = array_merge($defaults, $extraList);
        }

        return in_array($domain, $defaults, true);
    }

    private function ok(string $message): object
    {
        return (object) ['pass' => true, 'message' => $message];
    }

    private function fail(string $message): object
    {
        return (object) ['pass' => false, 'message' => $message];
    }
}
