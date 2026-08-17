# Commerce Suite (Dashboard, Products, and Orders)

## 1. What is this page?
The Commerce Suite is the transaction and inventory center of the platform. It consists of the **Commerce Dashboard (`/commerce`)** for purchase conversion analysis, the **Product Manager (`/commerce/products`)** for catalog synchronization, and the **Order Manager (`/commerce/orders`)** for managing customer transactions and automated shipping notifications.

## 2. Why is this page useful?
E-commerce businesses need a way to showcase catalog items and confirm orders directly within WhatsApp conversations without forcing users to leave their chat app.
- **Why do users need it?** To track purchase conversions, sync inventory items with Meta Business Catalogs, create manual checkout logs, and automate customer shipping updates.
- **What work does it make easier?** It unifies inventory items and customer orders inside the CRM, allowing support agents to check cart histories, change order stages, and trigger automated notification templates instantly.
- **What business process does it support?** Conversational Commerce, Order Fulfillment, E-Commerce Analytics, and Catalog Syncing.
- **What happens without it?** The system has no awareness of product SKU catalogs or customer checkout states, meaning teams cannot send WhatsApp catalog cards, trigger cart reminders, or automate shipping updates on status shifts.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To monitor e-commerce integration health, review bulk sync queues, and manage billing settings. |
| Marketing Manager | To track product catalog conversions (funnels) and trigger product restock alerts. |
| Customer Support Agent | To check order details, update shipping statuses (pending, shipped, paid), and record manual sales during customer chats. |

## 4. What can users do here?
- **Commerce Analytics Dashboard (`/commerce`):**
  - Monitor global stats: Total Revenue, Total Orders, Pending Orders, and Total Products.
  - Review Monthly Trends: Tracks percentage growth in orders and revenue compared to the prior month.
  - **Funnel Performance:** Maps the conversion progression from **Catalog Impressions** &rarr; **Active Carts** &rarr; **Orders Placed** with calculated checkout ratios.
  - **Fulfillment Center Operations:** Track out-of-stock items, ready-to-ship products, returns, and AI hours saved from automated bot replies.
- **Product Manager (`/commerce/products`):**
  - Search products by Name or Retailer SKU.
  - Create, edit, and delete products (Name, price, currency, custom retailer ID/SKU, description, website URL, availability status, active state, and category).
  - Upload catalog photos (up to 2MB) stored in public storage.
  - **Meta Catalog Sync:** Sync individual products to Meta's Catalog API, or trigger a background job (`SyncProductsToMetaJob`) to bulk sync the entire catalog.
- **Order Manager (`/commerce/orders`):**
  - Search order sheets by ID, customer name, or phone number.
  - Filter orders by status (Paid, Delivered, Cancelled, Pending, Shipped).
  - Create manual orders for CRM contacts (set custom total price, currency, and items lists).
  - **Status Fulfillment Editor:** Update order stages (e.g. from paid to shipped) which automatically triggers transactional customer notifications (if the `commerce_notifications` feature is active).
  - Review average order values (AOV) and revenue summaries.

## 5. What is involved?
- **Product Model:** Stores inventory items, currency, retailer IDs, availability, and sync states.
- **Order Model:** Stores order codes, customer IDs, total pricing, status flags, item lists, and source tags (e.g., WooCommerce, Manual).
- **Cart Model:** Tracks customer shopping cart sessions.
- **MetaCommerceService & WhatsAppCommerceService:** API handlers that synchronize local database products with Meta Catalog Manager systems.
- **SyncProductsToMetaJob:** A queue worker that handles bulk uploads of inventories to Meta.
- **OrderStatusUpdated Event:** Dispatches notification templates to customers when order stages change.

## 6. How does it work?
1. A store manager wants to offer product purchases directly on WhatsApp.
2. They go to `/commerce/products` and upload a new product: "Classic Hoodie" (SKU `CL-HOD-01`, price $40).
3. They click "Sync". The backend contacts Meta's Catalog API to register the hoodie in the company's Meta catalog.
4. When a customer messages the clinic asking for products, a chatbot triggers a catalog node, displaying the Hoodie.
5. The customer selects the hoodie, creating a shopping cart session in the platform database.
6. Once checkout is completed, the webhook triggers a state change. The manager goes to `/commerce/orders`, finds the order, updates the status to "shipped", and the system automatically sends a WhatsApp message: "Hi Alice, order MAN-8X9Y1Z has shipped! Tracking URL: ..."

## 7. What happens behind the scenes?
- **Catalog Funnel Calculator:** The dashboard calculates impressions based on views registered in `CustomerEvents` logs, cart counts from checkout tables, and orders from transaction databases. If no events exist, the dashboard runs fallback estimation queries based on typical conversion averages (e.g. estimating catalog impressions as 12x order count).
- **Product Locking:** When importing catalogs from WooCommerce, certain fields (like local descriptions or custom pricing) can be locked (`lockField` endpoint) to prevent subsequent Woo sync sweeps from overwriting custom configurations.
- **Transactional Dispatcher:** When an agent changes an order status in the Order Manager, a transaction event is fired. If `commerce_notifications` is active, the event loads the matching approved WhatsApp template (e.g., `order_delivery_update`), injects order variables, and dispatches the outbox message using the team's WhatsApp credentials.

