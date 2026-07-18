# Testing Patterns

**Analysis Date:** 2026-07-18

## Test Framework

**Runner:**
- PHPUnit 11.5.3
- Config: `phpunit.xml`

**Assertion Library:**
- PHPUnit's built-in assertions (`assertEquals`, `assertNotNull`, `assertDatabaseHas`, etc.)
- Illuminate testing helpers (`$response->assertStatus()`, `Mail::assertSent()`)

**Run Commands:**
```bash
php artisan test                              # Run all tests
php artisan test --filter ClassName           # Run single test class
php artisan test tests/Unit/OTPServiceTest.php # Run specific file
php artisan test --parallel                   # Run tests in parallel
php artisan test --coverage                   # Generate coverage report
```

## Test File Organization

**Location:**
- Unit tests: `tests/Unit/`
- Feature tests: `tests/Feature/`
- Organized by namespace: `Tests\Unit\`, `Tests\Feature\`

**Naming:**
- Files: `{Feature/Class}Test.php` (e.g., `InboundMessageTest.php`, `OTPServiceTest.php`)
- Classes: `{Feature/Class}Test` namespace
- Test methods: `snake_case` (older) OR `PascalCase` with `#[Test]` attribute (newer)

**Structure:**
```
tests/
├── Unit/
│   ├── EmailServiceTest.php
│   ├── OTPServiceTest.php
│   └── PolicyServiceTest.php
├── Feature/
│   ├── InboundMessageTest.php
│   ├── CallWebhookTest.php
│   ├── WhatsAppWebhookTest.php
│   └── WhatsApp/
│       └── WebhookPipelineTest.php
├── TestCase.php
└── webhook_test.php (manual integration test)
```

## Test Base Class

**`tests/TestCase.php`:**
```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        // Fix permission issues by using a temp directory
        $path = '/tmp/laravel_storage_test';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        // Create subdirectories
        mkdir($path.'/framework/views', 0777, true);
        mkdir($path.'/framework/cache', 0777, true);
        mkdir($path.'/framework/sessions', 0777, true);
        
        $app->useStoragePath($path);
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }
}
```

All test classes extend this `TestCase`.

## Test Structure

**Typical Test Class Setup:**

```php
<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallWebhookTest extends TestCase
{
    use RefreshDatabase;  // Rollback database after each test

    protected $team;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Shared test data creation
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create([
            'whatsapp_phone_number_id' => '1234567890',
        ]);

        $this->team->users()->attach($this->user, ['role' => 'admin']);
    }

    /** @test */
    public function it_handles_incoming_call_webhook()
    {
        Event::fake();
        
        $payload = [ /* ... */ ];
        $response = $this->postWebhook($payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('whatsapp_calls', [
            'call_id' => 'call_123',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();  // Clean up mocks
    }
}
```

**Key Elements:**
1. `use RefreshDatabase` - Wraps tests in database transaction, rolls back after
2. `protected function setUp(): void { parent::setUp(); }` - Initialize shared test data
3. `/** @test */` or `#[Test]` attribute - Mark test methods
4. `protected function tearDown()` - Cleanup (Mockery, etc.)

## Test Types & Patterns

### Unit Tests

**Scope:** Individual service/class in isolation, with dependencies mocked

**Files:**
- `tests/Unit/OTPServiceTest.php` - Service testing with mocked dependencies
- `tests/Unit/EmailServiceTest.php` - Email dispatcher testing
- `tests/Unit/PolicyServiceTest.php` - Policy/permission testing

**Pattern:**
```php
<?php

namespace Tests\Unit;

use App\Services\OTPService;
use Mockery;
use Tests\TestCase;

class OTPServiceTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_send_caches_email_otp_after_successful_delivery()
    {
        // Create anonymous class extending the service to override protected methods
        $service = new class extends OTPService
        {
            protected function sendEmail(string $email, string $code): bool
            {
                return true;
            }

            public function cacheKeyFor(string $identifier): string
            {
                return $this->getCacheKey($identifier);
            }
        };

        $identifier = 'success@example.com';

        $this->assertTrue($service->send($identifier, 'email', 1));
        $this->assertNotNull(\Illuminate\Support\Facades\Cache::get($service->cacheKeyFor($identifier)));
    }

    public function test_send_custom_whatsapp_otp_caches_after_successful_template_send()
    {
        // Mock external service
        $mock = Mockery::mock(\App\Services\WhatsAppService::class);
        $mock->shouldReceive('setTeam')->andReturnSelf();
        $mock->shouldReceive('sendTemplate')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'messages' => [
                        ['id' => 'wamid.test'],
                    ],
                ],
            ]);
        app()->instance(\App\Services\WhatsAppService::class, $mock);

        $service = new class extends OTPService { };
        $phone = '+918688771398';
        $code = '654321';
        $team = new Team;
        $team->id = 456;

        $this->assertTrue($service->sendCustomWhatsAppOtp($phone, $code, 'verification', 'en', [$code], $team));
    }
}
```

