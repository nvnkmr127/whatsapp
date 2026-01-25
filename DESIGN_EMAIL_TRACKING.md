# Email Delivery Tracking & Logging Design

## Overview
A comprehensive tracking system to monitor system email health, delivery rates, and failure classifications across the SaaS infrastructure.

## Data Model (`EmailLog`)
Each outbound email attempt creates a record with:
- **Recipient**: Target email address.
- **Use Case**: Purpose (OTP, Alert, Marketing).
- **Template Context**: Link to the DB template used.
- **Provider Context**: Which SMTP provider was used (for failover auditing).
- **Failure Classification**: Categorized errors (Network, Authentication, SMTP Error).
- **Timestamps**: `sent_at`, `failed_at`, and `delivered_at`.

## Delivery States
1. **SENT**: Handled by SMTP provider without immediate error.
2. **FAILED**: Synchronous failure during dispatch (logged with specific reason).
3. **DELIVERED**: (Reserved for future webhook integration from providers like Postmark/SendGrid).

## Admin-Only Audit Console
Located at `/admin/email-logs`:
- Real-time stream of all outbound system emails.
- Filter by recipient, status, and use-case.
- Visibility into **why** a delivery failed (e.g., "Invalid API Key on SendGrid", "Connection Timeout").

## Retention Policy
To prevent database bloat:
- **Default Retention**: 30 days.
- **Cleanup Job**: `php artisan email:cleanup-logs --days=30`
- **Automation**: Designed to be scheduled in `app/Console/Kernel.php`.

## Security & PII
- **Email Content**: We do **not** store the full rendered HTML content in the logs to protect user privacy (especially for OTPs).
- **Subject/Metadata**: Only non-sensitive metadata is retained.
