<?php

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasTeam;

    protected $guarded = [];

    protected $casts = [
        'custom_fields' => 'array',
        'metadata' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Generate a sequential or random unique ticket number with team's configured prefix.
     */
    public static function generateTicketNumber(?Team $team = null): string
    {
        $settings = $team?->ticket_settings ?? [];
        $prefix = strtoupper(trim($settings['prefix'] ?? 'TCK'));
        if (empty($prefix)) {
            $prefix = 'TCK';
        }

        $datePart = now()->format('ymd');
        $randomPart = strtoupper(Str::random(4));

        $number = "{$prefix}-{$datePart}-{$randomPart}";

        // Ensure uniqueness
        while (static::where('ticket_number', $number)->exists()) {
            $randomPart = strtoupper(Str::random(4));
            $number = "{$prefix}-{$datePart}-{$randomPart}";
        }

        return $number;
    }
}
