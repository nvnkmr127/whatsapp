# System Settings

## What is it?
**System Settings** allow you to brand the platform to look like your own software and configure regional behaviors.

## Why is it useful?
- **White Labeling**: Change the team name, logo, and primary color so the dashboard matches your brand identity.
- **Localization**: Set your country (e.g., India, UAE) to automatically apply local compliance rules and currency formats.
- **Operational Control**: Set timezones and date formats for accurate reporting.

## Option Buttons (UI Guide)
- **Team Name**: Rename your workspace.
- **Logo**: Upload a `.png` or `.jpg` to replace the default app logo.
- **Smart Country**: Select your operating region (e.g., `India`, `USA`).
  - *Note*: This auto-updates the `Policy Info` box with local regulations (e.g., DLT registration for India).
- **Timezone**: Set to your local time so charts show accurate data.
- **Primary Color**: Pick a hex code (e.g., `#FF5733`) for buttons and accents.

## Use Cases
1.  **Agency Mode**: You are a marketing agency managing a client "Pizza Hut". You upload the Pizza Hut logo and change the primary color to Red. The client feels right at home.
2.  **Global Expansion**: You expand to Dubai. You change the country to "United Arab Emirates". The system warns you about strictly verifying your Meta Business Manager.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Rebranding** | Admin | Save Details | 1. Updates `Team` record.<br>2. Updates global `Settings` table.<br>3. CSS variables refresh.<br>4. Dashboard instantly recolors. | Professional, branded experience. |

## How to Use
1.  **Navigate**: Go to **Settings** > **System**.
2.  **Brand**: Upload your Logo.
3.  **Localize**: Select your Country and Timezone.
4.  **Save**: Click **Update Settings**.
