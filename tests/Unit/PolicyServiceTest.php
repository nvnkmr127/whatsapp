<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Services\PolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_send_free_message_returns_true_within_24h()
    {
        $contact = new Contact(['last_interaction_at' => now()->subHours(1)]);
        $this->assertTrue((new PolicyService)->canSendFreeMessage($contact));
    }

    public function test_can_send_free_message_returns_false_after_24h()
    {
        $contact = new Contact(['last_interaction_at' => now()->subHours(25)]);
        $this->assertFalse((new PolicyService)->canSendFreeMessage($contact));
    }

    public function test_can_send_free_message_returns_false_if_opted_out()
    {
        $contact = new Contact([
            'last_interaction_at' => now()->subHours(1),
            'opt_in_status' => 'opted_out'
        ]);
        $this->assertFalse((new PolicyService)->canSendFreeMessage($contact));
    }

    public function test_can_send_template_marketing_requires_opt_in()
    {
        $policy = new PolicyService;

        $optedIn = new Contact(['opt_in_status' => 'opted_in']);
        $this->assertTrue($policy->canSendTemplate($optedIn, 'MARKETING'));

        $unknown = new Contact(['opt_in_status' => 'unknown']);
        $this->assertFalse($policy->canSendTemplate($unknown, 'MARKETING'));
    }

    public function test_can_send_template_utility_allowed_implicitly()
    {
        $policy = new PolicyService;

        $unknown = new Contact(['opt_in_status' => 'unknown']);
        $this->assertTrue($policy->canSendTemplate($unknown, 'UTILITY'));
    }

    public function test_can_send_template_blocked_globally_if_opted_out()
    {
        $policy = new PolicyService;

        $optedOut = new Contact(['opt_in_status' => 'opted_out']);
        $this->assertFalse($policy->canSendTemplate($optedOut, 'UTILITY'));
        $this->assertFalse($policy->canSendTemplate($optedOut, 'MARKETING'));
    }
}
