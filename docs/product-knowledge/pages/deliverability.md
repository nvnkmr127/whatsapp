# Deliverability Center

## 1. What is this page?
The Deliverability Center is a health monitoring dashboard for a business's WhatsApp phone number. It tracks Meta's quality rating over time, forecasts future health trends, and helps identify which message templates might be causing deliverability issues.

## 2. Why is this page useful?
Meta strictly monitors the quality of messages sent over WhatsApp. If customers block or report messages, Meta downgrades the number's quality rating, which can lead to reduced sending limits or account suspension.
- **Why do users need it?** To proactively monitor their WhatsApp number's health instead of waiting to be unexpectedly blocked by Meta.
- **What work does it make easier?** It automatically correlates rating drops with the specific message templates that were being sent right before the drop, making it easier to find and fix bad campaigns.
- **What business process does it support?** Compliance, campaign optimization, and maintaining uninterrupted customer communication.
- **What happens without it?** A business could blindly send spammy templates, get their WhatsApp number blocked, and have their entire communication channel shut down without warning or understanding why.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To ensure the business's main communication channel remains active and to acknowledge critical system alerts. |
| Marketing Manager | To check if recent broadcast campaigns negatively impacted the number's health and to identify problematic templates. |
| Operations/Support | To monitor daily messaging limits and current usage percentages to ensure they don't hit the cap during critical periods. |

## 4. What can users do here?
- View the current Quality Rating (Green, Yellow, Red).
- View the current Health Score and its historical trend.
- View the current Messaging Tier (e.g., 1K, 10K daily limit) and the daily usage percentage.
- Change the historical data view range (7, 30, or 90 days).
- View a forecast predicting when the number might hit a "critical" state if the current trend continues.
- View an "Attribution" list showing which templates were being sent right before the most recent rating drop.
- View a history timeline of all rating changes (upgrades and downgrades).
- Review and acknowledge open deliverability alerts.

## 5. What is involved?
- **Health Snapshots:** Periodic records (every 30 minutes) of the number's health.
- **Rating History:** Logs of exactly when Meta changed the quality rating.
- **Health Alerts:** System-generated warnings about deliverability risks.
- **Template Attribution:** Logic to correlate sent messages with rating drops.
- **Health Forecaster:** A statistical engine that predicts future health scores based on recent trends.

## 6. How does it work?
1. The system's background scheduler records the WhatsApp number's health from Meta every 30 minutes.
2. The user opens the Deliverability page.
3. The page loads the latest health snapshot to display current metrics (Rating, Score, Tier, Usage).
4. The page charts the daily average health score over the selected time range.
5. The `HealthForecaster` calculates a trend line based on the historical data and projects the future trajectory.
6. If a rating drop occurred recently, the `TemplateAttributionService` queries which templates were sent in the hours leading up to the drop and lists them as "suspects" ranked by volume.
7. The user can review and acknowledge any pending alerts from the queue.

## 7. What happens behind the scenes?
- **Decoupled Monitoring:** Instead of hitting Meta's API live every time the page loads, the page reads from a local history (`WhatsAppHealthSnapshot`) recorded by a background job (`whatsapp:calculate-health-scores`). This allows for fast loading and historical charting.
- **Forecasting (Linear Regression):** The `HealthForecaster` service uses the historical data points to calculate a slope (declining or improving), an R-squared value for confidence, and estimates the exact number of days until the score hits a critical threshold of 50.
- **Template Attribution:** When a drop occurs, the system looks back a specific number of hours (`TemplateAttributionService::WINDOW_HOURS`) and calculates the share of volume each template had. It explicitly warns users that this is *correlation, not proof*, as Meta does not disclose which exact message caused a block.
- **Team Scoping:** All queries manually filter by the user's `team_id` to ensure absolute tenant isolation.

## 8. Business Use Cases

**Use Case 1: Proactive Campaign Adjustment**
- **Situation:** A marketing team launches a new promotional broadcast.
- **How the feature is used:** The manager checks the Deliverability Center the next day and sees the forecast shifted to "Declining" and a new alert warns of negative feedback.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** The manager pauses the campaign before it causes a full rating drop to "Yellow" or "Red", preserving the number's sending limits.

**Use Case 2: Identifying the Culprit of a Rating Drop**
- **Situation:** The business receives a notification that their WhatsApp number quality dropped to "Yellow".
- **How the feature is used:** The admin opens the page and looks at the "Sending before your last drop" section. They see that a specific aggressive sales template accounted for 80% of the volume before the drop.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** The admin deletes or modifies the offending template to prevent further damage to the account's reputation.

**Use Case 3: Monitoring Tier Limits**
- **Situation:** A business is growing rapidly and sending more notifications every day.
- **How the feature is used:** Operations regularly checks the "Daily usage" gauge. They notice they are consistently hitting 85% of their daily limit.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Operations knows they need to organically increase their quality rating to be automatically upgraded by Meta to the next messaging tier (e.g., from 1K to 10K/day) before they hit a hard block.