**Mocking Strategy:**
- Use anonymous class extensions to override protected methods
- Use Mockery for external service dependencies
- Bind mocks to container: `app()->instance(ClassName::class, $mock)`

### Feature Tests

**Scope:** Full request/response cycle, database included, but external services stubbed

**Webhook Testing Pattern:**
```php
<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Config::set('whatsapp.app_secret', 'test-secret');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_rejects_webhook_post_with_invalid_signature()
    {
        config(['whatsapp.app_secret' => 'test_secret']);
        $payload = ['object' => 'whatsapp_business_account', 'entry' => []];

        $response = $this->withHeaders([
            'X-Hub-Signature-256' => 'sha256=invalid_hash',
        ])->postJson('/api/webhook/whatsapp', $payload);

        $response->assertStatus(403);
        $this->assertEquals('Invalid Signature', $response->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_accepts_webhook_post_with_valid_signature()
    {
        $secret = 'test_secret';
        config(['whatsapp.app_secret' => $secret]);
        $payload = ['object' => 'whatsapp_business_account', 'entry' => []];
        $jsonPayload = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $jsonPayload, $secret);

        $response = $this->withHeaders([
            'X-Hub-Signature-256' => $signature,
        ])->postJson('/api/webhook/whatsapp', $payload);

        $response->assertStatus(200);
        $this->assertEquals('EVENT_RECEIVED', $response->getContent());
    }
}
```

**Call Webhook Testing:**
```php
class CallWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function postWebhook(array $payload)
    {
        config(['whatsapp.app_secret' => 'test_secret']);

        // Manually compute signature to ensure payload/signature match
        $content = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $content, 'test_secret');

        $response = $this->withHeaders([
            'X-Hub-Signature-256' => $signature,
        ])->postJson('/api/webhook/whatsapp/calls', $payload);

        return $response;
    }

    /** @test */
    public function it_handles_incoming_call_webhook()
    {
        \Illuminate\Support\Facades\Event::fake();

        $payload = [
            'entry' => [
                [
                    'id' => $this->team->whatsapp_phone_number_id,
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'phone_number_id' => $this->team->whatsapp_phone_number_id,
                                ],
                                'calls' => [
                                    [
                                        'call_id' => 'call_123',
                                        'status' => 'ringing',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postWebhook($payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('whatsapp_calls', [
            'call_id' => 'call_123',
            'status' => 'ringing',
        ]);
    }
}
```

## Message Lifecycle End-to-End Testing

**Full flow tested in `tests/Feature/WhatsApp/WebhookPipelineTest.php`:**

```php
/**
 * Test path: Webhook → Job → Message Creation → Event
 */
public function test_webhook_storage_and_job_dispatch()
{
    Queue::fake();  // Don't actually queue jobs

    $payload = ['entry' => []];
    $signature = 'sha256='.hash_hmac('sha256', json_encode($payload), 'test-secret');

    $response = $this->withHeaders([
        'X-Hub-Signature-256' => $signature,
    ])->postJson('/api/webhook/whatsapp', $payload);

    $response->assertStatus(200);

    // Verify webhook payload stored
    $this->assertDatabaseHas('webhook_payloads', [
        'status' => 'pending',
    ]);

    // Verify job was dispatched
    Queue::assertPushed(\App\Jobs\ProcessWebhookJob::class);
}

/**
 * Test path: WebhookPayload → ProcessWebhookJob → Message Persistence
 */
public function test_job_processes_message_successfully()
{
    $team = Team::factory()->create([
        'whatsapp_phone_number_id' => '123456789',
    ]);

    $payloadData = [
        'entry' => [[
            'changes' => [[
                'value' => [
                    'metadata' => ['phone_number_id' => '123456789'],
                    'messages' => [[
                        'from' => '9876543210',
                        'id' => 'wamid.test',
                        'type' => 'text',
                        'text' => ['body' => 'Hello World'],
                    ]],
                    'contacts' => [[
                        'profile' => ['name' => 'Tester'],
                    ]],
                ],
            ]],
        ]],
    ];

    $payloadRecord = WebhookPayload::create([
        'payload' => $payloadData,
        'status' => 'pending',
    ]);

    // Manually call job handler (not dispatched)
    $job = new ProcessWebhookJob($payloadRecord->id);
    app()->call([$job, 'handle']);

    // Verify message persisted
    $this->assertDatabaseHas('messages', [
        'whatsapp_message_id' => 'wamid.test',
        'content' => 'Hello World',
        'team_id' => $team->id,
    ]);

    // Verify payload marked processed
    $this->assertEquals('processed', $payloadRecord->fresh()->status);
}

/**
 * Test idempotency: Duplicate message IDs shouldn't create duplicate records
 */
public function test_job_handles_duplicate_message_idempotently()
{
    $team = Team::factory()->create([
        'whatsapp_phone_number_id' => '123456789',
    ]);

    // Create first message
    Message::factory()->create([
        'whatsapp_message_id' => 'wamid.duplicate',
        'team_id' => $team->id,
    ]);

    $payloadData = [
        'entry' => [[
            'changes' => [[
                'value' => [
                    'metadata' => ['phone_number_id' => '123456789'],
                    'messages' => [[
                        'from' => '9876543210',
                        'id' => 'wamid.duplicate',  // Same ID
                        'type' => 'text',
                        'text' => ['body' => 'Hello Again'],
                    ]],
                ],
            ]],
        ]],
    ];

    $payloadRecord = WebhookPayload::create([
        'payload' => $payloadData,
        'status' => 'pending',
    ]);

    $job = new ProcessWebhookJob($payloadRecord->id);
    app()->call([$job, 'handle']);

    // Verify only ONE message exists (not duplicated)
    $this->assertCount(1, Message::where('whatsapp_message_id', 'wamid.duplicate')->get());
}
```

