# Inbound Webhook Sources Manager

## 1. What is this page?
The Inbound Webhook Sources Manager is the platform's data mapping portal. Located at `/developer/webhook-sources`, it allows administrators and developers to set up secure endpoints for third-party platforms (Shopify, Stripe, WooCommerce, Zapier, Make), capture incoming JSON feeds, map data visually, define retry policies, and configure triggers to send templates or OTPs.

## 2. Why is this page useful?
Linking third-party software to send WhatsApp updates often requires writing custom API mapping scripts, which is slow and hard to maintain.
- **Why do users need it?** To visually map customer data (like names, emails, and order totals) from platforms like Shopify directly into WhatsApp templates without writing code.
- **What work does it make easier?** It offers a step-by-step configuration wizard, captures incoming test payloads in real time, and handles retry queues for failed deliveries.
- **What business process does it support?** Visual Integration Mapping, E-Commerce Automation, and Retry Management.
- **What happens without it?** Developers must write and host custom middleware scripts to parse data from external webhooks and route them to WhatsApp, increasing setup times.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To download webhook message reports, toggle source statuses, and audit integration traffic. |
| Software Developer | To connect e-commerce endpoints, capture test payloads, build field mappings, and configure retry strategies. |

## 4. What can users do here?
- **Download Webhook Message Reports:**
  - Export CSV logs filtered by date ranges (7, 30, or 90 days) and status filters (All, Sent, Delivered, Read, Failed).
  - Export records for all sources or download failed message reports.
- **Audit Integration Sources:**
  - View all sources, platform types (Shopify, Stripe, WooCommerce, Zapier, Make, Custom), and unique callback URLs.
  - Open the **Live Monitor Modal** to review analytics (success rates, total processed/failed counts) and check recent log history.
  - Duplicate configurations to quickly create new endpoints.
- **Configure Webhooks with a Step-by-Step Wizard:**
  - **Step 1: Identify & Secure:** Select platform types and configure authentication methods (API Key, HMAC Signature, Basic Auth, or Open).
  - **Step 2: Capture Live Data:** Click "Start Capture" to listen for incoming events. The wizard uses Livewire polling to detect when a test payload hits the URL, showing a success alert when captured.
  - **Step 3: Visual Mapping:** Select flattened JSON fields (e.g. `customer.first_name`) from dropdowns to map payload data directly to template parameters.
  - **Step 4: Logic & Rules:**
    - **Filter Rules:** Define conditions (Field, Operator, Value) to skip specific events.
    - **Processing Delays:** Add delays (0 to 60 minutes) before triggering actions.
    - **Contact Tagging:** Auto-assign tags to contacts created or updated by the source.
    - **Retry Policies:** Configure retries (max retries, intervals, and linear or exponential backoffs).
    - **Action Config:** Choose triggers (send template, send OTP, or send media).

## 5. What is involved?
- **WebhookSource Model:** Stores configuration options, platforms, retry settings, and mappings.
- **WebhookPayload Model:** Logs payload history and statuses.
- **WebhookMappingService:** Handles JSON parsing, data mapping, and formatting rules (like phone number normalization).
- **ProcessMappedWebhookJob:** Background job that processes incoming payloads.

## 6. How does it work?
1. The Developer opens `/developer/webhook-sources` to map Shopify orders to a WhatsApp template.
2. They click "+ New Source", enter "Shopify Orders", select the platform "Shopify", and click next.
3. The wizard generates a callback URL. The developer clicks "Start Capture" and sends a test order from Shopify.
4. The wizard detects the test payload, imports the JSON structure, and opens the mapping step.
5. For the phone parameter, the developer selects `customer.phone` from the dropdown. For the name parameter, they select `customer.first_name`.
6. Under Step 4, they select their "Order Confirmation" template, configure a 2-minute delay, and enable exponential retries.
7. They save. Now, when a Shopify order occurs, the platform maps the data and triggers the WhatsApp message automatically.

## 7. What happens behind the scenes?
- **Deduplication Check:** When a webhook arrives, the system hashes the payload to prevent duplicate processing. If the hash matches an event processed within the last 5 minutes, the duplicate is ignored to prevent double-messaging customers.
- **Visual Mapping Flattening:** The system flattens incoming JSON payloads into dot-notation paths (`Arr::dot($payload)`), turning complex objects into simple options like `data.object.customer_details.phone` in dropdowns.
- **Automated Formatting: ** Before mapping data to templates, the system runs normalization filters. For example, phone values are automatically normalized to E.164 format, and Stripe transaction values are converted from cents to decimals.

