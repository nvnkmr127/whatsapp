# Developer Portal

## 1. What is this page?
The Developer Portal is the API integration and webhook management center of the platform. Located at `/developer`, it allows administrators and developers to generate auth tokens, create inbound webhook mapping endpoints, subscribe to outbound event webhooks, verify webhook delivery logs, connect Model Context Protocol (MCP) servers for desktop AI assistants, and toggle Sandbox Mode.

## 2. Why is this page useful?
Businesses need to integrate their custom CRM, marketing trackers, or customer databases with their WhatsApp communication workflows.
- **Why do users need it?** To bridge internal systems to WhatsApp, configure automated event webhooks (like triggering an internal database sync when a chat is resolved), test APIs without charges, and connect AI coding tools directly to their CRM workspace database.
- **What work does it make easier?** It organizes API credentials, lists webhook delivery records for debugging, and provides single-click sandbox toggles to pause pricing fees during testing.
- **What business process does it support?** API Integration Engineering, System Event Synchronization, and Developer Testing sandbox configurations.
- **What happens without it?** Developers cannot authenticate external systems, listen to event changes, or hook customized databases into WhatsApp chats, limiting CRM integrations.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To authorize API tokens, connect development tools (MCP), and toggle sandbox modes during updates. |
| Software Developer | To copy API documentation curl samples, inspect outbound webhook delivery logs, and generate webhook receivers. |

## 4. What can users do here?
- **Monitor Developer Metrics:**
  - View counts of active API tokens, active outbound webhooks, inbound data sources, and 7-day webhook delivery counts.
- **Access API Token Manager:**
  - Create and revoke secure API authentication tokens (`/developer/api-tokens`) to query endpoints.
- **Configure Inbound Sources:**
  - Setup webhook endpoints (`/developer/webhook-sources`) to receive and parse payloads from external systems.
- **Configure Outbound Webhooks:**
  - Subscribe your systems to platform events (`/developer/webhooks`) like message received, status changes, or ticket creation.
- **Debug Webhook Logs:**
  - Inspect transmission logs (`/webhooks/logs`) showing status codes, latencies, payloads, and response headers for debug tracking.
- **Access API Documentation:**
  - View the platform's API docs (`/developer/docs`) detailing routes, models, and curl request structures.
- **Manage MCP Servers (Claude/AI Connector):**
  - Configure Model Context Protocol parameters (`/developer/mcp`) to link desktop AI editors directly to the workspace database.
- **Toggle Sandbox Mode:**
  - Switch sandbox testing states on/off to test API commands and messaging routines without incurring Meta conversation rates.
- **Access Team Explorer (Super Admin Only):**
  - Navigate global tenant listings (`/developer/teams`) to audit workspace details.

## 5. What is involved?
- **Team Model:** Manages the sandbox state (`is_sandbox_mode`).
- **WebhookSubscription & WebhookDelivery Models:** Manages event triggers and delivery status histories.
- **WebhookSource Model:** Manages inbound data listeners.
- **Token System:** Manages authentication keys.

## 6. How does it work?
1. A developer wants to sync WhatsApp messages to their CRM database.
2. They open `/developer` and click "Outbound Webhooks" to create a subscription.
3. They enter their CRM endpoint URL, select the event trigger "Message Received", and save.
4. To test, they toggle on "Sandbox Mode" to ensure test messages don't charge their wallet.
5. They send a test message to their WhatsApp number.
6. The platform catches the message, serializes the JSON, and posts it to the CRM.
7. The developer returns to the developer portal, clicks "Webhook Logs", and confirms a `200 OK` status was returned, completing the integration.

## 7. What happens behind the scenes?
- **Sandbox Mode Execution:** Activating sandbox mode sets `is_sandbox_mode` to true in the active team row. When outbound message dispatchers parse calls under sandbox mode, they skip Meta API posts and mock successful deliveries, avoiding Meta charge rates during testing.
- **Signed Payload Security:** Outbound webhook dispatches encode payloads using SHA-256 HMAC signatures. The receiver can verify the header signature against the subscription's secret key to validate payloads.
- **Token Security:** Generated API keys are hashed before database storage. They are only shown once during creation, ensuring security even if database tables are compromised.

## 8. Business Use Cases

