# Billing, Subscriptions & Wallet Credits

## 1. What is this page?
The Billing, Subscriptions & Wallet Credits page is the financial control dashboard of the platform. Located at `/billing`, it allows administrators to manage monthly subscription plans (basic, growth, enterprise), track credit wallet balances, top up funds, request trial extensions, audit resource usage metrics, and download invoice histories.

## 2. Why is this page useful?
Managing billing plans, payment recharges, and usage limits is essential for keeping campaign features active and avoiding service disruptions.
- **Why do users need it?** To monitor monthly usage limits (messages, agent seats, custom fields), fund their wallets to cover WhatsApp's per-conversation API costs, and evaluate the impact of changing plans.
- **What work does it make easier?** It runs automated **Downgrade Impact Analysis** checks to flag resource violations, provides top-up inputs, and tracks invoice PDFs.
- **What business process does it support?** Subscription Renewal, Wallet Management, and Resource Limit Allocation.
- **What happens without it?** Teams cannot add messaging funds or upgrade their plans, causing automated messages and agent workspaces to shut down when limits are reached or wallets run out of credits.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To upgrade subscription plans, top up credit balances, request trial extensions, and download invoice PDFs. |

## 4. What can users do here?
- **Monitor Active Subscriptions:**
  - View current plan details, monthly costs, and renewal dates.
  - Track trial expiration countdowns and launch offers.
- **Add Credits (Top-Up Modal):**
  - View wallet balance.
  - Deposit credits into the team wallet using preset shortcuts ($10, $50, $100, $500) or custom amounts.
- **Request Trial Extensions (Modal):**
  - Request trial extensions (7, 14, or 30 days) by submitting a description of what you are testing.
- **Track Resource Usage Progress:**
  - View usage progress bars showing monthly limits for messages, agent seats, and lists.
  - Alert indicators change color: Green (safe), Yellow (above 80% usage), Red (above 90% usage).
