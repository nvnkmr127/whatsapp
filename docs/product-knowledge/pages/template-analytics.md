# Template Heatmap (Analytics)

## 1. What is this page?
The Template Heatmap page is an analytics tool that visualizes historical open and read rates of WhatsApp templates. It maps performance against sending hours and weekdays to identify optimal scheduling windows.

## 2. Why is this page useful?
Sending broadcasts at the wrong time results in messages getting buried in a user's notification list, lowering read rates.
- **Why do users need it?** To maximize campaign engagement by discovering when their target audience is most active and receptive to specific message templates.
- **What work does it make easier?** It automatically aggregates delivery and read timestamps into a visual 7x24 grid, removing the need for manual CSV reporting or SQL queries.
- **What business process does it support?** Outbound Marketing Optimization, Broadcast Scheduling, and Engagement Analytics.
- **What happens without it?** Marketers schedule broadcasts based on guesswork, leading to lower engagement rates and wasted messaging fees.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Marketing Manager | To analyze which hour and day yields the highest read rate for their marketing templates before scheduling a campaign. |
| Admin / Analyst | To track overall template performance trends and check if timing signals are statistically reliable. |

## 4. What can users do here?
- **Filter by Template:** Switch between approved WhatsApp templates to see individual heatmap charts.
- **View Summary Stats:**
  - **Baseline:** The overall average read rate for the selected template.
  - **Total Sends:** Total messages dispatched for this template in the last 90 days (sample size).
- **Inspect 7x24 Heatmap Grid:**
  - View weekdays (Monday - Sunday) mapped against 24-hour columns.
  - Colors represent intensity: Darker teal cells represent higher read rates; empty grid blocks represent no data.
- **Read Automated Timing Insights:**
  - **Best Slot Alert:** Displays the exact day and hour combination that yields the highest read rate, showing the baseline multiplier (e.g., "1.5x baseline") and confidence rating.
  - **Confidence warnings:** Flags if the guide is based on too few messages (low sample size), recommending how many more messages to send to achieve statistical reliability.

## 5. What is involved?
- **Message Model:** The core source of truth containing outbound message records, direction, status, timestamps (`sent_at`, `created_at`), and `read_at` flags.
- **WhatsappTemplate Model:** Used to resolve template names and IDs.
- **SQLite / MySQL Database Expressors:** Runs platform-agnostic date calculations (e.g. mapping `WEEKDAY()` and `HOUR()` functions across MySQL and SQLite drivers for tests).

## 6. How does it work?
1. The user opens the Template Heatmap page.
2. The backend queries outbound template messages sent over the last 90 days.
3. It groups the results by template ID, day-of-week, and hour-of-day.
4. The system calculates the read rate for each slot: `(read_count / sent_count) * 100`.
5. It identifies the slot with the highest read rate (meeting a minimum sample threshold) and labels it the "Best Slot".
6. The user selects a template (e.g., `abandoned_cart_reminder`). The heatmap renders, revealing that 2:00 PM on Tuesdays has a 92% read rate (compared to a 60% baseline).
7. The user schedules their next cart recovery campaign for Tuesday at 2:00 PM.

## 7. What happens behind the scenes?
- **Manual vs. Campaign Mapping:** The backend joins the `Message` table with the `Campaign` table. If a message was sent manually (not via a campaign), the template name is extracted from the message's `metadata` JSON column (`JSON_EXTRACT(messages.metadata, "$.template_name")`) to ensure all template communications are counted.
- **Statistical Confidence Engine:** The best slot calculator ignores noise by enforcing a minimum reliable sample size (at least 3 sends or 3% of total sends). It rates the best slot's confidence:
  - **High:** Slot has &ge; 25 sends or represents &ge; 12% of total sends.
  - **Medium:** Slot has &ge; 10 sends or represents &ge; 6% of total sends.
  - **Low:** Anything less, triggering a warning tip asking for a larger sample size.

## 8. Business Use Cases

**Use Case 1: Optimizing Newsletter Delivery**
- **Situation:** A weekly digest is sent to subscribers, but read rates are hovering around 40%.
- **How the feature is used:** The marketer opens the heatmap for `weekly_newsletter`. The chart shows a dark teal cluster on Thursday mornings at 10:00 AM where the read rate is 75%.
- **Customer experience:** Customers receive the newsletter at a time when they are actively browsing their phones.
- **Business outcome:** Open rates increase by 35% without changing the message copy.

