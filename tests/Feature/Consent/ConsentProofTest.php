<?php

namespace Tests\Feature\Consent;

use App\Models\Contact;
use App\Services\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentProofTest extends TestCase
{
    use RefreshDatabase;

    public function test_opt_in_stores_proof_url()
    {
        $contact = Contact::factory()->create(['opt_in_status' => 'none']);
        $service = new ConsentService();

        $proofUrl = 'https://s3.bucket/proof.png';
        $service->optIn($contact, 'API', 'User consented via form', $proofUrl);

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'opt_in_status' => 'opted_in']);
        $this->assertDatabaseHas('consent_logs', [
            'contact_id' => $contact->id,
            'action' => 'OPT_IN',
            'proof_url' => $proofUrl
        ]);
    }
}