**InboundMessageTest lifecycle:**
```php
/**
 * Full inbound flow: Webhook → Contact/Conversation → Message
 */
public function test_inbound_message_persistence_and_retrieval()
{
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

    Event::fake([\App\Events\MessageReceived::class]);

    // Execute job directly (bypasses async queue)
    $job = new PersistMessageJob($payload);
    $job->handle();

    // Assertions
    $contact = Contact::first();
    $this->assertNotNull($contact);
    $this->assertEquals($team->id, $contact->team_id);

    $conversation = Conversation::where('contact_id', $contact->id)->first();
    $this->assertNotNull($conversation);
    $this->assertEquals('open', $conversation->status);

    $message = Message::where('whatsapp_message_id', $payload['provider_id'])->first();
    $this->assertNotNull($message);
    $this->assertEquals('Hello World', $message->content);
    $this->assertEquals('inbound', $message->direction);

    Event::assertDispatched(\App\Events\MessageReceived::class);
}

/**
 * Test opt-out/stop keyword processing in message lifecycle
 */
public function test_custom_opt_out_keyword_opts_out_and_sends_confirmation()
{
    $team = Team::factory()->create([
        'opt_out_keywords' => ['Unsubscribe'],
        'opt_out_message' => 'You have been unsubscribed.',
        'opt_out_message_enabled' => true,
    ]);

    Event::fake([\App\Events\MessageReceived::class]);
    Queue::fake([\App\Jobs\SendMessageJob::class]);

    $payload = [
        'provider_id' => 'wamid.test.'.uniqid(),
        'from_phone' => '15550000001',
        'contact_name' => 'Opt Out User',
        'type' => 'text',
        'content' => ['type' => 'text', 'text' => ['body' => 'unsubscribe']],  // Matches keyword
    ];

    (new PersistMessageJob($payload))->handle();

    // Verify contact opted out
    $this->assertEquals('opted_out', Contact::first()->opt_in_status);

    // Verify confirmation message queued
    Queue::assertPushed(\App\Jobs\SendMessageJob::class);
}
```

## Mocking & Faking

**Event Faking:**
```php
Event::fake();                                    // Fake all events
Event::fake([\App\Events\MessageReceived::class]); // Fake specific event

// In test:
Event::assertDispatched(\App\Events\MessageReceived::class);
Event::assertNotDispatched(\App\Events\CallEnded::class);
```

**Queue Faking:**
```php
Queue::fake();                                    // Don't dispatch jobs
Queue::fake([\App\Jobs\SendMessageJob::class]);   // Fake specific job

// In test:
Queue::assertPushed(\App\Jobs\SendMessageJob::class);
Queue::assertNotPushed(\App\Jobs\ProcessWebhookJob::class);
Queue::assertPushedWithPayload(\App\Jobs\SendMessageJob::class, function ($payload) {
    return $payload['message_id'] === 123;
});
```

**Mail Faking:**
```php
Mail::fake();

// In test:
Mail::assertSent(\App\Mail\DynamicSystemMail::class, function ($mail) {
    return $mail->mailer === 'transactional';
});
```

