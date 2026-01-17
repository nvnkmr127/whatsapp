# Developer Tools

## What is it?
**Developer Tools** enable your technical team to connect this platform with other external software. Using "APIs" (software bridges) and "Webhooks" (instant notifications), you can make different systems talk to each other automatically.

## Why is it useful?
- **Automation**: Trigger actions in other apps. For example, when a sale happens on WhatsApp, automatically update your Google Sheet inventory.
- **Custom Integrations**: Connect to your existing CRM (Salesforce, HubSpot) or bespoke backend.
- **Real-time Data**: Receive data instantly on your own server whenever a message arrives.
- **Secure Access**: Generate unique "Keys" so your scripts can securely access the platform without a password.

## Option Buttons (UI Guide)
- **API Tokens**:
  - **Generate New Token**: Create a new secret key.
  - **Revoke**: Cancel an old key if it's compromised.
- **Webhooks**:
  - **Add Webhook**: Enter a URL (e.g., `https://mysite.com/hook`) and select events (e.g., "Message Received").
  - **Test**: Send a dummy event to check if the connection works.
- **Documentation**: Link to the technical API reference manual.

## Use Cases
1.  **Order Sync**: A user places an order in WhatsApp -> Webhook sends order details to your warehouse system -> Warehouse starts packing.
2.  **CRM Update**: A new lead chats in -> API automatically adds them to your Salesforce database.
3.  **External Trigger**: Your website detects an abandoned cart -> Website calls our API -> WhatsApp sends a "You forgot this item availability" message to the user.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Webhook** | System (Link) | New Inbound Message | 1. Customer says "Hello".<br>2. Platform receives message.<br>3. Platform sends JSON data to your configured URL.<br>4. Your external script logs the message. | Seamless ecosystem connectivity; Real-time data sync. |

## How to Use
1.  **Access Portal**: Go to **Developer** in the sidebar.
2.  **Get Token**: Click **API Tokens** > **Create Token**. Copy this key (keep it secret!).
3.  **Setup Webhook**:
    - Click **Webhooks**.
    - Enter the URL of your external server.
    - Check the boxes for events you want to listen to (e.g., `message.received`, `order.created`).
    - Click **Save**.
