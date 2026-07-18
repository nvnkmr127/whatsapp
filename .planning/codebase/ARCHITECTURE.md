# Architecture

**Analysis Date:** 2026-07-18

## Pattern Overview

**Overall:** Event-driven, queue-based message processing with multi-layer separation of concerns.

**Key Characteristics:**
- Webhook-triggered, asynchronous event processing via Laravel queues
- Strict inbound (webhook → job → persistence) and outbound (user action → job → WhatsApp API) separation
- Team-based multi-tenancy with WABA/phone number resolution
- Event bus publishing to `broadcast_events` table for side effects
- Real-time Livewire components for UI updates via broadcasting

## Layers

**Webhook Ingestion (Entry Layer):**
- Purpose: Receive and validate external events from WhatsApp Cloud API, commerce integrations, and inbound webhooks
- Location: `app/Http/Controllers/WhatsAppWebhookController.php`, `app/Http/Controllers/Webhooks/`, `app/Http/Controllers/Api/InboundWebhookController.php`
- Contains: Request verification, signature validation, payload storage
- Depends on: HTTP request/response, database storage
- Used by: WhatsApp Cloud API, Shopify, WooCommerce, custom integrations

**Queue Processing Layer:**
- Purpose: Asynchronously process queued jobs for message persistence, status updates, and side effects
- Location: `app/Jobs/`
- Contains: Job classes with retry logic, backoff strategies, and failure handling
- Depends on: Laravel Queue (Redis/database), database models, services
- Used by: Webhook layer (dispatches), job queue (consumes)
- Key jobs:
  - `ProcessWebhookJob` - Parse webhook, route events, resolve team context
  - `PersistMessageJob` - Store inbound messages, manage contacts/conversations, trigger workflows
  - `SendMessageJob` - Send outbound messages via WhatsApp API
  - `UpdateMessageStatusJob` - Update message status from webhooks

**Routing Layer:**
- Purpose: Classify webhook events and route to appropriate handlers
- Location: `app/Core/Webhooks/WhatsAppEventRouter.php`
- Contains: Event type detection (messages, status updates, templates, calls, account updates)
- Depends on: Webhook payload structure, event bus, services
- Used by: `ProcessWebhookJob`
- Routes to: Event-specific handlers and message persistence

**Services Layer:**
- Purpose: Encapsulate business logic for reusable domain operations
- Location: `app/Services/`
- Contains: WhatsApp integration, contact/conversation management, consent, billing, etc.
- Depends on: Models, external APIs, configurations
- Used by: Controllers, jobs, listeners, Livewire components
- Key services:
  - `WhatsAppService` - WhatsApp API client orchestration
  - `ContactService` - Contact creation/updates with concurrent safety
  - `ConversationService` - Conversation lifecycle management
  - `ConsentService` - Opt-in/opt-out keyword handling
  - `EventBusService` - Publishing events to broadcast table
  - `TraceContext` - Distributed tracing across async jobs
  - `WhatsAppClient` - Direct WhatsApp API HTTP client
  - `MessagingService` - Message type-specific formatting (text, template, interactive)

**Data Persistence Layer:**
- Purpose: Store and retrieve application state
- Location: `app/Models/`
- Contains: Eloquent models for teams, contacts, conversations, messages, campaigns, etc.
- Depends on: Laravel ORM, database
- Used by: All layers above
- Key models:
  - `Team` - Multi-tenant context (WABA ID, phone number ID, tokens)
  - `Contact` - Customer records with phone, name, tags, consent status
  - `Conversation` - Thread between team and contact (SLA, assignment, status)
  - `Message` - Inbound/outbound messages with WhatsApp provider IDs
  - `Campaign` - Broadcast campaigns with message templates
  - `WebhookPayload` - Raw webhook storage for audit trail

**Event Broadcasting (Real-time Layer):**
- Purpose: Broadcast state changes to connected clients for real-time UI updates
- Location: `app/Events/`, `app/Listeners/`, `app/Broadcasting/SafePusherBroadcaster.php`
- Contains: Queueable events, event listeners for side effects
- Depends on: Laravel Events, Pusher/Redis, database broadcast tables
- Used by: Jobs (dispatch events on key transitions)
- Events:
  - `MessageReceived` - Triggers PersistMessageJob side effects
  - `MessageSent` - Updates contact state
  - `MessageStatusUpdated` - Syncs message status
  - `ContactCreated`, `ContactUpdated` - Triggers automation workflows
  - `ConversationOpened`, `ConversationClosed` - Lifecycle events
  - `WhatsAppAccountRisk` - Alert on account issues

