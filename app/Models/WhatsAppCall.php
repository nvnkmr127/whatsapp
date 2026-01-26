<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppCall extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_calls';

    protected $fillable = [
        'team_id',
        'contact_id',
        'conversation_id',
        'call_id',
        'direction',
        'status',
        'from_number',
        'to_number',
        'initiated_at',
        'answered_at',
        'ended_at',
        'duration_seconds',
        'cost_amount',
        'metadata',
        'failure_reason',
    ];

    protected $casts = [
        'initiated_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'cost_amount' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * Get the team that owns the call.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the contact associated with the call.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the conversation associated with the call.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Scope a query to only include inbound calls.
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    /**
     * Scope a query to only include outbound calls.
     */
    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    /**
     * Scope a query to only include completed calls.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed calls.
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'rejected', 'missed', 'no_answer']);
    }

    /**
     * Scope a query to only include active calls.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['initiated', 'ringing', 'in_progress']);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration_seconds < 60) {
            return $this->duration_seconds . 's';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%dm %ds', $minutes, $seconds);
    }

    /**
     * Get formatted cost.
     */
    public function getCostFormattedAttribute(): string
    {
        return '$' . number_format((float) $this->cost_amount, 2);
    }

    /**
     * Mark the call as answered.
     */
    public function markAsAnswered(): void
    {
        $this->update([
            'status' => 'in_progress',
            'answered_at' => now(),
        ]);
    }

    /**
     * Mark the call as ended and calculate duration.
     */
    public function markAsEnded(): void
    {
        $endedAt = now();
        $duration = 0;

        if ($this->answered_at) {
            $duration = $endedAt->diffInSeconds($this->answered_at);
        }

        $this->update([
            'status' => 'completed',
            'ended_at' => $endedAt,
            'duration_seconds' => $duration,
            'cost_amount' => $this->calculateCost($duration),
        ]);
    }

    /**
     * Mark the call as failed.
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'ended_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Mark the call as rejected.
     */
    public function markAsRejected(): void
    {
        $this->update([
            'status' => 'rejected',
            'ended_at' => now(),
        ]);
    }

    /**
     * Mark the call as missed.
     */
    public function markAsMissed(): void
    {
        $this->update([
            'status' => 'missed',
            'ended_at' => now(),
        ]);
    }

    /**
     * Calculate call cost based on duration.
     * WhatsApp charges per minute, rounded up.
     */
    public function calculateCost(int $durationSeconds): float
    {
        if ($durationSeconds <= 0) {
            return 0;
        }

        // Round up to nearest minute
        $minutes = ceil($durationSeconds / 60);

        // Get pricing from config (default: $0.005 per minute)
        $pricePerMinute = config('whatsapp.calling.price_per_minute', 0.005);

        return $minutes * $pricePerMinute;
    }

    /**
     * Check if the call is active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['initiated', 'ringing', 'in_progress']);
    }

    /**
     * Check if the call was successful.
     */
    public function wasSuccessful(): bool
    {
        return $this->status === 'completed' && $this->duration_seconds > 0;
    }
}
