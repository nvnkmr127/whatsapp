# Outbound Webhooks Subscription Manager

## 1. What is this page?
The Outbound Webhooks Subscription Manager is the platform's event notifier. Located at `/developer/webhooks`, it allows administrators and developers to configure endpoints that listen to real-time events (like incoming messages, tag changes, order logs, or authentication OTPs), set HMAC-SHA256 signatures, test receiver servers, and rotate security secrets.

## 2. Why is this page useful?
Polling APIs continuously to check for new messages or customer state updates uses server bandwidth and causes delays.
- **Why do users need it?** To push real-time events immediately to external servers when action occurs (such as sending a shipping ticket to an external delivery platform as soon as a customer changes their address).
- **What work does it make easier?** It pushes payloads directly to external servers, provides one-click endpoint testing, and handles security signatures.
- **What business process does it support?** Real-Time CRM Updates, Automated Fulfillment Notifications, and Security Audit alerts.
- **What happens without it?** Systems must periodically call search APIs to check for updates, resulting in delayed processing and higher server load.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To authorize webhook destinations, audit active endpoints, and configure system-level receivers. |
| Software Developer | To configure endpoint parameters, copy signing secrets, and trigger test payloads to verify receivers. |

## 4. What can users do here?
- **View & Toggle Subscriptions:**
  - View all outbound webhook endpoints, names, secrets status (Signed/Unsigned), and subscribed events.
  - Turn off endpoints instantly using status toggles without deleting their configurations.
- **Trigger Test Payloads:**
  - Send mock test JSON events (`test.event`) to endpoints to verify connection integrity and review receiver responses.
- **Manage Webhook Secrets:**
  - Set custom signing keys or click "Generate Secret" to create randomized tokens.
  - Rotate signing keys instantly when credentials are leaked (invalidates the old secret immediately).
- **Create & Edit Webhooks:**
  - Name, URL endpoint, and event selectors.
  - **Available Events:**
    - `message.received` / `message.sent` / `message.status_updated`
    - `contact.created` / `contact.updated`
    - `conversation.started` / `conversation.assigned`
    - `campaign.completed` / `automation.triggered`
    - `otp.sent` / `otp.verified` / `otp.failed`
    - `billing.threshold_reached`
    - `auth.otp.login` (Super Admin Only)
  - Configure **System Webhooks (Super Admin Only):** Special endpoints that can capture platform authentication and login events.

## 5. What is involved?
- **WebhookSubscription Model:** Stores names, target URLs, event lists, active state flags, and signing secrets.
- **WebhookDelivery Model:** Logs payloads and status codes.
- **WebhookService:** The backend dispatcher that queues and fires HTTP POST payloads.

## 6. How does it work?
1. The Developer goes to `/developer/webhooks` to log incoming WhatsApp messages into an external database.
2. They input a Name: "CRM Sync Link" and Target URL: `https://api.mycrm.com/incoming`.
3. They click "Generate Secret" and save the generated HMAC key.
4. Under events, they check "Message Received" and click "Create Webhook".
5. They hover over the row and click "Send Test". The system dispatches a test payload.
6. The developer verifies their server parses the payload correctly.
7. Now, when a customer replies on WhatsApp, the platform generates a POST request to their endpoint within milliseconds.

## 7. What happens behind the scenes?
- **SHA-256 HMAC Payload Signing:** If a webhook has a signing secret, the system generates a SHA-256 HMAC hash of the raw JSON body and attaches it to the request header. The receiving server can calculate the hash using their copy of the secret to confirm the payload was sent by the platform and was not modified in transit.
- **Super Admin Event Restrictions:** The `auth.otp.login` event sends sensitive OTP details to third-party receivers. Standard admins are blocked from checking this option; if selected, the controller automatically filters it out unless authorized by a platform Super Admin.
- **Asynchronous Queue Dispatch:** Webhooks are queued in the background. If a receiver endpoint returns a 500 error or is offline, the queue worker logs the delivery attempt as failed and retries using exponential backoffs, preventing transient network issues from losing messages.

## 8. Business Use Cases

