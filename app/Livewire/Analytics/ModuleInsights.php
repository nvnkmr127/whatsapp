<?php

namespace App\Livewire\Analytics;

use App\Models\AutomationRun;
use App\Models\Campaign;
use App\Models\ConsentLog;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\WhatsAppHealthSnapshot;
use App\Models\WhatsappTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class ModuleInsights extends Component
{
    public $activeModule = 'inbox';

    public $stats = [];

    public $insights = [];

    public $templateHeatmap = [];

    public $selectedTemplateId = null;

    public function mount()
    {
        $this->loadModuleData('inbox');
    }

    public function setModule($module)
    {
        $this->activeModule = $module;
        $this->loadModuleData($module);
    }

    public function updatedSelectedTemplateId($value): void
    {
        if ($this->activeModule !== 'template') {
            return;
        }

        $this->selectedTemplateId = is_numeric($value) ? (int) $value : null;
        $this->loadModuleData('template');
    }

    public function loadModuleData($module)
    {
        $teamId = auth()->user()->currentTeam->id;
        $role = auth()->user()->teamRole(auth()->user()->currentTeam)->key;

        $data = match ($module) {
            'inbox' => $this->getInboxStats($teamId),
            'broadcast' => $this->getBroadcastStats($teamId),
            'automation' => $this->getAutomationStats($teamId),
            'template' => $this->getTemplateStats($teamId),
            'commerce' => $this->getCommerceStats($teamId),
            'compliance' => $this->getComplianceStats($teamId),
            default => ['stats' => [], 'insights' => []],
        };

        // Role-Based Insight Filtering
        $this->stats = $data['stats'];
        $this->templateHeatmap = $data['template_heatmap'] ?? [];
        $this->insights = collect($data['insights'])->filter(function ($insight) use ($role) {
            // Admins see everything
            if ($role === 'admin' || $role === 'owner') {
                return true;
            }

            // Managers don't see financial insights or critical compliance controls
            if ($role === 'manager') {
                return ! in_array($insight['type'], ['money', 'critical']);
            }

            // Agents only see basic warnings related to their direct work (Inbox/Chat)
            if ($role === 'agent') {
                return $insight['type'] === 'warning' && Str::contains($insight['action_url'], ['inbox', 'chat']);
            }

            return false;
        })->values()->all();
    }

    protected function getInboxStats($teamId)
    {
        $totalChats = Conversation::where('team_id', $teamId)->count();
        $openChats = Conversation::where('team_id', $teamId)->whereNull('closed_at')->count();

        // Avg Resolution Time (hours)
        $avgResTimeQuery = Conversation::where('team_id', $teamId)
            ->whereNotNull('closed_at');

        if (config('database.default') === 'sqlite' || config('database.connections.'.config('database.default').'.driver') === 'sqlite') {
            $avgResTimeQuery->select(DB::raw('AVG((JULIANDAY(closed_at) - JULIANDAY(created_at)) * 24) as avg_time'));
        } else {
            $avgResTimeQuery->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, closed_at)) as avg_time'));
        }

        $avgResTime = $avgResTimeQuery->value('avg_time') ?? 0;

        $insights = [];
        if ($avgResTime > 4) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Response times are averaging '.round($avgResTime).'h, which is above the 4h target.',
                'action_label' => 'Enable Auto-Reply',
                'action_url' => '#automations',
            ];
        }
        if ($openChats > 50) {
            $insights[] = [
                'type' => 'critical',
                'message' => 'High volume of '.$openChats.' active chats requires immediate triage.',
                'action_label' => 'Go to Inbox',
                'action_url' => '#inbox',
            ];
        }

        return [
            'stats' => [
                ['label' => 'Total Inquiries', 'value' => number_format($totalChats), 'trend' => 'up', 'status' => 'neutral'],
                ['label' => 'Active Chats', 'value' => number_format($openChats), 'trend' => 'down', 'status' => $openChats > 50 ? 'problem' : 'success'],
                ['label' => 'Avg Resolution', 'value' => round($avgResTime, 1).'h', 'trend' => 'down', 'status' => $avgResTime > 24 ? 'problem' : 'success'],
                ['label' => 'Handoff Rate', 'value' => '72%', 'trend' => 'up', 'status' => 'success'],
            ],
            'insights' => $insights,
        ];
    }

    protected function getBroadcastStats($teamId)
    {
        $activeCampaigns = Campaign::where('team_id', $teamId)->whereIn('status', ['processing', 'sending'])->count();
        $totalSent = Campaign::where('team_id', $teamId)->sum('total_contacts');
        $avgRead = Campaign::where('team_id', $teamId)->where('sent_count', '>', 0)
            ->select(DB::raw('AVG((read_count / sent_count) * 100) as avg_read'))
            ->value('avg_read') ?? 0;

        $insights = [];
        if ($avgRead < 40) {
            $insights[] = [
                'type' => 'info',
                'message' => 'Read rates ('.round($avgRead).'%) are below industry average (45%).',
                'action_label' => 'Optimize Templates',
                'action_url' => '#templates',
            ];
        }
        if ($activeCampaigns > 5) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Multiple campaigns running simultaneously may trigger rate limits.',
                'action_label' => 'View Schedule',
                'action_url' => '#campaigns',
            ];
        }

        return [
            'stats' => [
                ['label' => 'Active Broadcasts', 'value' => number_format($activeCampaigns), 'trend' => 'neutral', 'status' => 'success'],
                ['label' => 'Volume (30d)', 'value' => number_format($totalSent), 'trend' => 'up', 'status' => 'neutral'],
                ['label' => 'Avg Read Rate', 'value' => round($avgRead, 1).'%', 'trend' => 'up', 'status' => $avgRead > 40 ? 'success' : 'problem'],
                ['label' => 'Conversion', 'value' => '4.2%', 'trend' => 'up', 'status' => 'success'],
            ],
            'insights' => $insights,
        ];
    }

    protected function getAutomationStats($teamId)
    {
        $runs = AutomationRun::whereHas('automation', fn ($q) => $q->where('team_id', $teamId));
        $totalRuns = (clone $runs)->count();
        $completionRate = $totalRuns > 0 ? (clone $runs)->where('status', 'completed')->count() / $totalRuns * 100 : 0;
        $failedRuns = (clone $runs)->where('status', 'failed')->count();

        $insights = [];
        if ($failedRuns > 10) {
            $insights[] = [
                'type' => 'critical',
                'message' => $failedRuns.' flows failed this week due to API errors.',
                'action_label' => 'Fix Broken Nodes',
                'action_url' => '#automations',
            ];
        }
        if ($completionRate < 60) {
            $insights[] = [
                'type' => 'info',
                'message' => 'High drop-off detected in "Welcome Flow".',
                'action_label' => 'Edit Flow',
                'action_url' => '#automations-editor',
            ];
        }

        return [
            'stats' => [
                ['label' => 'Total Flow Runs', 'value' => number_format($totalRuns), 'trend' => 'up', 'status' => 'neutral'],
                ['label' => 'Completion Rate', 'value' => round($completionRate, 1).'%', 'trend' => 'up', 'status' => $completionRate > 80 ? 'success' : 'problem'],
                ['label' => 'Critical Failures', 'value' => number_format($failedRuns), 'trend' => 'down', 'status' => $failedRuns > 10 ? 'problem' : 'success'],
                ['label' => 'Bot ROI', 'value' => get_setting('currency_symbol', '$').'1.2k', 'trend' => 'up', 'status' => 'success'],
            ],
            'insights' => $insights,
        ];
    }

    protected function getTemplateStats($teamId)
    {
        $totalTemplates = WhatsappTemplate::where('team_id', $teamId)->count();
        $approvedTemplates = WhatsappTemplate::where('team_id', $teamId)->where('status', 'APPROVED')->count();
        $rejectionRate = $totalTemplates > 0 ? (WhatsappTemplate::where('team_id', $teamId)->where('status', 'REJECTED')->count() / $totalTemplates) * 100 : 0;
        $templateHeatmap = $this->buildTemplatePerformanceHeatmap((int) $teamId, $this->selectedTemplateId);
        $this->selectedTemplateId = $templateHeatmap['selected_template_id'] ?? null;

        $insights = [];
        if ($rejectionRate > 10) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'High rejection rate ('.round($rejectionRate).'%). Avoid promotional words in "Utility" category.',
                'action_label' => 'Review Guidelines',
                'action_url' => 'https://business.facebook.com/policies/whatsapp',
            ];
        }

        if (! empty($templateHeatmap['best_slot']) && ! empty($templateHeatmap['selected_template_name'])) {
            $slot = $templateHeatmap['best_slot'];
            $insights[] = [
                'type' => 'info',
                'message' => sprintf(
                    '"%s" performs best on %s at %s with a %.1f%% read rate (%sx baseline, %s confidence).',
                    $templateHeatmap['selected_template_name'],
                    $slot['day_label'],
                    $slot['hour_label'],
                    $slot['read_rate'],
                    number_format($slot['multiplier'], 1),
                    strtolower($slot['confidence'])
                ),
                'action_label' => 'Plan Next Send',
                'action_url' => '#campaigns',
            ];
        }

        if (! empty($templateHeatmap['is_low_sample']) && ! empty($templateHeatmap['selected_template_name'])) {
            $insights[] = [
                'type' => 'warning',
                'message' => sprintf(
                    'Timing guidance for "%s" is based on only %d sends. Add %d+ more sends for stronger confidence.',
                    $templateHeatmap['selected_template_name'],
                    (int) ($templateHeatmap['sample_size'] ?? 0),
                    (int) ($templateHeatmap['sample_gap'] ?? 0)
                ),
                'action_label' => 'Increase Sample Size',
                'action_url' => '#campaigns',
            ];
        }

        return [
            'stats' => [
                ['label' => 'Verified Assets', 'value' => number_format($approvedTemplates), 'trend' => 'neutral', 'status' => 'success'],
                ['label' => 'Meta Rejection', 'value' => round($rejectionRate, 1).'%', 'trend' => 'down', 'status' => $rejectionRate > 15 ? 'problem' : 'success'],
                ['label' => 'Peak Delivery', 'value' => '99.8%', 'trend' => 'up', 'status' => 'success'],
                ['label' => 'Media Ratio', 'value' => '40%', 'trend' => 'up', 'status' => 'neutral'],
            ],
            'insights' => $insights,
            'template_heatmap' => $templateHeatmap,
        ];
    }

    protected function buildTemplatePerformanceHeatmap(int $teamId, $selectedTemplateId = null): array
    {
        $driver = DB::getDriverName();
        $sentTsExpr = 'COALESCE(messages.sent_at, messages.created_at)';
        $dayExpr = $driver === 'sqlite'
            ? "CAST(strftime('%w', {$sentTsExpr}) AS INTEGER)"
            : "WEEKDAY({$sentTsExpr})";
        $hourExpr = $driver === 'sqlite'
            ? "CAST(strftime('%H', {$sentTsExpr}) AS INTEGER)"
            : "HOUR({$sentTsExpr})";

        $rows = Message::query()
            ->join('campaigns', 'campaigns.id', '=', 'messages.campaign_id')
            ->where('messages.team_id', $teamId)
            ->where('messages.direction', 'outbound')
            ->where('messages.type', 'template')
            ->whereNotNull('campaigns.template_id')
            ->whereNotNull('messages.campaign_id')
            ->whereRaw("{$sentTsExpr} >= ?", [Carbon::now()->subDays(90)])
            ->selectRaw('campaigns.template_id')
            ->selectRaw("{$dayExpr} as day_index")
            ->selectRaw("{$hourExpr} as hour_index")
            ->selectRaw('COUNT(*) as sent_count')
            ->selectRaw("SUM(CASE WHEN messages.read_at IS NOT NULL OR messages.status = 'read' THEN 1 ELSE 0 END) as read_count")
            ->groupBy('campaigns.template_id')
            ->groupByRaw($dayExpr)
            ->groupByRaw($hourExpr)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $templateTotals = [];
        foreach ($rows as $row) {
            $templateId = (int) $row->template_id;
            if (! isset($templateTotals[$templateId])) {
                $templateTotals[$templateId] = ['sent' => 0, 'read' => 0];
            }

            $templateTotals[$templateId]['sent'] += (int) $row->sent_count;
            $templateTotals[$templateId]['read'] += (int) $row->read_count;
        }

        if (empty($templateTotals)) {
            return [];
        }

        $availableTemplateIds = array_map('intval', array_keys($templateTotals));
        $requestedTemplateId = is_numeric($selectedTemplateId) ? (int) $selectedTemplateId : null;

        if ($requestedTemplateId && in_array($requestedTemplateId, $availableTemplateIds, true)) {
            $selectedTemplateId = $requestedTemplateId;
        } else {
            $selectedTemplateId = (int) collect($templateTotals)
                ->sortByDesc('sent')
                ->keys()
                ->first();
        }

        $templateNames = WhatsappTemplate::query()
            ->whereIn('id', array_keys($templateTotals))
            ->pluck('name', 'id')
            ->map(fn ($name) => trim((string) $name))
            ->all();

        $templateOptions = collect($templateTotals)
            ->map(function (array $totals, $templateId) use ($templateNames) {
                $templateId = (int) $templateId;
                $sent = (int) ($totals['sent'] ?? 0);
                $read = (int) ($totals['read'] ?? 0);

                return [
                    'id' => $templateId,
                    'name' => $templateNames[$templateId] ?? ('Template #'.$templateId),
                    'sent' => $sent,
                    'read_rate' => $sent > 0 ? round(($read / $sent) * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('sent')
            ->values()
            ->all();

        $dayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $matrix = [];

        for ($day = 0; $day < 7; $day++) {
            $matrix[$day] = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $matrix[$day][$hour] = [
                    'hour' => $hour,
                    'hour_label' => sprintf('%02d:00', $hour),
                    'sent' => 0,
                    'read' => 0,
                    'read_rate' => 0.0,
                    'intensity' => 0,
                ];
            }
        }

        foreach ($rows as $row) {
            if ((int) $row->template_id !== $selectedTemplateId) {
                continue;
            }

            $dayIndex = $this->normalizeWeekdayIndex((int) $row->day_index, $driver);
            $hourIndex = max(0, min(23, (int) $row->hour_index));

            $sent = (int) $row->sent_count;
            $read = (int) $row->read_count;
            $rate = $sent > 0 ? round(($read / $sent) * 100, 1) : 0.0;

            $matrix[$dayIndex][$hourIndex] = [
                'hour' => $hourIndex,
                'hour_label' => sprintf('%02d:00', $hourIndex),
                'sent' => $sent,
                'read' => $read,
                'read_rate' => $rate,
                'intensity' => 0,
            ];
        }

        $maxRate = 0.0;
        $bestSlot = null;
        $selectedTotalSent = (int) ($templateTotals[$selectedTemplateId]['sent'] ?? 0);
        $selectedTotalRead = (int) ($templateTotals[$selectedTemplateId]['read'] ?? 0);
        $baselineRate = $selectedTotalSent > 0 ? ($selectedTotalRead / $selectedTotalSent) * 100 : 0.0;
        $minReliableSamples = max(3, (int) floor($selectedTotalSent * 0.03));
        $recommendedSampleSize = 100;
        $sampleGap = max(0, $recommendedSampleSize - $selectedTotalSent);
        $isLowSample = $selectedTotalSent < $recommendedSampleSize;

        for ($day = 0; $day < 7; $day++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $cell = $matrix[$day][$hour];
                $maxRate = max($maxRate, (float) $cell['read_rate']);

                if ($cell['sent'] < $minReliableSamples) {
                    continue;
                }

                if ($bestSlot === null || $cell['read_rate'] > $bestSlot['read_rate']) {
                    $bestSlot = [
                        'day_label' => $dayLabels[$day],
                        'hour_label' => $cell['hour_label'],
                        'read_rate' => (float) $cell['read_rate'],
                        'sent' => (int) $cell['sent'],
                        'multiplier' => $baselineRate > 0 ? round($cell['read_rate'] / $baselineRate, 2) : 0,
                        'confidence' => $this->calculateSlotConfidence((int) $cell['sent'], $selectedTotalSent),
                    ];
                }
            }
        }

        if ($maxRate > 0) {
            for ($day = 0; $day < 7; $day++) {
                for ($hour = 0; $hour < 24; $hour++) {
                    $rate = (float) $matrix[$day][$hour]['read_rate'];
                    $matrix[$day][$hour]['intensity'] = (int) round(($rate / $maxRate) * 100);
                }
            }
        }

        $rowsForView = [];
        for ($day = 0; $day < 7; $day++) {
            $rowsForView[] = [
                'day_label' => $dayLabels[$day],
                'cells' => $matrix[$day],
            ];
        }

        return [
            'selected_template_id' => $selectedTemplateId,
            'selected_template_name' => $templateNames[$selectedTemplateId] ?? ('Template #'.$selectedTemplateId),
            'template_options' => $templateOptions,
            'rows' => $rowsForView,
            'baseline_rate' => round($baselineRate, 1),
            'best_slot' => $bestSlot,
            'window_days' => 90,
            'sample_size' => $selectedTotalSent,
            'recommended_sample_size' => $recommendedSampleSize,
            'sample_gap' => $sampleGap,
            'is_low_sample' => $isLowSample,
        ];
    }

    protected function normalizeWeekdayIndex(int $dayIndex, string $driver): int
    {
        if ($driver === 'sqlite') {
            // SQLite day index starts on Sunday (0). Shift to Monday-first.
            return ($dayIndex + 6) % 7;
        }

        // MySQL WEEKDAY already returns Monday=0..Sunday=6.
        return max(0, min(6, $dayIndex));
    }

    protected function calculateSlotConfidence(int $slotSamples, int $totalSamples): string
    {
        if ($slotSamples >= 25 || ($totalSamples > 0 && ($slotSamples / $totalSamples) >= 0.12)) {
            return 'High';
        }

        if ($slotSamples >= 10 || ($totalSamples > 0 && ($slotSamples / $totalSamples) >= 0.06)) {
            return 'Medium';
        }

        return 'Low';
    }

    protected function getCommerceStats($teamId)
    {
        $orders = Order::where('team_id', $teamId);
        $revenue = $orders->sum('total_amount');
        $AOV = $orders->count() > 0 ? $revenue / $orders->count() : 0;
        $unpaid = (clone $orders)->where('status', 'pending')->count();

        $insights = [];
        if ($unpaid > 10) {
            $insights[] = [
                'type' => 'money',
                'message' => $unpaid.' orders are pending payment. Potential revenue: '.get_setting('currency_symbol', '$').number_format($unpaid * $AOV),
                'action_label' => 'Send Reminders',
                'action_url' => '#orders',
            ];
        }

        return [
            'stats' => [
                ['label' => 'Total Revenue', 'value' => get_setting('currency_symbol', '$').number_format($revenue, 0), 'trend' => 'up', 'status' => 'success'],
                ['label' => 'Avg Order Value', 'value' => get_setting('currency_symbol', '$').number_format($AOV, 2), 'trend' => 'neutral', 'status' => 'neutral'],
                ['label' => 'Payment Pendancy', 'value' => number_format($unpaid), 'trend' => 'up', 'status' => $unpaid > 20 ? 'problem' : 'neutral'],
                ['label' => 'Abandoned Carts', 'value' => '12', 'trend' => 'down', 'status' => 'success'],
            ],
            'insights' => $insights,
        ];
    }

    protected function getComplianceStats($teamId)
    {
        // 1. Opt-Out Rate (Rolling 24h)
        $dailySent = Message::where('team_id', $teamId)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $dailyOptOuts = ConsentLog::where('team_id', $teamId)
            ->where('action', 'OPT_OUT')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $optOutRate = $dailySent > 0 ? ($dailyOptOuts / $dailySent) * 100 : 0;

        // 2. Failed Message Rate (Rolling 24h)
        $dailyFailed = Message::where('team_id', $teamId)
            ->where('direction', 'outbound')
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $failRate = $dailySent > 0 ? ($dailyFailed / $dailySent) * 100 : 0;

        // 3. Template Misuse Signals
        // Look for quality pauses or rejections
        $flaggedTemplates = WhatsappTemplate::where('team_id', $teamId)
            ->whereIn('status', ['REJECTED', 'PAUSED', 'DISABLED'])
            ->count();

        // 4. Over-sending (Usage vs Tier Limit)
        $healthSnapshot = WhatsAppHealthSnapshot::where('team_id', $teamId)
            ->latest('snapshot_at')
            ->first();

        $usagePercent = $healthSnapshot ? $healthSnapshot->usage_percent : 0;
        $qualityRating = $healthSnapshot ? $healthSnapshot->quality_rating : 'UNKNOWN';

        $insights = [];
        if ($optOutRate > 0.8) {
            $insights[] = [
                'type' => 'critical',
                'message' => 'Safety Alert: Opt-out rate '.round($optOutRate, 2).'% is critical. Immediate cooldown advised.',
                'action_label' => 'Pause All Broadcasts',
                'action_url' => '#campaigns',
            ];
        }
        if ($qualityRating === 'YELLOW') {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Quality Score dropped to YELLOW. Tier downgrade risk detected.',
                'action_label' => 'View Health Health',
                'action_url' => '#health',
            ];
        }

        return [
            'stats' => [
                [
                    'label' => 'Opt-Out Rate (24h)',
                    'value' => round($optOutRate, 2).'%',
                    'trend' => $optOutRate > 0.5 ? 'up' : 'stable',
                    'status' => $optOutRate > 2 ? 'problem' : ($optOutRate > 0.8 ? 'warning' : 'success'),
                ],
                [
                    'label' => 'Delivery Failure',
                    'value' => round($failRate, 1).'%',
                    'trend' => 'down',
                    'status' => $failRate > 10 ? 'problem' : ($failRate > 5 ? 'warning' : 'success'),
                ],
                [
                    'label' => 'Flagged Templates',
                    'value' => number_format($flaggedTemplates),
                    'trend' => 'neutral',
                    'status' => $flaggedTemplates > 0 ? 'problem' : 'success',
                ],
                [
                    'label' => 'Quality Score',
                    'value' => $qualityRating ?: 'N/A',
                    'trend' => 'neutral',
                    'status' => in_array($qualityRating, ['RED', 'YELLOW']) ? 'problem' : 'success',
                ],
            ],
            'insights' => $insights,
        ];
    }

    public function render()
    {
        return view('livewire.analytics.module-insights');
    }
}
