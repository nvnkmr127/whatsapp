<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mcp_initialize()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);

        $response = $this->actingAs($user)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'initialize',
            'id' => 1
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.protocolVersion', '2024-11-05');
    }

    public function test_mcp_invalid_jsonrpc()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);

        $response = $this->actingAs($user)->postJson('/api/v1/mcp', [
            'jsonrpc' => '1.0',
            'method' => 'initialize',
            'id' => 1
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('error.code', -32600);
    }
}
