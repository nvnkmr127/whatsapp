<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppCall;
use App\Services\CallService;
use App\Services\OutboundPreflightService;
use App\Services\WhatsAppService;
use App\Traits\StandardApiResponses;
use App\Http\Requests\Calls\InitiateCallRequest;
use App\Http\Requests\Calls\CallHistoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CallController extends Controller
{
    use StandardApiResponses;

    public function __construct(
        protected CallService $callService,
        protected WhatsAppService $whatsappService,
        protected OutboundPreflightService $preflightService
    ) {
    }

    /**
     * Initiate an outbound call.
     */
    public function initiate(InitiateCallRequest $request)
    {
        $team = $request->user()->currentTeam;
        
        // Setup services with context
        $this->callService->setTeam($team);
        
        // ── 1. Calculate Current Usage ───────────────────────────────────────
        $usage = $this->callService->checkUsageLimits();
        $minutesUsed = $usage['minutes_used'] ?? 0;

        $minBalance = config('whatsapp.calling.min_balance_to_start', 1.00);

        // ── 2. Run Clear Billing Precedence Preflight ────────────────────────
        $preflight = $this->preflightService->authorize(
            team: $team,
            feature: 'calling',
            limitKey: 'call_minutes',
            currentUsage: $minutesUsed,
            cost: (float) $minBalance
        );

        if (!$preflight['allowed']) {
            return $this->error(
                $preflight['reason'],
                403,
                ['upgrade_required' => in_array($preflight['code'], ['ERR_SUBSCRIPTION_INACTIVE', 'ERR_FEATURE_LOCKED', 'ERR_QUOTA_EXCEEDED'])],
                $preflight['code']
            );
        }

        try {
            $response = $this->callService->initiateCall(
                $request->phone_number,
                $request->options ?? [],
                $request->sdp
            );

            if (!($response['success'] ?? false)) {
                $status = ($response['block_category'] ?? null) ? 422 : 400;
                return $this->error($response['error'] ?? 'Initiation failed', $status);
            }

            return $this->success($response, 'Call initiated successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, null, 'ERR_SERVER_ERROR');
        }
    }

    /**
     * Check eligibility for calling a contact.
     */
    public function checkEligibility(Request $request)
    {
        $request->validate([
            'contact_id' => 'required|integer|exists:contacts,id',
        ]);

        $team = $request->user()->currentTeam;
        $contact = $team->contacts()->findOrFail($request->contact_id);

        try {
            $eligibilityService = new \App\Services\CallEligibilityService($team);
            $eligibility = $eligibilityService->checkEligibility($contact, 'user_initiated', [
                'trigger_source' => 'in_app_action'
            ], true);

            return $this->success($eligibility, 'Eligibility check completed.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, null, 'ERR_SERVER_ERROR');
        }
    }

    /**
     * Answer an incoming call.
     */
    public function answer(Request $request, string $callId)
    {
        $team = $request->user()->currentTeam;
        $this->whatsappService->setTeam($team);

        try {
            $call = WhatsAppCall::where('call_id', $callId)
                ->where('team_id', $team->id)
                ->first();
            
            if (!$call) {
                return $this->error('Call not found.', 404);
            }

            $phoneNumberId = $call->metadata['phone_number_id'] ?? null;
            $response = $this->whatsappService->answerCall($callId, null, $phoneNumberId);

            if (!($response['success'] ?? false)) {
                return $this->error($response['error'] ?? 'Failed to answer call', 400);
            }

            return $this->success($response, 'Call answered successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, null, 'ERR_SERVER_ERROR');
        }
    }

    /**
     * Reject an incoming call.
     */
    public function reject(Request $request, string $callId)
    {
        $team = $request->user()->currentTeam;
        $this->whatsappService->setTeam($team);

        try {
            $response = $this->whatsappService->rejectCall($callId);

            if (!($response['success'] ?? false)) {
                return $this->error($response['error'] ?? 'Failed to reject call', 400);
            }

            return $this->success($response, 'Call rejected successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, null, 'ERR_SERVER_ERROR');
        }
    }

    /**
     * End an active call.
     */
    public function end(Request $request, string $callId)
    {
        $team = $request->user()->currentTeam;
        $this->whatsappService->setTeam($team);

        try {
            $response = $this->whatsappService->endCall($callId);

            if (!($response['success'] ?? false)) {
                return $this->error($response['error'] ?? 'Failed to end call', 400);
            }

            return $this->success($response, 'Call ended successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, null, 'ERR_SERVER_ERROR');
        }
    }

    /**
     * Get call history.
     */
    public function index(CallHistoryRequest $request)
    {
        $team = $request->user()->currentTeam;

        if ($request->filled('contact_id')) {
            $contactExists = $team->contacts()->whereKey($request->contact_id)->exists();
            if (!$contactExists) {
                return $this->error('Contact not found', 404);
            }
        }

        $query = WhatsAppCall::where('team_id', $team->id)
            ->with('contact:id,name,phone_number');

        // Apply filters
        if ($request->has('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min((int) $request->get('per_page', 15), 100);
        $calls = $query->paginate($perPage);

        return $this->paginated($calls, 'Call history retrieved successfully.');
    }

    /**
     * Get call details.
     */
    public function show(Request $request, string $callId)
    {
        $team = $request->user()->currentTeam;
        if (!$team) {
            return $this->error('No team context selected.', 400);
        }

        try {
            $call = WhatsAppCall::where('team_id', $team->id)
                ->where('call_id', $callId)
                ->with('contact', 'conversation')
                ->firstOrFail();

            return $this->success([
                'id' => $call->id,
                'call_id' => $call->call_id,
                'direction' => $call->direction,
                'status' => $call->status,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number,
                'duration_seconds' => $call->duration_seconds,
                'duration_formatted' => $call->formatted_duration,
                'cost_amount' => $call->cost_amount,
                'cost_formatted' => $call->cost_formatted,
                'initiated_at' => $call->initiated_at,
                'answered_at' => $call->answered_at,
                'ended_at' => $call->ended_at,
                'failure_reason' => $call->failure_reason,
                'contact' => $call->contact ? [
                    'id' => $call->contact->id,
                    'name' => $call->contact->name,
                    'phone_number' => $call->contact->phone_number,
                ] : null,
                'conversation_id' => $call->conversation_id,
            ], 'Call details retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error('Call not found', 404);
        }
    }

    /**
     * Get call statistics.
     */
    public function statistics(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (!$team) {
            return $this->error('No team context selected.', 400);
        }
        
        $this->callService->setTeam($team);

        $validator = Validator::make($request->all(), [
            'period' => 'sometimes|in:today,week,month,year',
        ]);
        
        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $period = $request->get('period', 'month');
        $stats = $this->callService->getCallStatistics($period);

        // Add usage limits info
        $limitsInfo = $this->callService->checkUsageLimits();

        return $this->success(
            array_merge($stats, ['usage_limits' => $limitsInfo]),
            'Call statistics retrieved successfully.'
        );
    }

    /**
     * Get active calls.
     */
    public function active(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (!$team) {
            return $this->error('No team context selected.', 400);
        }
        
        $this->callService->setTeam($team);
        $activeCalls = $this->callService->getActiveCalls();

        return $this->success($activeCalls, 'Active calls retrieved successfully.');
    }

    /**
     * Get call history for a specific contact.
     */
    public function contactHistory(Request $request, int $contactId)
    {
        $team = $request->user()->currentTeam;
        if (!$team) {
            return $this->error('No team context selected.', 400);
        }

        $contact = $team->contacts()->findOrFail($contactId);
        $this->callService->setTeam($team);

        $limit = min($request->get('limit', 50), 100);
        $history = $this->callService->getContactCallHistory($contact, $limit);

        return $this->success($history, 'Contact history retrieved successfully.');
    }
}

