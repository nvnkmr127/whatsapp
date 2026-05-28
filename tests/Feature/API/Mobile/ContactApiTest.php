<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->user->teams()->attach($this->team, ['role' => 'admin']);
        $this->user->forceFill(['current_team_id' => $this->team->id])->save();
        $this->user->refresh();

        Sanctum::actingAs($this->user);
    }

    public function test_can_list_contacts()
    {
        Contact::factory()->count(3)->create(['team_id' => $this->team->id]);

        $response = $this->getJson('/api/v1/mobile/contacts', [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('total', 3);
    }

    public function test_can_create_contact()
    {
        $payload = [
            'phone_number' => '+15551234567',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'custom_attributes' => ['notes' => 'VIP Client'],
        ];

        $response = $this->postJson('/api/v1/mobile/contacts', $payload, [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('contact.phone_number', '+15551234567')
            ->assertJsonPath('contact.name', 'John Doe');

        $this->assertDatabaseHas('contacts', [
            'team_id' => $this->team->id,
            'phone_number' => '+15551234567',
            'name' => 'John Doe',
        ]);
    }

    public function test_cannot_create_duplicate_contact()
    {
        Contact::factory()->create([
            'team_id' => $this->team->id,
            'phone_number' => '+15551234567',
            'name' => 'John Doe Existing',
        ]);

        $payload = [
            'phone_number' => '+15551234567',
            'name' => 'John Doe New',
        ];

        $response = $this->postJson('/api/v1/mobile/contacts', $payload, [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Contact already exists.')
            ->assertJsonStructure([
                'message',
                'contact',
                'conversation' => [
                    'id',
                    'contact_id',
                    'name',
                    'phone',
                    'last',
                    'time',
                    'unread',
                    'status',
                    'online',
                    'pinned',
                    'bot',
                ]
            ]);
    }

    public function test_can_update_contact()
    {
        $contact = Contact::factory()->create([
            'team_id' => $this->team->id,
            'phone_number' => '+15551234567',
            'name' => 'John Doe',
        ]);

        $payload = [
            'name' => 'John Updated',
            'opt_in_status' => 'opted_out',
        ];

        $response = $this->putJson("/api/v1/mobile/contacts/{$contact->id}", $payload, [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('contact.name', 'John Updated')
            ->assertJsonPath('contact.opt_in_status', 'opted_out');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'John Updated',
            'opt_in_status' => 'opted_out',
        ]);
    }

    public function test_update_contact_syncs_email_column()
    {
        $contact = Contact::factory()->create([
            'team_id' => $this->team->id,
            'phone_number' => '+15551234567',
            'name' => 'John Doe',
            'email' => 'old@example.com',
        ]);

        $payload = [
            'custom_attributes' => [
                'email' => 'new@example.com',
                'company' => 'Acme Corp',
            ],
        ];

        $response = $this->putJson("/api/v1/mobile/contacts/{$contact->id}", $payload, [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('contact.email', 'new@example.com');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'email' => 'new@example.com',
        ]);
    }

    public function test_can_toggle_tag()
    {
        $contact = Contact::factory()->create(['team_id' => $this->team->id]);
        $tag = ContactTag::create(['team_id' => $this->team->id, 'name' => 'VIP']);

        $response = $this->postJson("/api/v1/mobile/contacts/{$contact->id}/tags/toggle", ['tag_id' => $tag->id], [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('contact_tag_pivot', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_can_create_tag()
    {
        $response = $this->postJson("/api/v1/mobile/contacts/tags", [
            'name' => 'New Premium Tag',
            'color' => '#FF5733',
        ], [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'tag' => [
                'name' => 'New Premium Tag',
                'color' => '#FF5733',
                'team_id' => $this->team->id,
            ]
        ]);

        $this->assertDatabaseHas('contact_tags', [
            'name' => 'New Premium Tag',
            'color' => '#FF5733',
            'team_id' => $this->team->id,
        ]);
    }

    public function test_cannot_create_duplicate_tag()
    {
        ContactTag::create([
            'team_id' => $this->team->id,
            'name' => 'Duplicate Tag',
            'color' => '#10B981',
        ]);

        $response = $this->postJson("/api/v1/mobile/contacts/tags", [
            'name' => 'Duplicate Tag',
            'color' => '#123456',
        ], [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'A tag with this name already exists.',
        ]);
    }
}
