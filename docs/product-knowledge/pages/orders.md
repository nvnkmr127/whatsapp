# Order Fulfillment Manager

## 1. What is this page?
The Order Fulfillment Manager is the transaction tracking and fulfillment workspace of the platform. Located at `/commerce/orders`, it allows support agents, marketers, and admins to review customer purchase histories, edit shipping statuses, and trigger automated WhatsApp order notifications.

## 2. Why is this page useful?
After a customer places an order on WhatsApp or a linked store, keeping them updated on fulfillment stages (e.g. confirming payments, shipping parcels) is critical to prevent support inquiries.
- **Why do users need it?** To track purchase transactions, create manual orders for clients during chat support, and update order statuses (e.g., from paid to shipped).
- **What work does it make easier?** It automatically links order transactions to CRM contact records, registers item details, and sends WhatsApp template updates to customers when order stages change.
- **What business process does it support?** Transaction Logging, Order Status Management, and Automated WhatsApp Delivery Alerts.
- **What happens without it?** Support agents cannot lookup order numbers, verify payment statuses, or trigger delivery notification updates directly from the CRM inbox.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Customer Support Agent | To search for a customer's order ID, verify items purchased, update shipping states, and check transaction dates. |
| Admin / Manager | To view average order values (AOV), create manual orders, and delete failed transactions. |

## 4. What can users do here?
- **Track Order Dashboard:**
  - Search orders by Order ID, customer name, or phone number.
  - Filter orders by status (Paid, Delivered, Cancelled, Pending, Shipped).
  - Review core stats: Revenue Total, Average Order Value (AOV), Pending Orders Count, and Total Orders Count.
- **Inspect Order Details (Modal):**
  - View customer name, contact link, order reference number, source (e.g. manual, WooCommerce), and date.
  - Review purchase items (image, name, SKU, price, quantity) and total transaction pricing.
  - Delete failed or incorrect orders.
- **Create Manual Orders:**
  - Select an active CRM contact.
  - Set custom transaction totals and currencies.
  - Input JSON item arrays for item tracking.
- **Update Fulfillment Status:**
  - Change the order stage (Paid, Shipped, Delivered, Cancelled) via a dropdown in the details modal.
  - Trigger automated customer notification templates upon status updates.

## 5. What is involved?
- **Order Model:** Stores transaction codes (`MAN-...`), total amounts, currency, items lists, and status strings.
- **Contact Model:** Connects transactions to buyer profiles.
- **OrderStatusUpdated Event:** Dispatches template notifications to buyers when state shifts occur.
- **OrderManager livewire component:** Handles listing layouts, searches, modal overlays, and status updates.

## 6. How does it work?
1. An agent is chatting with a customer who has purchased a product. The customer asks: "Has my package shipped?"
2. The agent goes to `/commerce/orders` and enters the customer's name in the search bar.
3. The customer's order appears. The agent clicks "View Details" to open the order modal.
4. The agent sees the status is "pending". They change the dropdown to "shipped" and click save.
5. The database updates the order. The system triggers the `OrderStatusUpdated` event.
6. If the team's notification template is active, the system sends an automated WhatsApp template to the customer: "Hi Alice, order MAN-1A2B3C has been shipped! Track here: ..."

## 7. What happens behind the scenes?
- **Relational Integrity Lookup:** The search bar performs a query on the `Order` table. It filters matching IDs and joins the `Contact` table to search customer name and phone matches, ensuring agents can look up transactions even if they only have the customer's phone number.
- **Fulfillment Hook Dispatcher:** When the `updateStatus` method executes successfully, the database commits the change. The code checks if the team has the `commerce_notifications` feature enabled. If true, it fires the `OrderStatusUpdated` event, which matches the new status with the team's notification templates and pushes the outbox message to the queue.
- **Metrics Calculations:** The stats header calculates average order value dynamically: `total_revenue / total_orders`, updating in real-time as filters are applied.

## 8. Business Use Cases