- **Compare & Change Plans (Switch Plan Modal):**
  - View available plans and pricing.
  - Analyze plan changes before confirming upgrades or downgrades.
  - **Impact Reports:** Reviews features gained, features lost, and resource warnings (e.g. warning that downgrading will suspend agents if you exceed the new plan's seat limits).
- **Audit Invoices & Transactions:**
  - View invoice history lists and download PDF records.
  - Search transaction histories by description keywords or filter by type (deposit, usage_charge, refund, plan_fee).

## 5. What is involved?
- **Plan Model:** Stores monthly costs, message limits, and feature permissions.
- **TeamWallet & TeamTransaction Models:** Manages account balances, recharges, and deductions.
- **TeamInvoice Model:** Stores invoice documents.
- **TrialExtensionRequest Model:** Logs trial extension requests.
- **BillingService & SubscriptionService:** Handles plan changes, impact reviews, and credit deposits.

## 6. How does it work?
1. The Admin opens `/billing` to downgrade from "Enterprise" to "Growth".
2. They click "Switch Plan" on the Growth plan card.
3. The Change Plan Modal displays an **Impact Analysis**:
   - Gained: None (downgrading).
   - Lost: AI Shop Assistant, Cohort Analytics.
   - Resource Warnings: "Your team has 5 active agents. The Growth plan only allows 3. Toggling confirm will lock 2 agents after a 7-day grace period."
4. The Admin decides to proceed, clicks "Confirm Switch", and the system updates their subscription plan.
5. They scroll to the Wallet card, click "Add Credits", and add $100 to cover future messaging charges.

## 7. What happens behind the scenes?
- **Secure Transaction Logging:** Top-ups process transactions via payment gateways. Once success is confirmed, `BillingService::deposit` updates the `TeamWallet` balance and records a `TeamTransaction` audit trail.
- **Impact Evaluation Script:** When a user selects a new plan, `SubscriptionService::analyzeImpact` compares the target plan limits against the team's active resources (e.g., active user count, custom field limits). If active usage exceeds the target limits, a warning is added to the confirmation modal.
- **Grace Periods:** Downgrading to a plan with lower resource limits triggers a 7-day grace period. Users must manually delete or deactivate over-limit resources (like removing agents) before the grace period ends, or the system will automatically suspend them.

## 8. Business Use Cases

**Use Case 1: Scaling Message Limits During Peak Seasons**
- **Situation:** A retail brand expects message volumes to double during a holiday sale and needs to avoid reaching their plan's limit.
- **How the feature is used:** They open the plan catalog and upgrade their subscription. The message limit updates instantly.
- **Customer experience:** Messaging flows remain active without interruption during the sale.
- **Business outcome:** High-volume customer queries resolved without support downtime.

**Use Case 2: Securing Wallet Top-Ups for Broadcast Campaigns**
- **Situation:** A marketing manager wants to run a broadcast campaign to 10,000 customers but needs to ensure their wallet has enough credits.
- **How the feature is used:** They top up their wallet by $200 using the credits modal, confirming the payment.
- **Customer experience:** N/A (Internal prep).
- **Business outcome:** Campaigns run smoothly without balance-related pauses.

**Use Case 3: Extending Evaluation Trials**
- **Situation:** An enterprise IT lead needs 2 more weeks to finish testing the API before committing to a paid plan.
- **How the feature is used:** They open the Launch Banner on the dashboard, request a 14-day trial extension, and submit their reasons.
- **Customer experience:** N/A (Internal evaluation extension).
- **Business outcome:** Extended testing access without payment blockages.

## 9. Industry Use Cases
- **Retail:** Upgrading limits before seasonal sales.
- **Agencies:** Tracking invoice histories for client billing.
- **Logistics:** Monitoring message volume usage to budget transactional shipping alerts.

## 10. Real Customer Example
An e-commerce brand is on a free trial with 15 days remaining. They request a 14-day extension, which is saved in the portal. They use the Top-Up modal to add $50 to their wallet for conversation fees, and verify the deposit in their transaction history. When they decide to buy, they compare plans and upgrade to the Growth plan, reviewing the gained features list in the confirmation modal.

## 11. Customer Journey
Admin views current plan &rarr; Monitors monthly usage gauges &rarr; Tops up wallet credits &rarr; Compares subscription plans &rarr; Evaluates upgrade/downgrade impacts &rarr; Confirms changes &rarr; Downloads invoice PDFs.

## 12. Inputs
- Selected plan name.
- Credit top-up amounts.
- Extension request reasons and duration.
- Transaction search keywords and filters.

## 13. Outputs
- Saved `TeamWallet` balances.
- Logged `TeamTransaction` entries.
- Created `TrialExtensionRequest` records.
- Downloaded invoice PDFs.

## 14. Dependencies
- **Plan, TeamWallet, TeamTransaction Models:** DB storage.
- **BillingService & SubscriptionService:** Transaction handlers.
- **InvoiceController:** Download router.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permissions.
- **Who can view information:** Admins.
- **Who can edit/top-up:** Admins.
- **Who cannot access it:** Managers, Support Agents.

## 16. Important Rules
- You must top up at least $10.00 per transaction.
- Downgrades include a 7-day grace period to prune over-limit resources before suspension.

## 17. Common Problems
- **Problem:** "Upgrade blocked due to resource limits" notice.
  - **Possible reason:** N/A (Downgrades are warned, but upgrades are rarely blocked. However, if resource exceptions are active, conflict errors can appear).
  - **What the user should do:** Review the resource warning logs in the modal, adjust your active resources (e.g. delete unused agents), and try switching plans again.
- **Problem:** Secure deposit failed or key validation issues.
  - **Possible reason:** Payment gateway API failures.
  - **What the user should do:** Contact support or wait 5 minutes and try adding credits again.

## 18. Simple Explanation for Sales
The Billing page is where you manage your plan. You can see your active limits, compare subscription options, top up your conversation credit wallet, and download payment invoices.

## 19. Simple Explanation for Marketing
Monitor your monthly message limits. If you are close to your limit, ask your admin to upgrade your plan or top up your wallet credits here.

## 20. Simple Explanation for Support
If you see warnings that your account is approaching its message limits, notify your admin so they can upgrade plans or add credits on this page.

## 21. Related Features
- [System Settings](./system-settings.md)
- [Analytics & Billing Dashboard](./analytics-billing.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/billing`
- **Implementation:** `App\Livewire\Billing\BillingDashboard`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Billing/BillingDashboard.php`
  - `resources/views/livewire/billing/billing-dashboard.blade.php`
  - `app/Models/Plan.php`
  - `app/Models/TeamWallet.php`
  - `app/Models/TeamTransaction.php`
  - `app/Models/TeamInvoice.php`
  - `app/Models/TrialExtensionRequest.php`
  - `app/Services/BillingService.php`
  - `app/Services/SubscriptionService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
