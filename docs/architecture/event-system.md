# Event System Contract & Design

## 1. Core Philosophy
The strict event contract system ensures that all events emitted within the system adhere to a rigid schema. This prevents "data drift," where listeners crash due to missing keys or changed data types effectively turning the event bus into a typed API.

## 2. Event Structure (The Contract)

Every event MUST extend `App\Events\Base\DomainEvent` and provide the following:

### A. Standard Metadata (Automatically Enforced)
| Field | Type | Description |
| :--- | :--- | :--- |
| `event_id` | UUID | Unique identifier for the specific event instance. |
| `occurred_at` | ISO-8601 | Timestamp of when the event happened. |
| `version` | int | Schema version (default: 1). |
| `source` | string | The module/service originating the event (e.g., `commerce`, `auth`). |
| `context` | array | Tracing info (User ID, Request ID, Team ID) for auditing. |

### B. Payload (Validation Required)
Each event class must define a `rules()` method returning standardized Laravel validation rules. The payload is validated **synchronously** upon instantiation.

## 3. Versioning Strategy

### Semantic Versioning Rules
1.  **Non-Breaking Changes (Minor)**:
    *   Adding a new field (nullable or with default).
    *   Relaxing validation rules.
    *   *Strategy*: Increment internal `$version` constant only. JSON serialization includes new fields.
2.  **Breaking Changes (Major)**:
    *   Renaming or removing a field.
    *   Changing data types (e.g., `string` -> `array`).
    *   Adding a required field without default.
    *   *Strategy*: create a **New Event Class** (e.g., `OrderPlacedV2`).
    *   *Backward/Forward Compatibility*: Transformers (Upcasters) can be written if listeners need to handle both, but generally, code emits V2, and listeners are updated to handle V2.

## 4. Validation & Error Handling

### Emit Time Validation
Validation occurs in the `__construct` method of the `BaseEvent`.

```php
public function __construct(array $payload) {
    $validator = Validator::make($payload, $this->rules());
    if ($validator->fails()) {
       throw new InvalidDomainEventException($validator->errors());
    }
}
```

### Invalid Event Handling
*   **Development/Testing**: Exception is thrown immediately, halting execution. Usage of invalid events is treated as a severe bug (500 Error).
*   **Production**: Exception is thrown. We do **not** emit invalid events silently. It is better to fail the request than to corrupt the data lake or crash downstream listeners unpredictably.

## 5. Implementation Guide

### Defining an Event
```php
class OrderPlaced extends DomainEvent
{
    protected int $version = 1;
    
    public function source(): string {
        return 'commerce';
    }

    public function rules(): array {
        return [
            'order_id' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3'
        ];
    }
}
```
