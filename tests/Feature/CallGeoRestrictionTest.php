<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Services\CallPermissionService;
use Tests\TestCase;

/**
 * Business-initiated calls are restricted by Meta in US/CA/EG/VN/NG.
 * Guards the country list and the phone_number field it reads.
 */
class CallGeoRestrictionTest extends TestCase
{
    private CallPermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CallPermissionService;
    }

    public function test_nigeria_is_restricted(): void
    {
        $contact = new Contact(['phone_number' => '+2348012345678']);
        $this->assertFalse($this->service->validateGeographicRestrictions($contact));
    }

    public function test_us_is_restricted(): void
    {
        $contact = new Contact(['phone_number' => '+15551234567']);
        $this->assertFalse($this->service->validateGeographicRestrictions($contact));
    }

    public function test_unrestricted_market_is_allowed(): void
    {
        $contact = new Contact(['phone_number' => '+919812345678']); // India
        $this->assertTrue($this->service->validateGeographicRestrictions($contact));
    }

    public function test_missing_number_does_not_error(): void
    {
        $contact = new Contact(['phone_number' => null]);
        $this->assertTrue($this->service->validateGeographicRestrictions($contact));
    }
}
