# Coding Conventions

**Analysis Date:** 2026-07-18

## Naming Patterns

**Files:**
- Classes: `PascalCase.php` (e.g., `WhatsAppService.php`, `ProcessWebhookJob.php`)
- Trait files: `PascalCase.php` (e.g., `ChecksTenantMaintenanceMode.php`)
- Config files: kebab-case (e.g., `whatsapp.php`, `webhook-platforms.php`)

**Functions & Methods:**
- Public/protected: `camelCase` - `dispatch()`, `sendWebhook()`, `broadcastOn()`
- Private: `camelCase` - `verifySignature()`, `resolveTeamId()`
- Test methods: `snake_case` (older) or `PascalCase` with `#[Test]` attribute (newer style)

**Variables:**
- Local: `camelCase` - `$teamId`, `$payloadRecord`, `$traceId`
- Properties: `camelCase` - `$phoneId`, `$message`, `$backoff`
- Constants: `UPPER_SNAKE_CASE` - `SKIPPED_DUPLICATE`

**Types:**
- Model properties: PHPDoc `@property` tags above class definition
```php
/**
 * @property int $id
 * @property string|null $whatsapp_phone_number_id
 */
class Team extends JetstreamTeam { }
```
- DTO properties: Constructor property promotion (PHP 8.2+)
```php
public function __construct(
    public array $errors = [],
    public bool $isValid = false
)
```

## Code Style

**Formatting:**
- Laravel Pint (^1.24) is configured as require-dev, but no `pint.json` exists
- Uses default Pint configuration (PSR-12 + Laravel conventions)
- Namespace declaration at top, blank line after
- Use statements grouped: Laravel/Illuminate first, then app-specific, then third-party
- Two-space soft tabs (standard Laravel)

**Linting:**
- No explicit `.eslintrc` or custom configuration found
- Relies on Pint defaults during CI/CD

## Import Organization

**Order:**
1. PHP built-in/namespaces
2. Illuminate/Laravel facades and contracts
3. App-specific (App\Models, App\Services, App\Events, App\Jobs)
4. Third-party packages (Mockery, etc.)
5. Local use statements last

**Example:**
```php
<?php
namespace App\Jobs;

use App\Models\Team;
use App\Models\WebhookPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
```

**Path Aliases:**
- No custom aliases configured in `composer.json`
- Standard PSR-4 namespacing: `App\` → `app/`

## Error Handling

**Patterns:**
1. **Service Layer:** Try/catch with logging, never throw to caller if non-critical
   ```php
   try {
       \App\Models\ActivityLog::create([...]);
   } catch (\Exception $e) {
       \Log::error("Audit log failed: ".$e->getMessage());
   }
   ```

2. **Job Failure:** Implement `failed()` method for cleanup
   ```php
   public function failed(\Throwable $exception): void {
       Log::error("Job FAILED");
       WebhookPayload::where('id', $this->payloadId)->update([
           'status' => 'failed',
           'error_message' => $exception->getMessage(),
       ]);
   }
   ```

3. **Controller:** Return early with error response, log before returning
   ```php
   if (empty($verifyToken)) {
       Log::error('WhatsApp webhook verify token not configured');
       return response('Webhook verify token not configured', 500);
   }
   ```

4. **Idempotent Retries:** Cache event IDs to prevent duplicate processing
   ```php
   $cacheKey = "whatsapp_processed_event:{$eventId}";
   if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
       return; // Skip duplicate
   }
   ```

## Logging

**Framework:** `Illuminate\Support\Facades\Log` (Laravel's default)

**Patterns:**
- `Log::info()` - Normal operation flow, performance metrics
- `Log::warning()` - Recoverable issues (invalid signatures, missing tokens)
- `Log::error()` - Failures that need investigation

**Structured Logging:**
```php
Log::info('WhatsApp Webhook Handled', [
    'payload_id' => $payloadRecord->id,
    'trace_id' => $traceId,
    'duration_ms' => $duration,
]);
```

**Special Markers:**
- `[PROD-HARDENING]` - Comments marking production safety measures
- `[NEW]` - Recent feature additions
- Inline comments explain "why" not "what"

## Comments

**When to Comment:**
- Complex business logic or non-obvious decisions
- Workarounds for third-party limitations
- PROD-HARDENING sections for reliability patterns
- DO NOT comment obvious code

**JSDoc/TSDoc:**
- PHPDoc for class properties: `@property` tags
- Method doc: Brief description of purpose and parameters
- Example from `Team.php`:
```php
/**
 * Get all of the users that belong to the team.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
 */
public function users()
```

## Function Design

**Size:** Methods typically 10-30 lines; complex logic delegates to separate methods

**Parameters:**
- Type hints required (PHP 8.2+)
- Use `?Type` for nullable
- Avoid more than 3 parameters; use DTO if needed

**Return Values:**
- Typed returns required
- Use union types sparingly (`string|int` only when necessary)
- `void` for methods with side effects only

**Example:**
```php
public function dispatch(?int $teamId, string $eventType, array $data): void
protected function sendWebhook(WebhookSubscription $subscription, string $eventType, array $data): void
protected function resolveTeamId(array $body, array $change, ?string $phoneId): ?int
```

## Module Design

**Exports:**
- Services: Single public class with injected dependencies
- Jobs: Extend Laravel's `Job` base, implement `ShouldQueue`
- Events: Extend Laravel's `Event`, implement `ShouldBroadcastNow` if needed
- Listeners: Implement `ShouldQueue`, define `$tries` and `$backoff`

**Example Service Structure:**
```php
namespace App\Services;

