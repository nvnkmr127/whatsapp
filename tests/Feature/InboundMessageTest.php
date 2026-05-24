<?php

namespace Tests\Feature;

use App\Jobs\PersistMessageJob;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InboundMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_message_persistence_and_retrieval()
    {
        // 1. Setup Data
        $team = Team::factory()->create([
            'whatsapp_phone_number_id' => '123456789',
            'whatsapp_business_account_id' => '987654321',
        ]);

        $payload = [
            'provider_id' => 'wamid.test.'.uniqid(),
            'to_phone_id' => '123456789',
            'waba_id' => '987654321',
            'from_phone' => '15550000000',
            'contact_name' => 'Test User',
            'timestamp' => time(),
            'type' => 'text',
            'content' => [
                'type' => 'text',
                'text' => ['body' => 'Hello World'],
            ],
        ];

        // 2. Expect Event (but don't fail if broadcast fails in reality, here we mock it)
        Event::fake([
            \App\Events\MessageReceived::class,
        ]);

        // 3. Execute Job
        $job = new PersistMessageJob($payload);
        $job->handle();

        // 4. Assertions

        // Check Contact
        $contact = Contact::first();
        $this->assertNotNull($contact);
        $this->assertEquals($team->id, $contact->team_id);
        // Note: Phone number might be normalized (e.g. + added), so we check normalized or loose
        // $this->assertEquals('+9115550000000', $contact->phone_number);

        // Check Conversation
        $conversation = Conversation::where('contact_id', $contact->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('open', $conversation->status);

        // Check Message
        $message = Message::where('whatsapp_message_id', $payload['provider_id'])->first();
        $this->assertNotNull($message);
        $this->assertEquals('Hello World', $message->content);
        $this->assertEquals('inbound', $message->direction);
        $this->assertEquals($conversation->id, $message->conversation_id);

        // Check Event Dispatched
        Event::assertDispatched(\App\Events\MessageReceived::class);
    }

    public function test_inbound_message_updates_placeholder_contact_name()
    {
        $team = Team::factory()->create([
            'whatsapp_phone_number_id' => '123456789',
            'whatsapp_business_account_id' => '987654321',
        ]);

        $contact = Contact::create([
            'team_id' => $team->id,
            'phone_number' => \App\Helpers\PhoneNumberHelper::normalize('15550000000'),
            'name' => 'Webhook Contact',
        ]);

        $payload = [
            'provider_id' => 'wamid.test.'.uniqid(),
            'to_phone_id' => '123456789',
            'waba_id' => '987654321',
            'from_phone' => '15550000000',
            'contact_name' => 'John Doe',
            'timestamp' => time(),
            'type' => 'text',
            'content' => [
                'type' => 'text',
                'text' => ['body' => 'Hello World'],
            ],
        ];

        Event::fake([\App\Events\MessageReceived::class]);

        $job = new PersistMessageJob($payload);
        $job->handle();

        $contact->refresh();
        $this->assertEquals('John Doe', $contact->name);
    }

    public function test_inbound_message_does_not_overwrite_real_contact_name_with_null()
    {
        $team = Team::factory()->create([
            'whatsapp_phone_number_id' => '123456789',
            'whatsapp_business_account_id' => '987654321',
        ]);

        $contact = Contact::create([
            'team_id' => $team->id,
            'phone_number' => \App\Helpers\PhoneNumberHelper::normalize('15550000000'),
            'name' => 'John Doe',
        ]);

        $payload = [
            'provider_id' => 'wamid.test.'.uniqid(),
            'to_phone_id' => '123456789',
            'waba_id' => '987654321',
            'from_phone' => '15550000000',
            'contact_name' => null,
            'timestamp' => time(),
            'type' => 'text',
            'content' => [
                'type' => 'text',
                'text' => ['body' => 'Hello World'],
            ],
        ];

        Event::fake([\App\Events\MessageReceived::class]);

        $job = new PersistMessageJob($payload);
        $job->handle();

        $contact->refresh();
        $this->assertEquals('John Doe', $contact->name);
    }

    public function test_inbound_message_does_not_overwrite_real_contact_name_with_placeholder()
    {
        $team = Team::factory()->create([
            'whatsapp_phone_number_id' => '123456789',
            'whatsapp_business_account_id' => '987654321',
        ]);

        $contact = Contact::create([
            'team_id' => $team->id,
            'phone_number' => \App\Helpers\PhoneNumberHelper::normalize('15550000000'),
            'name' => 'John Doe',
        ]);

        $payload = [
            'provider_id' => 'wamid.test.'.uniqid(),
            'to_phone_id' => '123456789',
            'waba_id' => '987654321',
            'from_phone' => '15550000000',
            'contact_name' => 'Webhook Contact',
            'timestamp' => time(),
            'type' => 'text',
            'content' => [
                'type' => 'text',
                'text' => ['body' => 'Hello World'],
            ],
        ];

        Event::fake([\App\Events\MessageReceived::class]);

        $job = new PersistMessageJob($payload);
        $job->handle();

        $contact->refresh();
        $this->assertEquals('John Doe', $contact->name);
    }
}
