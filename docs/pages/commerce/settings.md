# Commerce Settings

## What is it?
**Commerce Settings** control the behavior of your WhatsApp store. You configure currency, payment methods (COD), and automated notifications here.

## Why is it useful?
- **Localization**: Set your store's currency (USD, INR, EUR).
- **Automation**: Tell the system which message to send when an order is "Placed" or "Shipped".
- **Rules**: Decide if "Guest Checkout" is allowed or if you accept Cash on Delivery.

## Option Buttons (UI Guide)
- **Currency**: Dropdown code (e.g., `USD`).
- **Payment Methods**:
  - **COD Enabled**: Toggle for Cash on Delivery.
- **Cart Settings**:
  - **Expiry**: How long before a cart is deleted (minutes).
  - **Reminder**: When to send an "Abandoned Cart" nudged.
- **Notification Templates**: Map a "WhatsApp Template" to each event (e.g., Order Placed -> `order_conf_template`).

## Use Cases
1.  **Store Setup**: You are in India. You set Currency to `INR`, enable `COD`, and map the `order_confirmation` template.
2.  **Abandoned Cart**: You set "Reminder" to 30 minutes. If a user adds items but doesn't buy, they get a nudge after 30 mins automatically.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Configuration** | Admin | Save Settings | 1. Admin maps `shipping_update` template to `Shipped` status.<br>2. When Order Manager updates status, this specific template is used. | Customized communication flow. |

## How to Use
1.  **Navigate**: Go to **Commerce** > **Settings**.
2.  **General**: Set your currency and payment preference.
3.  **Notifications**: Select the valid templates you created in "Template Manager" for each event.
4.  **Save**: Click **Update Settings**.
