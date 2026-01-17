# Automations List (Bot Manager)

## What is it?
The **Automations List** is the command center for your chatbots. It shows every automated flow you have built, allowing you to turn them on/off, edit them, or duplicate them.

## Why is it useful?
- **Control**: Quickly pause a bot if it's misbehaving (using the toggle).
- **Management**: See all your active logic flows in one view.
- **Analytics**: (Future) See how many times each bot has triggered.
- **Versioning**: Duplicate a bot to test a new version while keeping the old one safe.

## Option Buttons (UI Guide)
- **New Automation**: Opens the Visual Builder.
- **Toggle Switch**:
  - **On (Green)**: Bot matches keywords and replies.
  - **Off (Gray)**: Bot ignores triggers.
- **Actions Menu (Three Dots)**:
  - **Edit**: Open the builder.
  - **Duplicate**: Clone the bot.
  - **Export**: Download the JSON file of the flow.
  - **Delete**: Remove the bot.

## Use Cases
1.  **Seasonal Promo**: You have a "Christmas Bot". Upon January 1st, you come here and toggle it **OFF** so it stops replying to "Xmas" keywords.
2.  **Template Installation**: You downloaded a flow from another account. You use **Import** (if available) or check your exported backups.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Activation** | Manager | Toggles Switch | 1. Updates database status to `is_active = true`.<br>2. Incoming message listener loads this bot into memory.<br>3. Bot starts responding. | Automation is live instantly. |

## How to Use
1.  **Navigate**: Go to **Engagement** > **Bot Manager** > **Automations**.
2.  **Search**: Type a name to find a specific flow.
3.  **Toggle**: Click the switch to enable/disable.
4.  **Edit**: Click the pencil icon or the name to enter the **Bot Builder**.
