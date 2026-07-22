<?php

namespace App\Livewire\Health;

use App\Models\QualityRatingHistory;
use App\Models\WhatsAppHealthAlert;
use App\Models\WhatsAppHealthSnapshot;
use App\Services\Health\HealthForecaster;
use App\Services\Health\TemplateAttributionService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * DeliverabilityCenter
 * ════════════════════
 * Reads the health history the scheduler has been recording all along
 * (whatsapp:calculate-health-scores every 30m) instead of hitting Meta live.
 *
 * These models use HasTeam/TenantScope, but that scope deliberately no-ops
 * under runningInConsole(). Every query here filters on team_id explicitly
 * rather than trusting an implicit scope at a tenant boundary.
 */
class DeliverabilityCenter extends Component
{
    /** Days of history to chart. */
    public int $days = 30;

    protected function teamId(): ?int
    {
        return auth()->user()?->current_team_id;
    }

    public function setRange(int $days): void
    {
        $this->days = in_array($days, [7, 30, 90], true) ? $days : 30;
    }

    public function acknowledge(int $alertId): void
    {
        $alert = WhatsAppHealthAlert::where('team_id', $this->teamId())->find($alertId);

        if ($alert) {
            $alert->acknowledge(auth()->user());
            $this->dispatch('notify', title: 'Acknowledged', message: 'Alert cleared from your queue.', type: 'success');
        }
    }

    /**
     * Daily rollup rather than raw rows: snapshots land every 30 minutes, so a
     * 90-day window is ~4.3k rows we do not want to hydrate just to draw a line.
     */
    protected function dailyTrend(): array
    {
        return WhatsAppHealthSnapshot::query()
            ->where('team_id', $this->teamId())
            ->selectRaw('DATE(snapshot_at) as day')
            ->selectRaw('ROUND(AVG(health_score)) as score')
            ->selectRaw('MIN(health_score) as worst')
            ->selectRaw('ROUND(AVG(usage_percent), 1) as usage_percent')
            ->where('snapshot_at', '>=', now()->subDays($this->days))
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => [
                'day' => $r->day,
                'score' => (int) $r->score,
                'worst' => (int) $r->worst,
                'usage_percent' => (float) $r->usage_percent,
            ])
            ->all();
    }

    /**
     * Templates in flight before the most recent rating drop.
     *
     * Only the latest drop is attributed — replaying this for every historical
     * drop produces a wall of near-identical lists nobody reads.
     *
     * @return array{lastDrop: \App\Models\QualityRatingHistory|null, suspects: array, attributionWindow: int}
     */
    protected function attribution(): array
    {
        $service = app(TemplateAttributionService::class);
        $drop = $service->latestDrop($this->teamId(), $this->days);

        return [
            'lastDrop' => $drop,
            'suspects' => $drop ? $service->templatesBefore($this->teamId(), $drop->created_at) : [],
            'attributionWindow' => TemplateAttributionService::WINDOW_HOURS,
        ];
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $latest = WhatsAppHealthSnapshot::where('team_id', $this->teamId())
            ->orderByDesc('snapshot_at')
            ->first();
        $trend = $this->dailyTrend();

        $ratingChanges = QualityRatingHistory::where('team_id', $this->teamId())
            ->where('created_at', '>=', now()->subDays($this->days))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $alerts = WhatsAppHealthAlert::where('team_id', $this->teamId())
            ->where('acknowledged', false)
            ->whereNull('resolved_at')
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        // Direction of travel over the charted window — the whole reason this
        // page exists rather than the single live gauge on the settings screen.
        $delta = null;
        if (count($trend) >= 2) {
            $delta = $trend[count($trend) - 1]['score'] - $trend[0]['score'];
        }

        return view('livewire.health.deliverability-center', [
            'latest' => $latest,
            'trend' => $trend,
            'delta' => $delta,
            'ratingChanges' => $ratingChanges,
            'alerts' => $alerts,
            // Null when there is not enough history; the view says so plainly
            // rather than drawing a projection nobody should act on.
            'forecast' => app(HealthForecaster::class)->forecast($trend),
            'minForecastDays' => HealthForecaster::MIN_DAYS,
            ...$this->attribution(),
        ]);
    }
}
