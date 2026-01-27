<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CallSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'phone_number_id',
        'calling_enabled',
        'call_icon_visibility',
        'business_hours',
        'callback_permission_enabled',
        'sip_config',
        'call_icons_config',
        'is_restricted',
        'restriction_reason',
        'restricted_at',
    ];

    protected $casts = [
        'calling_enabled' => 'boolean',
        'callback_permission_enabled' => 'boolean',
        'business_hours' => 'array',
        'sip_config' => 'array',
        'call_icons_config' => 'array',
        'is_restricted' => 'boolean',
        'restricted_at' => 'datetime',
    ];

    protected $hidden = [
        'sip_config', // Hide sensitive SIP credentials by default
    ];

    /**
     * Relationships
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Check if current time is within configured business hours
     */
    public function isWithinBusinessHours(?Carbon $time = null): bool
    {
        if (!$this->business_hours) {
            return true; // No restrictions if business hours not configured
        }

        $time = $time ?? now();
        $timezone = $this->business_hours['timezone'] ?? config('app.timezone');
        $currentTime = $time->setTimezone($timezone);

        $dayOfWeek = strtoupper($currentTime->format('D')); // MON, TUE, etc.
        $currentTimeStr = $currentTime->format('H:i');

        foreach ($this->business_hours['hours'] ?? [] as $schedule) {
            if ($schedule['day'] === $dayOfWeek) {
                $openTime = $schedule['open'];
                $closeTime = $schedule['close'];

                if ($currentTimeStr >= $openTime && $currentTimeStr <= $closeTime) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if the business can receive calls right now
     */
    public function canReceiveCalls(): bool
    {
        return $this->calling_enabled &&
            !$this->is_restricted &&
            $this->isWithinBusinessHours();
    }

    /**
     * Check if call icon should be visible
     */
    public function shouldShowCallIcon(?string $countryCode = null): bool
    {
        if ($this->call_icon_visibility === 'hide') {
            return false;
        }

        if (!$this->calling_enabled || $this->is_restricted) {
            return false;
        }

        // Check country-specific visibility rules
        if ($countryCode && $this->call_icons_config) {
            $showFor = $this->call_icons_config['show_for_countries'] ?? [];
            $hideFor = $this->call_icons_config['hide_for_countries'] ?? [];

            if (!empty($showFor) && !in_array($countryCode, $showFor)) {
                return false;
            }

            if (in_array($countryCode, $hideFor)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply restriction to calling
     */
    public function applyRestriction(string $reason): void
    {
        $this->update([
            'is_restricted' => true,
            'restriction_reason' => $reason,
            'restricted_at' => now(),
        ]);
    }

    /**
     * Remove restriction
     */
    public function removeRestriction(): void
    {
        $this->update([
            'is_restricted' => false,
            'restriction_reason' => null,
            'restricted_at' => null,
        ]);
    }

    /**
     * Get SIP configuration (only when explicitly requested)
     */
    public function getSipConfiguration(): ?array
    {
        return $this->sip_config;
    }

    /**
     * Update SIP configuration securely
     */
    public function updateSipConfiguration(array $config): void
    {
        $this->update(['sip_config' => $config]);
    }

    /**
     * Generate call link for this phone number
     */
    public function generateCallLink(): string
    {
        // Format: https://wa.me/{phone_number}?call=1
        $phoneNumber = str_replace('+', '', $this->phone_number_id);
        return "https://wa.me/{$phoneNumber}?call=1";
    }
}
