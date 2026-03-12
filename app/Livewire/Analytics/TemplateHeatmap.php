<?php

namespace App\Livewire\Analytics;

use App\Models\Message;
use App\Models\WhatsappTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TemplateHeatmap extends Component
{
    public $selectedTemplateId = null;
    public $heatmap = [];
    public $insights = [];

    public function mount(): void
    {
        $this->loadHeatmap();
    }

    public function updatedSelectedTemplateId(): void
    {
        $this->loadHeatmap();
    }

    public function loadHeatmap(): void
    {
        $teamId = (int) auth()->user()->currentTeam->id;
        $this->heatmap = $this->buildHeatmap($teamId, $this->selectedTemplateId);
        $this->selectedTemplateId = $this->heatmap['selected_template_id'] ?? null;
        $this->buildInsights();
    }

    protected function buildInsights(): void
    {
        $this->insights = [];

        if (!empty($this->heatmap['best_slot']) && !empty($this->heatmap['selected_template_name'])) {
            $slot = $this->heatmap['best_slot'];
            $this->insights[] = [
                'type' => 'info',
                'message' => sprintf(
                    '"%s" performs best on %s at %s with a %.1f%% read rate (%sx baseline, %s confidence).',
                    $this->heatmap['selected_template_name'],
                    $slot['day_label'],
                    $slot['hour_label'],
                    $slot['read_rate'],
                    number_format($slot['multiplier'], 1),
                    strtolower($slot['confidence'])
                ),
            ];
        }

        if (!empty($this->heatmap['is_low_sample']) && !empty($this->heatmap['selected_template_name'])) {
            $this->insights[] = [
                'type' => 'warning',
                'message' => sprintf(
                    'Timing guidance for "%s" is based on only %d sends. Add %d+ more sends for stronger confidence.',
                    $this->heatmap['selected_template_name'],
                    (int) ($this->heatmap['sample_size'] ?? 0),
                    (int) ($this->heatmap['sample_gap'] ?? 0)
                ),
            ];
        }
    }

    protected function buildHeatmap(int $teamId, $selectedTemplateId = null): array
    {
        $driver = DB::getDriverName();
        $sentTsExpr = "COALESCE(messages.sent_at, messages.created_at)";
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
            $tid = (int) $row->template_id;
            if (!isset($templateTotals[$tid])) {
                $templateTotals[$tid] = ['sent' => 0, 'read' => 0];
            }
            $templateTotals[$tid]['sent'] += (int) $row->sent_count;
            $templateTotals[$tid]['read'] += (int) $row->read_count;
        }

        if (empty($templateTotals)) {
            return [];
        }

        $availableIds = array_map('intval', array_keys($templateTotals));
        $requestedId = is_numeric($selectedTemplateId) ? (int) $selectedTemplateId : null;

        if ($requestedId && in_array($requestedId, $availableIds, true)) {
            $selectedTemplateId = $requestedId;
        } else {
            $selectedTemplateId = (int) collect($templateTotals)->sortByDesc('sent')->keys()->first();
        }

        $templateNames = WhatsappTemplate::query()
            ->whereIn('id', array_keys($templateTotals))
            ->pluck('name', 'id')
            ->map(fn($n) => trim((string) $n))
            ->all();

        $templateOptions = collect($templateTotals)
            ->map(function (array $totals, $tid) use ($templateNames) {
                $tid = (int) $tid;
                $sent = (int) ($totals['sent'] ?? 0);
                $read = (int) ($totals['read'] ?? 0);
                return [
                    'id' => $tid,
                    'name' => $templateNames[$tid] ?? ('Template #' . $tid),
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
            $dayIndex = $this->normalizeWeekday((int) $row->day_index, $driver);
            $hourIndex = max(0, min(23, (int) $row->hour_index));
            $sent = (int) $row->sent_count;
            $read = (int) $row->read_count;
            $matrix[$dayIndex][$hourIndex] = [
                'hour' => $hourIndex,
                'hour_label' => sprintf('%02d:00', $hourIndex),
                'sent' => $sent,
                'read' => $read,
                'read_rate' => $sent > 0 ? round(($read / $sent) * 100, 1) : 0.0,
                'intensity' => 0,
            ];
        }

        $selectedTotalSent = (int) ($templateTotals[$selectedTemplateId]['sent'] ?? 0);
        $selectedTotalRead = (int) ($templateTotals[$selectedTemplateId]['read'] ?? 0);
        $baselineRate = $selectedTotalSent > 0 ? ($selectedTotalRead / $selectedTotalSent) * 100 : 0.0;
        $minReliableSamples = max(3, (int) floor($selectedTotalSent * 0.03));
        $recommendedSampleSize = 100;
        $sampleGap = max(0, $recommendedSampleSize - $selectedTotalSent);
        $isLowSample = $selectedTotalSent < $recommendedSampleSize;

        $maxRate = 0.0;
        $bestSlot = null;

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
                        'confidence' => $this->slotConfidence((int) $cell['sent'], $selectedTotalSent),
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
            $rowsForView[] = ['day_label' => $dayLabels[$day], 'cells' => $matrix[$day]];
        }

        return [
            'selected_template_id' => $selectedTemplateId,
            'selected_template_name' => $templateNames[$selectedTemplateId] ?? ('Template #' . $selectedTemplateId),
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

    protected function normalizeWeekday(int $dayIndex, string $driver): int
    {
        if ($driver === 'sqlite') {
            return ($dayIndex + 6) % 7;
        }
        return max(0, min(6, $dayIndex));
    }

    protected function slotConfidence(int $slotSamples, int $totalSamples): string
    {
        if ($slotSamples >= 25 || ($totalSamples > 0 && ($slotSamples / $totalSamples) >= 0.12)) {
            return 'High';
        }
        if ($slotSamples >= 10 || ($totalSamples > 0 && ($slotSamples / $totalSamples) >= 0.06)) {
            return 'Medium';
        }
        return 'Low';
    }

    public function render()
    {
        return view('livewire.analytics.template-heatmap');
    }
}
