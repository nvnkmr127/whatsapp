# Codebase Structure

**Analysis Date:** 2026-07-18

## Directory Layout

```
project-root/
├── app/                           # Application code (PSR-4 auto-loaded)
│   ├── Http/
│   │   ├── Controllers/           # HTTP request handlers
│   │   │   ├── WhatsAppWebhookController.php     # Primary webhook entry point
│   │   │   ├── ExternalConversationController.php # Message send API
│   │   │   ├── Webhooks/                         # Secondary webhook handlers
│   │   │   ├── Api/                              # Sanctum-authenticated API endpoints
│   │   │   └── Auth/                             # Authentication (OAuth, passwordless)
│   │   └── Middleware/            # Request middleware
│   │       ├── VerifyWhatsAppSignature.php       # Webhook HMAC verification
│   │       ├── tenant.php                        # Multi-tenancy context resolution
│   │       └── ...
│   ├── Models/                    # Eloquent models (database entities)
│   │   ├── Team.php               # Multi-tenant context
│   │   ├── Contact.php            # Customer records
│   │   ├── Conversation.php       # Message threads
│   │   ├── Message.php            # Individual messages (inbound/outbound)
│   │   ├── Campaign.php           # Broadcast campaigns
│   │   ├── WebhookPayload.php     # Raw webhook audit trail
│   │   ├── ConversationLock.php   # Pessimistic locking for assignments
│   │   └── ... (75+ models total)
│   ├── Jobs/                      # Queued jobs (async processing)
│   │   ├── ProcessWebhookJob.php  # Main webhook router
│   │   ├── PersistMessageJob.php  # Inbound message persistence
│   │   ├── SendMessageJob.php     # Outbound message dispatch
│   │   ├── UpdateMessageStatusJob.php # Status webhook handler
│   │   ├── SendPushNotificationJob.php # FCM mobile notifications
│   │   └── ... (20+ jobs total)
│   ├── Events/                    # Queueable events (Laravel Events)
│   │   ├── MessageReceived.php    # Broadcast on inbound
│   │   ├── MessageSent.php        # Broadcast on successful send
│   │   ├── ContactCreated.php     # Broadcast on contact upsert
│   │   └── ... (15+ events)
│   ├── Listeners/                 # Event subscribers (handle side effects)
│   │   ├── AutomationTriggerListener.php # Trigger automations on events
│   │   ├── SendOutboundWebhook.php      # Call customer webhooks
│   │   ├── SendMessageStatusWebhook.php # Status update webhooks
│   │   └── ... (15+ listeners)
│   ├── Services/                  # Business logic (domain operations)
│   │   ├── WhatsAppService.php    # WhatsApp API orchestrator
│   │   ├── ContactService.php     # Contact lifecycle
│   │   ├── ConversationService.php # Conversation lifecycle
│   │   ├── ConsentService.php     # Opt-in/out logic
│   │   ├── EventBusService.php    # Event publishing
│   │   ├── TraceContext.php       # Distributed tracing (correlation IDs)
│   │   ├── WhatsApp/              # WhatsApp-specific services
│   │   │   └── MessagingService.php # Message type formatting
│   │   ├── Email/                 # Email service implementations
│   │   ├── AI/                    # AI/ML services
│   │   └── ... (50+ services)
│   ├── Core/                      # Core infrastructure (not domain-specific)
│   │   ├── WhatsApp/
│   │   │   ├── WhatsAppClient.php # HTTP client for WhatsApp Cloud API
│   │   │   ├── CredentialResolver.php # Token/credential retrieval
│   │   │   └── ManagementClient.php # WhatsApp template management API
│   │   ├── Webhooks/
│   │   │   └── WhatsAppEventRouter.php # Event classification and routing
│   │   └── Workflow/              # Workflow engine (automation system)
│   ├── Livewire/                  # Real-time UI components
│   │   ├── Chat/
│   │   │   ├── MessageWindow.php  # Chat display and send
│   │   │   ├── ConversationList.php # Inbox list
│   │   │   └── ...
│   │   ├── Contacts/              # Contact management UI
│   │   ├── Settings/              # Configuration UI
│   │   └── ... (30+ components)
│   ├── Broadcasting/              # Custom broadcaster implementation
│   │   └── SafePusherBroadcaster.php # Pusher integration
│   ├── Enums/                     # PHP enums for constants
│   │   ├── IntegrationState.php   # PROVISIONED, READY, RESTRICTED, etc.
│   │   ├── AlertSeverity.php      # LOW, MEDIUM, HIGH, CRITICAL
│   │   └── ...
│   ├── DTOs/                      # Data Transfer Objects
│   │   └── ... (request/response wrappers)
│   ├── Traits/                    # Reusable model behaviors
│   │   ├── HasTeam.php            # Auto-scopes queries to team
│   │   ├── HasFlashMessages.php   # Flash message helpers
│   │   └── StandardApiResponses.php # JSON response formatting
│   ├── Validators/                # Custom validation rules
│   ├── Policies/                  # Authorization policies (can: directives)
│   ├── Observers/                 # Model event listeners
│   ├── Providers/                 # Service providers (bootstrap)
│   │   ├── AppServiceProvider.php # Core services
│   │   ├── EventServiceProvider.php # Event→Listener mappings
│   │   └── ...
│   ├── Support/                   # Utility classes and helpers
│   ├── Exceptions/                # Custom exception classes
│   └── View/                      # View composers and data
├── routes/                        # HTTP route definitions
│   ├── api.php                    # API v1 routes (Sanctum authenticated)
│   ├── web.php                    # Web routes (Livewire components)
│   ├── console.php                # Artisan commands
│   └── channels.php               # Broadcasting channel auth
├── resources/
│   ├── views/                     # Blade template files
│   │   ├── components/            # Reusable Blade components
│   │   │   └── chat/              # Chat UI components (bubble.blade.php, etc.)
│   │   ├── dashboard.blade.php    # Main dashboard layout
│   │   └── ... (50+ views)
│   └── js/                        # Frontend JavaScript (Alpine, Livewire JS)
├── database/
│   ├── migrations/                # Schema definitions
│   │   ├── 2026_01_*_create_*_table.php
│   │   └── ... (80+ migrations)
│   ├── seeders/                   # Data population scripts
│   └── factories/                 # Model factories (testing)
├── config/                        # Application configuration
│   ├── app.php                    # Core app config (timezone, providers)
│   ├── queue.php                  # Queue driver (redis, database, sync)
│   ├── broadcasting.php           # Pusher/Redis config
│   ├── whatsapp.php               # WhatsApp API keys
│   └── ... (20+ config files)
├── storage/
│   ├── app/
│   │   ├── public/                # Public-accessible files (attachments)
│   │   └── private/               # Private files (media)
│   └── logs/                      # Application logs
├── bootstrap/
│   ├── app.php                    # Application instantiation
│   └── cache/                     # Compiled configuration cache
├── public/
│   ├── index.php                  # Web server entry point
│   └── ... (static assets)
├── tests/                         # Test suite
│   ├── Feature/                   # Feature tests (controllers, integration)
│   ├── Unit/                      # Unit tests (services, models)
│   └── TestCase.php               # Base test class
├── vendor/                        # Composer dependencies (not committed)
├── package.json                   # Node.js dependencies (for asset building)
├── composer.json                  # PHP dependencies
├── docker-compose.yml             # Local development environment
├── .env.example                   # Environment template (secrets in .env)
├── artisan                        # Laravel CLI entry point
└── .planning/
    └── codebase/                  # GSD planning documents
        ├── ARCHITECTURE.md
        ├── STRUCTURE.md
        ├── STACK.md
        └── ...
```

