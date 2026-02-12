<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamWallet;
use App\Models\User;
use App\Models\Contact;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BillingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_billing_requests_do_not_exceed_balance()
    {
        // Setup
        $team = Team::factory()->create(['name' => 'Billing Test Team']);
        $wallet = TeamWallet::create(['team_id' => $team->id, 'balance' => 0.15]); // Enough for 1 marketing msg (0.10) but not 2

        $contact1 = Contact::factory()->create(['team_id' => $team->id]);
        $contact2 = Contact::factory()->create(['team_id' => $team->id]);

        $service = new BillingService();
        $category = 'marketing'; // Cost 0.10

        // 1. First deduction (Contact 1)
        $result1 = $service->recordConversationUsage($team, $contact1->id, $category, 'wamid_1');

        // 2. Second deduction (Contact 2) - Should FAIL because remaining balance 0.05 < 0.10
        $result2 = $service->recordConversationUsage($team, $contact2->id, $category, 'wamid_2');

        $this->assertTrue($result1, 'First charge should succeed');
        $this->assertFalse($result2, 'Second charge should fail due to insufficient funds');

        $wallet->refresh();
        $this->assertEqualsWithDelta(0.05, $wallet->balance, 0.0001, 'Balance should be 0.05');
    }
}