**Use Case 1: Automating Order Receipts**
- **Situation:** An e-commerce brand wants to send order confirmations via WhatsApp and update their ERP tracking system simultaneously.
- **How the feature is used:** They subscribe to `automation.triggered` events, routing the payload containing purchase details to their ERP.
- **Customer experience:** N/A (Internal automated sync).
- **Business outcome:** Real-time ERP updates without manual entries.

**Use Case 2: Tracking Agent Performance**
- **Situation:** A manager wants to track agent response times on external analytics dashboards.
- **How the feature is used:** They subscribe to `conversation.assigned` and `message.sent` events, sending timestamps to their dashboard.
- **Customer experience:** N/A (Internal monitoring).
- **Business outcome:** Accurate agent performance dashboards.

**Use Case 3: Triggering ERP Leads**
- **Situation:** When a contact is created or tags are updated, a business wants to sync this contact information to their external ERP immediately.
- **How the feature is used:** They set up a webhook listening to `contact.created` and `contact.updated` events.
- **Customer experience:** N/A (Internal data sync).
- **Business outcome:** Synced contact records across systems.

## 9. Industry Use Cases
- **Retail:** Exporting customer cart tags to external marketing engines.
- **Finance:** Forwarding OTP failure alerts to security monitors.
- **Utilities:** Updating dispatch systems when customers submit locations.

## 10. Real Customer Example
A developer configures a webhook pointing to their CRM. They select the `message.received` event, generate a signing secret to secure the payload, and save. They click "Send Test" to verify their server is receiving payloads. When their server logs a successful test, they activate the webhook. Customer messages now route to their CRM in real time.

## 11. Customer Journey
Developer inputs endpoint URL &rarr; Generates signing secret &rarr; Subscribes to events &rarr; Sends test payload &rarr; Verifies connection &rarr; Deploys active webhook.

## 12. Inputs
- Webhook name and URL.
- Optional signing secret.
- Subscribed event checkboxes.
- Active/Inactive toggles.
- System flags (Super Admin).

## 13. Outputs
- Webhook subscription database records.
- Dispatched POST request payloads.
- Test alerts.
- Rotated credential files.

## 14. Dependencies
- **WebhookSubscription Model:** DB records.
- **WebhookService:** Background HTTP client dispatcher.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permissions on plans including `webhooks`.
- **Who can view information:** Admins/Developers.
- **Who can edit:** Admins/Developers.
- **Who cannot access it:** Managers, Support Agents.

## 16. Important Rules
- Webhook endpoints must accept HTTP POST requests and return a `200 OK` response code within timeout limits.
- Rotating a secret invalidates the old signature key immediately. Update your receiver server before rotating keys.

## 17. Common Problems
- **Problem:** Webhook logs show failures, but the destination server is online.
  - **Possible reason:** The destination server does not accept the HMAC signature, or it is blocking requests from the platform's IP.
  - **What the user should do:** Disable the signature by clearing the secret field to isolate the issue, or whitelist the platform's IP addresses in your firewall.
- **Problem:** "Auth OTP" events are missing from the configuration checkbox.
  - **Possible reason:** You are logged in as a tenant administrator. These events are restricted to platform Super Admins.
  - **What the user should do:** Contact a platform super admin if your workspace requires system authentication logs.

## 18. Simple Explanation for Sales
Webhooks are automated alerts that notify your other software (like your CRM or database) whenever something happens in this platform, such as a customer sending a message or getting assigned to an agent.

## 19. Simple Explanation for Marketing
Admins use this page to sync contacts and tags to your external marketing tools in real time, keeping your campaigns and customer profiles updated.

## 20. Simple Explanation for Support
If tags or conversations are not syncing to your external CRM, ask your developer to use this page to send a test payload and verify the integration is working.

## 21. Related Features
- [Developer Portal](./developer-portal.md)
- [System Settings](./system-settings.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/developer/webhooks`
- **Implementation:** `App\Livewire\Developer\WebhookManager`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Developer/WebhookManager.php`
  - `resources/views/livewire/developer/webhook-manager.blade.php`
  - `app/Models/WebhookSubscription.php`
  - `app/Services/WebhookService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
