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
                'message' => __('":template" performs best on :day at :hour with a :rate% read rate (:multiplierx baseline, :confidence confidence).', [
                    'template' => $this->heatmap['selected_template_name'],
                    'day' => __($slot['day_label']),
                    'hour' => $slot['hour_label'],
                    'rate' => number_format($slot['read_rate'], 1),
                    'multiplier' => number_format($slot['multiplier'], 1),
                    'confidence' => strtolower(__($slot['confidence'])),
                ]),
            ];
        }

        if (!empty($this->heatmap['is_low_sample']) && !empty($this->heatmap['selected_template_name'])) {
            $this->insights[] = [
                'type' => 'warning',
                'message' => __('Timing guidance for ":template" is based on only :count sends. Add :gap+ more sends for stronger confidence.', [
                    'template' => $this->heatmap['selected_template_name'],
                    'count' => (int) ($this->heatmap['sample_size'] ?? 0),
                    'gap' => (int) ($this->heatmap['sample_gap'] ?? 0)
                ]),
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

        // Query including manual template sends by checking metadata
        $query = Message::query()
            ->leftJoin('campaigns', 'campaigns.id', '=', 'messages.campaign_id')
            ->where('messages.team_id', $teamId)
            ->where('messages.direction', 'outbound')
            ->where('messages.type', 'template')
            ->whereRaw("{$sentTsExpr} >= ?", [Carbon::now()->subDays(90)]);

        $metaNameExpr = $driver === 'sqlite'
            ? 'JSON_EXTRACT(messages.metadata, "$.template_name")'
            : 'JSON_UNQUOTE(JSON_EXTRACT(messages.metadata, "$.template_name"))';

        $rawRows = $query
            ->selectRaw("COALESCE(campaigns.template_id, 0) as campaign_template_id")
            ->selectRaw("{$metaNameExpr} as meta_template_name")
            ->selectRaw("{$dayExpr} as day_index")
            ->selectRaw("{$hourExpr} as hour_index")
            ->selectRaw('COUNT(*) as sent_count')
            ->selectRaw("SUM(CASE WHEN messages.read_at IS NOT NULL OR messages.status = 'read' THEN 1 ELSE 0 END) as read_count")
            ->groupBy('campaign_template_id', 'meta_template_name', 'day_index', 'hour_index')
            ->get();

        if ($rawRows->isEmpty()) {
            return [];
        }

        // Map names to IDs for manual messages
        $allTemplates = WhatsappTemplate::where('team_id', $teamId)->pluck('id', 'name')->all();
        
        $processedRows = [];
        $templateTotals = [];

        foreach ($rawRows as $row) {
            $tid = (int) $row->campaign_template_id;
            if ($tid === 0 && !empty($row->meta_template_name)) {
                $tid = (int) ($allTemplates[$row->meta_template_name] ?? 0);
            }

            if ($tid === 0) continue; // Skip if we can't resolve template

            $dayIdx = $this->normalizeWeekday((int) $row->day_index, $driver);
            $hourIdx = (int) $row->hour_index;
            $sent = (int) $row->sent_count;
            $read = (int) $row->read_count;

            $key = "{$tid}_{$dayIdx}_{$hourIdx}";
            if (!isset($processedRows[$key])) {
                $processedRows[$key] = [
                    'template_id' => $tid,
                    'day_index' => $dayIdx,
                    'hour_index' => $hourIdx,
                    'sent' => 0,
                    'read' => 0
                ];
            }
            $processedRows[$key]['sent'] += $sent;
            $processedRows[$key]['read'] += $read;

            if (!isset($templateTotals[$tid])) {
                $templateTotals[$tid] = ['sent' => 0, 'read' => 0];
            }
            $templateTotals[$tid]['sent'] += $sent;
            $templateTotals[$tid]['read'] += $read;
        }

        if (empty($templateTotals)) {
            return [];
        }

        $availableIds = array_keys($templateTotals);
        $selectedTemplateId = in_array((int)$selectedTemplateId, $availableIds) 
            ? (int)$selectedTemplateId 
            : (int)collect($templateTotals)->sortByDesc('sent')->keys()->first();

        $templateNames = WhatsappTemplate::whereIn('id', $availableIds)->pluck('name', 'id')->all();

        $templateOptions = collect($templateTotals)
            ->map(function ($totals, $tid) use ($templateNames) {
                $sent = $totals['sent'];
                return [
                    'id' => (int)$tid,
                    'name' => $templateNames[$tid] ?? ('Template #' . $tid),
                    'sent' => $sent,
                    'read_rate' => $sent > 0 ? round(($totals['read'] / $sent) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('sent')
            ->values()
            ->all();

        $dayLabels = [__('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat'), __('Sun')];
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

        foreach ($processedRows as $row) {
            if ((int) $row['template_id'] !== $selectedTemplateId) {
                continue;
            }
            $dayIndex = (int) $row['day_index'];
            $hourIndex = max(0, min(23, (int) $row['hour_index']));
            $sent = (int) $row['sent'];
            $read = (int) $row['read'];
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
