<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Team;
use App\Services\AiCommerceService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class AiBusinessAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Grant AI + commerce features; the reply path is what we're testing, not billing.
        config(['app.full_access_all' => true]);
    }

    private function fakeAi(array $json): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode($json)]]],
                'model' => 'gpt-4o',
                'usage' => [],
            ]),
        ]);
    }

    /** A services-only business (no products, store not commerce-ready) still gets an AI answer. */
    public function test_service_business_with_no_products_still_replies()
    {
        $team = Team::factory()->create(['ai_auto_reply_enabled' => true, 'commerce_config' => []]);
        $contact = Contact::factory()->create(['team_id' => $team->id, 'phone_number' => '+919999999999']);
        set_setting("ai_openai_api_key_{$team->id}", 'test-key');

        $this->fakeAi([
            'matched' => false,
            'product_ids' => [],
            'reply_text' => 'We offer home cleaning every day from 9am to 6pm.',
            'kb_gap' => false,
        ]);

        $wa = Mockery::mock(WhatsAppService::class);
        $wa->shouldReceive('setTeam')->once();
        // Exactly one message — the answer. No checkout link, because the store can't sell.
        $wa->shouldReceive('sendText')->once()
            ->with('+919999999999', 'We offer home cleaning every day from 9am to 6pm.')
            ->andReturn(true);

        $handled = (new AiCommerceService($wa))->handle($contact, 'what are your timings?');

        $this->assertTrue($handled);
        Http::assertSentCount(1);
    }

    /** With the assistant switched off, nothing is called — no AI request, no reply. */
    public function test_disabled_assistant_does_not_reply()
    {
        $team = Team::factory()->create(['ai_auto_reply_enabled' => false, 'commerce_config' => []]);
        $contact = Contact::factory()->create(['team_id' => $team->id]);

        Http::fake();
        $wa = Mockery::mock(WhatsAppService::class);
        $wa->shouldNotReceive('sendText');

        $handled = (new AiCommerceService($wa))->handle($contact, 'hello');

        $this->assertFalse($handled);
        Http::assertNothingSent();
    }

    /** Store custom settings (currency, COD, min order) are given to the model. */
    public function test_store_settings_reach_the_model()
    {
        $team = Team::factory()->create([
            'ai_auto_reply_enabled' => true,
            'commerce_config' => [
                'currency' => 'INR',
                'cod_enabled' => true,
                'min_order_value' => 500,
            ],
        ]);
        $contact = Contact::factory()->create(['team_id' => $team->id, 'phone_number' => '+912222222222']);
        set_setting("ai_openai_api_key_{$team->id}", 'test-key');

        $this->fakeAi([
            'matched' => false,
            'product_ids' => [],
            'reply_text' => 'Haan, cash on delivery available hai.',
            'kb_gap' => false,
        ]);

        $wa = Mockery::mock(WhatsAppService::class);
        $wa->shouldReceive('setTeam');
        $wa->shouldReceive('sendText');

        (new AiCommerceService($wa))->handle($contact, 'do you have COD?');

        Http::assertSent(function ($request) {
            $system = $request['messages'][0]['content'];

            return str_contains($system, 'INR')
                && str_contains($system, 'cash_on_delivery')
                && str_contains($system, '500')
                && str_contains($system, 'STORE_INFO');
        });
    }

    /** The commerce-dashboard toggle also enables the assistant. */
    public function test_commerce_toggle_also_enables_assistant()
    {
        $team = Team::factory()->create([
            'ai_auto_reply_enabled' => false,
            'commerce_config' => ['ai_assistant_enabled' => true],
        ]);
        $contact = Contact::factory()->create(['team_id' => $team->id, 'phone_number' => '+911111111111']);
        set_setting("ai_openai_api_key_{$team->id}", 'test-key');

        $this->fakeAi([
            'matched' => false,
            'product_ids' => [],
            'reply_text' => 'Yes, we deliver.',
            'kb_gap' => false,
        ]);

        $wa = Mockery::mock(WhatsAppService::class);
        $wa->shouldReceive('setTeam')->once();
        $wa->shouldReceive('sendText')->once()->with('+911111111111', 'Yes, we deliver.')->andReturn(true);

        $this->assertTrue((new AiCommerceService($wa))->handle($contact, 'do you deliver?'));
    }
}