**Config Override:**
```php
config(['whatsapp.app_secret' => 'test_secret']);
config(['mail.from.address' => 'test@example.com']);
```

**Mockery:**
```php
use Mockery;

$mock = Mockery::mock(\App\Services\WhatsAppService::class);
$mock->shouldReceive('setTeam')->andReturnSelf();
$mock->shouldReceive('sendTemplate')
    ->once()
    ->with('phone', Mockery::any())
    ->andReturn(['success' => true]);

app()->instance(\App\Services\WhatsAppService::class, $mock);

// Remember teardown:
protected function tearDown(): void
{
    Mockery::close();
    parent::tearDown();
}
```

## Database Configuration

**Testing Database:**
- SQLite in-memory (`:memory:`)
- Set in `phpunit.xml`:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

**Migration:** Automatically run before each test suite via `RefreshDatabase` trait

**Assertions:**
```php
$this->assertDatabaseHas('contacts', [
    'phone_number' => '+15550000000',
    'team_id' => $team->id,
]);

$this->assertDatabaseMissing('webhook_payloads', [
    'status' => 'pending',
    'id' => 999,
]);
```

## Factory Usage

**Creating Test Data:**
```php
$user = User::factory()->create();
$user = User::factory()->create(['email' => 'test@example.com']);
$users = User::factory()->count(5)->create();

$team = Team::factory()->create([
    'whatsapp_phone_number_id' => '123456789',
    'subscription_status' => 'active',
]);

$message = Message::factory()->create([
    'team_id' => $team->id,
    'whatsapp_message_id' => 'wamid.test',
    'direction' => 'inbound',
]);
```

**Relationship Attachment:**
```php
$team->users()->attach($user, ['role' => 'admin']);
```

## Test Execution

**PHPUnit Configuration (`phpunit.xml`):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <ini name="memory_limit" value="1G"/>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
    </php>
</phpunit>
```

## Coverage Gaps & Issues

**Missing Test Coverage Areas:**

1. **Listener Tests:** 
   - No dedicated tests for listeners (e.g., `SendOutboundWebhook`, `AutomationTriggerListener`)
   - Currently tested indirectly through feature tests
   - `Fix approach:` Add `tests/Unit/Listeners/` with isolated listener tests

2. **Service Layer Coverage:**
   - Only 3 service tests exist: `OTPServiceTest`, `EmailServiceTest`, `PolicyServiceTest`
   - Many services lack unit tests: `WebhookService`, `WorkflowEngine`, `AutomationService`, `RateLimitService`
   - `Fix approach:` Create unit tests for each public service method

3. **Observer Tests:**
   - No tests for model observers (Eloquent hooks)
   - `ContactObserver`, `TeamObserver`, `UserObserver` not covered
   - `Fix approach:` Test observers via model events or separate observer test files

4. **Error Path Testing:**
   - Job failure handling (`failed()` method) rarely tested
   - Exception scenarios in services not covered
   - `Fix approach:` Add negative test cases for error paths

5. **Message Status Flow:**
   - Outbound message lifecycle (send → delivered → read) not fully end-to-end tested
   - Status update webhooks lack dedicated test coverage
   - `Fix approach:` Add `OutboundMessageLifecycleTest.php`

6. **Webhook Retry Logic:**
   - Job retry with backoff not tested
   - `Process failures and re-delivery not covered
   - `Fix approach:` Test `failed()` method and job release logic

7. **Broadcast Events:**
   - Real-time broadcast channel logic not tested
   - `broadcastWith()` payload structure not validated
   - `Fix approach:` Add broadcast channel tests

8. **API Endpoint Tests:**
   - Limited endpoint-level tests
   - Most coverage is webhook-focused
   - `Fix approach:` Add tests for REST API endpoints in `tests/Feature/API/`

## Test Best Practices Used

1. **AAA Pattern:** Arrange → Act → Assert clearly separated
2. **One assertion per test (mostly):** Tests fail clearly
3. **Descriptive names:** Test names explain what they test
4. **Setup reuse:** `setUp()` method for common initialization
5. **Isolation:** `RefreshDatabase` ensures test isolation
6. **No test interdependence:** Tests run independently
7. **Mocking external services:** Predictable, fast tests
8. **Trace context in assertions:** Correlate logs with tests

## Running Specific Test Suites

```bash
# All tests
php artisan test

# Unit tests only
php artisan test tests/Unit

# Feature tests only
php artisan test tests/Feature

# Single class
php artisan test tests/Feature/InboundMessageTest.php

# Single method
php artisan test --filter=test_inbound_message_persistence_and_retrieval

# With coverage
php artisan test --coverage

# Parallel execution
php artisan test --parallel
```

---

*Testing analysis: 2026-07-18*
