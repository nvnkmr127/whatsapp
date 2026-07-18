# External Integrations

**Analysis Date:** 2026-07-18

## Inbound Message Lifecycle

**Flow: Webhook → Processing → Response**

1. **Webhook Entry** (`app/Http/Controllers/Webhooks/`)
   - WhatsApp/Meta sends webhook to `/webhooks/whatsapp/messages`
   - Webhook signature verification via HMAC (app secret in `config/whatsapp.php`)
   - Webhook deduplication (24-hour window per `config/whatsapp.php`)

2. **Queue Processing** (`app/Jobs/ProcessWebhookJob.php`)
   - Payload stored in `WebhookPayload` model
   - Dispatched to `webhooks` queue
   - Trace context maintained across queue workers

3. **Event Processing** (`app/Services/EventBusService.php`)
   - Message events routed through event bus
   - Triggers workflows, automations, AI processing
   - Broadcast events to connected clients via WebSocket

4. **Outbound Responses**
   - WhatsApp messages sent via `app/Services/WhatsApp/MessagingService.php`
   - Sent through `app/Core/WhatsApp/WhatsAppClient.php`
   - Uses team-specific phone number ID and access token

## APIs & External Services

**WhatsApp Business Cloud API (Meta Graph API):**
- Service: Meta WhatsApp Cloud API v21.0
- What it's used for: Send/receive messages, manage templates, access contact info, handle calls
- SDK/Client: Custom built in `app/Core/WhatsApp/`
  - `WhatsAppClient.php` - HTTP client for Meta Graph API
  - `ManagementClient.php` - Account management API calls
  - `CredentialResolver.php` - Team credential lookup
- Auth: 
  - `WHATSAPP_APP_ID` - Meta app ID
  - `WHATSAPP_APP_SECRET` - App secret for webhook signature verification
  - `WHATSAPP_SYSTEM_ACCESS_TOKEN` - System-level token for platform-wide operations
  - Team-specific access tokens stored in `teams` table
  - Team phone number IDs in `teams.whatsapp_phone_number_id`
- Base URL: `https://graph.facebook.com/{API_VERSION}/{PHONE_NUMBER_ID}`
- Endpoints used:
  - `POST /{phone_id}/messages` - Send messages (`MessagingService.php`)
  - `POST /{phone_id}/media` - Upload/download media (`app/Jobs/DownloadMediaJob.php`)
  - `GET /{phone_id}/contacts` - Query contacts
  - `GET /me/message_templates` - List message templates
  - `POST /{phone_id}/messages` - Send templates (`MessagingService::sendTemplate()`)

**AI Providers (for intelligent responses & automation):**
- Multiple providers configured in `config/services.php`
- Manager: `app/Services/AI/AIProviderManager.php`
- Provider interface: `app/Services/AI/AIProviderInterface.php`
- Implementations in `app/Services/AI/Providers/`:
  - OpenAI (`OpenAIProvider.php`)
    - Auth: `OPENAI_API_KEY`
    - Default model: `gpt-4o`
  - Anthropic (`AnthropicProvider.php`)
    - Auth: `ANTHROPIC_API_KEY`
    - Default model: `claude-3-5-sonnet-20241022`
  - Google Gemini (`GeminiProvider.php`)
    - Auth: `GEMINI_API_KEY`
    - Default model: `gemini-1.5-pro`
  - DeepSeek (`DeepSeekProvider.php`)
    - Auth: `DEEPSEEK_API_KEY`
- Usage: 
  - `app/Services/AiCommerceService.php` - Commerce AI assistant
  - `app/Jobs/ProcessAiAssistantJob.php` - Async AI processing
  - Workflow nodes for AI-powered automation

## Data Storage

