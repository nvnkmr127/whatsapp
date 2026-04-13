<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Health\FailedJobsTable;
use App\Models\User;
use App\Support\FailedJobs\FailedJobInspector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class HealthFailedJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_job_inspector_extracts_team_id_and_job_label(): void
    {
        $payload = [
            'data' => [
                'command' => '... "team_id";i:123 ...',
            ],
            'displayName' => 'App\\Jobs\\ExampleJob',
        ];

        $this->assertSame(123, FailedJobInspector::teamIdFromPayload($payload));
        $this->assertSame('App\\Jobs\\ExampleJob', FailedJobInspector::jobLabelFromPayload($payload));
    }

    public function test_failed_jobs_table_lists_recent_failures_and_can_search_payload_or_exception(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);

        DB::table('failed_jobs')->insert([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\Foo',
                'data' => ['command' => '... "team_id";i:1 ...'],
                'meta' => ['trace_id' => '22dc80e6-f76e-41d7-be36-fed55f3cc09e'],
            ]),
            'exception' => 'Example exception text',
            'failed_at' => now()->subHour(),
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => '00000000-0000-0000-0000-000000000002',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\Bar',
                'data' => ['command' => '...'],
            ]),
            'exception' => 'Different exception',
            'failed_at' => now()->subHour(),
        ]);

        Livewire::actingAs($user)
            ->test(FailedJobsTable::class)
            ->assertSee('00000000-0000-0000-0000-000000000001')
            ->assertSee('App\\Jobs\\Foo')
            ->set('search', '22dc80e6-f76e-41d7-be36-fed55f3cc09e')
            ->assertSee('00000000-0000-0000-0000-000000000001')
            ->assertDontSee('00000000-0000-0000-0000-000000000002');
    }
}

