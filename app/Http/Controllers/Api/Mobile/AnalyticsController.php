<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get high-level mobile analytics for the team.
     */
    public function dashboard(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (! $team) {
            return response()->json(['error' => 'No team assigned'], 403);
        }

        $now = Carbon::now();

        $request->validate([
            'range' => 'sometimes|string|in:7d,30d,90d',
        ]);

        $range = $request->input('range', '30d');
        $days = 30;
        if ($range === '7d') {
            $days = 7;
        } elseif ($range === '90d') {
            $days = 90;
        }

        // 1. Conversation Stats
        $activeCount = Conversation::where('team_id', $team->id)->whereNull('closed_at')->count();
        $unreadCount = Conversation::where('team_id', $team->id)
            ->whereHas('messages', function ($q) {
                $q->where('direction', 'inbound')->whereNull('read_at');
            })->count();

        // 2. Message Stats (Based on range)
        $outboundCount = Message::where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', $now->copy()->subDays($days))
            ->count();

        $deliveredCount = Message::where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->where('status', 'delivered')
            ->where('created_at', '>=', $now->copy()->subDays($days))
            ->count();

        $readCount = Message::where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->where('status', 'read')
            ->where('created_at', '>=', $now->copy()->subDays($days))
            ->count();

        $deliveryRate = $outboundCount > 0 ? (int) (($deliveredCount / $outboundCount) * 100) : 100;
        $readRate = $deliveredCount > 0 ? (int) (($readCount / $deliveredCount) * 100) : 0;

        // 3. Sent Today
        $sentToday = Message::where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', $now->copy()->startOfDay())
            ->count();

        // 4. Credits
        $credits = $team->wallet->balance ?? 0.00;

        // 5. Message Activity (Daily counts for last 7 days — single query)
        $sevenDaysAgo = $now->copy()->subDays(6)->startOfDay();
        $activityRows = Message::where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', $sevenDaysAgo)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $activityLabels = [];
        $activityValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $activityLabels[] = $date->format('D');
            $activityValues[] = (int) ($activityRows[$date->toDateString()] ?? 0);
        }

        return response()->json([
            'credits' => round((float) $credits, 2),
            'conversations' => [
                'active' => $activeCount,
                'unread' => $unreadCount,
            ],
            'summary' => [
                'sent_today' => $sentToday,
                'total_period' => $outboundCount,
                'total_30d' => $outboundCount,
            ],
            'messages_30d' => [
                'sent' => $outboundCount,
                'delivered' => $deliveredCount,
                'read' => $readCount,
                'delivery_rate' => $deliveryRate,
                'read_rate' => $readRate,
            ],
            'message_activity' => [
                'labels' => $activityLabels,
                'values' => $activityValues,
            ],
            'last_sync' => now()->toISOString(),
        ]);

    }
}
