<?php

namespace Tests\Feature\Contact;

use App\Models\Contact;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactCustomAttributesTest extends TestCase
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

    public function test_can_create_contact_extracts_custom_attributes()
    {
        $payload = [
            'phone_number' => '+15551112222',
            'name' => 'John Doe',
            'custom_attributes' => [
                'email' => 'john.doe@example.com',
                'company' => 'Acme Corporation',
                'custom_field' => 'custom_val',
            ],
        ];

        $response = $this->postJson('/api/v1/mobile/contacts', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('contact.email', 'john.doe@example.com')
            ->assertJsonPath('contact.company', 'Acme Corporation')
            ->assertJsonMissingPath('contact.custom_attributes.email')
            ->assertJsonMissingPath('contact.custom_attributes.company')
            ->assertJsonPath('contact.custom_attributes.custom_field', 'custom_val');

        // Verify Database
        $this->assertDatabaseHas('contacts', [
            'team_id' => $this->team->id,
            'phone_number' => '+15551112222',
            'email' => 'john.doe@example.com',
        ]);

        $contact = Contact::where('phone_number', '+15551112222')->first();
        $this->assertNotNull($contact->company_id);

        $company = Company::find($contact->company_id);
        $this->assertNotNull($company);
        $this->assertEquals('Acme Corporation', $company->name);
        $this->assertEquals($this->team->id, $company->team_id);
    }

    public function test_can_update_contact_extracts_custom_attributes()
    {
        $contact = Contact::factory()->create([
            'team_id' => $this->team->id,
            'phone_number' => '+15551113333',
            'name' => 'Jane Smith',
        ]);

        $payload = [
            'name' => 'Jane Smith Updated',
            'custom_attributes' => [
                'email' => 'jane.smith@example.com',
                'company' => 'Globex Corp',
            ],
        ];

        $response = $this->putJson("/api/v1/mobile/contacts/{$contact->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('contact.email', 'jane.smith@example.com')
            ->assertJsonPath('contact.company', 'Globex Corp');

        // Verify Database
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Jane Smith Updated',
            'email' => 'jane.smith@example.com',
        ]);

        $contact->refresh();
        $this->assertNotNull($contact->company_id);
        $this->assertEquals('Globex Corp', $contact->company);
    }

    public function test_can_nullify_email_and_company_via_custom_attributes()
    {
        $company = Company::create(['team_id' => $this->team->id, 'name' => 'Acme Corp']);
        $contact = Contact::factory()->create([
            'team_id' => $this->team->id,
            'phone_number' => '+15551114444',
            'email' => 'old@example.com',
            'company_id' => $company->id,
        ]);

        $payload = [
            'custom_attributes' => [
                'email' => null,
                'company' => '',
            ],
        ];

        $response = $this->putJson("/api/v1/mobile/contacts/{$contact->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('contact.email', null)
            ->assertJsonPath('contact.company', null);

        $contact->refresh();
        $this->assertNull($contact->email);
        $this->assertNull($contact->company_id);
    }

    public function test_custom_attributes_nested_validation()
    {
        $response = $this->postJson('/api/v1/mobile/contacts', [
            'phone_number' => '+15551115555',
            'custom_attributes' => [
                'email' => 'not-an-email',
                'company' => 12345, // invalid string
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['custom_attributes.email', 'custom_attributes.company']);
    }
}
