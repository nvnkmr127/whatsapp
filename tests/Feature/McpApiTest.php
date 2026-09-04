<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpApiTest extends TestCase
{
    use RefreshDatabase;

    private function createWorkspaceUser(): array
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user, ['role' => 'admin']);
        $user->current_team_id = $team->id;
        $user->setRelation('currentTeam', $team);

        return [$team, $user];
    }

    public function test_mcp_initialize()
    {
        [$team, $user] = $this->createWorkspaceUser();

        $response = $this->actingAs($user)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'method'  => 'initialize',
            'id'      => 1,
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.protocolVersion', '2024-11-05');
        $response->assertJsonPath('result.serverInfo.name', 'WatxIO MCP Server');
    }

    public function test_mcp_ping()
    {
        [$team, $user] = $this->createWorkspaceUser();

        $response = $this->actingAs($user)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'method'  => 'ping',
            'id'      => 2,
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['jsonrpc', 'id', 'result']);
    }

    public function test_mcp_notifications_initialized()
    {
        [$team, $user] = $this->createWorkspaceUser();

        $response = $this->actingAs($user)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'method'  => 'notifications/initialized',
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(202);
    }

    public function test_mcp_invalid_jsonrpc()
    {
        [$team, $user] = $this->createWorkspaceUser();

        $response = $this->actingAs($user)->postJson('/api/v1/mcp', [
            'jsonrpc' => '1.0',
            'method'  => 'initialize',
            'id'      => 1,
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('error.code', -32600);
    }

    public function test_mcp_tools_list()
    {
        [$team, $user] = $this->createWorkspaceUser();

        $response = $this->actingAs($user)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'method'  => 'tools/list',
            'id'      => 3,
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(200);
        $tools = $response->json('result.tools');
        $this->assertIsArray($tools);
        $this->assertCount(38, $tools);

        $toolNames = array_column($tools, 'name');
        $this->assertContains('send_message', $toolNames);
        $this->assertContains('get_contact', $toolNames);
        $this->assertContains('upsert_contact', $toolNames);
        $this->assertContains('assign_conversation', $toolNames);
        $this->assertContains('initiate_call', $toolNames);
    }

    public function test_mcp_tools_call_get_contact()
    {
        [$team, $user] = $this->createWorkspaceUser();

        Contact::create([
            'team_id'      => $team->id,
            'name'         => 'Alice Test',
            'phone_number' => '+1234567890',
            'email'        => 'alice@test.com',
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'method'  => 'tools/call',
            'id'      => 4,
            'params'  => [
                'name'      => 'get_contact',
                'arguments' => [
                    'phone' => '+1234567890',
                ],
            ],
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.isError', false);
        $text = $response->json('result.content.0.text');
        $this->assertStringContainsString('Alice Test', $text);
    }

    public function test_mcp_tools_call_upsert_contact_with_company()
    {
        [$team, $user] = $this->createWorkspaceUser();

        $response = $this->actingAs($user)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'method'  => 'tools/call',
            'id'      => 5,
            'params'  => [
                'name'      => 'upsert_contact',
                'arguments' => [
                    'phone'   => '+1987654321',
                    'name'    => 'Bob Corporation',
                    'email'   => 'bob@acme.org',
                    'company' => 'Acme Corporation',
                ],
            ],
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.isError', false);

        $contact = Contact::where('team_id', $team->id)->where('phone_number', '+1987654321')->first();
        $this->assertNotNull($contact);
        $this->assertNotNull($contact->company_id);
        $this->assertEquals('Acme Corporation', $contact->company_id ? \App\Models\Company::find($contact->company_id)?->name : null);
    }

    public function test_mcp_tools_call_assign_conversation_to_team_owner()
    {
        [$team, $user] = $this->createWorkspaceUser();

        $contact = Contact::create([
            'team_id'      => $team->id,
            'name'         => 'Test Contact',
            'phone_number' => '+1234000000',
        ]);

        $conversation = Conversation::create([
            'team_id'    => $team->id,
            'contact_id' => $contact->id,
            'status'     => 'open',
        ]);

        // Assign to team owner ($team->user_id / $team->owner)
        $owner = $team->owner;

        $response = $this->actingAs($user)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'method'  => 'tools/call',
            'id'      => 6,
            'params'  => [
                'name'      => 'assign_conversation',
                'arguments' => [
                    'conversation_id' => $conversation->id,
                    'user_id'         => $owner->id,
                ],
            ],
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.isError', false);
        $this->assertEquals($owner->id, $conversation->fresh()->assigned_to);
    }

    public function test_mcp_tools_call_unknown_tool_returns_method_not_found()
    {
        [$team, $user] = $this->createWorkspaceUser();

        $response = $this->actingAs($user)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'method'  => 'tools/call',
            'id'      => 7,
            'params'  => [
                'name' => 'non_existent_tool',
            ],
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('error.code', -32601);
    }

    public function test_mcp_tools_call_execution_exception_returns_is_error_true()
    {
        [$team, $user] = $this->createWorkspaceUser();

        // Calling close_conversation with a non-existent conversation ID
        $response = $this->actingAs($user)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'method'  => 'tools/call',
            'id'      => 8,
            'params'  => [
                'name'      => 'close_conversation',
                'arguments' => [
                    'conversation_id' => 999999,
                ],
            ],
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.isError', true);
        $this->assertStringContainsString('Error', $response->json('result.content.0.text'));
    }

    public function test_mcp_tools_call_upsert_contact_merges_custom_attributes()
    {
        [$team, $user] = $this->createWorkspaceUser();

        // Pre-existing contact with tier attribute
        Contact::create([
            'team_id'           => $team->id,
            'name'              => 'Old Name',
            'phone_number'      => '+1555444333',
            'email'             => 'old@example.com',
            'custom_attributes' => ['tier' => 'platinum', 'initial' => 'yes'],
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'method'  => 'tools/call',
            'id'      => 9,
            'params'  => [
                'name'      => 'upsert_contact',
                'arguments' => [
                    'phone'             => '+1555444333',
                    'name'              => 'Updated Name',
                    'custom_attributes' => ['tier' => 'diamond', 'loyalty_id' => 'LY99'],
                ],
            ],
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.isError', false);

        $contact = Contact::where('team_id', $team->id)->where('phone_number', '+1555444333')->first();
        $this->assertEquals('Updated Name', $contact->name);
        $this->assertEquals('old@example.com', $contact->email); // Preserved
        $this->assertEquals('diamond', $contact->custom_attributes['tier'] ?? null); // Updated
        $this->assertEquals('yes', $contact->custom_attributes['initial'] ?? null); // Preserved
        $this->assertEquals('LY99', $contact->custom_attributes['loyalty_id'] ?? null); // Added
    }
}