**UI Layer (Livewire):**
- Purpose: Real-time, server-driven frontend for chat, contacts, campaigns
- Location: `app/Livewire/`, `resources/views/`
- Contains: Livewire components bound to models with event listeners
- Depends on: Broadcasting, models, routes
- Used by: Web UI served via `web.php` routes
- Key components:
  - `MessageWindow` - Chat display with message list, send, reactions
  - `ConversationList` - Inbox with filtering/search
  - `ContactDetails` - Contact profile, tags, history
  - `TemplatePicker` - WhatsApp template selection/preview
  - `CampaignController` - Campaign creation/reporting (web routes)

## Data Flow

### Inbound Message Flow (WhatsApp → UI)

1. **Webhook Ingestion** (`/webhook/whatsapp` POST)
   - WhatsApp Cloud API sends signed JSON payload
   - `WhatsAppWebhookController::handle()` verifies signature (HMAC-SHA256)
   - Stores raw payload in `WebhookPayload` table (for audit/replay)
   - Updates team's `last_webhook_received_at` health pulse
   - **Returns 200 immediately**

2. **Queue Production** (`ProcessWebhookJob`)
   - Dispatched to `webhooks` queue with trace ID
   - Checks for duplicates via cache (24hr TTL)
   - Resolves team via phone_number_id → WABA ID fallback
   - Instantiates `WhatsAppEventRouter`
   - Routes to appropriate handler (messages, statuses, templates, calls, account updates)

3. **Event Routing** (`WhatsAppEventRouter::route()`)
   - **Inbound Messages** → Extracts message data → Dispatches `PersistMessageJob`
   - **Status Updates** → Dispatches `UpdateMessageStatusJob` + publishes to event bus
   - **Templates** → Updates template status (APPROVED/FLAGGED/REJECTED)
   - **Calls** → Delegates to `WhatsAppCallProcessor`
   - **Account Updates** → Updates call settings or applies restrictions

4. **Message Persistence** (`PersistMessageJob`)
   - Restores trace context for logging
   - Resolves team from phone/WABA ID
   - Acquires distributed lock (10s) on message ID to prevent parallel processing
   - **Contact Creation**: Uses `ContactService::createOrUpdate()` with thread-safe upsert
   - **Conversation Ensure**: Gets or creates active conversation
   - **Consent Keyword Processing**: Checks for STOP/START keywords → Opt-out/opt-in logic
   - **Campaign Attribution**: Via message context ID (reply to campaign message) or temporal cache lookup
   - **Media Handling**: Extracts media ID/type, dispatches `DownloadMediaJob`
   - **Message Creation**: Persists `Message` record with direction=inbound, status=delivered
   - **Side Effects**: 
     - Dispatches `MessageReceived` event
     - Dispatches `SendPushNotificationJob` for FCM tokens
     - Event listeners trigger automations, webhooks, etc.

5. **Real-time UI Update** (Broadcasting)
   - `MessageReceived` event broadcasts via Pusher/Redis
   - Livewire `MessageWindow` component receives update
   - Message appears in chat UI (no page reload)
   - Notification badges/counters update

### Outbound Message Flow (UI → WhatsApp API)

1. **User Action** (Send Message via API)
   - Mobile/web client calls POST `/api/v1/messages`
   - Controller validates: phone number, type (text/template), content
   - Idempotency check via X-Idempotency-Key header (24hr cache)
   - Gets or creates contact/conversation
   - Pre-persists `Message` record with direction=outbound, status=queued
   - Dispatches `SendMessageJob` with message metadata
   - **Returns 202 Accepted** (async)

2. **Send Job Processing** (`SendMessageJob`)
   - Checks team's integration state (must be READY or READY_WARNING)
   - Sandbox mode check (returns without calling WhatsApp API if enabled)
   - Idempotency: If message already has `whatsapp_message_id`, skips (already sent)
   - Delegates to `WhatsAppService` based on message type:
     - `text` → `sendText()` → HTTP POST to `/messages` with text body
     - `template` → `sendTemplate()` → HTTP POST with template body + variables
     - `interactive` → `sendInteractiveButtons()` → Buttons payload
     - `media` → `sendMedia()` → Media type + media handle (WhatsApp media ID)
   - **Success**: WhatsApp API returns `message_id` → Updates `Message.whatsapp_message_id`
   - **Errors**:
     - Policy failures (codes 131047, 131051) → Logged, job stops (permanent)
     - Rate limit (131030) → Jittered backoff (60+5-30s) → Job released
     - Permission (200) → Marked failed, user must reconnect WhatsApp
   - Max 3 retries with backoff [10s, 30s, 60s]

