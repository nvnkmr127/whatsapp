# WhatsApp Configurations

## What is it?
**WhatsApp Configuration** is the admin settings page where you connect your business phone number to the platform. It handles the technical handshake between this software and Meta (Facebook).

## Why is it useful?
- **Connectivity**: Without this, you cannot send or receive messages.
- **Verification**: Displays your "Green Tick" status or business verification level.
- **Payment Method**: Links the credit card used to pay Meta for conversation charges.

## Option Buttons (UI Guide)
- **Connect with Facebook**: The primary button to launch the "Embedded Signup" popup.
- **Refresh Token**: Updates your connection security key if it expires.
- **Phone Number ID**: Displays the unique ID assigned by Meta.
- **WABA ID**: Displays your WhatsApp Business Account ID.
- **Disconnect**: Unlinks the phone number (Warning: Stops all messaging).

## Use Cases
1.  **Initial Setup**: You just signed up. You go here to scan the QR code/login to Facebook and bring your number online.
2.  **Re-connection**: You changed your Facebook password, so the connection broke. You come here to "Refresh Token".
3.  **Audit**: You need your WABA ID to give to a developer integration. You copy it from this screen.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Onboarding** | Admin | Click "Connect" | 1. Admin clicks "Connect with Facebook".<br>2. Facebook popup opens.<br>3. Admin selects their business.<br>4. System auto-fills Tokens and IDs.<br>5. Status changes to "Connected". | Business is live on WhatsApp; Ready to chat. |

## How to Use
1.  **Navigate**: Go to **Core** > **WhatsApp API** > **Configurations**.
2.  **Connect**: Click the blue **Connect with Facebook** button.
3.  **Authorize**: Log in to your Facebook account in the popup window. Select the business "Meta Business Manager" you want to link.
4.  **Confirm**: Select the specific phone number to use.
5.  **Done**: The page will reload and show a green "Connected" badge.
