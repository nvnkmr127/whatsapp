# Broadcasting

## What is it?
**Broadcasting** is the marketing engine of the platform. It allows you to send a single message to thousands of customers at once. It's used for newsletters, promotions, and updates.

## Why is it useful?
- **Mass Reach**: Communicate with your entire customer base instantly.
- **Personalization**: Use variables like `{{name}}` to make every message feel unique.
- **Scheduling**: Plan campaigns in advance to go out at the perfect time.
- **Efficiency**: Send 10,000 messages in a few clicks instead of manually copy-pasting.

## Option Buttons (UI Guide)
- **New Campaign**: Starts the wizard to create a new broadcast.
- **Filter List**: Search for past campaigns by name.
- **Status Badges**:
  - **Draft**: Saved but not sent.
  - **Scheduled**: Qued up for a future date.
  - **Processing**: Currently sending messages.
  - **Completed**: All messages sent.
- **Delete (Trash Icon)**: Removes an old campaign (cannot recover).

## Use Cases
1.  **Black Friday Sale**: You select your "All Customers" list, choose the "Sale Announcement" template, and schedule it for Friday at 9 AM.
2.  **Event Reminder**: You filter contacts who possess the tag "Registered" and send them a reminder 1 hour before the webinar starts.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Send Broadcast** | Marketer | Click "Launch" | 1. System checks daily sending limit.<br>2. Queues messages for each contact.<br>3. Throttle engine sends messages one by one.<br>4. Updates campaign status to "Completed". | High engagement; Revenue generation from offers. |

## How to Use
1.  **Navigate**: Go to **Engagement** > **Broadcasting**.
2.  **Create**: Click **New Campaign**.
3.  **Name It**: Give internal name (e.g., "Jan Newsletter").
4.  **Select Audience**: Choose a predefined "Contact Segment" or filter by tags (e.g., Tag = 'VIP').
5.  **Choose Content**: Pick a pre-approved **WhatsApp Template**.
6.  **Schedule**: Select "Send Now" or pick a Date/Time.
7.  **Launch**: Click **Submit** to start the process.
