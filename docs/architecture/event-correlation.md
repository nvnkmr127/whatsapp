# Event Correlation & Journey Reconstruction

## 1. Overview
The Event Correlation System allows us to track the causality and flow of operations across the system. It answers: *"What chain of events led to this specific state?"* (e.g., Which exact marketing campaign message triggered the specific order #1234?).

## 2. Terminology (Distributed Tracing Standard)
We adopt a simplified OpenTelemetry-style model:
*   **Trace ID**: A unique UUID representing a complete logical operation (e.g., "Process Webhook", "Send Campaign", "Checkout Flow").
*   **Span ID**: A unique UUID for a specific unit of work or event within that trace.
*   **Parent ID**: The `Span ID` of the event or process that *caused* the current event.

## 3. Correlation ID Generation Rules

### A. Entry Points (Roots)
Every "Edge" interaction generates a fresh `Trace ID` if one doesn't exist.
1.  **Incoming Webhooks (WhatsApp)**: Generate unique `trace_id`.
2.  **API Requests**: Use `X-Request-ID` header if present, else generate unique `trace_id`.
3.  **Scheduled Jobs**: Generate unique `trace_id` at the start of `handle()`.

### B. Propagation (Context Passing)
The `Trace ID` must be passed down to all children.

| Flow | Mechanism |
| :--- | :--- |
| **Synchronous Code** | Use `TraceContext` Singleton (Thread-local equivalent). |
| **Async Jobs** | Pass `trace_id` in the Job constructor/payload. |
| **Events** | Stored in `metadata.trace_id` and `metadata.parent_id`. |
| **Database Records** | Optional `correlation_id` column on pivot tables (e.g., `messages`, `orders`). |

## 4. Propagation Strategies

### A. Broadcast & Campaigns
1.  **Start**, Admin clicks "Send": Generates `Trace A`.
2.  **Job**, `SendCampaignMessageJob`: Inherits `Trace A`.
3.  **Event**, `MessageSent`: Metadata includes `trace_id: Trace A`.
4.  **Meta**, `Message` Model: Stores `metadata['trace_id'] = Trace A`.

### B. Inbox & Replies (The "Human" Gap)
This is harder because a user reply is technically a new HTTP request from WhatsApp.
*   **Strategy**: "Causal Linking" via `Context`.
    *   If User replies to Message X (`Trace A`):
    *   Webhook receives `context.id` pointing to Message X.
    *   System looks up Message X, finds `Trace A`.
    *   New Event `MessageReceived` gets `trace_id: New Trace B`, but `causal_trace_id: Trace A`.
    *   *Alternative (Simpler)*: We treat the "Conversation" as the container, and Tracing is for **system transactions** (like "Order Placement").

### C. Automations & Bots
1.  **Trigger**, `MessageReceived` (`Trace B`) fires.
2.  **Listener**, `AutomationTriggerListener` handles it.
3.  **Action**, System sends reply.
    *   The reply job/event inherits `trace_id: Trace B`.
    *   Result: We can see the user's message directly caused the bot's reply in the logs.

### D. Orders (Commerce)
1.  **Cart Updates**: Each "Add to Cart" event inherits the current interaction's `trace_id`.
2.  **Checkout**: When `createOrder` runs, we look at the active tracing context.
    *   If user clicked "Buy Now" button in a structured message flow -> Inherits that flow's `trace_id`.
    *   If manual web shop -> Inherits the Session's `trace_id`.

## 5. Event Schema Implementation

All events already have a `metadata` field (from our Event Contract). We formally define the structure:

```json
{
  "metadata": {
    "trace_id": "uuid-v4...",
    "span_id": "uuid-v4...",
    "parent_id": "uuid-v4... (optional)"
  }
}
```

## 6. Reconstruction (Journey Map)

To reconstruct a user's journey, we query the Event Lake (Logs) with two strategies:

### A. Transaction Trace (Vertical)
*"Show me everything that happened during Checkout #55"*
Query: `SELECT * FROM events WHERE metadata.trace_id = 'Trace-Checkout-55' ORDER BY occurred_at`

### B. User Timeline (Horizontal)
*"Show me the user's path from Campaign -> Order"*
This requires joining traces via "Causal Links" or common entities (Contact ID).

Query:
1.  Find `Contact ID`.
2.  Select events for `Contact ID` sorted by time.
3.  Visualize `Trace ID` clusters to group "Interactive Sessions".

## 7. Handling Failures / Invalid Events
If an event loses its context (e.g., a job failed to pass it), we:
1.  **Generate a new Trace ID** (Treat as new root).
2.  **Log a Warning**: "Broken Trace Chain at [Class Name]".
3.  **Link Fallback**: Attempt to link via `user_id` or `reference_id` during data analysis.
