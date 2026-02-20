<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppCall;
use App\Services\CallService;
use App\Services\EntitlementService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CallController extends Controller
{
    /**
     * Initiate an outbound call.
     */
    public function initiate(\App\Http\Requests\Calls\InitiateCallRequest $request)
    {
        $team = $request->user()->currentTeam;
        // ── 1. Calculate Current Usage ───────────────────────────────────────
        $minutesUsed = WhatsAppCall::where('team_id', $team->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('duration_seconds') / 60;

        // ── 2. Run Clear Billing Precedence Preflight ────────────────────────
        // This explicitly guarantees checks in order:
        // Trial Validity -> Grace State -> Plan Entitlement -> Usage Limit -> Wallet -> Meta Limits
        $preflight = app(\App\Services\OutboundPreflightService::class)->authorize(
            team: $team,
            feature: 'calling',
            limitKey: 'call_minutes',
            currentUsage: $minutesUsed,
            cost: 0.00 // Assuming basic initiation has no upfront fixed cost
        );

        if (!$preflight['allowed']) {
            return response()->json([
                'success' => false,
                'error' => $preflight['reason'],
                'code' => $preflight['code'],
                'upgrade_required' => in_array($preflight['code'], ['ERR_SUBSCRIPTION_INACTIVE', 'ERR_FEATURE_LOCKED', 'ERR_QUOTA_EXCEEDED']),
            ], 403);
        }

        $callService = new CallService($team);

        try {
            $response = $callService->initiateCall(
                $request->phone_number,
                $request->options ?? []
            );

            return response()->json($response, $response['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
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

            return response()->json([
                'success' => true,
                'data' => $eligibility,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Answer an incoming call.
     */
    public function answer(Request $request, string $callId)
    {
        $team = $request->user()->currentTeam;

        try {
            $whatsappService = new WhatsAppService($team);
            $call = \App\Models\WhatsAppCall::where('call_id', $callId)
                ->where('team_id', $team->id)
                ->first();
            $phoneNumberId = $call ? ($call->metadata['phone_number_id'] ?? null) : null;

            $response = $whatsappService->answerCall($callId, null, $phoneNumberId);

            return response()->json($response, $response['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject an incoming call.
     */
    public function reject(Request $request, string $callId)
    {
        $team = $request->user()->currentTeam;

        try {
            $whatsappService = new WhatsAppService($team);
            $response = $whatsappService->rejectCall($callId);

            return response()->json($response, $response['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * End an active call.
     */
    public function end(Request $request, string $callId)
    {
        $team = $request->user()->currentTeam;

        try {
            $whatsappService = new WhatsAppService($team);
            $response = $whatsappService->endCall($callId);

            return response()->json($response, $response['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get call history.
     */
    public function index(\App\Http\Requests\Calls\CallHistoryRequest $request)
    {
        $team = $request->user()->currentTeam;

        if ($request->filled('contact_id')) {
            $contactExists = $team->contacts()->whereKey($request->contact_id)->exists();
            if (!$contactExists) {
                return response()->json([
                    'success' => false,
                    'error' => 'Contact not found',
                ], 404);
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

        return response()->json([
            'success' => true,
            'data' => $calls->items(),
            'pagination' => [
                'current_page' => $calls->currentPage(),
                'last_page' => $calls->lastPage(),
                'per_page' => $calls->perPage(),
                'total' => $calls->total(),
            ],
        ]);
    }

    /**
     * Get call details.
     */
    public function show(Request $request, string $callId)
    {
        $team = $request->user()->currentTeam;
        if (!$team) {
            return response()->json([
                'success' => false,
                'error' => 'No team context',
            ], 400);
        }

        try {
            $call = WhatsAppCall::where('team_id', $team->id)
                ->where('call_id', $callId)
                ->with('contact', 'conversation')
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
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
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Call not found',
            ], 404);
        }
    }

    /**
     * Get call statistics.
     */
    public function statistics(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (!$team) {
            return response()->json([
                'success' => false,
                'error' => 'No team context',
            ], 400);
        }
        $callService = new CallService($team);

        $validator = Validator::make($request->all(), [
            'period' => 'sometimes|in:today,week,month,year',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $period = $request->get('period', 'month');
        $stats = $callService->getCallStatistics($period);

        // Add usage limits info
        $limitsInfo = $callService->checkUsageLimits();

        return response()->json([
            'success' => true,
            'data' => array_merge($stats, [
                'usage_limits' => $limitsInfo,
            ]),
        ]);
    }

    /**
     * Get active calls.
     */
    public function active(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (!$team) {
            return response()->json([
                'success' => false,
                'error' => 'No team context',
            ], 400);
        }
        $callService = new CallService($team);

        $activeCalls = $callService->getActiveCalls();

        return response()->json([
            'success' => true,
            'data' => $activeCalls,
        ]);
    }

    /**
     * Get call history for a specific contact.
     */
    public function contactHistory(Request $request, int $contactId)
    {
        $team = $request->user()->currentTeam;
        if (!$team) {
            return response()->json([
                'success' => false,
                'error' => 'No team context',
            ], 400);
        }

        $contact = $team->contacts()->findOrFail($contactId);
        $callService = new CallService($team);

        $limit = min($request->get('limit', 50), 100);
        $history = $callService->getContactCallHistory($contact, $limit);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
