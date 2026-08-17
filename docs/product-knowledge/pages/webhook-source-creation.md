# Webhook Source Configuration Wizard

## 1. What is this page?
The Webhook Source Configuration Wizard is the step-by-step setup modal for inbound integrations. Accessible by clicking "+ New Source" or editing an existing source on the Webhook Sources page, it provides a 4-step wizard to secure connections, capture test payloads, visually map JSON fields to template parameters, and define processing rules.

## 2. Why is this page useful?
Setting up API integrations often requires writing custom code to parse webhooks, normalize phone numbers, and match parameters, which is slow and prone to errors.
- **Why do users need it?** To connect external platforms (like Shopify or Stripe) to WhatsApp templates using a visual, no-code wizard.
- **What work does it make easier?** It generates unique callback endpoints, listens for test payloads in real time, flattens complex JSON structures into simple dropdown options, and handles retries automatically.
- **What business process does it support?** E-Commerce Integration Setup, Field Mapping, and Delivery Automation.
- **What happens without it?** Developers must manually write payload parsing scripts for every external system, slowing down integration times.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin / Lead | To verify security credentials, configure delay parameters, and choose which templates are triggered by webhooks. |
| Software Developer | To connect endpoints, capture test payloads, verify authentication headers, and map JSON properties. |

## 4. What can users do here?
- **Step 1: Identify & Secure:**
  - Name the connection (e.g., "Shopify Cancellations").
  - Choose a platform preset (Shopify, Stripe, WooCommerce, Zapier, Make, or Custom). Selecting a preset automatically configures default authentication headers.
  - Select the authorization method (API Key, HMAC Signature, Basic Auth, or Open).
  - Generate secure keys/secrets and copy the generated Callback URL.
- **Step 2: Capture Live Data:**
  - Click "Start Capture" to put the endpoint in listener mode.
  - View required headers (e.g., `X-API-Key`) and expected values to test the endpoint with Postman or curl.
  - Send a test request from the platform. The wizard automatically captures the payload and advances to the next step.
- **Step 3: Visual Field Mapping:**
  - Select fields (e.g., `customer.first_name`) from dropdowns containing dot-flattened JSON properties.
  - Map the target customer phone number.
- **Step 4: Logic, Rules & Launch:**
  - **Filter Rules:** Define rules to ignore specific events (e.g., ignore transactions under $10).
  - **Processing Delays:** Add delays (0 to 60 minutes) to wait before sending messages.
  - **Auto-Tagging:** Attach contact tags to profiles updated by the webhook. Create new tags on-the-fly with colors.
  - **Retry Policies:** Configure retries (max retries, intervals, and linear or exponential backoffs).
  - **Link Action Triggers:** Choose to send a template, send an OTP, or send media.

## 5. What is involved?
- **WebhookSource Model:** Stores platform types, credentials, retry policies, and mappings.
- **WebhookPayload Model:** Stores test payloads captured during setup.
- **WebhookMappingService:** Flattens JSON schemas into selectable options.

## 6. How does it work?
1. The Admin opens the wizard, enters "WooCommerce Orders", selects the platform "WooCommerce", and clicks Next.
2. The wizard displays the WooCommerce Callback URL and configures the default authorization header.
3. The Admin clicks "Start Capture", goes to their WooCommerce admin panel, and triggers a test webhook.
4. The wizard detects the test payload, imports the JSON structure, and opens the mapping step.
5. Under Step 3, they select `billing.phone` for the customer's phone number.
6. Under Step 4, they choose the Action Type "Send Template", select their "Order Confirmation" template, and map the order variables. They enable retries, click Save, and activate the webhook.

## 7. What happens behind the scenes?
- **State Initialization:** When Step 1 is saved, the system creates a database record with a status of `draft`. This generates a unique slug and URL endpoint before you progress to capture test data.
- **Polling Capture Listener:** During the Capture step, the wizard uses Livewire polling to check the `webhook_payloads` table for new entries matching the source ID. When a payload arrives, polling stops and the system imports the JSON schema.
- **JSON Flattening:** The system uses `Arr::dot()` to flatten complex, nested JSON payloads into key-value pairs (e.g., converting `{ "customer": { "phone": "123" } }` to `customer.phone`), populating the dropdown selectors in Step 3.

## 8. Business Use Cases

