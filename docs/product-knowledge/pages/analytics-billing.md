# Analytics & Billing Dashboard

## 1. What is this page?
The Analytics & Billing Dashboard is the financial and operational reporting hub of the platform. Located at `/analytics`, it allows administrators and managers to monitor messaging volumes, review wallet balances, audit billing transactions, evaluate WhatsApp delivery reports, and schedule recurring usage reports.

## 2. Why is this page useful?
WhatsApp campaigns and business conversations incur direct API fees from Meta.
- **Why do users need it?** To track messaging costs, monitor delivery success rates (read/failed percentages), manage wallet top-ups, and export transaction invoices.
- **What work does it make easier?** It consolidates technical webhook logs (message status updates) and billing transactions into simple graphs, grids, and CSV downloads.
- **What business process does it support?** Budget Management, Message Deliverability Auditing, and Campaign ROI Analysis.
- **What happens without it?** Businesses cannot monitor why messages are failing, check how much budget remains in their wallet, or track customer open rates, leading to campaign shutdowns when wallets run dry.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To monitor wallet balances, add messaging funds, export billing CSVs, audit API failures, and configure weekly email reports. |
| Marketing Manager | To analyze campaign open rates (delivery vs read metrics) and check message velocity trends. |

## 4. What can users do here?
- **Configure Time Filters:** Switch global analytics views between 7-day, 30-day, or 90-day windows.
- **Wallet & Billing Management:**
  - View current active Wallet Balance.
  - Audit a paginated log of all transactions (deposits, messaging deductions, and invoices).
  - Export transaction logs as a CSV file.
- **Review Core Usage Stats:**
  - Sent Messages volume (outbound).
  - Received Messages volume (inbound).
  - Lead Capture Conversions (scans vs form completions).
  - Tickets Resolved (customer support closed states).
- **Official Meta Insights Panel:**
  - View direct conversation costs and billing data retrieved via Meta APIs.
  - Inspect raw JSON response logs from Meta's billing servers.
- **Visualize Message Speed (Velocity Chart):**
  - Interactive line chart mapping sent (outbound) vs received (inbound) daily message counts.
- **Schedule Email Reports:** Toggle recurring email delivery of monthly usage reports.
- **Audit Message Delivery Report (Webhook Logs):**
  - Track every outbound message's delivery status timeline (Sent &rarr; Delivered &rarr; Read).
  - Check Meta Message IDs and copy them to clipboard.
  - Review detailed error logs for failed messages (e.g. invalid phone number, template mismatch).
  - Filter logs by status, contact name/phone, dates, or message ID searches.
  - **Export Webhook Report:** Download the filtered message logs to a CSV sheet.
  - **Extract List:** Download a CSV contact list containing only contacts from the filtered logs (useful for retargeting lists).

## 5. What is involved?
- **TeamWallet & TeamTransaction Models:** Manages account balances, recharges, and deductions.
- **Message Model:** The source table tracking messaging directions, states, and delivery times.
- **ScheduledReport Model:** Controls recurring email schedules.
- **WhatsAppService:** Queries Meta Graph APIs to retrieve official cost records.

## 6. How does it work?
1. The Marketer runs a marketing broadcast to 5,000 customers.
2. The system deducts conversation fees from the team's Wallet.
3. The marketer goes to `/analytics` to verify delivery.
4. They scroll to the Message Delivery Report. They see the delivery rate is 98% and the read rate is 65%.
5. They filter by "failed" status. The log updates to show 100 failed messages with error: "User phone number not active on WhatsApp".
6. The marketer clicks "Extract List" to download the failed contacts. They upload this list to their Audience Center to prune inactive numbers.
7. They check the Wallet card. It shows $50 remaining. They click "Add Funds" to prepare for next week's campaign.

## 7. What happens behind the scenes?
- **Brief Query Caching:** To prevent slow page loads from scanning millions of message rows on every filter change, key statistics (sent/received counts, funnel metrics) are cached for 60 seconds. Meta API billing queries are cached for 300 seconds to respect Meta rate limits.
- **Webhook Status Pipeline:** When Meta's delivery webhook triggers (e.g., customer reads a template), the server updates `read_at` or `delivered_at` on the `Message` model. The delivery report calculates rates dynamically: `(delivered + read) / attempted * 100`.
- **Database Driver Date Normalization:** Chart data aggregates date ranges. The code maps SQL date functions dynamically based on the database driver (using SQLite strftime for tests and MySQL WEEKDAY/HOUR for production) to ensure dashboard charts draw correctly in all environments.

## 8. Business Use Cases

