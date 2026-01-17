# Flow Builder

## What is it?
**Flow Builder** is the management interface for **WhatsApp Flows**. WhatsApp Flows are native, in-app forms that allow you to collect structured data (like appointment bookings, signups, or surveys) directly within the chat window.

## Why is it useful?
- **Better UX**: Replaces long back-and-forth questions with a single, clean form.
- **Conversion**: Users are more likely to complete a native form than click a link to an external website.
- **Sync**: Form submissions can be saved to your database or sent to an external API.

## Option Buttons (UI Guide)
- **Create Flow**: Launches the setup wizard.
- **Sync Flows**: Pulls existing flows from your Meta Business account.
- **Preview**: Test the form experience.
- **Delete**: Remove a flow.

## Use Cases
1.  **Appointment Booking**: You create a flow with a date picker and time slot selector. Customers book a meeting without leaving WhatsApp.
2.  **Lead Qualification**: A form asks "Name", "Budget", and "Timeline". You get a structured lead instantly.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Form Open** | User | Clicks Button | 1. WhatsApp opens a native popup.<br>2. Flow loads JSON layout.<br>3. User fills data & clicks "Submit".<br>4. Data sent to your system. | High-quality, structured data collection. |

## How to Use
1.  **Navigate**: Go to **Intelligence** > **Smart Flows** > **Flow Builder**.
2.  **Create**: Click **Create Flow**.
3.  **Name It**: e.g., "Survey Form".
4.  **Edit**: You will be redirected to the Visual Canvas (Builder) to design the screens.