## 8. Business Use Cases

**Use Case 1: Syncing WooCommerce Catalogs**
- **Situation:** An e-commerce store wants to sell their WordPress inventories via WhatsApp.
- **How the feature is used:** They connect their WooCommerce integration. The system imports all items to the Product Manager. The manager edits descriptions to format them for mobile screens, locks those fields, and clicks "Sync All" to upload them to Meta.
- **Customer experience:** Customers view accurate product details directly inside WhatsApp.
- **Business outcome:** Streamlined omni-channel sales pipeline.

**Use Case 2: Tracking Checkout Abandonment**
- **Situation:** A marketing manager wants to audit why sales dropped this week.
- **How the feature is used:** They open the Commerce Dashboard and look at the checkout funnel ratios. They notice that Catalog Impressions were high (10,000) and Carts Created were steady (1,200), but Orders Placed fell from 400 to 50.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** The manager identifies a checkout link failure, resolving a critical payment portal bug.

**Use Case 3: Manual Order Entry**
- **Situation:** A customer service agent resolves a product return query and sells the customer a replacement item directly in chat.
- **How the feature is used:** The agent opens the Order Manager, clicks "Create Order", selects the customer's contact record, inputs the replacement total, and saves it.
- **Customer experience:** The customer receives a receipt notification and booking update.
- **Business outcome:** High-touch, direct support conversions.

## 9. Industry Use Cases
- **Retail:** Managing catalog syncs and cart notifications.
- **Services:** Generating manual orders for booking fees directly during chats.
- **Fulfillment:** Triage centers updating shipping states to trigger delivery templates.

## 10. Real Customer Example
A bakery lists 20 cake designs in the Product Manager. They run a bulk sync to Meta so the cakes display in their WhatsApp profile. When a client opens their profile, clicks "View Catalog", and adds a chocolate cake to their cart, a cart log is created. The client submits their order, creating a pending card in the Order Manager. A kitchen agent bakes the cake, updates the status to "delivered" in the panel, and the bakery's WhatsApp profile sends a confirmation template with pickup instructions.

## 11. Customer Journey
Marketer uploads/imports products &rarr; Items synced to Meta Business Catalog &rarr; Customer browses catalog cards in chat &rarr; Cart checkout creates pending order record &rarr; Fulfillment updates order stage &rarr; Automated shipping alerts sent.

## 12. Inputs
- Product details (name, price, SKU, URL, availability).
- Uploaded product photos.
- Order details (contact ID, total pricing, status, item lists).
- Status update flags (Paid, Shipped, Delivered, Cancelled).

## 13. Outputs
- Saved product inventory rows.
- Synchronized Meta Catalog items.
- Generated order logs and manual sales records.
- Dispatched order status template updates.

## 14. Dependencies
- **Product, Order, and Cart Models:** Database targets.
- **Integration Model:** Meta commerce credentials.
- **Meta Commerce Service / WhatsApp Commerce Service:** Core APIs.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission on plans including `commerce`.
- **Who can view information:** Admins/Marketers/Agents.
- **Who can edit:** Admins/Marketers/Agents (product creations are limited to Marketer/Admin roles).
- **Who cannot access it:** Standard guest users.

## 16. Important Rules
- Product Retailer IDs (SKUs) must be unique and alphanumeric (spaces are invalid).
- Transaction status updates require an active Meta API token connection to deliver customer templates.

## 17. Common Problems
- **Problem:** Product sync fails showing "Unverified Catalog ID" message.
  - **Possible reason:** The Meta Business Manager account has not approved access to the catalog ID, or the integration token has expired.
  - **What the user should do:** Go to Integration Settings, reconnect the Meta Business suite, check catalog ownership, and retry the sync.
- **Problem:** WooCommerce product fields keep reverting after automated syncs.
  - **Possible reason:** The field lock toggle was not activated, meaning the hourly background sync overwrote your local edits.
  - **What the user should do:** Go to the Product card, click "Lock Fields" for description/pricing, and save.

## 18. Simple Explanation for Sales
The Commerce Suite is your store manager. It lets you display products directly inside WhatsApp, track customer sales, and update order statuses so customers get shipping updates automatically.

## 19. Simple Explanation for Marketing
Check your checkout conversion stats. See how many people look at your catalog cards, how many add items to their cart, and review monthly revenue trends to optimize campaign ROI.

## 20. Simple Explanation for Support
When a customer asks where their order is, look them up in the Order Manager. You can check order items, total amounts, update status flags, and trigger automated delivery updates on the fly.

## 21. Related Features
- [Audience Center](./contacts.md)
- [Campaign Wizard](./campaign-wizard.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/commerce`, `/commerce/products`, and `/commerce/orders`
- **Implementation:** `App\Livewire\Commerce\Dashboard`, `App\Livewire\Commerce\ProductManager`, and `App\Livewire\Commerce\OrderManager`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Commerce/Dashboard.php`
  - `app/Livewire/Commerce/ProductManager.php`
  - `app/Livewire/Commerce/OrderManager.php`
  - `app/Models/Product.php`
  - `app/Models/Order.php`
  - `app/Services/Integrations/MetaCommerceService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
