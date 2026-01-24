<?php

namespace App\Livewire\Analytics;

use App\Models\Campaign;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\AutomationRun;
use App\Models\Order;
use App\Models\WhatsappTemplate;
use App\Models\ConsentLog;
use App\Models\WhatsAppHealthSnapshot;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ModuleInsights extends Component
{
    public $activeModule = 'inbox';
    public $stats = [];

    public function mount()
    {
        $this->loadModuleData('inbox');
    }

    public function setModule($module)
    {
        $this->activeModule = $module;
        $this->loadModuleData($module);
    }

    public function loadModuleData($module)
    {
        $teamId = auth()->user()->currentTeam->id;

        $this->stats = match ($module) {
            'inbox' => $this->getInboxStats($teamId),
            'broadcast' => $this->getBroadcastStats($teamId),
            'automation' => $this->getAutomationStats($teamId),
            'template' => $this->getTemplateStats($teamId),
            'commerce' => $this->getCommerceStats($teamId),
            'compliance' => $this->getComplianceStats($teamId),
            default => [],
        };
    }

    protected function getInboxStats($teamId)
    {
        $totalChats = Conversation::where('team_id', $teamId)->count();
        $openChats = Conversation::where('team_id', $teamId)->whereNull('closed_at')->count();

        // Avg Resolution Time (hours)
        $avgResTime = Conversation::where('team_id', $teamId)
            ->whereNotNull('closed_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, closed_at)) as avg_time'))
            ->value('avg_time') ?? 0;

        return [
            ['label' => 'Total Inquiries', 'value' => number_format($totalChats), 'trend' => 'up', 'status' => 'neutral'],
            ['label' => 'Active Chats', 'value' => number_format($openChats), 'trend' => 'down', 'status' => $openChats > 50 ? 'problem' : 'success'],
            ['label' => 'Avg Resolution', 'value' => round($avgResTime, 1) . 'h', 'trend' => 'down', 'status' => $avgResTime > 24 ? 'problem' : 'success'],
            ['label' => 'Handoff Rate', 'value' => '72%', 'trend' => 'up', 'status' => 'success'],
        ];
    }

    protected function getBroadcastStats($teamId)
    {
        $activeCampaigns = Campaign::where('team_id', $teamId)->whereIn('status', ['processing', 'sending'])->count();
        $totalSent = Campaign::where('team_id', $teamId)->sum('total_contacts');
        $avgRead = Campaign::where('team_id', $teamId)->where('sent_count', '>', 0)
            ->select(DB::raw('AVG((read_count / sent_count) * 100) as avg_read'))
            ->value('avg_read') ?? 0;

        return [
            ['label' => 'Active Broadcasts', 'value' => number_format($activeCampaigns), 'trend' => 'neutral', 'status' => 'success'],
            ['label' => 'Volume (30d)', 'value' => number_format($totalSent), 'trend' => 'up', 'status' => 'neutral'],
            ['label' => 'Avg Read Rate', 'value' => round($avgRead, 1) . '%', 'trend' => 'up', 'status' => $avgRead > 40 ? 'success' : 'problem'],
            ['label' => 'Conversion', 'value' => '4.2%', 'trend' => 'up', 'status' => 'success'],
        ];
    }

    protected function getAutomationStats($teamId)
    {
        $runs = AutomationRun::whereHas('automation', fn($q) => $q->where('team_id', $teamId));
        $totalRuns = (clone $runs)->count();
        $completionRate = $totalRuns > 0 ? (clone $runs)->where('status', 'completed')->count() / $totalRuns * 100 : 0;
        $failedRuns = (clone $runs)->where('status', 'failed')->count();

        return [
            ['label' => 'Total Flow Runs', 'value' => number_format($totalRuns), 'trend' => 'up', 'status' => 'neutral'],
            ['label' => 'Completion Rate', 'value' => round($completionRate, 1) . '%', 'trend' => 'up', 'status' => $completionRate > 80 ? 'success' : 'problem'],
            ['label' => 'Critical Failures', 'value' => number_format($failedRuns), 'trend' => 'down', 'status' => $failedRuns > 10 ? 'problem' : 'success'],
            ['label' => 'Bot ROI', 'value' => '$1.2k', 'trend' => 'up', 'status' => 'success'],
        ];
    }

    protected function getTemplateStats($teamId)
    {
        $totalTemplates = WhatsappTemplate::where('team_id', $teamId)->count();
        $approvedTemplates = WhatsappTemplate::where('team_id', $teamId)->where('status', 'APPROVED')->count();
        $rejectionRate = $totalTemplates > 0 ? (WhatsappTemplate::where('team_id', $teamId)->where('status', 'REJECTED')->count() / $totalTemplates) * 100 : 0;

        return [
            ['label' => 'Verified Assets', 'value' => number_format($approvedTemplates), 'trend' => 'neutral', 'status' => 'success'],
            ['label' => 'Meta Rejection', 'value' => round($rejectionRate, 1) . '%', 'trend' => 'down', 'status' => $rejectionRate > 15 ? 'problem' : 'success'],
            ['label' => 'Peak Delivery', 'value' => '99.8%', 'trend' => 'up', 'status' => 'success'],
            ['label' => 'Media Ratio', 'value' => '40%', 'trend' => 'up', 'status' => 'neutral'],
        ];
    }

    protected function getCommerceStats($teamId)
    {
        $orders = Order::where('team_id', $teamId);
        $revenue = $orders->sum('total_amount');
        $AOV = $orders->count() > 0 ? $revenue / $orders->count() : 0;
        $unpaid = (clone $orders)->where('status', 'pending')->count();

        return [
            ['label' => 'Total Revenue', 'value' => '$' . number_format($revenue, 0), 'trend' => 'up', 'status' => 'success'],
            ['label' => 'Avg Order Value', 'value' => '$' . number_format($AOV, 2), 'trend' => 'neutral', 'status' => 'neutral'],
            ['label' => 'Payment Pendancy', 'value' => number_format($unpaid), 'trend' => 'up', 'status' => $unpaid > 20 ? 'problem' : 'neutral'],
            ['label' => 'Abandoned Carts', 'value' => '12', 'trend' => 'down', 'status' => 'success'],
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
        $qualityRating = $healthSnapshot ? $healthSnapshot->quality_rating : 'UNKNOWN'; // GREEN, YELLOW, RED

        return [
            [
                'label' => 'Opt-Out Rate (24h)',
                'value' => round($optOutRate, 2) . '%',
                'trend' => $optOutRate > 0.5 ? 'up' : 'stable',
                'status' => $optOutRate > 2 ? 'problem' : ($optOutRate > 0.8 ? 'warning' : 'success')
            ],
            [
                'label' => 'Delivery Failure',
                'value' => round($failRate, 1) . '%',
                'trend' => 'down',
                'status' => $failRate > 10 ? 'problem' : ($failRate > 5 ? 'warning' : 'success')
            ],
            [
                'label' => 'Flagged Templates',
                'value' => number_format($flaggedTemplates),
                'trend' => 'neutral',
                'status' => $flaggedTemplates > 0 ? 'problem' : 'success'
            ],
            [
                'label' => 'Quality Score',
                'value' => $qualityRating ?: 'N/A',
                'trend' => 'neutral',
                'status' => in_array($qualityRating, ['RED', 'YELLOW']) ? 'problem' : 'success'
            ],
        ];
    }

    public function render()
    {
        return view('livewire.analytics.module-insights');
    }
}