**Use Case 1: Automating Internal CRM Updates**
- **Situation:** A sales team wants their CRM lead records updated automatically whenever a user is tagged as a "Qualified Lead" on WhatsApp.
- **How the feature is used:** They create an outbound webhook subscription triggered by tag events, pointing to their CRM's endpoint.
- **Customer experience:** N/A (Internal automated sync).
- **Business outcome:** Accurate database synchronization with zero manual entry.

**Use Case 2: Sandbox Testing Before Launch**
- **Situation:** A developer wants to test a new auto-reply routing script but has no budget for test messaging fees.
- **How the feature is used:** They toggle on Sandbox Mode to test the script with mock message deliveries.
- **Customer experience:** N/A (Internal developer testing).
- **Business outcome:** Safe testing at zero cost.

**Use Case 3: Connecting AI Coding Assistants**
- **Situation:** An agency developer wants Claude to inspect customer tables and build custom charts.
- **How the feature is used:** They configure an MCP server connection on this page, linking Claude to their workspace database.
- **Customer experience:** N/A (Internal database querying).
- **Business outcome:** Fast data reporting using AI search assistants.

## 9. Industry Use Cases
- **Retail:** Linking Shopify checkouts to custom shipping systems.
- **Real Estate:** Pushing contact details to agent dialer platforms.
- **Logistics:** Updating shipping tracking statuses via inbound webhooks.

## 10. Real Customer Example
An agency developer opens `/developer` and toggles on Sandbox Mode. They generate a secure API token to authorize their backend systems. They configure an outbound webhook for "message received" events, and review the Webhook Logs to debug payload transmissions. Once verified, they turn off Sandbox Mode, deploying the live integration to route customer messages directly to their custom CRM.

## 11. Customer Journey
Developer opens portal &rarr; Toggles sandbox mode &rarr; Generates API tokens &rarr; Links inbound data sources &rarr; Subscribes to outbound webhooks &rarr; Inspects delivery logs &rarr; Deploys integrations.

## 12. Inputs
- Webhook endpoints.
- API Token descriptions.
- Webhook trigger events.
- Sandbox mode toggle.
- MCP configurations.

## 13. Outputs
- Generated API Keys.
- Active Webhook subscriptions.
- Delivery log histories.
- Team explorer reports.

## 14. Dependencies
- **WebhookSubscription & WebhookDelivery Models:** DB records.
- **WebhookSource Model:** Inbound receivers.
- **AI MCP System:** Claude integration.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permissions on plans including `api_access`.
- **Who can view information:** Admins/Developers.
- **Who can edit:** Admins/Developers.
- **Who cannot access it:** Managers, Support Agents.

## 16. Important Rules
- API tokens are only displayed once during creation; if lost, they must be deleted and regenerated.
- Sandbox mode must be turned off to send actual messages to customers.

## 17. Common Problems
- **Problem:** Webhook logs show `500 Internal Error` messages.
  - **Possible reason:** Your external database endpoint is failing to process the payload.
  - **What the user should do:** Open the Webhook Logs table on this page, click details to review the response body, and fix your receiver script.
- **Problem:** Sandbox mode is active, but customers are not receiving messages.
  - **Possible reason:** Sandbox mode blocks actual message deliveries to avoid charges during testing.
  - **What the user should do:** Toggle Sandbox Mode off on the developer portal dashboard.

## 18. Simple Explanation for Sales
The Developer Portal is where programmers connect external software to your WhatsApp account. They can generate access tokens, configure automatic alerts (webhooks), and use sandbox modes to test integrations for free.

## 19. Simple Explanation for Marketing
Admins use this page to sync WhatsApp customer data with your external marketing trackers and CRM databases automatically.

## 20. Simple Explanation for Support
If automatic database updates fail (e.g. contact tags are not updating on your external CRM), your developer can use this portal to check the delivery logs for errors.

## 21. Related Features
- [System Settings](./system-settings.md)
- [Analytics & Billing Dashboard](./analytics-billing.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/developer`
- **Implementation:** `App\Livewire\Developer\DeveloperOverview`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Developer/DeveloperOverview.php`
  - `resources/views/livewire/developer/developer-overview.blade.php`
  - `app/Models/WebhookSubscription.php`
  - `app/Models/WebhookDelivery.php`
  - `app/Models/WebhookSource.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
