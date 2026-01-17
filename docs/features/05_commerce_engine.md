# Commerce Engine

## What is it?
The **Commerce Engine** allows you to sell products directly inside WhatsApp. It includes a Product Catalog, Shopping Cart functionality, and an Order Management system. It effectively turns a chat window into an e-commerce store.

## Why is it useful?
- **Frictionless Buying**: Customers don't need to leave WhatsApp to visit a website. They browse and buy instantly.
- **Higher Conversion**: The "Chat-to-Buy" experience is faster and feels more personal.
- **Automated Updates**: Automatically send "Order Confirmed" and "Shipped" notifications to the customer's WhatsApp.
- **Sync**: Manage your inventory and prices in one dashboard.

## Option Buttons (UI Guide)
- **Product Manager**:
  - **Add Product**: Form to enter Name, Price, Description, and Image.
  - **Stock Toggle**: Mark items as "In Stock" or "Out of Stock".
- **Order Dashboard**:
  - **Status Tabs**: "Pending", "Processing", "Completed", "Cancelled".
  - **Order Details**: Click to see what key items were purchased and the customer's address.
- **Settings**:
  - **Currency**: Set your store's currency (USD, INR, EUR, etc.).
  - **Notifications**: Toggle which automated messages customers receive.

## Use Cases
1.  **Restaurant Menu**: A pizza place uploads their menu. Customers browse pizzas, select toppings, and place a delivery order in chat.
2.  **Boutique Store**: A fashion brand launches a new bag. They send a broadcast with the product attached. Customers click "View Product" -> "Add to Cart" -> "Checkout".
3.  **Service Booking**: A salon lists "Haircut" and "Manicure" as products. Clients "buy" the service to book their slot.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Ordering** | Customer | Browse Catalog | 1. Customer views catalog in WhatsApp.<br>2. Adds 2 items to cart.<br>3. Sends cart to business.<br>4. Business receives order in Order Dashboard.<br>5. Business marks as "Accepted". | Seamless sale; No website required; Instant transaction. |

## How to Use
1.  **Setup Catalog**: Go to **Commerce** > **Products**.
2.  **Add Item**: Click **New Product**. Upload a photo, set a price (e.g., $20), and write a description. Save it.
3.  **Share**: Open the **Team Inbox**, chat with a customer, click the **Attachment** icon, and select **Product**. Send the item you just created.
4.  **Manage Orders**: Go to **Commerce** > **Orders**. When a customer buys something, it will appear here. Click the order to view details and update its status.
