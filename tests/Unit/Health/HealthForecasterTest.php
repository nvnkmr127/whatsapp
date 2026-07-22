<?php

namespace Tests\Unit\Health;

use App\Services\Health\HealthForecaster;
use PHPUnit\Framework\TestCase;

class HealthForecasterTest extends TestCase
{
    private HealthForecaster $forecaster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forecaster = new HealthForecaster;
    }

    /** @return array<int, array{day: string, score: int}> */
    private function series(array $scores): array
    {
        return array_map(
            fn ($score, $i) => ['day' => sprintf('2026-07-%02d', $i + 1), 'score' => $score],
            $scores,
            array_keys($scores)
        );
    }

    public function test_it_declines_to_forecast_below_the_minimum_window(): void
    {
        $this->assertNull($this->forecaster->forecast($this->series([90, 80, 70])));
        $this->assertNull($this->forecaster->forecast([]));
    }

    public function test_it_forecasts_at_exactly_the_minimum_window(): void
    {
        $this->assertNotNull($this->forecaster->forecast($this->series([90, 88, 86, 84, 82, 80, 78])));
    }

    public function test_it_detects_a_steady_decline(): void
    {
        // Exactly -3/day, perfectly linear.
        $result = $this->forecaster->forecast($this->series([90, 87, 84, 81, 78, 75, 72, 69, 66, 63]));

        $this->assertSame('declining', $result['direction']);
        $this->assertSame(-3.0, $result['slope']);
        $this->assertSame(1.0, $result['r_squared']);
    }

    public function test_it_detects_improvement(): void
    {
        $result = $this->forecaster->forecast($this->series([50, 53, 56, 59, 62, 65, 68, 71]));

        $this->assertSame('improving', $result['direction']);
        $this->assertSame(3.0, $result['slope']);
    }

    public function test_flat_history_is_stable_and_does_not_divide_by_zero(): void
    {
        $result = $this->forecaster->forecast($this->series([80, 80, 80, 80, 80, 80, 80, 80]));

        $this->assertSame('stable', $result['direction']);
        $this->assertSame(0.0, $result['slope']);
        $this->assertSame(1.0, $result['r_squared']);
        $this->assertNull($result['days_to_critical']);
    }

    public function test_it_projects_when_the_score_crosses_critical(): void
    {
        // Ends at 63, falling 3/day → (63-50)/3 = 4.33 → 5 days.
        $result = $this->forecaster->forecast($this->series([90, 87, 84, 81, 78, 75, 72, 69, 66, 63]));

        $this->assertSame(5, $result['days_to_critical']);
    }

    public function test_it_does_not_project_a_crossing_while_improving(): void
    {
        $result = $this->forecaster->forecast($this->series([50, 53, 56, 59, 62, 65, 68, 71]));

        $this->assertNull($result['days_to_critical']);
    }

    public function test_it_does_not_project_below_the_threshold(): void
    {
        // Already critical: there is no future crossing to warn about.
        $result = $this->forecaster->forecast($this->series([40, 37, 34, 31, 28, 25, 22, 19]));

        $this->assertNull($result['days_to_critical']);
    }

    public function test_a_distant_crossing_is_not_reported(): void
    {
        // The horizon guard only fires in a narrow band: the decline must be
        // steep enough to count as a trend (<= -0.5/day) yet still take over 90
        // days to reach 50, which needs a near-perfect current score.
        // Slope here is exactly -0.5 from 96 → 92 days, just past the horizon.
        $result = $this->forecaster->forecast($this->series([99, 99, 98, 98, 97, 97, 96]));

        $this->assertSame(-0.5, $result['slope']);
        $this->assertSame('declining', $result['direction']);
        $this->assertNull($result['days_to_critical']);
    }

    public function test_a_crossing_inside_the_horizon_is_reported(): void
    {
        // ~0.6/day from 95: reaches 50 in well under 90 days, so it is actionable.
        $scores = [];
        for ($i = 0; $i < 20; $i++) {
            $scores[] = (int) round(95 - ($i * 0.6));
        }

        $this->assertSame(58, $this->forecaster->forecast($this->series($scores))['days_to_critical']);
    }

    public function test_noisy_data_reports_low_confidence(): void
    {
        $result = $this->forecaster->forecast($this->series([90, 40, 85, 45, 88, 42, 91, 38, 87, 44]));

        $this->assertSame('low', $result['confidence']);
        $this->assertLessThan(0.4, $result['r_squared']);
    }

    public function test_a_short_clean_window_is_still_only_low_confidence(): void
    {
        // Perfect fit, but 7 days is too little to call it high confidence.
        $result = $this->forecaster->forecast($this->series([90, 88, 86, 84, 82, 80, 78]));

        $this->assertSame(1.0, $result['r_squared']);
        $this->assertSame('low', $result['confidence']);
    }

    public function test_a_long_clean_window_reports_high_confidence(): void
    {
        $scores = [];
        for ($i = 0; $i < 21; $i++) {
            $scores[] = 95 - $i;
        }

        $this->assertSame('high', $this->forecaster->forecast($this->series($scores))['confidence']);
    }

    public function test_small_drift_is_treated_as_noise_not_a_trend(): void
    {
        $result = $this->forecaster->forecast($this->series([80, 80, 81, 80, 81, 80, 81, 80]));

        $this->assertSame('stable', $result['direction']);
        $this->assertNull($result['days_to_critical']);
    }
}
