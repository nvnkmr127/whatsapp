# Webhooks

## What is it?
**Webhooks** are "Real-Time Notifications". Instead of asking WhatsApp "Do I have new messages?" every minute, WhatsApp *tells* your server "Here is a new message!" the instant it arrives.

## Why is it useful?
- **Speed**: Instant data transfer.
- **Efficiency**: Saves server resources (no posing/polling).
- **Flexibility**: Listen for specific events like `message.received`, `message.sent`, or `campaign.completed`.

## Option Buttons (UI Guide)
- **Target URL**: The address of your server (e.g., `https://api.myapp.com/whatsapp-hook`).
- **Events**: Select which events you want to listen to.
- **Secret**: A security key to verify the data is truly from us.
- **Test**: Send a fake "Test Payload" to see if your server is working.

## Use Cases
1.  **Order Update**: Your shipping software listens for `message.received`. If a user types "Where is my order?", your software reads the webhook, checks the status, and replies.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Event Dispatch** | System | Message Arrives | 1. System detects new message.<br>2. Finds active Webhook Subscription.<br>3. POSTs JSON data to your URL.<br>4. Server responds `200 OK`. | Real-time synchronization. |

## How to Use
1.  **Navigate**: Go to **Developer** > **Webhooks**.
2.  **Create**: Click **New Subscription**.
3.  **Config**: Enter your URL and select `message.received`.
4.  **Save**: Click **Create**.
5.  **Test**: Click the **Test** button to ensure your server works.
