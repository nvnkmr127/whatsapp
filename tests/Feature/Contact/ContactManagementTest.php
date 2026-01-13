<?php

namespace Tests\Feature\Contact;

use App\Models\Contact;
use App\Models\Team;
use App\Services\ContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_or_update_merges_attributes()
    {
        $team = Team::factory()->create();
        $service = new ContactService();

        // 1. Create with initial attribute
        $contact = $service->createOrUpdate([
            'team_id' => $team->id,
            'phone_number' => '123456789',
            'custom_attributes' => ['plan' => 'free']
        ]);

        $this->assertEquals('free', $contact->custom_attributes['plan']);

        // 2. Update with new attribute (should merge)
        $service->createOrUpdate([
            'team_id' => $team->id,
            'phone_number' => '123456789',
            'custom_attributes' => ['verified' => true]
        ]);

        $contact->refresh();
        $this->assertEquals('free', $contact->custom_attributes['plan']);
        $this->assertTrue($contact->custom_attributes['verified']);
    }

    public function test_sync_tags_creates_and_assigns()
    {
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);
        $service = new ContactService();

        // Sync by name
        $service->syncTags($contact, ['VIP', 'Lead']);

        $this->assertCount(2, $contact->tags);
        $this->assertTrue($contact->tags->contains('name', 'VIP'));
    }

    public function test_add_tags_preserves_existing()
    {
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);
        $service = new ContactService();

        $service->syncTags($contact, ['VIP']);
        $service->addTags($contact, ['Lead']);

        $contact->refresh();
        $this->assertCount(2, $contact->tags);
        $this->assertTrue($contact->tags->contains('name', 'VIP'));
        $this->assertTrue($contact->tags->contains('name', 'Lead'));
    }
    public function test_can_view_contact_details()
    {
        $team = Team::factory()->create();
        $this->actingAs($team->owner);

        $contact = Contact::factory()->create(['team_id' => $team->id, 'name' => 'John Doe']);

        $component = \Livewire\Livewire::test(\App\Livewire\Contacts\ContactManager::class);

        $component->call('viewContact', $contact->id)
            ->assertSet('isViewModalOpen', true)
            ->assertSet('viewingContact.id', $contact->id)
            ->assertSee('John Doe');
    }
}
