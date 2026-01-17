# Consent Registry

## What is it?
The **Consent Registry** is the "White List" of your business. It contains the official record of every customer who has explicitly agreed to receive messages from you.

## Why is it useful?
- **Proof of Consent**: If WhatsApp investigates a spam report, this registry is your proof that the user asked to be messaged.
- **Status Check**: Quickly verify if a specific phone number is allowed to be messaged or not.

## Option Buttons (UI Guide)
- **Search**: Find a specific contact by name or phone.
- **Filter Status**: Show only "Granted" or "Revoked".
- **Export**: Download the list for legal auditing.

## Use Cases
1.  **Customer Dispute**: A user complains "Why did you message me?". You check the registry and see they Opted In on "Jan 12th via Web Form". You screenshot this as proof.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Registry Update** | System | User types "START" | 1. System logs `OPT_IN` action.<br>2. Updates Contact's `consent_status` to `active`.<br>3. Adds entry to Registry. | Legal safety and automated list cleaning. |

## How to Use
1.  **Navigate**: Go to **Compliance** > **Registry**.
2.  **Filter**: Select **Status: Granted** to see your active reachable audience.