**Databases:**
- **Primary (Relational):**
  - MySQL 8.0+ (default in production)
  - PostgreSQL 12+ (alternative)
  - SQLite (default for development)
  - Connection: via `config/database.php` (configurable by `DB_CONNECTION`)
  - Client: Laravel Eloquent ORM (`app/Models/`)
  - Models: `Message`, `Contact`, `Team`, `WebhookPayload`, `WebhookDelivery`, `Integration`, `Order`, `WhatsAppCall`, etc.

- **Cache/Session:**
  - Redis (primary)
    - Host: `REDIS_HOST` (default: 127.0.0.1)
    - Port: `REDIS_PORT` (default: 6379)
    - Password: `REDIS_PASSWORD` (optional)
    - Prefix: `REDIS_PREFIX` (for multi-tenant isolation)
    - Database: `REDIS_DB` (default: 0)
  - Fallback: Database cache driver (`CACHE_STORE=database`)

**File Storage:**
- **Cloudflare R2** (S3-compatible)
  - Auth: `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`
  - Bucket: `R2_BUCKET`
  - Region: `auto`
  - Endpoint: `R2_ENDPOINT`
  - URL: `R2_URL` (public access)
  - Disk config: `config/filesystems.php` (`r2` disk)
  - Usage: Media attachments, message documents
  - Adapter: Flysystem AWS S3 v3 adapter (`league/flysystem-aws-s3-v3`)

- **AWS S3** (alternative/fallback)
  - Auth: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`
  - Region: `AWS_DEFAULT_REGION` (default: us-east-1)
  - Bucket: `AWS_BUCKET`
  - Endpoint: `AWS_ENDPOINT` (optional, for S3-compatible services)
  - URL: `AWS_URL` (public access)
  - Disk config: `config/filesystems.php` (`s3` disk)

- **Local Filesystem**
  - Storage paths: `storage/app/private/` and `storage/app/public/`
  - Used for: Temporary files, flow private keys (`WHATSAPP_FLOW_PRIVATE_KEY_PATH`)
  - Public access: `/storage` URL prefix

## Authentication & Identity

**Auth Provider:**
- Custom (Laravel Sanctum for API)
- OAuth integration for team setup

**API Authentication:**
- Implementation: `Laravel Sanctum` (`config/sanctum.php`)
- Token format: Plain text API tokens
- Token lifetime: `SANCTUM_TOKEN_EXPIRATION` (default: 4320 minutes = 3 days)
- Storage: Tokens table (database)
- Usage: Mobile API (`app/Http/Controllers/Api/Mobile/`), developer API

**OAuth Providers (for team onboarding):**
- **Facebook Login** (Meta solution partner flow)
  - Auth: `FACEBOOK_APP_ID`, `FACEBOOK_CLIENT_SECRET`
  - Redirect: `FACEBOOK_REDIRECT_URI`
  - SDK: `laravel/socialite` 
  - Flow: `app/Http/Controllers/Auth/` (OAuth callback handlers)

- **Google OAuth** (team setup)
  - Auth: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`
  - Redirect: `GOOGLE_REDIRECT_URI`
  - SDK: `laravel/socialite`, `google/auth`
  - Usage: Firebase credentials management

**WhatsApp App Credentials:**
- Managed per team in `teams` table
- Columns: `whatsapp_app_id`, `whatsapp_app_secret`, `whatsapp_access_token`, `whatsapp_phone_number_id`
- Resolver: `app/Core/WhatsApp/CredentialResolver.php`
- Sandbox mode support: `teams.is_sandbox_mode` flag for testing

## Webhooks (Inbound - External Services → Platform)

**Webhook Controllers:** `app/Http/Controllers/Webhooks/`

**Supported Platforms:** `config/webhook-platforms.php`

**Shopify Integration:**
- Controller: `ShopifyWebhookController.php`
- Auth method: HMAC-SHA256 verification
- Header: `X-Shopify-Hmac-Sha256`
- Events: `orders/create`, `orders/cancelled`
- Data mapping: Phone, customer name, order ID, total, currency
- Flow: Webhook → `OrderService` → Trigger workflows

