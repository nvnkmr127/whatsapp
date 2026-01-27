<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CallPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'phone_number_id',
        'contact_id',
        'permission_status',
        'permission_requested_at',
        'permission_granted_at',
        'permission_expires_at',
        'calls_made_count',
        'last_call_at',
        'requests_in_24h',
        'requests_in_7d',
        'first_request_in_24h',
        'first_request_in_7d',
    ];

    protected $casts = [
        'permission_requested_at' => 'datetime',
        'permission_granted_at' => 'datetime',
        'permission_expires_at' => 'datetime',
        'last_call_at' => 'datetime',
        'first_request_in_24h' => 'datetime',
        'first_request_in_7d' => 'datetime',
        'calls_made_count' => 'integer',
        'requests_in_24h' => 'integer',
        'requests_in_7d' => 'integer',
    ];

    /**
     * Relationships
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('permission_status', 'granted')
            ->where('permission_expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('permission_status', 'granted')
            ->where('permission_expires_at', '<=', now());
    }

    public function scopeWithinWindow($query)
    {
        return $query->where('permission_status', 'granted')
            ->where('permission_expires_at', '>', now());
    }

    /**
     * Check if a new permission request can be made
     * Rules: Max 1 request per 24h, Max 2 requests per 7d
     */
    public function canRequestPermission(): bool
    {
        // Check 24-hour limit
        if (
            $this->first_request_in_24h &&
            $this->first_request_in_24h->diffInHours(now()) < 24 &&
            $this->requests_in_24h >= 1
        ) {
            return false;
        }

        // Check 7-day limit
        if (
            $this->first_request_in_7d &&
            $this->first_request_in_7d->diffInDays(now()) < 7 &&
            $this->requests_in_7d >= 2
        ) {
            return false;
        }

        return true;
    }

    /**
     * Grant permission and set 72-hour expiration window
     */
    public function grantPermission(): void
    {
        $this->update([
            'permission_status' => 'granted',
            'permission_granted_at' => now(),
            'permission_expires_at' => now()->addHours(72),
        ]);
    }

    /**
     * Check if permission is within the 72-hour calling window
     */
    public function isWithinCallingWindow(): bool
    {
        return $this->permission_status === 'granted' &&
            $this->permission_expires_at &&
            $this->permission_expires_at->isFuture();
    }

    /**
     * Record a call made using this permission
     */
    public function recordCall(): void
    {
        $this->increment('calls_made_count');
        $this->update(['last_call_at' => now()]);

        // Reset rate limits after a successful connected call
        $this->resetRateLimits();
    }

    /**
     * Track a permission request
     */
    public function trackRequest(): void
    {
        $now = now();

        // Reset 24h counter if window has passed
        if (!$this->first_request_in_24h || $this->first_request_in_24h->diffInHours($now) >= 24) {
            $this->first_request_in_24h = $now;
            $this->requests_in_24h = 0;
        }

        // Reset 7d counter if window has passed
        if (!$this->first_request_in_7d || $this->first_request_in_7d->diffInDays($now) >= 7) {
            $this->first_request_in_7d = $now;
            $this->requests_in_7d = 0;
        }

        // Increment counters
        $this->increment('requests_in_24h');
        $this->increment('requests_in_7d');

        $this->update([
            'permission_requested_at' => $now,
            'permission_status' => 'requested',
        ]);
    }

    /**
     * Reset rate limits after successful call
     */
    protected function resetRateLimits(): void
    {
        $this->update([
            'requests_in_24h' => 0,
            'requests_in_7d' => 0,
            'first_request_in_24h' => null,
            'first_request_in_7d' => null,
        ]);
    }

    /**
     * Mark permission as expired
     */
    public function markAsExpired(): void
    {
        $this->update(['permission_status' => 'expired']);
    }

    /**
     * Revoke permission
     */
    public function revoke(): void
    {
        $this->update(['permission_status' => 'revoked']);
    }
}