## 8. Business Use Cases

**Use Case 1: Post-Purchase E-Commerce Confirmation**
- **Situation:** An e-commerce brand wants to send a WhatsApp order summary when a customer purchases on WooCommerce.
- **How the feature is used:** They connect WooCommerce, map `billing.phone` to the phone parameter, and link the order created webhook.
- **Customer experience:** Customers receive a WhatsApp message containing their order number and confirmation.
- **Business outcome:** Reduced order tracking support queries.

**Use Case 2: Payment Failure Alerts**
- **Situation:** A subscription business wants to notify customers on WhatsApp when a Stripe payment fails.
- **How the feature is used:** They connect Stripe, map payment failure webhook payloads, and link them to a failed payment template.
- **Customer experience:** Customers receive a payment failure notification with a link to update their card.
- **Business outcome:** Improved subscription recovery rates.

**Use Case 3: Zapier Custom Workflows**
- **Situation:** A business wants to trigger a WhatsApp message when a new lead is added to a Google Sheet.
- **How the feature is used:** They connect Zapier to a custom webhook source, mapping sheet rows to template parameters.
- **Customer experience:** Leads receive welcome messages.
- **Business outcome:** Automated sales follow-ups.

## 9. Industry Use Cases
- **Travel:** Sending boarding updates from booking webhooks.
- **Education:** Messaging students when enrollment forms are submitted.
- **E-Commerce:** Sending shipping updates when warehouse webhooks trigger.

## 10. Real Customer Example
A developer configures a Shopify webhook source. They generate an HMAC secret to secure the endpoint, copy the callback URL, and paste it into their Shopify settings. They use the Capture wizard to import a test payload, mapping `customer.phone` to the phone parameter and `order_number` to the template parameters. They configure a 5-minute processing delay to avoid messaging customers too quickly.

## 11. Customer Journey
Developer creates source &rarr; Selects platform &rarr; Secures endpoint &rarr; Captures test payload &rarr; Maps payload fields visually &rarr; Configures delay and retry settings &rarr; Activates source.

## 12. Inputs
- Source name and platform type.
- Auth configs (API keys, secrets).
- Mapped JSON paths.
- Action triggers and parameters.
- Retry and delay configurations.

## 13. Outputs
- Webhook source database records.
- Unique callback URLs.
- Filtered CSV reports.
- Processed background jobs.

## 14. Dependencies
- **WebhookSource & WebhookPayload Models:** DB records.
- **WebhookMappingService:** Formatting parser.
- **ProcessMappedWebhookJob:** Background worker.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permissions on plans including `webhooks`.
- **Who can view information:** Admins/Developers.
- **Who can edit/save:** Admins/Developers.
- **Who cannot access it:** Managers, Support Agents.

## 16. Important Rules
- You must capture a test payload first to populate field dropdowns.
- Phone number parameters must map to E.164 formats to send messages successfully.

## 17. Common Problems
- **Problem:** Field dropdowns are empty in the mapping step.
  - **Possible reason:** You skipped the Capture step, or the test payload was empty.
  - **What the user should do:** Return to Step 2, click "Start Capture", send a test request from your platform, and confirm the payload is received.
- **Problem:** WooCommerce webhooks fail verification checks.
  - **Possible reason:** The secret key configured in WooCommerce does not match the shared secret on the platform.
  - **What the user should do:** Regenerate the secret key on this page and paste it into your WooCommerce webhook settings.

## 18. Simple Explanation for Sales
The Inbound Sources page is where you connect external apps (like Shopify, WooCommerce, or Stripe) to WhatsApp. It allows your team to map customer details visually, sending automated WhatsApp updates when orders or payments occur.

## 19. Simple Explanation for Marketing
Admins use this page to trigger automated messaging templates when actions occur in your online store, helping you automate order confirmations and cart reminders.

## 20. Simple Explanation for Support
If customers are not receiving automated order confirmations, ask your administrator to check the Live Monitor stats on this page to see if webhook payloads are failing.

## 21. Related Features
- [Developer Portal](./developer-portal.md)
- [Webhook Logs](./webhook-logs.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/developer/webhook-sources`
- **Implementation:** `App\Livewire\Developer\WebhookSourceManager`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Developer/WebhookSourceManager.php`
  - `resources/views/livewire/developer/webhook-source-manager.blade.php`
  - `app/Models/WebhookSource.php`
  - `app/Services/WebhookMappingService.php`
  - `app/Jobs/ProcessMappedWebhookJob.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