3. **Status Updates** (WhatsApp → DB)
   - WhatsApp sends status webhook (sent, delivered, read, failed)
   - `ProcessWebhookJob` → `WhatsAppEventRouter` routes to status handler
   - Dispatches `UpdateMessageStatusJob`
   - Updates `Message.status` and timestamps (sent_at, delivered_at, read_at)
   - Publishes to event bus → Listeners update UI in real-time
   - Circuit breaker: 10+ consecutive delivery failures → Team set to RESTRICTED state

### Conversation Lifecycle Flow

1. **Inbound Message** → Creates conversation if none exists
2. **Contact Interaction** → Updates `last_interaction_at`, `last_customer_message_at`
3. **SLA Assignment** → On new conversation or if no SLA → `SlaService::assignPolicy()`
4. **Conversation State**: open → assigned/unassigned → closed/reopened
5. **Assignment**: Single agent lock per conversation (`ConversationLock` table) to prevent concurrent replies

## Key Abstractions

**Team (Multi-tenancy):**
- Purpose: Isolate data and configuration per business account
- Files: `app/Models/Team.php`, middleware `tenant`
- Integration state enum: `PROVISIONED → READY → READY_WARNING → DEGRADED → RESTRICTED → SUSPENDED`
- Stores: WhatsApp WABA ID, phone number ID, access token, opt-in/out keywords, settings

**Contact:**
- Purpose: Represents a customer (phone number, name, tags, consent status)
- Files: `app/Models/Contact.php`, `app/Services/ContactService.php`
- Lifecycle: Created on first inbound message or via UI
- Consent: ConsentRegistry model tracks opt-in/out status per contact
- Tags: Pivot table `contact_tag_pivot` for dynamic categorization

**Conversation:**
- Purpose: Represents a thread between team and contact
- Files: `app/Models/Conversation.php`, `app/Services/ConversationService.php`
- State: open, closed (soft delete available)
- Assignment: Multi-user support via `ConversationLock` (pessimistic locking)
- SLA: Policy assigned per conversation for response time tracking

**Message:**
- Purpose: Represents a single message (inbound or outbound)
- Files: `app/Models/Message.php`, `app/Jobs/PersistMessageJob.php`, `app/Jobs/SendMessageJob.php`
- Direction: inbound or outbound
- Status: queued → sent → delivered → read (or failed)
- Types: text, template, image, video, audio, document, sticker, interactive, location, button
- Metadata: Flexible JSON for type-specific data (template variables, media MIME type, etc.)
- Attribution: Tracks which campaign a message is part of (for reporting)

**WhatsApp Event Router:**
- Purpose: Single point of classification for incoming webhook events
- Files: `app/Core/Webhooks/WhatsAppEventRouter.php`
- Pattern: Event type switch with handler methods (no external dispatch)
- Responsibilities: Route to persistence, publish to event bus, update status

**Event Bus:**
- Purpose: Decouple job output from side effects
- Files: `app/Services/EventBusService.php`, `broadcast_events` table
- Pattern: Jobs publish events → Listeners consume asynchronously
- Transport: Database (not Redis/Kafka) for simple, auditable event stream

**TraceContext:**
- Purpose: Propagate correlation ID through async job chain
- Files: `app/Services/TraceContext.php`
- Pattern: Set in webhook handler → Passed through jobs → Logged throughout
- Goal: Link all logs for a single webhook to single trace ID for debugging

**Distributed Locking:**
- Message processing: Cache lock on `webhook_lock:{message_id}` (10s timeout) → Prevents parallel duplicate processing
- Message persistence: Lock on `persist_message_{provider_id}` → Idempotency check
- Conversation assignment: `ConversationLock` table with `locked_at` + `locked_by` + heartbeat mechanism

## Entry Points

