# Webhook Logs & Payload Inspector

## 1. What is this page?
The Webhook Logs & Payload Inspector is the platform's diagnostic utility for incoming data feeds. Located at `/webhooks/logs`, it allows administrators and developers to audit raw incoming payloads, track processing statuses, view error logs with stack traces, and manually replay failed payloads.

## 2. Why is this page useful?
Payload delivery issues from external systems (like Meta or customized checkout storefronts) can disrupt contact syncs or automated messaging templates.
- **Why do users need it?** To verify that payloads are arriving successfully, diagnose processing errors, inspect raw JSON variables, and resubmit failed payloads once receiver issues are resolved.
- **What work does it make easier?** It logs stack traces, parses nested JSON, and provides one-click manual replay buttons.
- **What business process does it support?** API Troubleshooting, Integration Debugging, and Message Delivery Auditing.
- **What happens without it?** Developers must search database tables or server logs to diagnose webhook failures, increasing troubleshooting times.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To monitor webhook traffic and verify system stability. |
| Software Developer | To audit payloads, diagnose code exceptions, inspect stack traces, and test system integrations. |

## 4. What can users do here?
- **Track 24h Webhook Volume:**
  - View total webhook requests received in the last 24 hours.
- **Filter Log Histories:**
  - Search log records by ID or payload keywords.
  - Filter by status: Processed (success), Failed (error), or Pending (in queue).
  - Filter logs by date range or select pagination lengths (10, 15, 25, or 50 entries per page).
- **Inspect Payloads (Modal):**
  - View execution times, status flags, and retry attempts.
  - View error logs and expand full stack traces for failed webhooks.
  - Copy pretty-printed, syntax-highlighted raw JSON payloads.
- **Manually Replay Failed Webhooks:**
  - Re-enqueue failed payloads for processing with one click.

## 5. What is involved?
- **WebhookPayload Model:** Stores database IDs, raw payload arrays, processing statuses, error logs, and retry parameters.
- **ProcessMappedWebhookJob:** The background job that processes the webhook payload.

## 6. How does it work?
1. An administrator notices that contact records are not updating after a broadcast.
2. They open `/webhooks/logs` and search for the contact's ID.
3. They find a failed log, click "Inspect", and review the error panel: `Failed: Column 'phone' cannot be null`.
4. They click "Show Full Log" to review the stack trace and identify the database configuration error.
5. Once the developer fixes the database error, the administrator returns to the log row and clicks "Replay".
6. The system sets the status to pending and re-queues the job, processing the contact details.

## 7. What happens behind the scenes?
- **Replay State Resets:** When an administrator clicks "Replay", the controller resets the payload's database status to `pending`, clears the `error_message`, resets the `retry_count` to 0, and dispatches the background worker job (`ProcessMappedWebhookJob`).
- **Trace ID Propagation:** Replayed jobs carry forward their original trace IDs, allowing developers to track execution logs across servers.
- **Database Indexing:** Search queries search inside JSON payload structures. The database indexing strategy scans for matches quickly without locking tables.

## 8. Business Use Cases

**Use Case 1: Debugging Cart Updates**
- **Situation:** Checkout updates are not triggering cart notifications.
- **How the feature is used:** The developer searches logs for the e-commerce payload, identifies a JSON parsing error, updates the field mappings, and replays the failed logs.
- **Customer experience:** Cart recovery messages resume.
- **Business outcome:** Recovered sales with minimal downtime.

**Use Case 2: Tracking API Volatility**
- **Situation:** Webhooks fail intermittently during peak hours due to server timeouts.
- **How the feature is used:** The developer checks date ranges and status filters to identify timeout trends.
- **Customer experience:** N/A (Internal debugging).
- **Business outcome:** Informed decisions about hosting capacity upgrades.

**Use Case 3: Retrying Failed OTP Audits**
- **Situation:** Security OTP events fail to log due to a database lock.
- **How the feature is used:** Once the database lock is resolved, the admin replays the failed OTP logs to update the logs.
- **Customer experience:** N/A (Internal sync).
- **Business outcome:** Complete security audits.

## 9. Industry Use Cases
- **Retail:** Recovering failed checkout webhooks.
- **Logistics:** Diagnosing tracking payload failures.
- **Fintech:** Replaying failed transaction notifications.

## 10. Real Customer Example
A developer notices customer registrations are failing. They open `/webhooks/logs` and filter by status "Failed". They inspect the payload, click "Show Full Log" to view the stack trace, and identify a field mapping issue. Once fixed, they replay the failed payloads to update their records.

## 11. Customer Journey
Developer opens logs &rarr; Filters by failed status &rarr; Inspects payload and stack trace &rarr; Identifies system issue &rarr; Fixes mappings &rarr; Replays failed payloads.

## 12. Inputs
- Search terms.
- Status filters.
- Date ranges.
- Pagination size.

## 13. Outputs
- Filtered log grids.
- Pretty-printed JSON payloads.
- Stack trace panels.
- Re-enqueued jobs.

## 14. Dependencies
- **WebhookPayload Model:** DB records.
- **ProcessMappedWebhookJob:** Background worker.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permissions on plans including `webhooks`.
- **Who can view information:** Admins/Developers.
- **Who can edit/replay:** Admins/Developers.
- **Who cannot access it:** Managers, Support Agents.

## 16. Important Rules
- Replaying a webhook executes the processing job immediately. Verify code changes are deployed before re-running payloads.
- Webhook payloads can contain sensitive customer data. Do not share raw inspect logs outside secure channels.

## 17. Common Problems
- **Problem:** Replay fails with "Source not found" error.
  - **Possible reason:** The inbound webhook source config was deleted after the log was created.
  - **What the user should do:** Recreate the webhook source with the same path parameters, or skip replaying this log.
- **Problem:** Date filters return zero results.
  - **Possible reason:** Date queries default to local server timezones, which may differ from your local time.
  - **What the user should do:** Expand date filters by 1 day to catch offset entries.

## 18. Simple Explanation for Sales
The Webhook Logs page is an inspection screen for developers. If customer data fails to sync from external storefronts, developers can use this page to view the errors and reprocess the files.

## 19. Simple Explanation for Marketing
Admins use this page to troubleshoot data sync issues. If tag updates are delayed, they can find the failed webhook here and retry it.

## 20. Simple Explanation for Support
If customer logs are missing on your CRM, ask your administrator to inspect this page to check for failed webhook payloads.

## 21. Related Features
- [Developer Portal](./developer-portal.md)
- [Outbound Webhooks](./outbound-webhooks.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/webhooks/logs`
- **Implementation:** `App\Livewire\Webhooks\WebhookLogs`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Webhooks/WebhookLogs.php`
  - `resources/views/livewire/webhooks/webhook-logs.blade.php`
  - `app/Models/WebhookPayload.php`
  - `app/Jobs/ProcessMappedWebhookJob.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