## Directory Purposes

**app/Http/Controllers/:**
- Purpose: HTTP request handlers (thin layer delegating to services)
- Contains: Controller classes with action methods per route
- Key files: 
  - `WhatsAppWebhookController.php` - Webhook verification and ingestion
  - `ExternalConversationController.php` - Message send, conversation retrieval
  - `Api/*` - Sanctum-authenticated API endpoints (mobile clients)
  - `Webhooks/*` - Secondary webhook handlers (Shopify, WooCommerce, etc.)

**app/Models/:**
- Purpose: Database entities and Eloquent ORM models
- Contains: Fillable attributes, relationships, accessors, query scopes
- Pattern: Traits for shared behavior (HasTeam auto-scopes to team)
- Key files: Team, Contact, Conversation, Message, Campaign, WebhookPayload, etc.

**app/Jobs/:**
- Purpose: Queueable background jobs for async processing
- Contains: Queued job classes with handle() method
- Pattern: Retry logic ($tries, $backoff), failure handling, exception throwing
- Execution: Dispatched via Queue facade, consumed by queue worker
- Key files: ProcessWebhookJob, PersistMessageJob, SendMessageJob

**app/Events/:**
- Purpose: Event classes (not controllers!) for domain state changes
- Contains: Queueable events dispatched by jobs/listeners
- Pattern: Dispatchable, Queueable traits; ShouldBroadcast for real-time UI
- Consumed by: Listeners (see app/Listeners/)

