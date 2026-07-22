<?php

namespace App\Services\Health;

/**
 * HealthForecaster
 * ════════════════
 * Least-squares trend over daily health scores, used to answer "is this getting
 * worse, and roughly when does it become a problem".
 *
 * Deliberately refuses to answer below MIN_DAYS of history rather than fitting a
 * line to two noisy points — a confident wrong projection about deliverability
 * is worse than no projection.
 */
class HealthForecaster
{
    /** Below this many daily points we decline to forecast at all. */
    public const MIN_DAYS = 7;

    /** Health score at which the account is considered critical. */
    public const CRITICAL_THRESHOLD = 50;

    /** Projections beyond this horizon are not meaningfully actionable. */
    public const MAX_HORIZON_DAYS = 90;

    /**
     * @param  array<int, array{day: string, score: int}>  $trend  ascending by day
     * @return array{
     *     slope: float,
     *     r_squared: float,
     *     current: int,
     *     direction: string,
     *     confidence: string,
     *     sample_days: int,
     *     days_to_critical: int|null
     * }|null  null when there is not enough history
     */
    public function forecast(array $trend): ?array
    {
        $n = count($trend);

        if ($n < self::MIN_DAYS) {
            return null;
        }

        $scores = array_map(static fn ($p) => (float) $p['score'], $trend);

        // x is the day index; spacing is uniform enough for this purpose, and
        // missing days simply compress the window rather than skew the fit.
        $meanX = ($n - 1) / 2;
        $meanY = array_sum($scores) / $n;

        $covariance = 0.0;
        $varianceX = 0.0;

        foreach ($scores as $i => $y) {
            $dx = $i - $meanX;
            $covariance += $dx * ($y - $meanY);
            $varianceX += $dx * $dx;
        }

        $slope = $varianceX > 0 ? $covariance / $varianceX : 0.0;
        $intercept = $meanY - ($slope * $meanX);

        // R²: how much of the movement the straight line actually explains.
        $ssTot = 0.0;
        $ssRes = 0.0;

        foreach ($scores as $i => $y) {
            $predicted = $intercept + ($slope * $i);
            $ssTot += ($y - $meanY) ** 2;
            $ssRes += ($y - $predicted) ** 2;
        }

        // A perfectly flat history has no variance to explain; treat it as a
        // clean fit of a flat line rather than dividing by zero.
        $rSquared = $ssTot > 0 ? max(0.0, 1 - ($ssRes / $ssTot)) : 1.0;

        $current = (int) end($scores);

        return [
            'slope' => round($slope, 2),
            'r_squared' => round($rSquared, 2),
            'current' => $current,
            'direction' => $this->direction($slope),
            'confidence' => $this->confidence($rSquared, $n),
            'sample_days' => $n,
            'days_to_critical' => $this->daysToCritical($current, $slope),
        ];
    }

    /**
     * Sub-0.5/day drift is noise, not a trend worth naming.
     */
    private function direction(float $slope): string
    {
        return match (true) {
            $slope <= -0.5 => 'declining',
            $slope >= 0.5 => 'improving',
            default => 'stable',
        };
    }

    private function confidence(float $rSquared, int $days): string
    {
        if ($days < 14 || $rSquared < 0.4) {
            return 'low';
        }

        return $rSquared >= 0.7 ? 'high' : 'moderate';
    }

    /**
     * Only project a crossing when actually heading toward it.
     */
    private function daysToCritical(int $current, float $slope): ?int
    {
        if ($slope >= -0.5 || $current <= self::CRITICAL_THRESHOLD) {
            return null;
        }

        $days = (int) ceil(($current - self::CRITICAL_THRESHOLD) / abs($slope));

        return $days <= self::MAX_HORIZON_DAYS ? $days : null;
    }
}
