# Inbox Settings

## What is it?
**Inbox Settings** give you control over how the Shared Team Inbox behaves. You can configure auto-assignment rules, working hours, and "Away" messages.

## Why is it useful?
- **Efficiency**: Automatically assign new chats to the right team instead of a general pool.
- **Expectation Management**: Auto-reply with "We are closed" if a customer messages at midnight.
- **Organization**: Define tags or reasons that agents must select when closing a chat.

## Option Buttons (UI Guide)
- **Auto-Assignment**:
  - **Round Robin**: Distribute chats equally among online agents (Agent A -> Agent B -> Agent C).
  - **Manual**: Chats stay in "Unassigned" until picked up.
- **Operating Hours**:
  - **Time Range**: Set specific hours (e.g., 9 AM - 5 PM) for each day of the week.
  - **Timezone**: Define your business timezone.
- **Away Message**:
  - **Message Text**: "Thanks for contacting us. We are currently closed."
  - **Enable**: Toggle this on/off.

## Use Cases
1.  **After Hours**: It's 8 PM. A customer messages. The system checks **Operating Hours**, sees you are closed, and sends the **Away Message**.
2.  **Busy Team**: You have 5 agents. You turn on **Round Robin** so no single agent gets overwhelmed with all the new chats; they are shared equally.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Auto-Reply** | System | Inbound Message | 1. Check time vs Operating Hours.<br>2. If outside hours, send "Away Message".<br>3. Keep chat open for morning team. | Customer knows when to expect a reply; Professional image. |

## How to Use
1.  **Navigate**: Go to **Core** > **WhatsApp API** > **Inbox Settings**.
2.  **Set Hours**: Click the checkboxes for the days you are open and use the sliders to set the time (e.g., 09:00 to 17:00).
3.  **Write Away Message**: Enter the text you want users to receive when you are closed.
4.  **Assignment**: Choose "Round Robin" if you want the system to distribute work automatically.
5.  **Save**: Click **Update Settings**.
