<?php

namespace Tests\Feature\Contact;

use App\Models\Contact;
use App\Models\ContactField;
use App\Models\Team;
use App\Services\ContactImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_manage_custom_fields()
    {
        $team = Team::factory()->create();

        $field = ContactField::create([
            'team_id' => $team->id,
            'label' => 'Birthday',
            'key' => 'birthday',
            'type' => 'date',
        ]);

        $this->assertDatabaseHas('contact_fields', [
            'key' => 'birthday',
            'type' => 'date',
        ]);
    }

    public function test_import_service_handles_csv_with_custom_fields()
    {
        $team = Team::factory()->create();

        // Define Custom Attribute
        ContactField::create([
            'team_id' => $team->id,
            'label' => 'Company',
            'key' => 'company',
            'type' => 'text',
        ]);

        // Create CSV Content
        $csvContent = implode("\n", [
            'Name,Phone,Email,Tags,Company',
            'John Doe,+1234567890,john@example.com,"VIP, Lead",Acme Corp',
            'Jane Smith,+1987654321,,New,Globex',
        ]);

        $filePath = sys_get_temp_dir().'/test_contacts.csv';
        file_put_contents($filePath, $csvContent);

        // Run Import
        $service = new ContactImportService($team);
        $mapping = [
            'Name' => 'name',
            'Phone' => 'phone_number',
            'Email' => 'email',
            'Tags' => 'tags',
            'Company' => 'company',
        ];

        $result = $service->import($filePath, $mapping);

        // Assertions
        $this->assertEquals(2, $result['success_count']);

        $john = Contact::where('phone_number', '+1234567890')->first();
        $this->assertNotNull($john);
        $john->load('companyRelation');
        $this->assertEquals('John Doe', $john->name);
        $this->assertEquals('Acme Corp', $john->company);
        $this->assertTrue($john->tags->contains('name', 'VIP'));
        $this->assertTrue($john->tags->contains('name', 'Lead'));

        $jane = Contact::where('phone_number', '+1987654321')->first();
        $jane->load('companyRelation');
        $this->assertEquals('Globex', $jane->company);
        $this->assertTrue($jane->tags->contains('name', 'New'));
    }

    public function test_can_generate_sample_csv_with_custom_fields()
    {
        $team = Team::factory()->create();
        $this->actingAs($team->owner);

        ContactField::create([
            'team_id' => $team->id,
            'label' => 'Company',
            'key' => 'company',
            'type' => 'text',
        ]);

        $component = \Livewire\Livewire::test(\App\Livewire\Contacts\ContactManager::class);

        $response = $component->call('downloadSampleCsv');

        $response->assertFileDownloaded('sample_contacts.csv');
    }
}
