# Billing & Usage

## What is it?
The **Billing Dashboard** manages your subscription plan and "Wallet". Since WhatsApp charges per conversation, you need a prepaid wallet balance to send messages.

## Why is it useful?
- **Transparency**: See exactly where your money is going (e.g., "Marketing Messages", "Service Fees").
- **Control**: Top up your wallet manually to ensure your bots never stop working.
- **Invoices**: View a history of every credit addition.

## Option Buttons (UI Guide)
- **Current Plan**: Shows if you are on "Basic", "Pro", or "Enterprise".
- **Wallet Balance**: Your available credits (e.g., `$50.00`).
- **Top Up Wallet**: Button to add funds (currently simulates a payment).
- **Usage Gauge**: A visual circle showing how many messages you've sent this month vs. your plan limit.
- **Transactions Table**: List of all deposits and deductions.

## Use Cases
1.  **Running Low**: You get an alert that your balance is under $10. You login, click **Top Up**, add $50, and your campaigns continue smoothly.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Top Up** | Admin | Click "Add Funds" | 1. Admin enters amount.<br>2. System processes payment (simulated).<br>3. Adds credit to `TeamWallet`.<br>4. Creates `TeamTransaction` record. | Uninterrupted service availability. |

## How to Use
1.  **Navigate**: Go to **Settings** > **Billing**.
2.  **Check**: Ensure your **Usage** is within limits.
3.  **Refill**: If **Wallet Balance** is low, click **Top Up Wallet**.
