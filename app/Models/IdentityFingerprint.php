<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * IdentityFingerprint
 * ───────────────────
 * Stores hashed identity signals (email domain, signup IP, payment
 * method) captured at registration. Read by UniqueIdentityService
 * to block duplicate accounts before a trial is assigned.
 *
 * @property string  $type           email_domain | signup_ip | payment_method
 * @property string  $fingerprint    HMAC-SHA256 hash (never raw PII for IPs)
 * @property ?int    $team_id
 * @property ?int    $user_id
 * @property bool    $is_blocked
 * @property ?string $blocked_reason
 * @property ?int    $blocked_by
 * @property ?\Carbon\Carbon $blocked_at
 */
class IdentityFingerprint extends Model
{
    // ------------------------------------------------------------------
    // Constants – fingerprint types
    // ------------------------------------------------------------------

    public const TYPE_EMAIL_DOMAIN = 'email_domain';
    public const TYPE_SIGNUP_IP = 'signup_ip';
    public const TYPE_PAYMENT_METHOD = 'payment_method';

    public const TYPES = [
        self::TYPE_EMAIL_DOMAIN,
        self::TYPE_SIGNUP_IP,
        self::TYPE_PAYMENT_METHOD,
    ];

    protected $guarded = [];

    protected $casts = [
        'is_blocked' => 'boolean',
        'blocked_at' => 'datetime',
    ];

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function blocker()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    /** Fingerprints that are explicitly hard-blocked by an admin. */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    /** All fingerprints of a given type. */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Hash a raw value consistently so the stored fingerprint is never
     * reversible (important for IP addresses).
     */
    public static function hash(string $raw): string
    {
        return hash_hmac('sha256', strtolower(trim($raw)), config('app.key'));
    }

    /**
     * How many distinct teams already own this fingerprint?
     */
    public static function countTeams(string $type, string $fingerprint): int
    {
        return static::where('type', $type)
            ->where('fingerprint', $fingerprint)
            ->whereNotNull('team_id')
            ->distinct('team_id')
            ->count('team_id');
    }

    /**
     * Is this fingerprint explicitly hard-blocked by an admin?
     */
    public static function isHardBlocked(string $type, string $fingerprint): bool
    {
        return static::where('type', $type)
            ->where('fingerprint', $fingerprint)
            ->where('is_blocked', true)
            ->exists();
    }
}