**app/Listeners/:**
- Purpose: Event subscribers that handle side effects
- Contains: Handle method that receives event instance
- Pattern: Can be queued or synchronous; can dispatch jobs or other events
- Key files: AutomationTriggerListener, SendOutboundWebhook, UpdateContactState

**app/Services/:**
- Purpose: Business logic encapsulation (not models, not jobs)
- Contains: Methods for domain operations (create contact, send message, etc.)
- Pattern: Dependency injection via constructor; no static methods
- Key services: WhatsAppService, ContactService, ConversationService, ConsentService

**app/Core/:**
- Purpose: Infrastructure and architectural concerns (not domain-specific)
- Contains: WhatsAppClient (HTTP), WhatsAppEventRouter (routing), Workflow engine
- Pattern: Lower-level abstractions used by Services and Jobs
- Key files: WhatsAppClient.php (API HTTP calls), WhatsAppEventRouter.php (webhook routing)

**app/Livewire/:**
- Purpose: Real-time, server-driven UI components
- Contains: Livewire component classes with mount(), render(), listeners
- Pattern: Properties bound to database models; events trigger UI updates
- Layout: Mirrors feature areas (Chat/, Contacts/, Settings/, etc.)
- Key files: MessageWindow.php (chat UI), ConversationList.php (inbox)

**app/Broadcasting/:**
- Purpose: Custom broadcaster implementations
- Contains: SafePusherBroadcaster.php for Pusher integration
- Pattern: Implements BroadcasterContract interface
- Usage: Configured in config/broadcasting.php

**routes/:**
- Purpose: Route-to-controller mappings
- Contains: api.php (API routes), web.php (Livewire routes), console.php (Artisan commands)
- Pattern: Groups, middleware, named routes
- Key routes:
  - GET/POST `/webhook/whatsapp` - Webhook verification/ingestion
  - POST `/api/v1/messages` - Message send
  - GET/POST `/api/v1/conversations/*` - Conversation retrieval

**database/migrations/:**
- Purpose: Versioned schema definitions
- Contains: Up/down methods for table creation, column additions, indexes
- Pattern: Timestamp-based filenames (2026_01_15_120000_create_*.php)
- Execution: `php artisan migrate` runs pending migrations

**config/:**
- Purpose: Application configuration (non-secret, can be committed)
- Contains: app.php (timezone, providers), queue.php (driver), whatsapp.php (webhook token)
- Pattern: Env variable fallbacks (config('whatsapp.webhook_verify_token'))
- Secret values: Externalized to .env file (not committed)

## Key File Locations

**Entry Points:**
- `routes/api.php` - API route definitions (line 226-228: webhook routes)
- `routes/web.php` - Web route definitions (Livewire components)
- `app/Providers/AppServiceProvider.php` - Service registration
- `app/Providers/EventServiceProvider.php` - Event→Listener mappings

**Configuration:**
- `.env` - Environment variables (secrets, team-specific config)
- `config/app.php` - Application timezone, locale, providers
- `config/queue.php` - Queue driver selection (redis, database, sync)
- `config/whatsapp.php` - WhatsApp API configuration

**Core Logic:**
- `app/Http/Controllers/WhatsAppWebhookController.php` - Webhook ingestion
- `app/Jobs/ProcessWebhookJob.php` - Webhook routing and job dispatch
- `app/Core/Webhooks/WhatsAppEventRouter.php` - Event classification
- `app/Jobs/PersistMessageJob.php` - Inbound message persistence
- `app/Jobs/SendMessageJob.php` - Outbound message sending
- `app/Services/WhatsAppService.php` - WhatsApp API orchestration

**Testing:**
- `tests/Feature/` - Integration tests (controllers, jobs)
- `tests/Unit/` - Unit tests (services, models)
- `phpunit.xml` - PHPUnit configuration
- `.env.testing` - Test environment configuration

## Naming Conventions

