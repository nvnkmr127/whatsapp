# Audit Logs

## What is it?
**Audit Logs** provide a chronological history of every consent change. Unlike the Registry (which shows current status), Logs show the *history* (e.g., User A opted in on Monday, opted out on Tuesday, opted in again on Friday).

## Why is it useful?
- **Timeline Analysis**: Understand the complete journey of a user's permission status.
- **Troubleshooting**: See exactly when and why a user was unsubscribed (e.g., was it automatic or manual?).

## Option Buttons (UI Guide)
- **Time Filter**: View logs for the last 30 days.
- **Action Column**: Shows `OPT_IN` (Green) or `OPT_OUT` (Red).
- **Source**: Shows how they opted in (e.g., `keyword`, `api`, `manual`).

## Use Cases
1.  **Investigation**: You want to know when a VIP client unsubscribed. You verify the log date and match it with the message sent that day to see what annoyed them.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Log Creation** | System | Consent Change | 1. Any change to consent status creates a `ConsentLog` record.<br>2. Record includes Timestamp, Actor (User/System), and Previous/New State. | Immutable history of data privacy actions. |

## How to Use
1.  **Navigate**: Go to **Compliance** > **Audit Logs**.
2.  **Search**: Type a phone message to see their specific history.