**Use Case 2: Auditing Low-Sample Campaigns**
- **Situation:** A new template `customer_survey` has just been launched. The marketer wants to know if they can trust the timing guide.
- **How the feature is used:** The marketer checks this page. The sidebar warns: "Timing guidance for survey is based on only 15 sends. Add 85+ more sends for stronger confidence."
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Marketers avoid making premature scheduling decisions based on skewed, low-volume data.

**Use Case 3: Comparing Direct Campaigns vs. Retargeting**
- **Situation:** A team wants to see if follow-up templates have different active hours than initial offer templates.
- **How the feature is used:** They switch the template filter on this page to compare the heatmap of `welcome_offer` (best at Mon 9:00 AM) against `offer_reminder` (best at Fri 4:00 PM).
- **Customer experience:** Customers receive follow-ups during weekend leisure hours when they are more likely to buy.
- **Business outcome:** Improved conversion rates on retargeting blasts.

## 9. Industry Use Cases
- **Retail:** Timing discount codes to hit phones right during Friday lunchtime breaks.
- **Education:** Scheduling course reminder updates to arrive after school hours (4:00 PM - 6:00 PM).
- **SaaS:** Delivering onboarding tips at times when analytics show users are actively logging into the platform.

## 10. Real Customer Example
A fitness studio broadcasts a template called `class_schedule_update` to 2,000 members. After running it for a month, the manager checks the Template Heatmap. The stats show a clear peak: Sunday at 6:00 PM has an 88% read rate with "High Confidence" (200+ sends in that slot), whereas Monday at 9:00 AM has a 30% read rate. The manager updates their weekly scheduling schedule to blast updates on Sunday evenings, resulting in class bookings filling up faster.

## 11. Customer Journey
Marketer runs broadcasts &rarr; Messages gather read/open timestamps &rarr; Heatmap normalizes dates/hours &rarr; System flags optimal slot and data confidence &rarr; Marketer reschedules future campaigns.

## 12. Inputs
- Selected Template ID.
- Message status logs (read/sent timestamps).

## 13. Outputs
- Heatmap grid intensity array.
- Best Slot analytics text.
- Confidence Warning states.

## 14. Dependencies
- **Message & Campaign Models:** Data source.
- **WhatsappTemplate Model:** Data filter.
- **Analytics Dashboard:** Parent navigation tab.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permission on plans including `analytics`.
- **Who can view information:** Marketers/Admins.
- **Who cannot access it:** Support agents.

## 16. Important Rules
- Heatmap data is restricted to outbound template messages sent within the last 90 days.
- If no messages exist in the last 90 days, the page displays a "No data" empty state.

## 17. Common Problems
- **Problem:** Heatmap grid is empty or shows "No template send data yet" warning.
  - **Possible reason:** No template messages have been sent in the last 90 days, or they were sent as raw free-form text instead of template format.
  - **What the user should do:** Launch a campaign using an approved WhatsApp template. Once messages are sent and read receipts arrive, refresh the page to populate data.
- **Problem:** "Low data confidence" warning.
  - **Possible reason:** The selected template has not been sent at least 100 times, making timing guidelines statistically unreliable.
  - **What the user should do:** Keep using the template. The system will automatically remove the warning once the sample size grows.

## 18. Simple Explanation for Sales
The Template Heatmap analyzes when your customers read your messages. By mapping open times against hours and days of the week, it tells you the exact best time to schedule your templates so they get opened instead of ignored.

## 19. Simple Explanation for Marketing
Timing is everything. This analytics tool shows you a color-coded grid of when your templates perform best. It calculates baseline read rates and points out the day and hour that will give you the highest open rates.

## 20. Simple Explanation for Support
If you need to know why a marketing campaign was sent at a specific odd hour (like Sunday at 6:00 PM), it is likely because this heatmap showed that is when clients are most active on WhatsApp.

## 21. Related Features
- [Campaigns List](./campaign-list.md)
- [WhatsApp Templates](./templates.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/analytics/templates`
- **Implementation:** `App\Livewire\Analytics\TemplateHeatmap`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Analytics/TemplateHeatmap.php`
  - `resources/views/livewire/analytics/template-heatmap.blade.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
