# Analytics Dashboard

## What is it?
The **Analytics Dashboard** is your high-level overview of message volume and costs. It helps you understand how much you are spending and how much your team is using the platform.

## Why is it useful?
- **Cost Control**: Monitor your "Wallet" balance to ensure you don't run out of credits.
- **Volume Tracking**: See how many messages are sent vs. received daily.
- **Reporting**: Export transaction history for your finance team.

## Option Buttons (UI Guide)
- **Time Range**: Filter charts by Last 30 Days (default).
- **Wallet Balance**: Large card showing current available funds.
- **Message Stats**:
  - **Sent**: Green line on the chart.
  - **Received**: Teal line on the chart.
- **Ticket Resolution**: Number of support tickets closed.
- **Export Transactions**: Download a CSV of all payments and charges.

## Use Cases
1.  **Budget Review**: At the end of the month, the manager checks the dashboard to see if message volume increased and downloads the invoice.
2.  **Health Check**: You notice a sudden drop in "Received Messages" on the chart. You investigate if the connection is down.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Billing** | System | Monthly Report | 1. Aggregates daily usage.<br>2. Calculates cost per conversation.<br>3. Deducts from Wallet.<br>4. Updates Dashboard balance. | Clear financial transparency. |

## How to Use
1.  **Navigate**: Go to **Intelligence** > **Analytics** > **Dashboard**.
2.  **View**: Check the main chart for trends.
3.  **Export**: Click **Export Transactions** at the bottom to get your spending report.
