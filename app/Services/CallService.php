<?php

namespace App\Services;

use App\Models\Team;
use App\Models\WhatsAppCall;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CallService
{
    protected $team;
    protected $whatsappService;

    public function __construct(Team $team)
    {
        $this->team = $team;
        $this->whatsappService = new WhatsAppService($team);
    }

    /**
     * Initiate an outbound call with validation.
     */
    public function initiateCall(string $phoneNumber, array $options = []): array
    {
        // Find or create contact
        $contact = Contact::where('team_id', $this->team->id)
            ->where('phone_number', $phoneNumber)
            ->first();

        if (!$contact) {
            return [
                'success' => false,
                'error' => 'Contact not found. Please add the contact first.',
            ];
        }

        // Run comprehensive eligibility checks
        $eligibilityService = new CallEligibilityService($this->team);
        $eligibility = $eligibilityService->checkEligibility($contact);

        if (!$eligibility['eligible']) {
            return [
                'success' => false,
                'error' => $eligibility['user_message'],
                'block_reason' => $eligibility['block_reason'],
                'block_category' => $eligibility['block_category'],
                'can_retry_at' => $eligibility['can_retry_at'],
                'eligibility_details' => $eligibility,
            ];
        }

        try {
            $response = $this->whatsappService->initiateCall($phoneNumber, $options);

            if ($response['success'] ?? false) {
                Log::info("Call initiated successfully", [
                    'team_id' => $this->team->id,
                    'phone' => $phoneNumber,
                    'contact_id' => $contact->id,
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("Call initiation failed", [
                'team_id' => $this->team->id,
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if team has exceeded usage limits.
     */
    public function checkUsageLimits(): array
    {
        $currentMonth = now()->format('Y-m');
        $minutesUsed = WhatsAppCall::where('team_id', $this->team->id)
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])
            ->sum('duration_seconds') / 60;

        if (!$this->team->max_call_minutes_per_month) {
            return [
                'allowed' => true,
                'minutes_used' => $minutesUsed,
                'minutes_limit' => null,
                'minutes_remaining' => null,
            ];
        }

        $limit = $this->team->max_call_minutes_per_month;
        $remaining = $limit - $minutesUsed;

        if ($minutesUsed >= $limit) {
            return [
                'allowed' => false,
                'reason' => "Monthly call limit of {$limit} minutes has been reached. Used: {$minutesUsed} minutes.",
                'minutes_used' => $minutesUsed,
                'minutes_limit' => $limit,
                'minutes_remaining' => 0,
            ];
        }

        return [
            'allowed' => true,
            'minutes_used' => $minutesUsed,
            'minutes_limit' => $limit,
            'minutes_remaining' => $remaining,
        ];
    }

    /**
     * Get call statistics for the team.
     */
    public function getCallStatistics(string $period = 'month'): array
    {
        $query = WhatsAppCall::where('team_id', $this->team->id);

        // Apply period filter
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        $calls = $query->get();

        return [
            'total_calls' => $calls->count(),
            'inbound_calls' => $calls->where('direction', 'inbound')->count(),
            'outbound_calls' => $calls->where('direction', 'outbound')->count(),
            'completed_calls' => $calls->where('status', 'completed')->count(),
            'failed_calls' => $calls->whereIn('status', ['failed', 'rejected', 'missed', 'no_answer'])->count(),
            'total_duration_seconds' => $calls->sum('duration_seconds'),
            'total_duration_minutes' => round($calls->sum('duration_seconds') / 60, 2),
            'average_duration_seconds' => $calls->where('status', 'completed')->avg('duration_seconds') ?? 0,
            'total_cost' => $calls->sum('cost_amount'),
            'success_rate' => $calls->count() > 0
                ? round(($calls->where('status', 'completed')->count() / $calls->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get call history for a contact.
     */
    public function getContactCallHistory(Contact $contact, int $limit = 50): array
    {
        $calls = WhatsAppCall::where('team_id', $this->team->id)
            ->where('contact_id', $contact->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $calls->map(function ($call) {
            return [
                'id' => $call->id,
                'call_id' => $call->call_id,
                'direction' => $call->direction,
                'status' => $call->status,
                'duration' => $call->formatted_duration,
                'cost' => $call->cost_formatted,
                'initiated_at' => $call->initiated_at?->format('Y-m-d H:i:s'),
                'answered_at' => $call->answered_at?->format('Y-m-d H:i:s'),
                'ended_at' => $call->ended_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    /**
     * Calculate estimated cost for a call duration.
     */
    public function estimateCallCost(int $durationSeconds): float
    {
        if ($durationSeconds <= 0) {
            return 0;
        }

        // Round up to nearest minute
        $minutes = ceil($durationSeconds / 60);

        // Get pricing from config
        $pricePerMinute = config('whatsapp.calling.price_per_minute', 0.005);

        return $minutes * $pricePerMinute;
    }

    /**
     * Get active calls for the team.
     */
    public function getActiveCalls(): array
    {
        $activeCalls = WhatsAppCall::where('team_id', $this->team->id)
            ->whereIn('status', ['initiated', 'ringing', 'in_progress'])
            ->with('contact')
            ->get();

        return $activeCalls->map(function ($call) {
            return [
                'id' => $call->id,
                'call_id' => $call->call_id,
                'direction' => $call->direction,
                'status' => $call->status,
                'contact_name' => $call->contact->name ?? 'Unknown',
                'contact_phone' => $call->contact->phone_number,
                'initiated_at' => $call->initiated_at?->diffForHumans(),
            ];
        })->toArray();
    }

    /**
     * Handle call completion and trigger agent cooldown.
     */
    public function handleCallEnded(WhatsAppCall $call): void
    {
        $agentId = $call->agent_id;
        if (!$agentId)
            return;

        $user = User::find($agentId);
        if (!$user)
            return;

        $membership = $this->team->users()->where('users.id', $agentId)->first()?->membership;
        if (!$membership)
            return;

        // Update status to cooldown
        $this->team->users()->updateExistingPivot($agentId, [
            'call_status' => 'cooldown',
            'last_call_ended_at' => now(),
        ]);

        Log::info("Agent entered cooldown", [
            'team_id' => $this->team->id,
            'agent_id' => $agentId,
            'call_id' => $call->call_id,
        ]);
    }

    /**
     * Route a call to an appropriate agent.
     */
    public function routeCall(Contact $contact): array
    {
        $routingService = new CallRoutingService($this->team);
        return $routingService->findAgent($contact);
    }
}
