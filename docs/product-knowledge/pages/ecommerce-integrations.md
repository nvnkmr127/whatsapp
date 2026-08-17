# Ecommerce Integrations

## 1. What is this page?
The Ecommerce Integrations page is the connectivity center of the platform. Located at `/integrations/ecommerce`, it allows administrators to connect external storefronts (Shopify, WooCommerce, Custom APIs), link Meta Commerce Catalogs, authorize Meta Marketing Ads, set up webhooks, and audit integration health scores.

## 2. Why is this page useful?
Manually copying product pricing and copying customer checkout details between an online store and a WhatsApp CRM causes shipping delays and errors.
- **Why do users need it?** To bridge the gap between their e-commerce website and their WhatsApp inbox, allowing catalog details and order events to flow automatically.
- **What work does it make easier?** It provides setup inputs for APIs, provides copy-paste webhook endpoints, sets synchronization scopes (like selective category syncing), and runs automated diagnostics to detect connection issues.
- **What business process does it support?** Storefront Synchronization, Webhook Event Integration, and API Access Control.
- **What happens without it?** Customer orders and product catalogs remain isolated, preventing features like automated catalog messaging, cart recovery drip campaigns, and shipping alerts.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To authorize API tokens, paste WooCommerce/Shopify webhook handlers, and review connection error logs. |
| Marketing Manager | To adjust synchronization scopes (e.g., only pulling specific sales categories) and review overall catalog health scores. |

## 4. What can users do here?
- **Connect New Channels:**
  - **Shopify:** Input Shop Domain (`myshopify.com`) and Custom App API Admin access tokens.
  - **WooCommerce:** Input WordPress site URL, Consumer Key, and Consumer Secret.
  - **Custom Site (API):** Generate a custom `X-Integration-Token` API key to push inventory details from home-grown e-commerce backends.
  - **Meta Commerce Catalog:** Connect Meta Catalog ID and System User tokens to sync local products with Meta.
  - **Meta Marketing API:** Input Marketing System User tokens with `ads_management` permissions to link Facebook/Instagram Ad accounts.
- **Monitor Active Connections:**
  - View normalized status tags: Ready/Active (green), Degraded (orange), Broken/Error/Expired (red).
  - Audit connection scores (out of 100).
  - Track "Last Synced" timestamps.
  - Access generated Integration Keys and Webhook Receiver Endpoints.
- **Diagnostic Controls (Modal):**
  - View health scores and a list of issues (e.g., webhook verification warnings, invalid API scopes).
  - Review a timeline of recent sync sessions (processed entities, failed counts, error summaries).
- **Fulfillment & Sync Settings (Modal):**
  - **Sync Scope:** Select "All Active Products" or "Selective Mode" (filter by Shopify Collection or WooCommerce Category IDs).
  - **Authority Rules:** Toggle "Sync Stock" (pull quantities) and "Sync Price" (pull prices).
  - **Webhook Security:** Input Webhook HMAC secrets (for Shopify/WC verification checks).
- **Disconnect Integrations:** Remove connections.

## 5. What is involved?
- **Integration Model:** Stores credentials (credentials JSON), statuses, sync dates, and webhook secrets.
- **SyncSession Model:** Logs sync batches, entity success rates, and errors.
- **ShopifyService & WooCommerceService:** Orchestrates product fetching and webhooks parsing.
- **IntegrationHealthService:** Runs diagnostic checks (credentials test, webhook audits) to output a health score.

## 6. How does it work?
1. The Admin opens the integrations page and clicks "Connect WooCommerce".
2. They input their WordPress store URL, consumer key, and consumer secret.
3. Once connected, they copy the WooCommerce Webhook URL shown under the WC details card.
4. They open their WordPress admin panel, go to WooCommerce settings, create a webhook for event "Order Updated", and paste the copied URL.
5. They return to the integrations page, click "Sync Now", and the system pulls all WooCommerce products into their local product catalog, marking the integration status as "Active".

## 7. What happens behind the scenes?
- **HMAC Signature Check:** When WooCommerce or Shopify pushes an order update webhook to the platform, the receiver script checks the payload header signature against the `webhook_secret` saved in the database. If they don't match, the request is rejected to prevent spoofing.
- **Health Auditor Algorithm:** The `IntegrationHealthService` performs background checks: it pings the storefront API, verifies token scopes, and audits recent sync failure ratios. If more than 20% of catalog items fail to sync, the score drops and the status changes from "Active" to "Degraded".
- **Dynamic Session Logger:** Each sync run spawns a `SyncSession` record. It tracks the duration, entity counters, and stores the first few error details in an `error_summary` column for easy debugging.

## 8. Business Use Cases