**Use Case 1: Manual Sales Logging**
- **Situation:** A customer calls support to buy a replacement item. The agent processes the payment manually and wants to log the sale.
- **How the feature is used:** The agent opens the Order Manager, clicks "Create Order", selects the customer's contact card, inputs the price, and saves.
- **Customer experience:** The customer receives a receipt notification.
- **Business outcome:** Centralized transaction records for manual sales.

**Use Case 2: Sending Shipping Alerts**
- **Situation:** A fulfillment agent ships a batch of orders and wants to notify customers.
- **How the feature is used:** They search the orders on this page, click details, update the status to "shipped", and save.
- **Customer experience:** Customers receive their shipping template containing tracking codes.
- **Business outcome:** High-volume customer notifications with zero manual drafting.

**Use Case 3: Resolving Support Queries**
- **Situation:** A customer claims they paid but their order is showing as unpaid.
- **How the feature is used:** The agent looks up the order ID, checks the items list, matches the payment reference, and updates the status to "paid".
- **Customer experience:** The customer gets immediate confirmation of their payment status.
- **Business outcome:** Fast dispute resolution and accurate payment records.

## 9. Industry Use Cases
- **Retail:** Tracking package shipments and delivery alerts.
- **Education:** Logging manual course enrollment fees.
- **Automotive:** Confirming vehicle order bookings.

## 10. Real Customer Example
A customer purchases a bike from an online shop. The order appears in `/commerce/orders` as "pending". An agent checks the details modal, packs the bike, updates the status to "shipped", and clicks save. The customer's WhatsApp profile immediately receives a template message with their package details. Once delivered, the courier webhook updates the status to "delivered", triggering a follow-up survey template.

## 11. Customer Journey
Order recorded (manual or synced) &rarr; Agent searches order ID &rarr; Opens detail modal &rarr; Updates status dropdown &rarr; System dispatches notification template &rarr; Transaction completed.

## 12. Inputs
- Order ID, name, or phone queries.
- New status selection (Paid, Shipped, Delivered, Cancelled).
- Manual order details (contact, amount, items list).

## 13. Outputs
- Updated order status in database.
- Logged transaction records.
- Dispatched WhatsApp status updates.

## 14. Dependencies
- **Order Model:** Database record.
- **Contact Model:** Buyer details.
- **OrderStatusUpdated Event:** Trigger engine.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission on plans including `commerce`.
- **Who can view information:** Admins/Marketers/Agents.
- **Who can edit:** Admins/Marketers/Agents.
- **Who cannot access it:** Guest accounts.

## 16. Important Rules
- Changing an order's status to "cancelled" or "returned" subtracts those amounts from the dashboard's revenue calculations.
- Deleting an order is permanent and cannot be undone.

## 17. Common Problems
- **Problem:** Status template update is not sending.
  - **Possible reason:** The team has not enabled the `commerce_notifications` feature flag, or the approved template is missing.
  - **What the user should do:** Check settings to ensure commerce notifications are active, and verify that the status templates are approved by Meta.
- **Problem:** Customer name is not showing up on manual order creator.
  - **Possible reason:** The contact record has not been created in the Audience Center.
  - **What the user should do:** Go to the Contacts page, add the customer contact record, and then return to create the order.

## 18. Simple Explanation for Sales
The Order Manager keeps track of your sales. You can view what customers bought, update their delivery status (paid, shipped, delivered), and send them automated WhatsApp receipts.

## 19. Simple Explanation for Marketing
Review purchase metrics. See total sales volume, monitor average order values, and check pending orders to optimize campaign schedules.

## 20. Simple Explanation for Support
Manage customer bookings. Find order details by name or code, edit fulfillment statuses, and trigger automated delivery updates to keep clients informed.

## 21. Related Features
- [Commerce Dashboard](./commerce.md)
- [Product Catalog Manager](./products.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/commerce/orders`
- **Implementation:** `App\Livewire\Commerce\OrderManager`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Commerce/OrderManager.php`
  - `app/Models/Order.php`
  - `app/Events/OrderStatusUpdated.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
