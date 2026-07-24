<?php

namespace Tests\Feature;

use App\Livewire\Chat\ContactDetails;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactVCardExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_vcard_download_returns_streamed_vcf_response(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        $contact = Contact::factory()->create([
            'team_id' => $team->id,
            'name' => 'Jane Doe',
            'phone_number' => '+15551234567',
            'email' => 'jane@example.com',
        ]);

        $conversation = Conversation::factory()->create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
        ]);

        Livewire::test(ContactDetails::class, ['conversationId' => $conversation->id])
            ->call('downloadVCard')
            ->assertFileDownloaded("contact_{$contact->id}.vcf");
    }

    public function test_editing_contact_dispatches_contact_updated_event(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        $contact = Contact::factory()->create([
            'team_id' => $team->id,
            'name' => 'Original Name',
        ]);

        $conversation = Conversation::factory()->create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
        ]);

        Livewire::test(ContactDetails::class, ['conversationId' => $conversation->id])
            ->call('startEdit')
            ->set('editName', 'Updated Name')
            ->call('saveContact')
            ->assertDispatched('contact-updated');

        $this->assertSame('Updated Name', $contact->fresh()->name);
    }
}
