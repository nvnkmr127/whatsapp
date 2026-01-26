<?php

namespace App\Services;

use App\Models\WhatsAppCall;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class CallLogService
{
    /**
     * Log a call terminal event to the chat timeline.
     */
    public function logCall(WhatsAppCall $call): ?Message
    {
        if (!$call->conversation_id || !$call->contact_id) {
            Log::warning("Call missing conversation or contact ID, skipping log entry", ['call_id' => $call->call_id]);
            return null;
        }

        $summary = $this->formatSummary($call);

        return Message::create([
            'type' => 'call_log',
            'team_id' => $call->team_id,
            'contact_id' => $call->contact_id,
            'conversation_id' => $call->conversation_id,
            'content' => $summary,
            'status' => 'sent',
            'direction' => $call->direction,
            'sent_at' => now(),
            'metadata' => [
                'call_id' => $call->call_id,
                'status' => $call->status,
                'duration_seconds' => $call->duration_seconds,
                'failure_reason' => $call->failure_reason,
            ],
        ]);
    }

    /**
     * Format the call summary for the timeline.
     */
    protected function formatSummary(WhatsAppCall $call): string
    {
        $direction = ucfirst($call->direction);
        $status = ucfirst($call->status);
        $emoji = $call->direction === 'inbound' ? '📞' : '📱';

        $summary = "{$emoji} {$direction} Call: {$status}";

        if ($call->status === 'completed' && $call->duration_seconds > 0) {
            $summary .= " (" . $call->formatted_duration . ")";
        }

        if ($call->status === 'failed' && $call->failure_reason) {
            $summary .= " - Reason: {$call->failure_reason}";
        }

        return $summary;
    }
}
