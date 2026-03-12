<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\AutomationStepLedger;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AutomationAnalyticsService
{
    private const ATTRIBUTION_WINDOW_DAYS = 7;

    public function buildDashboard(Automation $automation): array
    {
        $runsQuery = AutomationRun::query()->where('automation_id', $automation->id);

        $totalRuns = (clone $runsQuery)->count();
        $completedRuns = (clone $runsQuery)->where('status', 'completed')->count();
        $failedRuns = (clone $runsQuery)->where('status', 'failed')->count();

        $completionRate = $totalRuns > 0 ? round(($completedRuns / $totalRuns) * 100, 2) : 0.0;
        $failureRate = $totalRuns > 0 ? round(($failedRuns / $totalRuns) * 100, 2) : 0.0;

        $funnel = $this->buildFunnelData($automation);
        $stepTiming = $this->buildAverageStepTiming($automation);
        $failureNode = $this->findMostCommonFailureNode($automation);
        $revenue = $this->estimateAttributedRevenue($automation);

        return [
            'summary' => [
                'total_runs' => $totalRuns,
                'completed_runs' => $completedRuns,
                'failed_runs' => $failedRuns,
                'completion_rate' => $completionRate,
                'failure_rate' => $failureRate,
            ],
            'funnel' => $funnel,
            'average_step_timing' => $stepTiming,
            'most_common_failure_node' => $failureNode,
            'revenue' => $revenue,
        ];
    }

    private function buildFunnelData(Automation $automation): array
    {
        $flowData = $automation->flow_data ?? [];
        $nodeLabels = $this->buildNodeLabels($flowData);
        $orderedNodeIds = $this->resolveNodeOrder($flowData);

        $ledgerPairs = AutomationStepLedger::query()
            ->join('automation_runs', 'automation_runs.id', '=', 'automation_step_ledger.automation_run_id')
            ->where('automation_runs.automation_id', $automation->id)
            ->where('automation_step_ledger.status', 'success')
            ->where('automation_step_ledger.execution_key', 'not like', '%_wait')
            ->get([
                'automation_step_ledger.automation_run_id',
                'automation_step_ledger.node_id',
            ]);

        $runIdsWithLedger = [];
        $reachSetByNode = [];

        foreach ($ledgerPairs as $pair) {
            $runId = (int) $pair->automation_run_id;
            $nodeId = (string) $pair->node_id;

            $runIdsWithLedger[$runId] = true;
            $reachSetByNode[$nodeId] ??= [];
            $reachSetByNode[$nodeId][$runId] = true;
        }

        // Fallback to execution history for runs that do not have ledger records.
        $historyRuns = AutomationRun::query()
            ->where('automation_id', $automation->id)
            ->when(!empty($runIdsWithLedger), fn($q) => $q->whereNotIn('id', array_keys($runIdsWithLedger)))
            ->get(['id', 'execution_history']);

        foreach ($historyRuns as $run) {
            $runId = (int) $run->id;
            foreach ($this->extractHistoryNodes($run->execution_history) as $nodeId) {
                $reachSetByNode[$nodeId] ??= [];
                $reachSetByNode[$nodeId][$runId] = true;
            }
        }

        $steps = [];
        $previousReached = null;

        foreach ($orderedNodeIds as $index => $nodeId) {
            $reached = isset($reachSetByNode[$nodeId]) ? count($reachSetByNode[$nodeId]) : 0;
            $dropoffCount = null;
            $dropoffRate = null;

            if ($previousReached !== null) {
                $dropoffCount = max(0, $previousReached - $reached);
                $dropoffRate = $previousReached > 0 ? round(($dropoffCount / $previousReached) * 100, 2) : 0.0;
            }

            $steps[] = [
                'step' => $index + 1,
                'node_id' => $nodeId,
                'label' => $nodeLabels[$nodeId] ?? $nodeId,
                'reached_contacts' => $reached,
                'dropoff_count' => $dropoffCount,
                'dropoff_rate' => $dropoffRate,
            ];

            $previousReached = $reached;
        }

        return $steps;
    }

    private function buildAverageStepTiming(Automation $automation): array
    {
        $flowData = $automation->flow_data ?? [];
        $nodeLabels = $this->buildNodeLabels($flowData);

        $ledgerRows = AutomationStepLedger::query()
            ->join('automation_runs', 'automation_runs.id', '=', 'automation_step_ledger.automation_run_id')
            ->where('automation_runs.automation_id', $automation->id)
            ->where('automation_step_ledger.status', 'success')
            ->where('automation_step_ledger.execution_key', 'not like', '%_wait')
            ->orderBy('automation_step_ledger.automation_run_id')
            ->orderBy('automation_step_ledger.created_at')
            ->get([
                'automation_step_ledger.automation_run_id',
                'automation_step_ledger.node_id',
                'automation_step_ledger.created_at',
            ]);

        $pairTotals = [];
        $previousByRun = [];
        $runIdsWithLedger = [];

        foreach ($ledgerRows as $row) {
            $runId = (int) $row->automation_run_id;
            $currentNodeId = (string) $row->node_id;
            $currentTs = Carbon::parse($row->created_at)->getTimestamp();
            $runIdsWithLedger[$runId] = true;

            if (!isset($previousByRun[$runId])) {
                $previousByRun[$runId] = ['node_id' => $currentNodeId, 'ts' => $currentTs];
                continue;
            }

            $prevNodeId = $previousByRun[$runId]['node_id'];
            $prevTs = $previousByRun[$runId]['ts'];
            $diffSeconds = $currentTs - $prevTs;

            if ($diffSeconds >= 0 && $prevNodeId !== $currentNodeId) {
                $key = $prevNodeId . '>>' . $currentNodeId;

                if (!isset($pairTotals[$key])) {
                    $pairTotals[$key] = [
                        'from_node_id' => $prevNodeId,
                        'to_node_id' => $currentNodeId,
                        'total_seconds' => 0,
                        'samples' => 0,
                    ];
                }

                $pairTotals[$key]['total_seconds'] += $diffSeconds;
                $pairTotals[$key]['samples']++;
            }

            $previousByRun[$runId] = ['node_id' => $currentNodeId, 'ts' => $currentTs];
        }

        // Fallback timing from execution_history for runs missing ledger transitions.
        $historyRuns = AutomationRun::query()
            ->where('automation_id', $automation->id)
            ->when(!empty($runIdsWithLedger), fn($q) => $q->whereNotIn('id', array_keys($runIdsWithLedger)))
            ->get(['id', 'execution_history']);

        foreach ($historyRuns as $run) {
            $transitions = $this->extractHistoryTransitions($run->execution_history);

            foreach ($transitions as $transition) {
                $key = $transition['from_node_id'] . '>>' . $transition['to_node_id'];

                if (!isset($pairTotals[$key])) {
                    $pairTotals[$key] = [
                        'from_node_id' => $transition['from_node_id'],
                        'to_node_id' => $transition['to_node_id'],
                        'total_seconds' => 0,
                        'samples' => 0,
                    ];
                }

                $pairTotals[$key]['total_seconds'] += $transition['seconds'];
                $pairTotals[$key]['samples']++;
            }
        }

        $rows = [];
        foreach ($pairTotals as $pair) {
            if ($pair['samples'] === 0) {
                continue;
            }

            $avgSeconds = (int) round($pair['total_seconds'] / $pair['samples']);

            $rows[] = [
                'from_node_id' => $pair['from_node_id'],
                'to_node_id' => $pair['to_node_id'],
                'from_label' => $nodeLabels[$pair['from_node_id']] ?? $pair['from_node_id'],
                'to_label' => $nodeLabels[$pair['to_node_id']] ?? $pair['to_node_id'],
                'avg_seconds' => $avgSeconds,
                'avg_human' => $this->humanDuration($avgSeconds),
                'samples' => $pair['samples'],
            ];
        }

        usort($rows, function (array $a, array $b) {
            return $b['samples'] <=> $a['samples'];
        });

        return $rows;
    }

    private function findMostCommonFailureNode(Automation $automation): ?array
    {
        $flowData = $automation->flow_data ?? [];
        $nodeLabels = $this->buildNodeLabels($flowData);

        $row = AutomationStepLedger::query()
            ->join('automation_runs', 'automation_runs.id', '=', 'automation_step_ledger.automation_run_id')
            ->where('automation_runs.automation_id', $automation->id)
            ->where('automation_step_ledger.status', 'failed')
            ->selectRaw('automation_step_ledger.node_id, COUNT(*) as failures')
            ->groupBy('automation_step_ledger.node_id')
            ->orderByDesc('failures')
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'node_id' => $row->node_id,
            'label' => $nodeLabels[$row->node_id] ?? $row->node_id,
            'failures' => (int) $row->failures,
        ];
    }

    private function estimateAttributedRevenue(Automation $automation): array
    {
        $windowSeconds = self::ATTRIBUTION_WINDOW_DAYS * 24 * 60 * 60;

        $runsByContact = AutomationRun::query()
            ->where('automation_id', $automation->id)
            ->orderBy('contact_id')
            ->orderBy('created_at')
            ->get(['contact_id', 'created_at'])
            ->groupBy('contact_id')
            ->map(function ($runs) {
                return $runs
                    ->map(fn($run) => Carbon::parse($run->created_at)->getTimestamp())
                    ->values()
                    ->all();
            })
            ->all();

        if (empty($runsByContact)) {
            return [
                'attributed_orders' => 0,
                'attributed_revenue' => 0.0,
                'window_days' => self::ATTRIBUTION_WINDOW_DAYS,
            ];
        }

        $orders = Order::query()
            ->where('team_id', $automation->team_id)
            ->whereIn('contact_id', array_keys($runsByContact))
            ->get(['contact_id', 'total_amount', 'created_at']);

        $attributedRevenue = 0.0;
        $attributedOrders = 0;

        foreach ($orders as $order) {
            $contactRuns = $runsByContact[$order->contact_id] ?? null;
            if (!$contactRuns) {
                continue;
            }

            $orderTs = Carbon::parse($order->created_at)->getTimestamp();

            if ($this->hasRunWithinWindow($contactRuns, $orderTs, $windowSeconds)) {
                $attributedOrders++;
                $attributedRevenue += (float) $order->total_amount;
            }
        }

        return [
            'attributed_orders' => $attributedOrders,
            'attributed_revenue' => round($attributedRevenue, 2),
            'window_days' => self::ATTRIBUTION_WINDOW_DAYS,
        ];
    }

    private function hasRunWithinWindow(array $contactRuns, int $orderTs, int $windowSeconds): bool
    {
        for ($i = count($contactRuns) - 1; $i >= 0; $i--) {
            $runTs = $contactRuns[$i];

            if ($runTs > $orderTs) {
                continue;
            }

            return ($orderTs - $runTs) <= $windowSeconds;
        }

        return false;
    }

    private function buildNodeLabels(array $flowData): array
    {
        $labels = [];

        foreach (($flowData['nodes'] ?? []) as $node) {
            if (empty($node['id'])) {
                continue;
            }

            $labels[$node['id']] = $this->formatNodeLabel($node);
        }

        return $labels;
    }

    private function formatNodeLabel(array $node): string
    {
        $type = Str::title(str_replace('_', ' ', (string) ($node['type'] ?? 'Step')));
        $data = $node['data'] ?? [];

        $candidate = $data['label']
            ?? $data['title']
            ?? $data['name']
            ?? $data['question']
            ?? $data['text']
            ?? null;

        if (!$candidate) {
            return $type;
        }

        return $type . ': ' . Str::limit(trim(strip_tags((string) $candidate)), 40);
    }

    private function resolveNodeOrder(array $flowData): array
    {
        $nodes = $flowData['nodes'] ?? [];
        $edges = $flowData['edges'] ?? [];

        if (empty($nodes)) {
            return [];
        }

        $allNodeIds = [];
        $adjacency = [];

        foreach ($nodes as $node) {
            if (empty($node['id'])) {
                continue;
            }

            $allNodeIds[] = $node['id'];
            $adjacency[$node['id']] = [];
        }

        foreach ($edges as $edge) {
            $source = $edge['source'] ?? null;
            $target = $edge['target'] ?? null;

            if ($source && $target && isset($adjacency[$source])) {
                $adjacency[$source][] = $target;
            }
        }

        $startNode = null;
        foreach ($nodes as $node) {
            if (($node['type'] ?? null) === 'trigger' && !empty($node['id'])) {
                $startNode = $node['id'];
                break;
            }
        }

        $ordered = [];
        $seen = [];
        $queue = [];

        if ($startNode) {
            $queue[] = $startNode;
        }

        while (!empty($queue)) {
            $current = array_shift($queue);

            if (isset($seen[$current])) {
                continue;
            }

            $seen[$current] = true;
            $ordered[] = $current;

            foreach ($adjacency[$current] ?? [] as $target) {
                if (!isset($seen[$target])) {
                    $queue[] = $target;
                }
            }
        }

        foreach ($allNodeIds as $nodeId) {
            if (!isset($seen[$nodeId])) {
                $ordered[] = $nodeId;
            }
        }

        return $ordered;
    }

    private function humanDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }

        if ($seconds < 3600) {
            return round($seconds / 60, 1) . 'm';
        }

        if ($seconds < 86400) {
            return round($seconds / 3600, 1) . 'h';
        }

        return round($seconds / 86400, 1) . 'd';
    }

    private function extractHistoryNodes($executionHistory): array
    {
        if (!is_array($executionHistory)) {
            return [];
        }

        $nodes = [];
        foreach ($executionHistory as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $nodeId = $entry['node_id'] ?? null;
            if (!$nodeId) {
                continue;
            }

            $nodes[$nodeId] = true;
        }

        return array_keys($nodes);
    }

    private function extractHistoryTransitions($executionHistory): array
    {
        if (!is_array($executionHistory)) {
            return [];
        }

        $orderedEntries = [];

        foreach ($executionHistory as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $nodeId = $entry['node_id'] ?? null;
            $timestamp = $entry['timestamp'] ?? null;

            if (!$nodeId || !$timestamp) {
                continue;
            }

            try {
                $ts = Carbon::parse($timestamp)->getTimestamp();
            } catch (\Throwable $e) {
                continue;
            }

            $orderedEntries[] = [
                'node_id' => (string) $nodeId,
                'ts' => $ts,
            ];
        }

        if (count($orderedEntries) < 2) {
            return [];
        }

        usort($orderedEntries, fn($a, $b) => $a['ts'] <=> $b['ts']);

        $transitions = [];
        for ($i = 1; $i < count($orderedEntries); $i++) {
            $prev = $orderedEntries[$i - 1];
            $current = $orderedEntries[$i];

            if ($prev['node_id'] === $current['node_id']) {
                continue;
            }

            $diff = $current['ts'] - $prev['ts'];
            if ($diff < 0) {
                continue;
            }

            $transitions[] = [
                'from_node_id' => $prev['node_id'],
                'to_node_id' => $current['node_id'],
                'seconds' => $diff,
            ];
        }

        return $transitions;
    }
}