**Use Case 1: Selective Catalog Sync**
- **Situation:** A business sells 5,000 items on Shopify but only wants to display a specific "Best Sellers" collection of 100 items on WhatsApp.
- **How the feature is used:** In the settings modal, they set Sync Scope to "Selective" and paste the Shopify Collection ID.
- **Customer experience:** WhatsApp customers only see the selected 100 items in the catalog.
- **Business outcome:** Focused mobile marketing and lower catalog maintenance.

**Use Case 2: Webhook Sync Debugging**
- **Situation:** Order status changes on WooCommerce are not triggering templates on WhatsApp.
- **How the feature is used:** The admin opens the diagnostics panel for WooCommerce. They see the health score is 40/100 and a warning: "Webhook verification failed: Invalid HMAC secret."
- **Customer experience:** N/A (Internal use).
- **Business outcome:** The admin inputs the correct secret, restoring instant client notifications.

**Use Case 3: Custom CRM Hooking**
- **Situation:** A company has a custom-built website and wants to sync orders without standard plugins.
- **How the feature is used:** They generate a "Custom API Key" on this page, copy the X-Integration-Token header, and configure their developer to push order payloads to the platform's custom endpoint.
- **Customer experience:** Customers get instant order templates.
- **Business outcome:** Multi-system connectivity without framework limitations.

## 9. Industry Use Cases
- **Retail:** Connecting Shopify stores to automate catalog campaigns.
- **Services:** Linking Meta Marketing tokens to manage local ads budgets.
- **B2B Wholesale:** Using custom API keys to sync ERP systems.

## 10. Real Customer Example
An organic food brand connects their WooCommerce store. They toggle off "Sync Price" because they want to offer special, lower prices to their WhatsApp chat audience. They copy the webhook URL to WordPress. When a customer changes their order status on the site, WordPress pings the webhook URL. The order updates instantly in the platform's Order Manager, dispatching a delivery confirmation template.

## 11. Customer Journey
Admin connects store API &rarr; Configures sync scope filters &rarr; Configures webhook endpoints &rarr; Sync session populates database products &rarr; Health diagnostics monitored.

## 12. Inputs
- Integration name.
- Shopify domain and Admin API tokens.
- WooCommerce store URLs and key/secret pairs.
- Meta Catalog IDs and System User access tokens.
- Webhook HMAC security secrets.
- Product sync collection/category ID filters.

## 13. Outputs
- Saved `Integration` credentials.
- Auto-generated Webhook Receiver endpoints.
- Logged `SyncSession` events.
- Health scores and issue notices.

## 14. Dependencies
- **Integration & SyncSession Models:** Core DB tables.
- **ShopifyService & WooCommerceService:** Sync drivers.
- **IntegrationHealthService:** Diagnostics engine.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permission on plans including `commerce`.
- **Who can view information:** Admins/Marketers.
- **Who can edit:** Admins.
- **Who cannot access it:** Support agents.

## 16. Important Rules
- Webhook URL security tokens are required for order tracking.
- Syncing large catalogs will block subsequent sync commands until the active background queue job completes.

## 17. Common Problems
- **Problem:** Sync fails showing "Unauthorized" or "Token Expired" errors.
  - **Possible reason:** The store access token was revoked, or the Meta System User token has expired.
  - **What the user should do:** Generate a new API access token in Shopify or Meta, open the Connect Modal for that integration, paste the new token, and save.
- **Problem:** Diagnostics panel displays "Webhook missing" warning.
  - **Possible reason:** You connected the API but forgot to create and paste the webhook URL inside your WooCommerce/Shopify admin settings.
  - **What the user should do:** Copy the Webhook endpoint URL from the active connection card on this page, go to your storefront settings, create a webhook, paste the URL, and save.

## 18. Simple Explanation for Sales
Ecommerce Integrations connect your online store (like Shopify or WooCommerce) to our WhatsApp CRM. This lets your product list sync automatically and alerts the CRM whenever a customer places an order, so you can send them automated shipping updates.

## 19. Simple Explanation for Marketing
Connect your catalogs. Choose whether to import all items or just specific collections, and link Facebook Ads manager to manage marketing budgets.

## 20. Simple Explanation for Support
If you suspect an order sync is broken, notify your admin. They can check the Diagnostics logs on this page to check for connection failures.

## 21. Related Features
- [Product Catalog Manager](./products.md)
- [Order Fulfillment Manager](./orders.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/integrations/ecommerce`
- **Implementation:** `App\Livewire\Integrations\EcommerceIntegrations`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Integrations/EcommerceIntegrations.php`
  - `app/Models/Integration.php`
  - `app/Models/SyncSession.php`
  - `app/Services/Integrations/ShopifyService.php`
  - `app/Services/Integrations/WooCommerceService.php`
  - `app/Services/Integrations/IntegrationHealthService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
