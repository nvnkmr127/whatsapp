# Opt-In Manager

## What is it?
The **Opt-In Manager** allows you to automate how customers subscribe (opt-in) or unsubscribe (opt-out) from your messages using specific keywords. It ensures you respect user choices automatically.

## Why is it useful?
- **Automation**: You don't need a human to read every "Stop" message and manually block the user.
- **Compliance**: Keeps you legally safe by strictly honoring "Stop" requests instantly.
- **Growth**: Automatically welcomes users who type "Join" or "Start".

## Option Buttons (UI Guide)
- **Opt-In Keywords**: List of words that trigger a subscription (e.g., `START`, `JOIN`, `SUBSCRIBE`).
  - **Add Keyword**: Type a word and hit Enter.
- **Opt-Out Keywords**: List of words that trigger an unsubscription (e.g., `STOP`, `UNSUBSCRIBE`, `CANCEL`).
- **Auto-Response Message**:
  - **Opt-In text**: The message sent when someone joins (e.g., "Welcome! You are now subscribed.").
  - **Opt-Out text**: The message sent when someone leaves (e.g., "You have been unsubscribed.").
- **Enable/Disable Toggles**: Switch these auto-responses on or off.

## Use Cases
1.  **Marketing Campaign**: You put a billboard up saying "Text JOIN to 555-0199". You add `JOIN` as an Opt-In keyword here.
2.  **Safety**: A user gets annoyed and types "STOP". The system detects it, sends the "Goodbye" message, and bans them from future marketing.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Auto-Stop** | Customer | Types "STOP" | 1. System matches keyword "STOP".<br>2. Updates contact status to "Opt-out".<br>3. Sends configured "Opt-Out Message". | Instant compliance; User trust maintained. |

## How to Use
1.  **Navigate**: Go to **Core** > **WhatsApp API** > **Opt-In Manager**.
2.  **Add Keywords**: Under "Opt-In Keywords", type `START` and click **Add**. Under "Opt-Out", type `STOP` and `QUIT`.
3.  **Set Messages**: Write a friendly greeting in the "Opt-In Message" box.
4.  **Save**: Click **Save Changes** at the bottom.
