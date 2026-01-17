# Webhook Logs

## What is it?
**Webhook Logs** (Deliveries) show the history of every data packet sent to your server. It tells you if the delivery was Successful (200 OK) or Failed (404/500 Error).

## Why is it useful?
- **Debugging**: If your integration breaks, check here first. If you see "500 Internal Server Error", the bug is on your server.
- **Replay**: (Future) Resend a failed webhook to try again.

## Option Buttons (UI Guide)
- **Status Badge**: Green (Success) or Red (Failed).
- **Response Code**: The HTTP code returned by your server (e.g., 200, 404, 500).
- **Payload**: Click to view the exact JSON data that was sent.
- **Timestamp**: Exact time of delivery attempt.

## Use Cases
1.  **Troubleshooting**: You stopped receiving messages in your CRM. You look at logs and see "401 Unauthorized". You realize you changed your API password and forgot to update the webhook headers.

## How to Use
1.  **Navigate**: Go to **Developer** > **Webhooks**.
2.  **View**: Click closely on a subscription row or "View Deliveries".
3.  **Inspect**: Click a failed delivery to see the error message.
