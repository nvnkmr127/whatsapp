# App-Only Marketing Email Design

## Overview
A secure and compliant subsystem for sending marketing communications exclusively to registered platform users who have opted in.

## Audience & Compliance
- **Platform Users Only**: Audience is restricted to users in the internal `users` table.
- **Opt-In Enforcement**: Emails only sent to users where `marketing_opt_in` is `true`.
- **Double Opt-In Context**: The system only targets users with `email_verified_at` set.
- **Unsubscribe Handling**: Every email contains a mandatory, unique `unsubscribe_url`. Unsubsidizing is a one-click process that immediately updates the user record.

## Throttling & Performance
- **Queue-Based**: Marketing emails are dispatched as high-volume/low-priority jobs.
- **Worker Throttling**: The marketing queue is specifically limited (e.g., via `throttle` or `sleep` in the dispatcher) to avoid triggering SMTP provider spam filters.
- **SMTP Isolation**: Marketing emails are automatically routed through secondary SMTP providers (e.g., SendGrid/Mailchimp) to protect the reputation of the primary OTP provider (Postmark/SES).

## Audit Logging
- **Delivery Logs**: Integrated with the central `EmailLog` system.
- **Unsubscribe Logs**: Every unsubscribe action is logged in `audit_logs` for compliance auditing.
- **Campaign Tracking**: Admins can track campaign reach vs. conversion (unsubscribe rate).

## Implementation Details
- **Service**: `App\Services\Email\AppMarketingService`
- **Controller**: `App\Http\Controllers\MarketingUnsubscribeController`
- **Model**: `App\Models\User` (extended with `marketing_opt_in`, `unsubscribe_token`)
- **Safety**: System-critical emails (OTPs, Alerts) bypass the marketing opt-in check to ensure account security.