**Use Case 1: Campaign Deliverability Audit**
- **Situation:** A marketer notices a broadcast has lower engagement than usual.
- **How the feature is used:** They open the Webhook Delivery Report on this page, filter by "Failed" status for today's date, and export the report. They analyze the error column and find Meta returned "Template not approved for this language".
- **Customer experience:** N/A (Internal use).
- **Business outcome:** The marketer fixes the campaign template translation, avoiding future broadcast failures.

**Use Case 2: Tracking Billing Deductions**
- **Situation:** An accountant wants to reconcile monthly credit card charges with WhatsApp usage invoices.
- **How the feature is used:** They go to the Billing History grid, filter by the prior month, and click "Download Billing" to export the transaction logs as a CSV.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Fast invoice reconciliation.

**Use Case 3: Retargeting Unread Broadcasts**
- **Situation:** A marketer wants to send a follow-up SMS or email only to customers who *did not* read the WhatsApp template message.
- **How the feature is used:** They filter the delivery report by status "delivered" (meaning it reached the phone but was not opened/read), click "Extract List", and export the contacts CSV.
- **Customer experience:** Customers get a backup SMS, avoiding spamming users who already read the WhatsApp message.
- **Business outcome:** Smart multi-channel retargeting.

## 9. Industry Use Cases
- **Retail:** Tracking click-throughs and wallet balances for promo campaigns.
- **Logistics:** Monitoring delivery reports to ensure tracking links reach customer phones.
- **Finance:** Auditing transaction invoices for internal accounting compliance.

## 10. Real Customer Example
A retail store sets up a weekly usage report schedule. Every Monday, they check `/analytics` to monitor message velocity. The chart shows outbound traffic spiked on Thursday during their flash sale, with a 90% read rate. They check the billing log and download the invoice showing $35 was deducted for the 3,000 marketing conversations. They review the remaining wallet balance ($150) and confirm they have enough credits for next week.

## 11. Customer Journey
Marketer opens dashboard &rarr; Filters dates &rarr; Inspects message velocity and Meta costs &rarr; Checks webhook logs for failures &rarr; Downloads billing invoices.

## 12. Inputs
- Selected date range filter.
- Webhook filter parameters (status, date bounds, contact queries, search terms).
- Wallet funds input.

## 13. Outputs
- Exported transaction CSV.
- Exported webhook report CSV.
- Exported filtered contact list CSV.
- Created `ScheduledReport` logs.

## 14. Dependencies
- **Message, TeamWallet, TeamTransaction Models:** Core DB tables.
- **ScheduledReport Model:** Email routines.
- **WhatsAppService:** Meta API connectivity.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permission on plans including `analytics`.
- **Who can view information:** Admins/Managers.
- **Who can edit/export:** Admins.
- **Who cannot access it:** Standard support agents.

## 16. Important Rules
- Webhook report exports are limited to outbound messages sent within the selected date window.
- The Wallet balance must remain above $0.00; if it drops below zero, the platform suspends outbound campaigns.

## 17. Common Problems
- **Problem:** Meta Insights shows empty values or "See JSON Details" notice.
  - **Possible reason:** Meta has not compiled billing logs for the selected daily interval yet, or the WABA is on a free tier.
  - **What the user should do:** Click "View Data Response" to inspect Meta's raw API payload for detailed error warnings.
- **Problem:** Webhook report download is blank.
  - **Possible reason:** The date filters or status filters are too restrictive (e.g. searching for failed messages on a day where all succeeded).
  - **What the user should do:** Clear the search filters, extend the date range to 90 days, and try downloading again.

## 18. Simple Explanation for Sales
The Analytics page is your billing dashboard. It shows your messaging stats (how many messages were sent and read), tracks your wallet balance, and lists all invoices and deductions so you can monitor your expenditures.

## 19. Simple Explanation for Marketing
Check your template open rates. Monitor how many messages are successfully read, check daily volume charts, and download lists of customers who didn't open your templates to target them in retargeting campaigns.

## 20. Simple Explanation for Support
If customers complain they are not receiving messages, check the Webhook Delivery Report on this page to find the specific message ID and check the error message details.

## 21. Related Features
- [Audience Center](./contacts.md)
- [Campaign Report](./campaign-report.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/analytics`
- **Implementation:** `App\Livewire\Analytics\AnalyticsDashboard`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Analytics/AnalyticsDashboard.php`
  - `resources/views/livewire/analytics/analytics-dashboard.blade.php`
  - `app/Models/Message.php`
  - `app/Models/TeamWallet.php`
  - `app/Models/TeamTransaction.php`
  - `app/Models/ScheduledReport.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