**Webhook Verification** (GET `/webhook/whatsapp`)
- Location: `app/Http/Controllers/WhatsAppWebhookController::verify()`
- Triggers: WhatsApp Cloud API subscription setup
- Responsibilities: Return hub_challenge token if token matches (secure subscription handshake)

**Webhook Receipt** (POST `/webhook/whatsapp`)
- Location: `app/Http/Controllers/WhatsAppWebhookController::handle()`
- Triggers: WhatsApp sends inbound message, delivery status, call event, etc.
- Responsibilities: Signature verification → Store payload → Dispatch ProcessWebhookJob
- Middleware: `VerifyWhatsAppSignature`, `throttle:600,1` (rate limit)

**Inbound Webhook Integration** (POST `/api/v1/webhooks/inbound`)
- Location: `app/Http/Controllers/Api/InboundWebhookController::handle()`
- Triggers: External systems (Shopify, WooCommerce, custom) send events
- Responsibilities: Store webhook → Map to contact/message → Trigger workflow
- Auth: Sanctum token required, source IP whitelist optional

**Message Send API** (POST `/api/v1/messages`)
- Location: `app/Http/Controllers/ExternalConversationController::send()`
- Triggers: Mobile/web client sends message
- Responsibilities: Validate → Create message record → Dispatch SendMessageJob
- Returns: 202 Accepted with job status

**Livewire Components** (Web routes)
- Location: `routes/web.php`, `app/Livewire/`
- Triggers: User navigates to `/dashboard` (authenticated)
- Responsibilities: Real-time chat, contact list, campaign management
- Updates: Via broadcasting on model events

**WhatsApp Call Webhook** (POST `/webhook/whatsapp/calls`)
- Location: `app/Http/Controllers/Webhooks/WhatsAppCallWebhookController::handle()`
- Triggers: WhatsApp call events (ringing, answered, completed)
- Responsibilities: Process call state → Update call record → Broadcast to UI

## Error Handling

**Strategy:** Multi-layered with circuit breaker for account-level issues.

**Patterns:**
1. **Message-level errors** (SendMessageJob)
   - Policy failures → Mark message failed, log warning (do not retry)
   - Rate limit → Release job with jittered backoff
   - Permission error → Mark failed with user-facing message
   - Other transient → Throw → Job retried up to 3 times

2. **Webhook-level errors** (ProcessWebhookJob)
   - Team resolution fails → Release job to retry in 10s (exponential backoff)
   - Routing fails → Log error, mark payload as failed (no retry)
   - Database error on persistence → Throw → Job retried (default queue behavior)

3. **Account-level errors** (WhatsAppEventRouter)
   - 10+ consecutive delivery failures in 10min window → Team marked RESTRICTED
   - Successful delivery → Error counter reset (self-healing)
   - Quality update webhook → If RED or FLAGGED → Dispatch `WhatsAppAccountRisk` event

4. **Persistence layer errors** (PersistMessageJob)
   - Lock acquisition timeout → Job skipped (parallel execution detected)
   - Duplicate message → Idempotency check skips
   - DB constraint violation → Logged, job continues (partial failure isolation)

## Cross-Cutting Concerns

**Logging:**
- Framework: Laravel Log (PSR-3)
- Pattern: Structured logging with trace ID, team ID, message ID context
- Files: `storage/logs/`
- Sensitive data: Masked in logs (phone numbers, message content truncated)

**Validation:**
- Controllers: Laravel Request validation
- Jobs: Payload schema validation (type, direction, required fields)
- Services: Business logic validation (consent, SLA, quota)
- Models: Fillable/guarded attributes, casts for type safety

**Authentication:**
- API: Sanctum tokens for mobile/external clients
- Web: Session-based with middleware `auth:sanctum`, `verified`
- Webhook: Signature verification (HMAC-SHA256) + no auth needed (public endpoint)
- Team context: Middleware `tenant` resolves team from user or webhook

**Multi-tenancy:**
- Isolation: `team_id` foreign key on all customer-facing tables
- Middleware: `tenant` middleware resolves team context from authenticated user
- Webhook resolution: Phone number ID or WABA ID → Team lookup via cache
- Scoping: Models auto-scope queries to current team via trait `HasTeam`

**Rate Limiting:**
- Webhook: `throttle:600,1` (600 requests per 1 minute)
- Message send: No explicit limit, but WhatsApp API enforces
- Auth endpoints: `throttle:mobile-auth`, `throttle:10,1` etc.

---

*Architecture analysis: 2026-07-18*