**Stripe Integration:**
- Controller: `StripeWebhookController.php`
- Auth method: HMAC-SHA256 verification
- Header: `Stripe-Signature`
- Events: `payment_intent.succeeded`, `payment_intent.payment_failed`
- Data mapping: Customer details, amount, currency, payment ID
- Use case: Payment notifications via WhatsApp

**WooCommerce Integration:**
- Controller: `WooCommerceWebhookController.php`
- Auth method: HMAC-SHA256 verification
- Header: `X-WC-Webhook-Signature`
- Events: `order.created`
- Data mapping: Billing info, order total, currency
- Use case: E-commerce order notifications

**Meta Commerce (Catalog Sync):**
- Controller: `MetaCommerceWebhookController.php`
- Purpose: Product catalog synchronization
- Auth: `META_COMMERCE_VERIFY_TOKEN` verification
- Models affected: Synced to `Order` model for product tracking

**Custom Webhooks:**
- Controller: `CustomSiteWebhookController.php`
- Auth: API key in `X-API-Key` header
- Flexible field mapping via workflow configuration
- Use case: Generic integration with third-party systems

**Email Webhooks:**
- Controller: `app/Http/Controllers/Webhooks/Email/EmailWebhookController.php`
- Handler: `ResendWebhookHandler.php`
- Events: Email delivery status updates
- Status tracking: Webhook deliveries logged to `webhook_deliveries` table
- Auth: Resend webhook secret (`RESEND_WEBHOOK_SECRET`)

**WhatsApp Webhook (Inbound Messages):**
- Route: `POST /webhooks/whatsapp/messages` (implicit, via webhook handler)
- Verification: 
  - Webhook verify token: `WHATSAPP_WEBHOOK_VERIFY_TOKEN` or `WHATSAPP_VERIFY_TOKEN`
  - Signature verification: HMAC-SHA256 using `WHATSAPP_APP_SECRET`
- Payload storage: `WebhookPayload` model
- Processing: `ProcessWebhookJob` job (queued)
- Deduplication: 24-hour window configured in `config/whatsapp.php`

**WhatsApp Call Webhook (Voice Calls):**
- Controller: `WhatsAppCallWebhookController.php`
- Events: Incoming/outgoing call status
- Phone ID resolution: Map to team via `teams.whatsapp_phone_number_id`
- Models: `WhatsAppCall` for call tracking
- Job: `MonitorCallTimeoutJob.php` for call timeout handling

## Webhooks (Outbound - Platform → External Services)

**Outbound Webhook System:**
- Manager: `app/Services/WebhookService.php`
- Models: `WebhookSubscription`, `WebhookDelivery`
- Job: `app/Jobs/ExecuteOutboundWebhookJob.php`

**Delivery Mechanism:**
- HTTP POST to subscribed URLs
- Payload format: JSON with `event`, `event_type`, `timestamp`, `data`
- Signature: HMAC-SHA256 in `X-Webhook-Signature` header (if secret configured)
- Retry logic: 3 attempts with exponential backoff [10, 60, 300 seconds]
- Queue: `webhooks` queue
- Delivery tracking: Each attempt logged to `webhook_deliveries` table

**Event Types Sent:**
- Message events (send, receive, status updates)
- Contact events (created, updated)
- Order events (from integrated platforms)
- Workflow events (execution, completion)
- Call events (incoming, outgoing)

**Subscribable Event System:**
- Platform developers can subscribe to events
- Credentials stored securely in `WebhookSubscription.secret`
- Active/inactive subscription management

## Email Services

**Email Providers:**
- Configuration: `config/mail.php`
- Provider selection: `EMAIL_PROVIDER` (default: smtp)