**Files:**
- Controllers: `{Feature}Controller.php` (e.g., `ExternalConversationController.php`)
- Models: `{Entity}.php` PascalCase (e.g., `Message.php`, `Conversation.php`)
- Jobs: `{Verb}{Noun}Job.php` (e.g., `SendMessageJob.php`, `ProcessWebhookJob.php`)
- Events: `{Noun}{Action}.php` (e.g., `MessageReceived.php`, `ContactCreated.php`)
- Listeners: `{Action}{Entity}.php` or `{Verb}{Noun}.php` (e.g., `UpdateContactStateOnMessageSent.php`)
- Services: `{Domain}Service.php` (e.g., `WhatsAppService.php`, `ContactService.php`)
- Livewire components: `{Feature}.php` (e.g., `MessageWindow.php`, `ConversationList.php`)
- Migrations: `YYYY_MM_DD_HHMMSS_{action}_{table}.php` (e.g., `2026_07_18_120000_create_messages_table.php`)

**Directories:**
- Feature-based: `app/Livewire/{Feature}/`, `resources/views/{feature}/`
- Subdomain-based: `app/Services/{Domain}/`, `app/Http/Controllers/{Domain}/`
- Logical grouping: `app/Jobs/`, `app/Events/`, `app/Listeners/`

## Where to Add New Code

**New Feature (e.g., SMS support alongside WhatsApp):**
- Primary code:
  - `app/Http/Controllers/SmsWebhookController.php` - Webhook handler
  - `app/Jobs/ProcessSmsWebhookJob.php` - Parse SMS events
  - `app/Services/SmsService.php` - SMS API client
  - `app/Core/Sms/SmsClient.php` - HTTP client wrapper
- Update:
  - `routes/api.php` - Add webhook route
  - `app/Providers/EventServiceProvider.php` - Register listeners
- Tests: `tests/Feature/SmsWebhookTest.php`, `tests/Unit/SmsServiceTest.php`

**New Message Type (e.g., video message):**
- Modify:
  - `app/Models/Message.php` - Add type to fillable, add migration for new columns
  - `app/Jobs/PersistMessageJob.php` - Handle extraction of video metadata
  - `app/Core/Webhooks/WhatsAppEventRouter.php` - Handle new message type in router
  - `app/Services/WhatsApp/MessagingService.php` - Add formatting method (sendVideo)
- Tests: `tests/Feature/WhatsAppWebhookVideoTest.php`

**New Service (e.g., analytics aggregation):**
- Implementation:
  - `app/Services/AnalyticsService.php` - Main service class
  - Register in `app/Providers/AppServiceProvider.php` if it's a singleton
- Usage: Inject in controllers/jobs: `app(AnalyticsService::class)->calculate()`
- Tests: `tests/Unit/AnalyticsServiceTest.php`

**New Livewire Component (e.g., advanced search for contacts):**
- Files:
  - `app/Livewire/Contacts/AdvancedSearch.php` - Component class
  - `resources/views/livewire/contacts/advanced-search.blade.php` - Blade template
- Register route: Add to `routes/web.php` with authentication middleware
- Tests: `tests/Feature/ContactAdvancedSearchTest.php`

**New Webhook Integration (e.g., custom CRM):**
- Files:
  - `app/Http/Controllers/Webhooks/CrmWebhookController.php` - Entry point
  - `app/Jobs/ProcessCrmWebhookJob.php` - Parse and route CRM events
  - `database/migrations/2026_MM_DD_create_crm_webhook_logs_table.php` - Audit trail
- Routes: `routes/api.php` → `POST /api/v1/webhooks/crm`
- Update: `app/Models/WebhookSource.php` - Add CRM as source type

## Special Directories

**bootstrap/cache/:**
- Purpose: Compiled config and route cache
- Generated: `php artisan config:cache`, `php artisan route:cache`
- Committed: No (regenerated on deploy)
- Invalidate: `php artisan cache:clear`

**storage/app/public/ vs storage/app/private/:**
- Public: User-facing files (media attachments) → Served via symlink
- Private: Internal files (backups, temp logs) → Not directly accessible

**database/factories/ and database/seeders/:**
- Purpose: Test data generation and seeding
- Usage: `php artisan tinker` or test fixtures
- Execution: `php artisan seed` or `php artisan db:seed`

**tests/:**
- Feature: Full request lifecycle tests (controllers, jobs, events)
- Unit: Single-class tests (services, models, helpers)
- Coverage: Aim for 80%+ on services and critical paths

---

*Structure analysis: 2026-07-18*
