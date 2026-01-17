# Customer Events

## What is it?
**Customer Events** give you a granular timeline of user actions. Unlike aggregate chats, this tracks specific behaviors like "Clicked Button", "Opened Link", or "Added to Cart".

## Why is it useful?
- **Deep Insights**: Know exactly what a customer did before they messaged you.
- **Troubleshooting**: See if a user tried to pay but failed (e.g., "Payment Failed" event).
- **Segmentation**: Find all users who performed a specific action.

## Option Buttons (UI Guide)
- **Filter Event Type**: Dropdown to show only specific events (e.g., `added_to_cart`).
- **Date Range**: Slider to look back 7, 30, or 90 days.
- **Event List**: Chronological list of actions.
  - **Detail View**: Click any row to see the raw JSON data.
- **Export**: Download the raw event log for external analysis.

## Use Cases
1.  **Lost Sales Recovery**: You filter for `payment_failed`. You see 5 customers failed yesterday. Your team calls them to help complete the purchase.
2.  **Bot Performance**: You track how many times the event `bot_flow_completed` fired to see if users are finishing your automated survey.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Event Tracking** | System | User Action | 1. User clicks "Buy" in WhatsApp.<br>2. System logs `click_buy` event with timestamp.<br>3. Adds to Timeline. | granular user journey mapping. |

## How to Use
1.  **Navigate**: Go to **Intelligence** > **Analytics** > **Customer Events**.
2.  **Filter**: Select an **Event Type** you are interested in.
3.  **Inspect**: Click the "Eye" icon on a row to see details.