**Resend** (Primary transactional email):
- Auth: `RESEND_API_KEY`
- From address: `RESEND_FROM_ADDRESS`
- Webhook secret: `RESEND_WEBHOOK_SECRET` (for delivery notifications)
- Mailer config: `config/mail.php` (`resend` transport)
- Usage: Transactional emails from `app/Mail/`
- SDK: `resend/resend-php`

**SMTP** (Fallback/Custom):
- Host: `MAIL_HOST` (default: mailpit)
- Port: `MAIL_PORT` (default: 1025)
- Username/Password: `MAIL_USERNAME`, `MAIL_PASSWORD`
- Encryption: `MAIL_ENCRYPTION` (optional)

**AWS SES** (Alternative):
- Auth: AWS credentials (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`)
- Region: `AWS_DEFAULT_REGION`
- Mailer config: `config/mail.php` (`ses` transport)

**Postmark** (Alternative):
- Auth: `POSTMARK_API_KEY`

**Email Routing by Type:**
- Marketing: `EMAIL_PROVIDER_MARKETING` (overrides default)
- Alert: `EMAIL_PROVIDER_ALERT`
- Notification: `EMAIL_PROVIDER_NOTIFICATION`
- Invoice: `EMAIL_PROVIDER_INVOICE`
- Each can use different transport independently

**Email Job:**
- `app/Jobs/SendEmailJob.php` - Async email sending
- Queue: Default queue
- Retry: Automatic on failure

## Error Tracking & Monitoring

**Sentry** (Optional):
- DSN: `SENTRY_LARAVEL_DSN` (leave empty to disable)
- Trace sampling: `SENTRY_TRACES_SAMPLE_RATE` (default: 0.1 = 10%)
- Profile sampling: `SENTRY_PROFILES_SAMPLE_RATE` (default: 0.1 = 10%)
- Release tracking: `SENTRY_RELEASE` (optional, for source-map mapping)
- Configuration: `config/sentry.php`
- Integration: `sentry/sentry-laravel`

**Application Logging:**
- Channel: `LOG_CHANNEL` (options: stack, single, daily, slack, papertrail, stderr, syslog)
- Log level: `LOG_LEVEL` (options: debug, info, notice, warning, error, critical, alert, emergency)
- Slack integration: `LOG_SLACK_WEBHOOK_URL` (optional)
- Papertrail integration: `PAPERTRAIL_URL`, `PAPERTRAIL_PORT` (optional)
- Log viewer UI: `opcodesio/log-viewer` (`config/log-viewer.php`)

## Broadcasting & Real-time (WebSocket)

**Primary Broadcaster:**
- Default: `BROADCAST_CONNECTION` (default: null, can be: reverb, pusher, ably, redis, log)

**Laravel Reverb** (Recommended):
- Server host: `REVERB_SERVER_HOST` (default: 0.0.0.0)
- Server port: `REVERB_SERVER_PORT` (default: 8080)
- Client host: `REVERB_HOST` (default: localhost)
- Client port: `REVERB_PORT` (default: 8080)
- Scheme: `REVERB_SERVER_SCHEME` (http or https)
- App credentials: `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`
- Max connections: `REVERB_APP_MAX_CONNECTIONS` (optional limit)
- Max message size: `REVERB_APP_MAX_MESSAGE_SIZE` (default: 10000 bytes)
- Ping interval: `REVERB_APP_PING_INTERVAL` (default: 60 seconds)
- Activity timeout: `REVERB_APP_ACTIVITY_TIMEOUT` (default: 30 seconds)
- Scaling: Redis-backed scaling enabled by default
- Configuration: `config/reverb.php`

**Pusher** (Alternative):
- App ID: `PUSHER_APP_ID`
- App key: `PUSHER_APP_KEY`
- App secret: `PUSHER_APP_SECRET`
- Cluster: `PUSHER_APP_CLUSTER` (e.g., mt1)
- Custom host: `PUSHER_HOST` (optional)
- Configuration: `config/broadcasting.php`

**Frontend Client:**
- SDK: `pusher-js` (Pusher.js v8.4.0)
- Wrapper: `laravel-echo` v2.2.7 (Event broadcasting abstraction)
- Events dispatched from backend via `broadcast()` helper

**Event Broadcasting Channels:**
- Private channels for team-specific updates
- Presence channels for user activity tracking
- Used for: Real-time chat updates, status changes, notification delivery

## Queue System

**Queue Driver:** `QUEUE_CONNECTION` (default: database)

**Available Drivers:**
- `database` - Queue jobs stored in `jobs` table
- `redis` - High-performance Redis queue
- `aws-sqs` - Amazon SQS
- `beanstalkd` - Beanstalkd daemon
- `sync` - Synchronous (development only)

**Queue Channels** (per `composer.json` dev command):
- `messages` - Message sending and processing
- `webhooks` - Incoming/outgoing webhook processing
- `ai_processing` - AI assistant job processing
- `broadcasts` - Real-time event broadcasting
- `notifications` - Notification delivery
- `default` - Fallback queue

**Job Classes:** `app/Jobs/`
- `ProcessWebhookJob` - Parse and route webhooks
- `SendMessageJob` - Send WhatsApp message
- `ExecuteOutboundWebhookJob` - Deliver outbound webhooks
- `ProcessAiAssistantJob` - AI-powered message handling
- `SendEmailJob` - Async email sending
- `UpdateMessageStatusJob` - Sync message delivery status
- `ExecuteWorkflowNodesJob` - Workflow automation execution
- `MonitorCallTimeoutJob` - Track WhatsApp call timeout
- `CheckIntegrationHealth` - Health checks for integrations
- Job retry: Configurable per job (typically 3 attempts with backoff)

**Failed Jobs:**
- Storage: `failed_jobs` table (database driver)
- Driver: `database-uuids`
- Retry after: 90 seconds (default `DB_QUEUE_RETRY_AFTER`)

## Performance & Caching

**Cache Store:** `CACHE_STORE` (default: database)
- Primary: Redis
- Fallback: Database
- Session storage: Database (default)

**Session Configuration:**
- Driver: `database`
- Lifetime: `SESSION_LIFETIME` (default: 120 minutes)
- Encryption: `SESSION_ENCRYPT` (false by default)

## Third-Party Utilities

**Document Generation:**
- PDF: `barryvdh/laravel-dompdf` (DomPDF)
  - Usage: Invoices, reports, exports (`app/Jobs/`, `app/Mail/`)
- Excel: `phpoffice/phpspreadsheet`
  - Usage: Data exports, bulk imports (`app/Jobs/`, spreadsheet download routes)

**PDF Parsing:**
- Parser: `smalot/pdfparser`
  - Usage: Extract data from uploaded PDF documents

**CSV Handling:**
- Library: `league/csv`
- Usage: Bulk contact import/export

## Testing & Development

**Testing Database:**
- Configured in `phpunit.xml`
- Uses SQLite in-memory by default

**Faker/Fixtures:**
- `fakerphp/faker` for test data generation
- Factories: `database/factories/`

## Summary: Request/Message Lifecycle

```
External Event (Webhook)
    ↓
WhatsApp/Meta/E-commerce API
    ↓
Webhook Controller Verification (HMAC)
    ↓
Store WebhookPayload → ProcessWebhookJob
    ↓
Event Bus Processing (EventBusService)
    ↓
Parallel Paths:
  A) Workflow Execution
  B) AI Assistant Job (ProcessAiAssistantJob)
  C) Contact/Order Updates
    ↓
Send WhatsApp Response (MessagingService → WhatsAppClient → Meta API)
    ↓
Outbound Webhook Notifications (ExecuteOutboundWebhookJob)
    ↓
Real-time UI Updates (Broadcasting via Reverb/Pusher)
    ↓
Database Persistence + Analytics
```

---

*Integration audit: 2026-07-18*
