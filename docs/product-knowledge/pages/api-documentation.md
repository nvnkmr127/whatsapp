# API Documentation Reference

## 1. What is this page?
The API Documentation Reference page is the developer handbook of the platform. Located at `/developer/docs`, it provides API specifications, base endpoint parameters, headers syntax, JSON structures, cURL commands, and callback configurations for integrating the WhatsApp ecosystem into external systems.

## 2. Why is this page useful?
Developers need clear references, code snippets, and exact path URLs to build software integrations without guess-checking variables.
- **Why do users need it?** To quickly set up API requests, map JSON properties, verify payload structures, copy callback URLs for their Meta Business Manager, and build secure CRM integrations.
- **What work does it make easier?** It offers side-by-side code blocks with copy-to-clipboard buttons, lists endpoints, and details parameters.
- **What business process does it support?** API Integration Engineering, CRM Synchronization, and System Event Automation.
- **What happens without it?** Developers must search database tables or experiment with API endpoints, causing configuration delays.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To copy the Meta Callback URL for configuring the WABA setup inside Meta Business Manager. |
| Software Developer | To copy cURL commands, inspect JSON payloads, and verify required request headers. |

## 4. What can users do here?
- **Navigate API Sections:**
  - Quickly jump to subsections using the Navigation Sidebar: Intro, Secure Authentication, Identity & Audience (Contacts), Conversational Messaging (Messages), Message Templates, Advanced Inbound Webhooks, and Meta Webhooks.
- **Copy Base URL & Headers:**
  - Retrieve the API Base URL endpoint (e.g. `https://yourdomain.com/api/v1`).
  - Copy authorization headers: `Authorization: Bearer YOUR_API_TOKEN`.
- **Review Contacts API Specs (`v1/contacts`):**
  - GET `/contacts`: Retrieve contact lists. Copy sample cURL commands.
  - POST `/contacts`: Sync profiles with custom fields. Review JSON payload structures (phone numbers, custom tags, opt-in statuses).
- **Review Messages API Specs (`v1/messages`):**
  - POST `/messages`: Send text or template messages.
  - Review side-by-side examples:
    - *Standard Text:* JSON structures for plain messages.
    - *Marketing Template:* JSON structures for WhatsApp templates (parameters, OTP codes, locales).
- **Review Templates API Specs (`v1/templates`):**
  - GET `/templates`: Retrieve approved templates. Copy sample cURL commands.
- **Review Inbound Webhook Specs:**
  - View endpoint structures: `/api/v1/webhooks/inbound/{slug}`.
  - Copy sample cURL commands for mapping WooCommerce checkout parameters.
- **Copy Meta Callback URL:**
  - Copy the system callback URL (`https://yourdomain.com/api/webhook/whatsapp`) to paste into the Meta App configuration dashboard.

## 5. What is involved?
- **DocumentationService:** The backend helper that returns API documentation sections, endpoints, and code blocks.
- **ApiDocumentationController:** Handles rendering the view.

## 6. How does it work?
1. A developer wants to sync customer orders to trigger a templates message.
2. They open `/developer/docs` and click "Conversational Messaging" in the sidebar.
3. They copy the POST `/messages` JSON template code block.
4. They paste this template into their script, replacing placeholder values with their active customer data variables.
5. They click "Secure Authentication", copy the auth header template, and paste their generated API token into the header configurations.
6. They test their script. The platform processes the API call and delivers the template message.

## 7. What happens behind the scenes?
- **Dynamic URL Generation:** The controller automatically replaces placeholder base URLs inside code blocks with your active server domain URL (e.g. converting `{base_url}` to `https://crm.yourcompany.com/api/v1`), preventing copy-paste domain errors.
- **Jetstream Scope Mapping:** Endpoint permissions are validated against user tokens. If a script calls GET `/contacts` using a token that lacks read permissions, the system blocks the query.
- **Secure Copy Script:** Custom scripts copy snippets to the developer's clipboard and display a brief "Copied!" confirmation on the button, making it easier to copy headers.

## 8. Business Use Cases

