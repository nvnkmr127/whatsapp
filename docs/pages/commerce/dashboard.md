# Commerce Dashboard

## What is it?
The **Commerce Dashboard** provides a snapshot of your WhatsApp store's performance. It tracks sales, orders, and product count in real-time.

## Why is it useful?
- **Revenue Tracking**: See exactly how much money you are making from WhatsApp sales.
- **Order Monitoring**: Quickly spot "Pending" orders that need attention.
- **Inventory Check**: See how many products you have listed.

## Option Buttons (UI Guide)
- **Total Revenue**: Sum of all non-cancelled orders.
- **Total Orders**: Count of all orders ever placed.
- **Pending Orders**: Count of orders that haven't been shipped yet.
- **Total Products**: Number of items in your active catalog.

## Use Cases
1.  **Morning Check**: The store manager logs in at 9 AM to check "Pending Orders" and assigns the warehouse team to pack them.
2.  **Performance Review**: The owner checks "Total Revenue" to see if the recent marketing broadcast increased sales.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Data Update** | System | New Order | 1. Customer places order in WhatsApp.<br>2. System records transaction.<br>3. Dashboard "Pending Orders" and "Revenue" count increases. | Real-time visibility into business health. |

## How to Use
1.  **Navigate**: Go to **Commerce** > **Overview**.
2.  **Analyze**: View the tiles at the top for instant stats.
