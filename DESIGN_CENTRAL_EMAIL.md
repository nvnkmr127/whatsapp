# Centralized OTP & System Email Delivery Design

## Overview
A robust, secure, and resilient infrastructure for delivering critical system emails, specifically focused on OTP delivery and automated alerts.

## High-Level Architecture
1. **Client Request**: Application requests an OTP via `CentralEmailService`.
2. **Rate Limiting**: Enforced at the service level using `RateLimiter` (3 per minute per recipient).
3. **Template Enforcement**: `EmailTemplateService` renders content using DB-locked templates with strict variable schema validation.
4. **Asynchronous Queue**: Emails are dispatched to `SendSystemEmailJob`.
5. **Failover Dispatch**: `EmailDispatcher` attempts delivery. If the primary SMTP fails, it automatically health-checks and rotates to fallback providers.
6. **Retries**: The Job implements exponential backoff (30s, 60s, 120s).

## Key Features

### 1. Delivery & Resilience
- **SMTP Failover**: Automatic rotation between multiple providers (e.g., Postmark -> SendGrid -> SES) based on real-time health monitoring.
- **Exponential Backoff**: Prevents hammering failing providers while ensuring eventual delivery for transient network issues.
- **Centralized Logs**: All attempts, successes, and final failures are logged to `audit_logs` and system logs.

### 2. Rate Limiting Rules
- **OTP Requests**: Max 3 per 60 seconds per email address.
- **Global Thresholds**: Prevents massive cost spikes in case of a system bug or logic loop.

### 3. Template Enforcement
- **Immutability**: Critical templates (OTP, Reset Password) are `is_locked` in the database.
- **Type Safety**: Variable schemas prevent missing variables from breaking the email layout.
- **Fallback**: Plain-text fallback is always required for high deliverability.

### 4. Security Considerations
- **PII Protection**: We avoid logging variables in clear-text system logs when possible (especially OTP codes).
- **Anti-Spam**: Rate limits prevent the platform from being used for "Email Bombing".
- **Domain Alignment**: `EmailDispatcher` ensures the `From` address matches the SMTP provider's verified identity to maintain high SPF/DKIM trust.
- **Encrypted SMTP**: All dynamic SMTP configurations require TLS/SSL encryption.

## Implementation Details
- **Service**: `App\Services\Email\CentralEmailService`
- **Dispatcher**: `App\Services\Email\EmailDispatcher`
- **Job**: `App\Jobs\Email\SendSystemEmailJob`
- **Model**: `App\Models\EmailTemplate`
