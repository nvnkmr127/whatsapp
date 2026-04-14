<?php

namespace App\Jobs;

use App\Models\Team;
use App\Services\TraceContext;
use App\Services\WhatsAppVerificationEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ValidateWhatsAppTokens implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $teamId;
    public ?string $traceId;

    public function __construct(int $teamId, ?string $traceId = null)
    {
        $this->teamId = $teamId;
        $this->traceId = $traceId;
    }

    public function handle(WhatsAppVerificationEngine $engine): void
    {
        if ($this->traceId) {
            TraceContext::set($this->traceId);
        } else {
            TraceContext::ensureTraceId();
        }

        $team = Team::find($this->teamId);
        if (! $team) {
            Log::warning('ValidateWhatsAppTokens: team not found', [
                'trace_id' => TraceContext::getTraceId(),
                'team_id' => $this->teamId,
            ]);
            return;
        }

        $result = $engine->setTeam($team)->verify();

        Log::info('ValidateWhatsAppTokens: verification complete', [
            'trace_id' => TraceContext::getTraceId(),
            'team_id' => $team->id,
            'state' => $result['state']->value ?? null,
        ]);
    }
}

