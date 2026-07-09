<?php

namespace App\Services\Mcp\Tools;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\Team;
use App\Services\Mcp\McpTool;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetDashboardAnalyticsTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'get_dashboard_analytics',
            'description' => 'Get local dashboard analytics: message sent/delivered/read counts, delivery rate, read rate, active conversations, unread count, and daily activity for the last 7 days. Uses local database — instant, no Meta API call needed.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'range' => [
                        'type'    => 'string',
                        'enum'    => ['7d', '30d', '90d'],
                        'default' => '30d',
                        'description' => 'Date range for message stats',
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $now  = Carbon::now();
        $days = match ($input['range'] ?? '30d') { '7d' => 7, '90d' => 90, default => 30 };
        $since = $now->copy()->subDays($days);

        $activeCount = Conversation::where('team_id', $team->id)->whereNull('closed_at')->count();
        $unreadCount = Conversation::where('team_id', $team->id)
            ->whereHas('messages', fn($q) => $q->where('direction', 'inbound')->whereNull('read_at'))
            ->count();

        $outbound  = Message::where('team_id', $team->id)->where('direction', 'outbound')->where('created_at', '>=', $since)->count();
        $delivered = Message::where('team_id', $team->id)->where('direction', 'outbound')->where('status', 'delivered')->where('created_at', '>=', $since)->count();
        $read      = Message::where('team_id', $team->id)->where('direction', 'outbound')->where('status', 'read')->where('created_at', '>=', $since)->count();
        $failed    = Message::where('team_id', $team->id)->where('direction', 'outbound')->where('status', 'failed')->where('created_at', '>=', $since)->count();
        $sentToday = Message::where('team_id', $team->id)->where('direction', 'outbound')->where('created_at', '>=', $now->copy()->startOfDay())->count();

        $deliveryRate = $outbound > 0 ? round(($delivered / $outbound) * 100, 1) : 100;
        $readRate     = $delivered > 0 ? round(($read / $delivered) * 100, 1) : 0;

        // 7-day activity chart
        $activityRows = Message::where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', $now->copy()->subDays(6)->startOfDay())
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i);
            $labels[] = $d->format('D');
            $values[] = (int) ($activityRows[$d->toDateString()] ?? 0);
        }

        $credits = $team->wallet->balance ?? 0.00;

        $result = [
            'range'         => ($input['range'] ?? '30d'),
            'credits'       => round((float) $credits, 2),
            'conversations' => ['active' => $activeCount, 'unread' => $unreadCount],
            'messages'      => [
                'sent_today'   => $sentToday,
                'outbound'     => $outbound,
                'delivered'    => $delivered,
                'read'         => $read,
                'failed'       => $failed,
                'delivery_rate'=> $deliveryRate . '%',
                'read_rate'    => $readRate . '%',
            ],
            'activity_7d'   => ['labels' => $labels, 'values' => $values],
        ];

        return [['type' => 'text', 'text' => json_encode($result, JSON_PRETTY_PRINT)]];
    }
}