class WebhookService
{
    public function dispatch(?int $teamId, string $eventType, array $data): void { }
    protected function sendWebhook(...) { }
}
```

**Example Job Structure:**
```php
namespace App\Jobs;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3;
    public $backoff = [10, 60, 300];
    
    public function __construct($payloadId, $traceId = null) { }
    public function handle(Service $service): void { }
    public function failed(\Throwable $exception): void { }
}
```

**Barrel Files:**
- Not used in this codebase; imports always reference specific files

## Service Layer Patterns

**Single Responsibility:**
- Services focus on one domain concern
- Methods are public if called from controllers/listeners, protected if internal
- Example: `WebhookService` handles webhook dispatch only

**Dependency Injection:**
- Constructor injection or method-level injection via Laravel container
- Example: `public function handle(\App\Services\EventBusService $eventBus): void`

**No Static Methods:**
- Services instantiated via `new` or resolved from container
- Example: `$webhookService = new \App\Services\WebhookService;`

## Job Queue Patterns

**Queue Configuration:**
- Defined in constructor: `$this->onQueue('webhooks')`
- Multiple queues used: `webhooks`, `ai_processing`, `broadcasts`, `notifications`, `default`
- Retry strategy: `$tries` + `$backoff` array (staggered delays)

**Async Processing:**
- Long-running logic dispatched as jobs: webhook payload storage → async processing
- Webhook handler stores payload synchronously, dispatches job asynchronously
- Trace context passed through job for observability

**Example:**
```php
class ProcessWebhookJob implements ShouldQueue
{
    public $tries = 3;
    public $backoff = [10, 60, 300]; // 10s, 60s, 300s
    
    public function __construct($payloadId, $traceId = null)
    {
        $this->payloadId = $payloadId;
        $this->traceId = $traceId;
        $this->onQueue('webhooks');
    }
}
```

## Event & Listener Patterns

**Events:**
- Named after what happened: `MessageReceived`, `CallEnded`, `ContactCreated`
- Use `ShouldBroadcastNow` to push to WebSocket (Reverb/Pusher)
- Implement `broadcastAs()`, `broadcastOn()`, `broadcastWith()` for real-time updates
- Pass model ID in broadcast (not full model) to reduce payload

**Listeners:**
- Implement `ShouldQueue` for async handling
- Define retry strategy: `$tries`, `$backoff`
- One listener per responsibility (e.g., `SendOutboundWebhook`, `LogContactEvents`)
- Registered in `AppServiceProvider::boot()` using `Event::listen()`

**Example Event:**
```php
class MessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(public Message $message) { }
    
    public function broadcastAs(): string
    {
        return 'MessageReceived';
    }
    
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('teams.'.$this->message->team_id),
            new PresenceChannel('conversation.'.$this->message->conversation_id),
        ];
    }
    
    public function broadcastWith(): array
    {
        return ['message' => [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'timestamp' => now()->timestamp,
        ]];
    }
}
```

## Request Validation Patterns

**Authorization:**
- Implement `authorize()` returning bool
- Check user's team membership: `$this->user()->currentTeam !== null`

**Validation Rules:**
- Return array from `rules()` method
- Use Laravel's validation syntax
- Regex patterns for specific formats (phone numbers, etc.)

**Example:**
```php
class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentTeam !== null;
    }
    
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'name' => 'nullable|string',
            'custom_attributes' => 'nullable|array',
        ];
    }
}
```

## Trait Usage

**Common Traits:**
- `ChecksTenantMaintenanceMode` - Added to jobs for maintenance checks
- `HasFactory` - On models for factory support
- `HasApiTokens` - On User/Team for Sanctum token auth
- `RefreshDatabase` - On tests for transaction rollback between tests

**Example:**
```php
class ProcessWebhookJob implements ShouldQueue
{
    use \App\Traits\ChecksTenantMaintenanceMode;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
}
```

## Trace Context & Observability

**Trace ID Propagation:**
- Generated on request entry in `TraceContext::ensureTraceId()`
- Passed through job payloads via `Queue::createPayloadUsing()`
- Restored in job handlers: `TraceContext::set($this->traceId)`
- Logged in every structured log entry for correlation

**Pattern:**
```php
$traceId = \App\Services\TraceContext::getTraceId();
\App\Jobs\ProcessWebhookJob::dispatch($payloadRecord->id, $traceId)->onQueue('webhooks');

Log::info('WhatsApp Webhook Handled', [
    'payload_id' => $payloadRecord->id,
    'trace_id' => $traceId,
]);
```

## Model Relationships

**Relation Definition:**
- Eloquent relations return fluent interface
- Use `withPivot()` for pivot data in many-to-many
- Use `as()` to alias pivot as property

**Example from Team model:**
```php
public function users()
{
    return $this->belongsToMany(User::class, \Laravel\Jetstream\Jetstream::membershipModel())
        ->withPivot('role', 'receives_tickets', 'call_status')
        ->withTimestamps()
        ->as('membership');
}
```

**Eager Loading:**
- Define `$with` property to auto-eager-load commonly needed relations
```php
protected $with = ['wallet'];
```

---

*Convention analysis: 2026-07-18*