## 9. Industry Use Cases
- **Marketing Agencies:** Agencies use this page to prove to clients that their campaign strategies are maintaining a healthy WhatsApp channel, or to quickly identify if a client-requested message is causing spam complaints.
- **Ecommerce:** High-volume stores use the forecasting to ensure their transactional order updates won't be suddenly throttled during a major sale event like Black Friday due to previous marketing missteps.
- **Financial Services:** Banks must guarantee delivery of OTPs and alerts. They monitor this dashboard closely to ensure their number is always in the "Green".

## 10. Real Customer Example
A retail business sends a Friday afternoon broadcast promoting a weekend sale, but they accidentally send it to their entire database instead of an engaged segment. Many customers, finding the message irrelevant, tap "Block & Report" on WhatsApp. On Monday, the business owner logs in and sees their Quality Rating has dropped to "Red" (Critical). They check the "Sending before your last drop" section, identify the exact Friday promotional template as the top suspect, immediately pause any remaining automations using that template, and acknowledge the system alerts. They then monitor the "Health score trend" over the next two weeks as the score slowly recovers.

## 11. Customer Journey
Business sends messages &rarr; Customers interact (or block/report) &rarr; Meta adjusts rating &rarr; Background job records snapshot &rarr; Admin views Deliverability Center &rarr; Admin identifies problematic templates &rarr; Admin adjusts strategy to recover rating.

## 12. Inputs
- Selected time range (7 days, 30 days, 90 days).
- Acknowledgement of specific alerts.

## 13. Outputs
- Visual charts and metric gauges.
- Forecast projections.
- Template attribution lists.
- Dismissed alert status in the database.

## 14. Dependencies
- **Background Scheduler:** Required to populate the `WhatsAppHealthSnapshot` table every 30 minutes.
- **QualityRatingHistory:** Required to show the timeline of rating changes.
- **WhatsAppHealthAlert:** Required to display actionable system warnings.
- **TemplateAttributionService & HealthForecaster:** Core backend services required for insights.
- **WhatsApp Configuration:** The page will prompt the user to check their setup if no health history exists.

## 15. Permissions
- **Who can access this page:** Users with dashboard or health monitoring access (typically Admins and Managers).
- **Who can view information:** Scoped to the authenticated user's `team_id`.
- **Who can edit/acknowledge alerts:** Users with access to the page can acknowledge alerts for their team.

## 16. Important Rules
- The forecast requires a minimum number of days of history (`HealthForecaster::MIN_DAYS`) before it will display a projection, preferring to show nothing over a wildly inaccurate guess.
- Template attribution explicitly ranks by volume sent during the window. It is documented on the UI as *correlation, not proof*.
- The `is_acknowledged` status clears an alert from the active queue but does not delete the alert history.

## 17. Common Problems
- **Problem:** Page shows "No health history yet".
  - **Possible reason:** The WhatsApp number was just connected, or the background scheduler (`whatsapp:calculate-health-scores`) is failing to run.
  - **What the user should do:** Wait an hour for the first snapshot. If it still doesn't appear, check the WhatsApp Configuration page to ensure the connection is active.
- **Problem:** Forecast section says "Forecast unavailable".
  - **Possible reason:** There is not enough historical data (e.g., less than 7 days) to calculate a statistically significant trend.
  - **What the user should do:** Wait for the system to collect more daily snapshots over the coming days.

## 18. Simple Explanation for Sales
The Deliverability Center protects your most valuable asset: your WhatsApp phone number. It constantly monitors how Meta is rating your messages, warns you if you're on a downward trend, and explicitly pinpoints which specific campaigns might be causing customers to report you as spam.

## 19. Simple Explanation for Marketing
This page is your safety net for campaigns. It shows you exactly how your broadcasts affect your overall WhatsApp reputation and helps you identify which specific templates are triggering spam complaints, allowing you to iterate on your copy and avoid being blocked by Meta.

## 20. Simple Explanation for Support
If a customer complains that they can't send messages anymore, check their Deliverability Center. It will immediately show if Meta has restricted their daily limits or downgraded their rating due to spam complaints, and it will list the exact templates that caused the issue so you can help them fix their strategy.

## 21. Related Features
- [WhatsApp Configuration](./whatsapp-configuration.md)
- [Campaigns](./campaigns.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/deliverability`
- **Implementation:** `App\Livewire\Health\DeliverabilityCenter`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Health/DeliverabilityCenter.php`
  - `resources/views/livewire/health/deliverability-center.blade.php`
  - `app/Services/Health/HealthForecaster.php`
  - `app/Services/Health/TemplateAttributionService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
