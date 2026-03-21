# Resend Integration — Micro-Task Checklist
## Future-Compatible Email Driver Architecture
### 9 Groups · 47 Tasks · ~2 Days Dev Time

> [!IMPORTANT]
> **Rule**: No existing email file is deleted at any point. Every task is additive or a minimal modification.
> **Rollback at any time**: Set `EMAIL_PROVIDER=smtp` in [.env](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/.env) → instant rollback, no restart needed.

---

## 🔑 GROUP 0 — Prerequisites (Account Setup — No Code)

| # | Task | Done? |
|---|------|-------|
| 0.1 | Sign up at [resend.com](https://resend.com) — free tier (3,000 emails/month, 100/day) | ☐ |
| 0.2 | Go to Resend → Domains → Add your domain (e.g. `watxio.com` or `flow.watxio.com`) | ☐ |
| 0.3 | Add the **3 DNS records** Resend gives you to your DNS provider (SPF, DKIM, DMARC). Wait for green ✅ status | ☐ |
| 0.4 | Go to Resend → API Keys → Create key with "Sending Access" scope only | ☐ |
| 0.5 | Add to [.env](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/.env): `RESEND_API_KEY=re_xxxxxxxxxxxx` | ☐ |
| 0.6 | Add to [.env](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/.env): `EMAIL_PROVIDER=smtp` ← keep this as [smtp](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Models/EmailLog.php#39-43) until all code is ready | ☐ |
| 0.7 | Add both keys (without values) to [.env.example](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/.env.example) so other devs know to set them | ☐ |

---

## 📦 GROUP 1 — The Contract & Value Objects (Foundation)

> **Goal**: Define the rules every email driver must follow. No logic here — just interfaces.

| # | Task | File to Create | Done? |
|---|------|---------------|-------|
| 1.1 | Create directory `app/Services/Email/Contracts/` | (directory) | ☐ |
| 1.2 | Create directory `app/Services/Email/Drivers/` | (directory) | ☐ |
| 1.3 | Create **`EmailProviderContract.php`** — PHP interface with 4 method signatures: [send()](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/EmailDispatcher.php#19-94), `supports()`, `getName()`, `isHealthy()` | `app/Services/Email/Contracts/EmailProviderContract.php` | ☐ |
| 1.4 | Create **`EmailPayload.php`** — readonly class / DTO with properties: `$to`, `$subject`, `$html`, `$text`, `$useCase` (EmailUseCase), `$metadata` (array), `$replyTo` (?string), `$headers` (array) | `app/Services/Email/Contracts/EmailPayload.php` | ☐ |
| 1.5 | Create **`EmailResult.php`** — readonly class with: `$success` (bool), `$messageId` (?string), `$providerName` (string), `$error` (?string). Add static constructors: `EmailResult::ok(string $provider, string $messageId)` and `EmailResult::fail(string $provider, string $error)` | `app/Services/Email/Contracts/EmailResult.php` | ☐ |
| 1.6 | **✅ Done when**: All 3 files exist, no syntax errors (`php artisan tinker` → `new App\Services\Email\Contracts\EmailPayload(...)` works) | — | ☐ |

---

## 📦 GROUP 2 — `SmtpDriver` (Wrap Existing Logic)

> **Goal**: Existing SMTP code becomes a proper driver that implements the contract. Zero behavior change.

| # | Task | File to Create | Done? |
|---|------|---------------|-------|
| 2.1 | Create **`SmtpDriver.php`** implementing `EmailProviderContract` | `app/Services/Email/Drivers/SmtpDriver.php` | ☐ |
| 2.2 | In `SmtpDriver::send()`: accept `EmailPayload`, create a [DynamicSystemMail](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Mail/DynamicSystemMail.php#11-48) from payload's `$html`/`$subject`/`$text`, delegate to existing [EmailDispatcher](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/EmailDispatcher.php#10-167) SMTP logic (inject [EmailDispatcher](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/EmailDispatcher.php#10-167) via constructor), return `EmailResult` | Same file | ☐ |
| 2.3 | In `SmtpDriver::supports()`: return `true` for ALL `EmailUseCase` values (SMTP is the universal fallback) | Same file | ☐ |
| 2.4 | In `SmtpDriver::getName()`: return `'smtp'` | Same file | ☐ |
| 2.5 | In `SmtpDriver::isHealthy()`: call `SmtpHealthService::isAnyProviderHealthy()` — if that method doesn't exist, return `SmtpConfig::where('is_active', true)->where('health_status', '!=', 'failing')->exists()` | Same file | ☐ |
| 2.6 | **✅ Done when**: `SmtpDriver` class has no PHP errors and implements all 4 interface methods | — | ☐ |

---

## 📦 GROUP 3 — `ResendDriver` (The New Provider)

> **Goal**: A clean Resend implementation using their PHP SDK.

| # | Task | File to Create | Done? |
|---|------|---------------|-------|
| 3.1 | Run: `composer require resend/resend-php` | Terminal | ☐ |
| 3.2 | Create **`ResendDriver.php`** implementing `EmailProviderContract` | `app/Services/Email/Drivers/ResendDriver.php` | ☐ |
| 3.3 | In constructor: accept `string $apiKey`, `string $fromAddress`, `string $fromName` | Same file | ☐ |
| 3.4 | In `ResendDriver::send()`: build the Resend payload array: `['from' => "{$fromName} <{$fromAddress}>", 'to' => [$payload->to], 'subject' => $payload->subject, 'html' => $payload->html, 'text' => $payload->text]`. Call `\Resend\Client::emails()->send($data)`. Wrap in `try/catch`. Return `EmailResult::ok('resend', $response->id)` on success, `EmailResult::fail('resend', $e->getMessage())` on failure | Same file | ☐ |
| 3.5 | In `ResendDriver::send()`: **if** `$payload->useCase === EmailUseCase::MARKETING` **and** `$payload->headers['List-Unsubscribe']` exists → add it to the Resend payload's `headers` array. This enables Gmail's one-click unsubscribe natively | Same file | ☐ |
| 3.6 | In `ResendDriver::supports()`: return `true` for `MARKETING` and `ALERT` use cases. Return `false` for [OTP](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/OTPService.php#12-602) (WhatsApp handles OTP) | Same file | ☐ |
| 3.7 | In `ResendDriver::getName()`: return `'resend'` | Same file | ☐ |
| 3.8 | In `ResendDriver::isHealthy()`: use `Cache::remember('resend_health', 300, fn() => ...)` — make a lightweight API call to Resend's `/emails` endpoint with the API key. Return `true` if HTTP 200, `false` otherwise | Same file | ☐ |
| 3.9 | **✅ Done when**: `ResendDriver::send()` tested manually via `php artisan tinker` — sends a real email to your test inbox | — | ☐ |

---

## 📦 GROUP 4 — `EmailProviderManager` (The Resolver)

> **Goal**: Central registry that resolves the right driver from config. Inspired by Laravel's `CacheManager`.

| # | Task | File to Create | Done? |
|---|------|---------------|-------|
| 4.1 | Create **`EmailProviderManager.php`** | `app/Services/Email/EmailProviderManager.php` | ☐ |
| 4.2 | In constructor: accept array of registered drivers `[string $name => EmailProviderContract $driver]` | Same file | ☐ |
| 4.3 | Add method `driver(string $name): EmailProviderContract` — looks up driver by name, throws `InvalidArgumentException` if not found | Same file | ☐ |
| 4.4 | Add method `forUseCase(EmailUseCase $useCase): EmailProviderContract` — reads [config('mail.routing.' . $useCase->value)](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/EmailDispatcher.php#146-166) first, falls back to [config('mail.provider', 'smtp')](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/EmailDispatcher.php#146-166), resolves that driver. If driver doesn't `supports()` the use case, falls back to `SmtpDriver` | Same file | ☐ |
| 4.5 | Add method [send(EmailPayload $payload): EmailResult](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/EmailDispatcher.php#19-94) — calls `$this->forUseCase($payload->useCase)->send($payload)`. All future callers use this, not the driver directly | Same file | ☐ |
| 4.6 | Add method `getAvailableDrivers(): array` — returns list of all registered driver names. Used by admin dashboard to show configured providers | Same file | ☐ |
| 4.7 | **✅ Done when**: `app(EmailProviderManager::class)->driver('resend')` returns `ResendDriver` instance | — | ☐ |

---

## 📦 GROUP 5 — Config & Service Container Registration

> **Goal**: Wire everything into Laravel's IoC container and add config keys.

| # | Task | File to Modify | Done? |
|---|------|---------------|-------|
| 5.1 | Add to [config/services.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/config/services.php): `'resend' => ['api_key' => env('RESEND_API_KEY'), 'from_address' => env('RESEND_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')), 'from_name' => env('RESEND_FROM_NAME', env('MAIL_FROM_NAME'))]` | [config/services.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/config/services.php) | ☐ |
| 5.2 | Add to [config/mail.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/config/mail.php): `'provider' => env('EMAIL_PROVIDER', 'smtp')` | [config/mail.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/config/mail.php) | ☐ |
| 5.3 | Add to [config/mail.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/config/mail.php): `'routing' => ['marketing' => env('EMAIL_PROVIDER_MARKETING', null), 'alert' => env('EMAIL_PROVIDER_ALERT', null), 'invoice' => env('EMAIL_PROVIDER_INVOICE', null)]` — all null by default (uses global `provider`) | [config/mail.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/config/mail.php) | ☐ |
| 5.4 | In `AppServiceProvider::register()`, register `EmailProviderManager` as a **singleton**: instantiate `SmtpDriver` and `ResendDriver` (only if `RESEND_API_KEY` is set), register both with the manager | [app/Providers/AppServiceProvider.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Providers/AppServiceProvider.php) | ☐ |
| 5.5 | Run `php artisan config:clear` and `php artisan cache:clear` to flush old config | Terminal | ☐ |
| 5.6 | **✅ Done when**: `php artisan tinker` → `app(\App\Services\Email\EmailProviderManager::class)->getAvailableDrivers()` returns `['smtp', 'resend']` | — | ☐ |

---

## 📦 GROUP 6 — Update [EmailDispatcher](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/EmailDispatcher.php#10-167) (Minimal Change)

> **Goal**: Make [EmailDispatcher](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/EmailDispatcher.php#10-167) aware of the manager. 6 lines added. Zero lines deleted.

| # | Task | File to Modify | Done? |
|---|------|---------------|-------|
| 6.1 | Add `EmailProviderManager` as an injected dependency in `EmailDispatcher::__construct()` | [app/Services/Email/EmailDispatcher.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/EmailDispatcher.php) | ☐ |
| 6.2 | At the **very top** of `EmailDispatcher::send()`, before any existing code, extract `$subject`, `$html`, `$text` from the `$mailable` object (use the existing reflection logic that's already there for `$subject`, build html/text by rendering the mailable) | Same file | ☐ |
| 6.3 | After extracting content, build an `EmailPayload` object from those values | Same file | ☐ |
| 6.4 | Ask the manager: `$driver = $this->manager->forUseCase($useCase)`. If driver is NOT `SmtpDriver`, call `$result = $driver->send($payload)`, then call `$this->logResult(...)`, then `return` | Same file | ☐ |
| 6.5 | Add private method `logResult(string $to, EmailUseCase $useCase, EmailResult $result, ?int $templateId)` — writes to [EmailLog](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Models/EmailLog.php#8-44) with `provider_name = $result->providerName`, `metadata = ['message_id' => $result->messageId]`, sets `sent_at` or `failed_at` based on `$result->success` | Same file | ☐ |
| 6.6 | **✅ Done when**: With `EMAIL_PROVIDER=smtp` set, existing email behavior is **100% unchanged**. Run any existing email flow and confirm [EmailLog](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Models/EmailLog.php#8-44) still shows SMTP entries | — | ☐ |

---

## 📦 GROUP 7 — Database: Add `message_id` to [EmailLog](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Models/EmailLog.php#8-44)

> **Goal**: Store provider-agnostic message IDs for bounce tracking.

| # | Task | File to Create | Done? |
|---|------|---------------|-------|
| 7.1 | Create migration: `php artisan make:migration add_message_id_to_email_logs` | New migration file | ☐ |
| 7.2 | In migration [up()](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/AnalyticsDashboard.php#34-39): `$table->string('message_id')->nullable()->after('provider_name')` and `$table->json('metadata')->nullable()->after('message_id')` (skip `metadata` if it already exists — check first with `Schema::hasColumn`) | New migration file | ☐ |
| 7.3 | Run: `php artisan migrate` | Terminal | ☐ |
| 7.4 | Add `'message_id'` and `'metadata'` to `EmailLog::$fillable` | [app/Models/EmailLog.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Models/EmailLog.php) | ☐ |
| 7.5 | Add `'metadata' => 'array'` to `EmailLog::$casts` (skip if already present) | [app/Models/EmailLog.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Models/EmailLog.php) | ☐ |
| 7.6 | **✅ Done when**: `php artisan tinker` → `\App\Models\EmailLog::first()->metadata` returns an array (not an error) | — | ☐ |

---

## 📦 GROUP 8 — Bounce Webhook Handler (Provider-Agnostic)

> **Goal**: One controller, one route pattern. Adding future providers = add one handler class only.

| # | Task | File to Create/Modify | Done? |
|---|------|----------------------|-------|
| 8.1 | Create directory `app/Http/Controllers/Webhooks/Email/` | (directory) | ☐ |
| 8.2 | Create **`ResendWebhookHandler.php`** with one public method [handle(Request $request): void](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Jobs/ProcessCampaignJob.php#22-40). Verify Resend signature header `svix-signature` using the webhook secret from [config('services.resend.webhook_secret')](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/EmailDispatcher.php#146-166). If invalid, return `403` | `app/Http/Controllers/Webhooks/Email/ResendWebhookHandler.php` | ☐ |
| 8.3 | In `ResendWebhookHandler::handle()`, switch on `$request->json('type')`: case `'email.delivered'` → find [EmailLog](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Models/EmailLog.php#8-44) where `message_id = $request->json('data.email_id')`, update `delivered_at = now()`. Case `'email.bounced'` → update `failed_at = now()`, `failure_type = 'bounce'`, `failure_reason = $request->json('data.bounce.message')`. Case `'email.complained'` → log a warning (future: add opt-out) | Same file | ☐ |
| 8.4 | Create **`EmailWebhookController.php`** — a thin router that reads `{provider}` from the route and delegates to the right handler: `'resend' => ResendWebhookHandler`, throws `404` for unknown providers | `app/Http/Controllers/Webhooks/Email/EmailWebhookController.php` | ☐ |
| 8.5 | Add route to [routes/api.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/routes/api.php): `Route::post('/webhooks/email/{provider}', [\App\Http\Controllers\Webhooks\Email\EmailWebhookController::class, 'handle'])` | [routes/api.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/routes/api.php) | ☐ |
| 8.6 | Add `api/webhooks/email/*` to `$except` in CSRF middleware (the api routes already skip CSRF, verify this is already the case via [routes/api.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/routes/api.php) — it is, since all api routes are stateless) | `app/Http/Middleware/VerifyCsrfToken.php` | ☐ |
| 8.7 | In Resend dashboard → Webhooks: Add endpoint `https://yourapp.com/api/webhooks/email/resend`. Select events: `email.delivered`, `email.bounced`, `email.complained` | Resend Dashboard | ☐ |
| 8.8 | Add `RESEND_WEBHOOK_SECRET=whsec_xxxx` to [.env](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/.env) (Resend gives this when you create the webhook) | [.env](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/.env) | ☐ |
| 8.9 | Add to [config/services.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/config/services.php) under `resend`: `'webhook_secret' => env('RESEND_WEBHOOK_SECRET')` | [config/services.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/config/services.php) | ☐ |
| 8.10 | **✅ Done when**: Use Resend dashboard's "Test Webhook" to fire a `email.delivered` event. Confirm [EmailLog](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Models/EmailLog.php#8-44) row gets `delivered_at` filled in | — | ☐ |

---

## 📦 GROUP 9 — Testing & Cutover

| # | Task | Done? |
|---|------|-------|
| 9.1 | **Safety test — SMTP still works**: Ensure `EMAIL_PROVIDER=smtp` in [.env](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/.env). Trigger [BillingThresholdAlert](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Mail/BillingThresholdAlert.php#12-55) email (simulate by calling `BillingService::getWarningStatus()` for a team at 90% usage). Confirm [EmailLog](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Models/EmailLog.php#8-44) shows `provider_name = 'smtp'` | ☐ |
| 9.2 | **Resend test — Marketing email**: Change [.env](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/.env) to `EMAIL_PROVIDER=resend`. Send a test marketing email via [AppMarketingService](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/AppMarketingService.php#11-76). Check Resend dashboard → Emails tab for the entry. Check [EmailLog](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Models/EmailLog.php#8-44) for `provider_name = 'resend'` and a `message_id` | ☐ |
| 9.3 | **Resend test — Billing alert**: While `EMAIL_PROVIDER=resend`, trigger a threshold alert. Confirm it arrives via Resend | ☐ |
| 9.4 | **Fallback test**: Temporarily set an invalid `RESEND_API_KEY`. With `EMAIL_PROVIDER=resend`, confirm the send fails gracefully (returns `EmailResult::fail()`). Confirm no PHP exceptions bubble up to the user | ☐ |
| 9.5 | **Rollback test**: Set `EMAIL_PROVIDER=smtp`. Run `php artisan config:clear`. Fire any email. Confirm it uses SMTP again. No restart needed | ☐ |
| 9.6 | **Production Cutover**: On production server: (1) Deploy new code, (2) Run `php artisan migrate`, (3) Add `RESEND_API_KEY` and `RESEND_WEBHOOK_SECRET` to [.env](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/.env), (4) Keep `EMAIL_PROVIDER=smtp` for 24h to verify no issues, (5) Change to `EMAIL_PROVIDER=resend`, (6) Run `php artisan config:clear`, (7) Monitor `email_logs` for 30 min | ☐ |

---

## 📁 Complete File List

| File | Action |
|------|--------|
| `app/Services/Email/Contracts/EmailProviderContract.php` | 🆕 CREATE |
| `app/Services/Email/Contracts/EmailPayload.php` | 🆕 CREATE |
| `app/Services/Email/Contracts/EmailResult.php` | 🆕 CREATE |
| `app/Services/Email/Drivers/SmtpDriver.php` | 🆕 CREATE |
| `app/Services/Email/Drivers/ResendDriver.php` | 🆕 CREATE |
| `app/Services/Email/EmailProviderManager.php` | 🆕 CREATE |
| `app/Http/Controllers/Webhooks/Email/ResendWebhookHandler.php` | 🆕 CREATE |
| `app/Http/Controllers/Webhooks/Email/EmailWebhookController.php` | 🆕 CREATE |
| `database/migrations/xxxx_add_message_id_to_email_logs.php` | 🆕 CREATE |
| [app/Services/Email/EmailDispatcher.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/EmailDispatcher.php) | ✏️ +15 lines |
| [app/Providers/AppServiceProvider.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Providers/AppServiceProvider.php) | ✏️ +12 lines |
| [config/services.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/config/services.php) | ✏️ +6 lines |
| [config/mail.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/config/mail.php) | ✏️ +8 lines |
| [app/Models/EmailLog.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Models/EmailLog.php) | ✏️ +3 lines |
| [routes/api.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/routes/api.php) | ✏️ +1 line |
| [.env](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/.env) | ✏️ +3 lines |
| [.env.example](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/.env.example) | ✏️ +3 lines |

> [!TIP]
> **Zero files deleted.** [CentralEmailService](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/CentralEmailService.php#12-95), [AppMarketingService](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/AppMarketingService.php#11-76), [EmailTemplateService](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/EmailTemplateService.php#9-114), [SmtpHealthService](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/Email/SmtpHealthService.php#10-62), [DynamicSystemMail](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Mail/DynamicSystemMail.php#11-48), [SendSystemEmailJob](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Jobs/Email/SendSystemEmailJob.php#15-74), [OTPService](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/OTPService.php#12-602) — all completely untouched.

---

## 🗓️ Suggested Execution Order

```
Day 1 Morning  → Group 0 (Account setup — no code)
Day 1 Morning  → Group 1 (Contract + DTOs — 3 files, pure interfaces)
Day 1 Afternoon → Group 2 (SmtpDriver — wraps existing code)
Day 1 Afternoon → Group 3 (ResendDriver — new logic, test email send)
Day 2 Morning  → Group 4 (EmailProviderManager — resolver)
Day 2 Morning  → Group 5 (Config + ServiceProvider registration)
Day 2 Afternoon → Group 6 (Modify EmailDispatcher — the only existing file that changes meaningfully)
Day 2 Afternoon → Group 7 (DB migration for message_id)
Day 2 Afternoon → Group 8 (Bounce webhook handler)
Day 2 Late     → Group 9 (Testing + cutover)
```
