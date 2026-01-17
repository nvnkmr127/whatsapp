# Inbound Sources

## What is it?
**Inbound Sources** allow you to receive data **FROM** other apps **INTO** WhatsApp. It is the opposite of Webhooks. It creates a special URL that other apps can send data to.

## Why is it useful?
- **Unified Logic**: Map data from Shopify, Stripe, or Typeform into a standard "Event" format inside our platform.
- **Trigger Automation**: Use this incoming data to start a specific Bot Flow (e.g., "When Stripe Payment Succeeds -> Send Thank You Bot").

## Option Buttons (UI Guide)
- **Source Name**: Internal label (e.g., "Shopify Order").
- **Platform**: Select preset (Shopify, Stripe) or Custom.
- **Webhook URL**: The unique link we generate for you to paste into the other app.
- **Capturing**: A "Listening Mode" to auto-detect the data structure when you send a test event.
- **Field Mapping**: Drag-and-drop tool to say "Shopify `customer.phone` = WhatsApp `phone`".

## Use Cases
1.  **Payment Comfirmation**:
    - **Trigger**: Customer pays on Stripe.
    - **Inbound Source**: Receives Stripe JSON.
    - **Action**: Maps `email` to Contact Email. Triggers "Payment Receipt" template on WhatsApp.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Data Ingestion** | System | External POST | 1. External App hits Inbound Source URL.<br>2. System validates Auth Key.<br>3. Maps JSON fields to internal variables.<br>4. Executes defined Action (Send Message). | Seamless 3rd-party integration. |

## How to Use
1.  **Create**: Go to **Developer** > **Inbound Sources** > **New Source**.
2.  **Select**: Choose "Stripe" (or Custom).
3.  **Copy**: Copy the generated **Webhook URL**.
4.  **Paste**: Go to your Stripe Dashboard and paste this URL in their Webhook settings.
5.  **Map**: Send a test payment. Our system captures it. You map the "Email" field to our "Email" field.
