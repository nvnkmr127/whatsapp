# Compliance Overview

## What is it?
The **Compliance Overview** dashboard monitors your adherence to data privacy laws (like GDPR) and WhatsApp's Consent Policy. It gives you a quick health check of your opt-in rates.

## Why is it useful?
- **Risk Management**: Ensure you are not messaging people illegally, which can get your number banned.
- **Opt-In Rate**: Track what percentage of users say "Yes" vs. "No" to receiving messages.

## Option Buttons (UI Guide)
- **Total Consent Requests**: Total number of users you asked for permission.
- **Granted (Opt-In)**: Number of users who said Yes.
- **Revoked (Opt-Out)**: Number of users who said Stop.
- **Consent Rate**: Percentage of successful opt-ins.

## Use Cases
1.  **Audit**: Your legal team asks for a report on how many active consents you have. You show them this dashboard.
2.  **Health Check**: You see your "Revoked" count spiking. You realize your last marketing campaign was too aggressive, so you pause it.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Stats Calculation** | System | Page Load | 1. Counts `ConsentLog` where action is `OPT_IN`.<br>2. Counts `OPT_OUT`.<br>3. Computes ratio.<br>4. Displays cards. | Instant visibility into compliance health. |

## How to Use
1.  **Navigate**: Go to **Compliance** > **Overview**.
2.  **Review**: Check the top cards for key metrics.
