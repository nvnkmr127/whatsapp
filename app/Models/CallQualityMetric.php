<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallQualityMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_call_id',
        'sdp_offer_received_at',
        'sdp_answer_sent_at',
        'connection_established_at',
        'answer_latency_ms',
        'connection_latency_ms',
        'ice_candidates_count',
        'selected_codec',
        'connection_type',
        'network_quality_score',
        'error_logs',
        'retry_attempts',
        'validation_passed',
        'validation_warnings',
        'api_response_time_ms',
        'webrtc_state',
    ];

    protected $casts = [
        'sdp_offer_received_at' => 'datetime',
        'sdp_answer_sent_at' => 'datetime',
        'connection_established_at' => 'datetime',
        'answer_latency_ms' => 'integer',
        'connection_latency_ms' => 'integer',
        'ice_candidates_count' => 'integer',
        'network_quality_score' => 'integer',
        'error_logs' => 'array',
        'retry_attempts' => 'integer',
        'validation_passed' => 'boolean',
        'validation_warnings' => 'array',
        'api_response_time_ms' => 'integer',
    ];

    /**
     * Get the WhatsApp call that owns this metric
     */
    public function whatsappCall(): BelongsTo
    {
        return $this->belongsTo(WhatsAppCall::class);
    }

    /**
     * Scope for calls with quality issues
     */
    public function scopeWithQualityIssues($query)
    {
        return $query->where(function ($q) {
            $q->where('network_quality_score', '<=', 2)
                ->orWhere('validation_passed', false)
                ->orWhere('retry_attempts', '>', 0)
                ->orWhereNotNull('error_logs');
        });
    }

    /**
     * Scope for high quality calls
     */
    public function scopeHighQuality($query)
    {
        return $query->where('network_quality_score', '>=', 4)
            ->where('validation_passed', true)
            ->where('retry_attempts', 0);
    }

    /**
     * Scope for slow answer times
     */
    public function scopeSlowAnswers($query, int $thresholdMs = 3000)
    {
        return $query->where('answer_latency_ms', '>', $thresholdMs);
    }

    /**
     * Calculate answer latency from timestamps
     */
    public function calculateAnswerLatency(): ?int
    {
        if ($this->sdp_offer_received_at && $this->sdp_answer_sent_at) {
            return $this->sdp_offer_received_at->diffInMilliseconds($this->sdp_answer_sent_at);
        }
        return null;
    }

    /**
     * Calculate connection latency from timestamps
     */
    public function calculateConnectionLatency(): ?int
    {
        if ($this->sdp_answer_sent_at && $this->connection_established_at) {
            return $this->sdp_answer_sent_at->diffInMilliseconds($this->connection_established_at);
        }
        return null;
    }

    /**
     * Get quality summary
     */
    public function getQualitySummary(): array
    {
        return [
            'overall_score' => $this->network_quality_score,
            'answer_latency' => $this->answer_latency_ms,
            'connection_latency' => $this->connection_latency_ms,
            'codec' => $this->selected_codec,
            'connection_type' => $this->connection_type,
            'had_issues' => !$this->validation_passed || $this->retry_attempts > 0 || !empty($this->error_logs),
            'retry_count' => $this->retry_attempts,
        ];
    }
}