**Use Case 1: Mapping Custom Order Forms**
- **Situation:** A business uses a custom order form on their website and wants to send WhatsApp confirmation messages automatically.
- **How the feature is used:** They create a Custom source, use Postman to send a test payload, and map the form fields to their welcome template variables.
- **Customer experience:** Customers receive instant welcome messages after submitting forms.
- **Business outcome:** Seamless order confirmation automation.

**Use Case 2: Configuring Delays for Cart Reminders**
- **Situation:** A marketing team wants to send cart reminders to customers who abandon checkout, but wants to wait 30 minutes to avoid annoying them.
- **How the feature is used:** They connect Shopify checkouts and configure a 30-minute processing delay in Step 4.
- **Customer experience:** Customers receive a friendly cart reminder 30 minutes after checkout abandonment.
- **Business outcome:** Increased checkout recovery rates.

**Use Case 3: Retrying Failed Delivery Alerts**
- **Situation:** A logistics company sends shipping updates via webhook, but wants to retry automatically if their customer's phone is temporarily out of service.
- **How the feature is used:** They configure a retry policy with a max of 3 retries and an exponential backoff strategy.
- **Customer experience:** Delivery updates are retried automatically until they are successfully delivered.
- **Business outcome:** Reliable message deliveries.

## 9. Industry Use Cases
- **Retail:** Mapping order checkouts to shipping updates.
- **Real Estate:** Mapping property submission forms to agent alert workflows.
- **Logistics:** Mapping delivery tracking updates to customer notifications.

## 10. Real Customer Example
A developer maps WooCommerce checkouts to a template. They create a WooCommerce webhook source, copy the Callback URL, and paste it into their WooCommerce settings. They click "Start Capture", send a test checkout payload, and map `billing.phone` to the customer's phone. They configure a 5-minute processing delay to avoid messaging customers too quickly, choose a welcome template, and click Save.

## 11. Customer Journey
Developer names source &rarr; Selects platform &rarr; Secures endpoint &rarr; Captures test payload &rarr; Maps fields visually &rarr; Configures delays and retries &rarr; Activates integration.

## 12. Inputs
- Connection name.
- Platform type.
- Auth details (keys, secrets, headers).
- Target phone number and template mappings.
- Custom tag colors and names.
- Delay and retry settings.

## 13. Outputs
- Saved `Draft` and `Active` source records.
- Unique slug endpoints.
- Flattened mapping dropdowns.
- Background retry rules.

## 14. Dependencies
- **WebhookSource Model:** DB records.
- **WebhookMappingService:** JSON parser.
- **Livewire Polling Engine:** Capture listener.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permissions on plans including `webhooks`.
- **Who can view information:** Admins/Developers.
- **Who can edit:** Admins/Developers.
- **Who cannot access it:** Managers, Support Agents.

## 16. Important Rules
- You must click "Start Capture" and send a test payload before mapping fields; you cannot proceed to Step 3 without an imported JSON schema.
- Phone number mapping must point to a field containing E.164 formats, or normalized phone numbers.

## 17. Common Problems
- **Problem:** The wizard is stuck on "Listening for Events".
  - **Possible reason:** The test webhook was not sent, or it used incorrect authentication credentials.
  - **What the user should do:** Confirm your external server sent the payload to the correct URL, and verify the authorization header matches the values displayed in the modal.
- **Problem:** A mapped field fails validation checks.
  - **Possible reason:** The selected JSON path returned null or was formatted incorrectly.
  - **What the user should do:** Open the Capture JSON preview in Step 2 to verify the field path matches.

## 18. Simple Explanation for Sales
The Webhook Source Wizard is a 4-step setup assistant. It allows your developers to connect external apps to WhatsApp visually, capture test data, map fields, and set up message retries without writing custom code.

## 19. Simple Explanation for Marketing
Admins use this wizard to map data fields (like names and order totals) from your online store to WhatsApp templates, helping you automate personalized notifications.

## 20. Simple Explanation for Support
If automated templates contain incorrect data, ask your developer to open this configuration wizard to verify the fields are mapped correctly.

## 21. Related Features
- [Developer Portal](./developer-portal.md)
- [Inbound Webhook Sources](./inbound-webhook-sources.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/developer/webhook-sources` (Wizard modal)
- **Implementation:** `App\Livewire\Developer\WebhookSourceManager`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Developer/WebhookSourceManager.php`
  - `resources/views/livewire/developer/webhook-source-manager.blade.php`
  - `app/Models/WebhookSource.php`
  - `app/Services/WebhookMappingService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
