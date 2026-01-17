# Compliance & Security

## What is it?
The **Compliance Module** is your safety net. It strictly manages "Consent" (who has agreed to receive messages) to ensure you don't violate WhatsApp's policies or local privacy laws (like GDPR). It also keeps a secure audit log of all system activities.

## Why is it useful?
- **Avoid Bans**: WhatsApp bans numbers that spam people. This system prevents you from messaging anyone who hasn't opted in.
- **Legal Protection**: Maintains a permanent proof of when and how a user gave consent.
- **Data Security**: Tracks every action your employees take (e.g., "Agent Smith deleted a contact"), so you have full accountability.
- **Automatic filtering**: If a user says "STOP", the system blocks them instantly.

## Option Buttons (UI Guide)
- **Consent Registry**: A table listing every opt-in.
  - **Source**: How they opted in (e.g., "Website Form", "In-Chat").
  - **Status**: "Active" or "Revoked".
- **Activity Log**:
  - **Actor**: Who did it? (e.g., "Admin").
  - **Action**: What did they do? (e.g., "Updated Settings").
  - **IP Address**: Where were they?
- **Opt-In Settings**: Configure standard messages for opting in/out.

## Use Cases
1.  **User unsubscribes**: A user replies "STOP". The system auto-tags them "Opt-Out" and blocks any future campaign from sending to them.
2.  **Security Audit**: You notice a setting was changed. You check the **Activity Log** to see which Admin changed it and when.
3.  **Dispute Resolution**: A customer claims you spammed them. You check the **Consent Registry** to show the exact date and time they clicked "I Agree".

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Opt-Out** | Customer | User sends "Stop" | 1. System detects keyword.<br>2. Updates user status to "Unsubscribed".<br>3. Adds to blocklist.<br>4. Sends "You have been unsubscribed" confirmation. | Compliance with law; Protection of business reputation. |

## How to Use
1.  **View Logs**: Go to **Compliance** > **Activity Logs** to see what your team has been doing.
2.  **Check Consent**: Go to **Compliance** > **Registry** to search for a specific user's consent history.
3.  **Manage Settings**: Go to **Compliance** > **Settings** to define your "Opt-in" and "Opt-out" keywords (e.g., START, STOP, SUBSCRIBE).