**Use Case 1: Integrating Custom Storefronts**
- **Situation:** An e-commerce brand wants to send order confirmations via WhatsApp when a customer checks out on their custom website.
- **How the feature is used:** The developer reviews the POST `/messages` section to copy the JSON structure for template messages, linking it to their checkout script.
- **Customer experience:** Customers receive instant order confirmation messages on WhatsApp.
- **Business outcome:** Faster automated order confirmations.

**Use Case 2: Syncing Lead Generation Forms**
- **Situation:** A marketing team wants to add new leads from their website form directly to their CRM audience list.
- **How the feature is used:** The developer copies the POST `/contacts` code block to route form submissions directly to the audience API.
- **Customer experience:** N/A (Internal lead capture).
- **Business outcome:** Seamless lead capture without manual imports.

**Use Case 3: Setting Up Meta Callbacks**
- **Situation:** A business is setting up their WhatsApp Business Account for the first time and needs to sync incoming message status updates.
- **How the feature is used:** The admin copies the Meta Webhook Callback URL and pastes it into their Meta Developer App dashboard.
- **Customer experience:** N/A (Meta integration setup).
- **Business outcome:** Real-time message status updates.

## 9. Industry Use Cases
- **Retail:** Syncing WooCommerce checkouts to WhatsApp template triggers.
- **Professional Services:** Scheduling booking reminders from custom calendars.
- **Real Estate:** Pushing properties details to customer chat threads.

## 10. Real Customer Example
A developer connects their billing system to the platform. They open `/developer/docs` and use the sidebar to find the Contacts API. They copy the POST `/contacts` JSON structure to sync new users, and use the Messages API reference to configure welcome templates. Finally, they copy the Meta Webhook Callback URL to set up real-time delivery status updates in their Meta App settings.

## 11. Customer Journey
Developer opens docs &rarr; Reviews authentication headers &rarr; Copies endpoint URLs &rarr; Evaluates JSON payload examples &rarr; Integrates snippets into code &rarr; Verifies callback URLs.

## 12. Inputs
- Navigation click triggers.
- Click to copy events.

## 13. Outputs
- Dynamic base URL details.
- Meta Callback URL parameters.
- Copyable cURL and JSON code snippets.

## 14. Dependencies
- **DocumentationService:** Formats text files.
- **ApiDocumentationController:** Serves routes.

## 15. Permissions
- **Who can access this page:** Users on plans that include `api_access`.
- **Who can view information:** Admins/Developers.
- **Who can edit:** N/A (Read-only reference page).
- **Who cannot access it:** Users on plans without API access.

## 16. Important Rules
- You must replace `YOUR_API_TOKEN` placeholders with actual generated API keys.
- Webhook endpoints must support secure HTTPS connections.

## 17. Common Problems
- **Problem:** API requests return `401 Unauthorized` errors.
  - **Possible reason:** The API token is expired, revoked, or formatted incorrectly in the request header.
  - **What the user should do:** Confirm the token is active in the API Tokens manager, and verify the header is formatted as `Authorization: Bearer <token>`.
- **Problem:** Copying cURL commands leads to connection timeouts.
  - **Possible reason:** Your server firewall is blocking outbound requests, or the destination IP is blocked.
  - **What the user should do:** Whitelist the API domain in your server's network configuration settings.

## 18. Simple Explanation for Sales
The API Docs page is an online instruction manual for developers. It provides code snippets and templates, helping your developers link your external software and websites to this platform.

## 19. Simple Explanation for Marketing
Admins use this page to set up connections between WhatsApp and your website forms, allowing new leads to sync automatically.

## 20. Simple Explanation for Support
If customer message updates stop syncing, ask your developer to verify their API calls against the examples on this page.

## 21. Related Features
- [Developer Portal](./developer-portal.md)
- [API Tokens Manager](./api-tokens.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/developer/docs`
- **Implementation:** `App\Http\Controllers\Developer\ApiDocumentationController`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Http/Controllers/Developer/ApiDocumentationController.php`
  - `resources/views/developer/api-documentation.blade.php`
  - `app/Services/Developer/DocumentationService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
