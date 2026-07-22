<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Four separate fields were silently dropped in production because they were
 * missing from $fillable (user phone, company_name, utm_*, campaign filename).
 * Production now reports the violation instead of swallowing it.
 */
class DiscardedAttributeReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // These are global statics; leaving them set would leak into other tests.
        Model::preventSilentlyDiscardingAttributes(false);
        Model::handleDiscardedAttributeViolationUsing(null);

        parent::tearDown();
    }

    /**
     * Replicates the production wiring from AppServiceProvider::boot().
     */
    private function applyProductionHandler(): void
    {
        Model::preventSilentlyDiscardingAttributes();

        Model::handleDiscardedAttributeViolationUsing(function ($model, $keys) {
            Log::warning('Discarded unfillable attributes', [
                'model' => $model::class,
                'attributes' => $keys,
            ]);
        });
    }

    public function test_production_logs_the_violation_instead_of_throwing(): void
    {
        $this->applyProductionHandler();

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'Discarded unfillable attributes'
                    && $context['model'] === Team::class
                    && in_array('definitely_not_fillable', $context['attributes'], true);
            });

        // The whole point: this must not blow up a customer request.
        $team = Team::factory()->create();
        $team->fill(['definitely_not_fillable' => 'x']);

        $this->assertTrue(true);
    }

    public function test_the_write_still_succeeds_after_a_violation(): void
    {
        $this->applyProductionHandler();
        Log::shouldReceive('warning');

        $team = Team::factory()->create();
        $team->fill(['name' => 'Renamed', 'definitely_not_fillable' => 'x']);
        $team->save();

        // Legitimate attributes are unaffected; only the unfillable one is lost,
        // exactly as before — but now it leaves a trace.
        $this->assertSame('Renamed', $team->fresh()->name);
    }
}
