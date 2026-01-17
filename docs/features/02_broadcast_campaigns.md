# Broadcast Campaigns

## What is it?
**Broadcast Campaigns** allow you to send a single message to hundreds or thousands of customers simultaneously. Unlike a group chat where everyone sees everyone else, a broadcast sends a private message to each recipient. It's the "Email Marketing" of WhatsApp.

## Why is it useful?
- **Mass Reach**: Instantly update your entire customer base about sales, news, or alerts.
- **Personalization**: Use variables like `{{1}}` to automatically insert the customer's name (e.g., "Hi John", "Hi Sarah").
- **High Read Rates**: WhatsApp messages have a 98% open rate compared to email's 20%.
- **Targeted Sending**: Send only to specific groups (e.g., "VIPs" or "New Users") using tags.

## Option Buttons (UI Guide)
- **Create Campaign Button**: Starts the wizard to build a new broadcast.
- **Template Selector**: Dropdown to choose a Meta-approved message template.
- **Audience Filter**:
  - **All Contacts**: Sends to everyone.
  - **Filter by Tag**: Sends only to contacts with a specific tag.
- **Scheduler**:
  - **Send Now**: Launches immediately.
  - **Schedule for Later**: DATE/TIME picker to set future delivery.
- **Status Dashboard**:
  - **Sent**: Messages pushed to the network.
  - **Delivered**: Messages reached the user's phone.
  - **Read**: Blue ticks (user opened it).

## Use Cases
1.  **Product Launch**: "Our new Summer Collection is here! Check it out."
2.  **Event Reminder**: "Webinar starts in 1 hour. Join here: [Link]."
3.  **Payment Reminder**: "Your subscription expires tomorrow. Renew now."

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Broadcast** | Marketing Manager | New Promotion Launch | 1. Manager selects "Summer Sale" template.<br>2. Selects "VIP Customers" tag.<br>3. Schedule for Friday 10 AM.<br>4. System sends individual messages to 500 people. | High engagement; Increased traffic to store/website. |

## How to Use
1.  **Go to Broadcasting**: Click **Broadcasting** > **Create Campaign**.
2.  **Name Your Campaign**: Give it an internal name (e.g., "Feb Newsletter").
3.  **Choose Components**:
    - **Template**: Select the message you want to send.
    - **Image/Header**: Upload media if your template requires it.
4.  **Select Audience**: Choose the specific **Tag** of users to receive this, or select **All Customers**.
5.  **Launch**: Click **Send Now** (or use the scheduler).
6.  **Track**: Go to the **Campaigns List** to see how many people read your message.
