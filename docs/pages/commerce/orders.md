# Order Manager

## What is it?
The **Order Manager** is where you process sales. It lists every purchase made by customers in WhatsApp, allowing you to update statuses (e.g., Shipped, Delivered).

## Why is it useful?
- **Centralization**: Manage all WhatsApp orders in one table.
- **Status Updates**: Changing a status to "Shipped" can automatically notify the user.
- **Customer Info**: View exactly who bought what, their phone number, and address.

## Option Buttons (UI Guide)
- **Status Filter**: View only "Pending", "Shipped", or "Cancelled" orders.
- **Order ID**: Clickable link to view full details.
- **Customer Name**: Links to the Contact profile.
- **Update Status**: Button inside the details view to move an order to the next stage.

## Use Cases
1.  **Fulfillment**: Warehouse staff checks "Pending" orders. They pack the items for `Order #1005`. They change status to `Shipped`. System sends "Your order is on the way!" message.
2.  **Support**: Customer asks "Where is my stuff?". Agent finds `Order #1005` here and sees it checks "Delivered" yesterday.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Order Processing** | Staff | Change Status | 1. Staff changes status `Pending` -> `Shipped`.<br>2. System saves change.<br>3. Triggers "Order Shipped" notification template. | Operational efficiency and proactive customer service. |

## How to Use
1.  **Navigate**: Go to **Commerce** > **Orders**.
2.  **View**: Click the **Eye Icon** or Order ID to see items ordered.
3.  **Update**: Select a new status (e.g., `Shipped`) from the dropdown and click **Update**.
