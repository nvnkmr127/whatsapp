# Campaign Report (Details)

## 1. What is this page?
The Campaign Report (Details) page is the analytics dashboard for a specific sent campaign. It displays delivery rates, open rates, individual recipient logs, and error traces, and allows managers to retry failed messages.

## 2. Why is this page useful?
Sending a broadcast is only the first step. Marketers need to verify that their messages are actually arriving and being read.
- **Why do users need it?** To measure the success and return on investment (ROI) of their broadcast campaigns.
- **What work does it make easier?** It surfaces delivery errors and lets users retry failed messages with a single click instead of setting up a new campaign.
- **What business process does it support?** Campaign Performance Analysis, Database Hygiene, and Support troubleshooting.
- **What happens without it?** Businesses would be blind to their message deliverability, unable to distinguish between valid phone numbers and dead numbers, and unable to resolve delivery errors.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Customer Support Agent | To check if a specific customer received the promotional broadcast when they complain about missing out. |
| Marketing Manager | To review delivery rates, read rates, look up failure errors, retry failed items, and retarget segments. |

## 4. What can users do here?
- **Review Summary Metrics:**
  - Total Contacts Targeted.
  - Total Messages Sent (Dispatched).
  - Total Messages Delivered.
  - Total Messages Read.
  - Total Messages Failed.
  - Calculated **Delivery Rate** (Delivered / Sent) and **Read Rate** (Read / Delivered).
- **Inspect Recipient Delivery Logs:**
  - View a paginated table of every outbound message.
  - See recipient name and phone number.
  - Track individual status (Sent, Delivered, Read, Failed).
  - Review specific error logs for failed messages.
- **Trigger Remedial Actions:**
  - **Replay Message:** Re-queue and resend a failed message to a specific contact.
  - **Retarget:** Target a segment of this campaign (e.g., non-readers) to launch a follow-up campaign in the Wizard.

## 5. What is involved?
- **Campaign Model:** Holds the parent campaign metadata.
- **Message Model:** Used as the source of truth for delivery states (delivered, read, failed).
- **CampaignDetail Model:** Stores target recipient mappings, representing the target list size even if messages failed to generate.
- **SendCampaignMessageJob:** Dispatched when replaying a failed message to try sending it again.

## 6. How does it work?
1. The Marketing Manager opens a completed campaign's report.
2. The page queries the `Message` table where `campaign_id` matches. It calculates sums for read, delivered, and failed statuses.
3. The page displays the statistics in visual counter cards and list tables.
4. The user notices a contact has a "Failed" status with the error: "User is temporarily unavailable".
5. The user clicks "Replay".
6. The backend updates the Message's status back to `queued` and dispatches `SendCampaignMessageJob` to retry delivery.
7. The table updates, showing the message status change in real-time.

## 7. What happens behind the scenes?
- **Data Integrity / Scope Check:** On page load, the system verifies that the logged-in user belongs to the Team that owns the campaign. If not, it aborts with a 403 Forbidden error (Super Admins bypass this check).
- **Real-time Metrics Calculation:** Delivery rate calculations are performed dynamically:
  - Delivery Rate: `(delivered_count / sent_count) * 100`
  - Read Rate: `(read_count / delivered_count) * 100`
- **Replay Logic:** Replaying resets the message status fields (`status` = `queued`, `error_message` = `null`, `retry_count` = 0) and queues the send job.

## 8. Business Use Cases

**Use Case 1: Troubleshooting Campaign Failures**
- **Situation:** A manager notices that out of 500 messages, 50 failed. They want to know why.
- **How the feature is used:** They open the Campaign Report page and scroll through the logs. They see the error column says "Capability mismatch" for those 50 contacts, meaning those phone numbers do not have active WhatsApp accounts.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** The manager clean up their database by removing those phone numbers, saving future messaging credits.

**Use Case 2: Manual Re-sending of Critical Announcements**
- **Situation:** A school sends a critical closure alert to 200 parents. One parent messages support saying they never got it.
- **How the feature is used:** The support agent opens the campaign report, searches for the parent's number, sees the status was "Failed" due to a temporary carrier drop, and clicks "Replay".
- **Customer experience:** The parent receives the emergency notification.
- **Business outcome:** Improved communication reliability.

**Use Case 3: Performance Reports for Clients**
- **Situation:** An agency manager needs to report campaign performance to their client.
- **How the feature is used:** The manager opens this page to copy the Delivery Rate (99%) and Read Rate (85%) to paste into their client report.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** High trust and transparent agency reporting.

## 9. Industry Use Cases
- **Retail:** Checking which items had errors to prune fake phone numbers collected in-store.
- **Logistics:** Auditing if delivery alerts were read before customers claim they weren't notified.
- **SaaS:** Measuring the read rate on trial onboarding broadcasts to gauge customer interest.

## 10. Real Customer Example
A restaurant sends a menu update broadcast to 300 customers. The owner opens the report page the next day. The stats show 290 delivered (96%) and 260 read (89%). They notice 10 failed. Under the error logs, 8 say "Sender rate limit reached". The owner clicks "Replay" for those 8 contacts, and they are safely sent out. They click "Retarget" for the remaining 30 customers who didn't read the message, sending them a follow-up discount code.

## 11. Customer Journey
Marketer selects campaign &rarr; Reviews Delivery and Read stats &rarr; Identifies failures &rarr; Replays failed items or prunes numbers &rarr; Resolves campaign pipeline.

## 12. Inputs
- Campaign ID parameter.
- Replay message trigger.
- Retarget criteria selection.

## 13. Outputs
- Re-queued message delivery jobs.
- Plucked retargeting lists.

## 14. Dependencies
- **Campaign Model:** Core database object.
- **Message Model:** Status tracking database object.
- **CampaignDetail Model:** Mapped database object.
- **SendCampaignMessageJob:** Background execution queue.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission on plans including `campaigns`.
- **Who can view information:** Marketers/Admins.
- **Who can edit:** Marketers/Admins.
- **Who cannot access it:** Users lacking campaign permissions.

## 16. Important Rules
- You can only replay messages that have failed. Replaying successfully delivered or read messages is blocked.

## 17. Common Problems
- **Problem:** Clicking "Replay" doesn't change the status.
  - **Possible reason:** The background queue worker is down, or the message is encountering the exact same API error on retry.
  - **What the user should do:** Refresh the page to check for status updates. If it still says failed, check the new error log message to see if the issue is persistent (e.g. invalid phone number).
- **Problem:** Access Denied (403 Error).
  - **Possible reason:** The user is logged into the wrong team workspace and does not own the campaign.
  - **What the user should do:** Use the team switcher in the top navigation to select the correct team workspace.

## 18. Simple Explanation for Sales
The Campaign Report page shows you the results of your broadcasts. It tells you exactly how many people received your message, how many read it, and lists any errors so you can retry sending to them.

## 19. Simple Explanation for Marketing
This is your post-campaign dashboard. It provides delivery rates and read rates, showing you individual recipient logs. If a message fails due to a network glitch, you can retry sending it directly from the list.

## 20. Simple Explanation for Support
If a customer claims they didn't receive a promotion, look up the campaign on this page. You can search for their phone number to see if it was delivered or failed, and manually re-send it to them if needed.

## 21. Related Features
- [Campaigns List](./campaign-list.md)
- [Campaign Creator](./campaign-wizard.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/campaigns/{id}`
- **Implementation:** `App\Livewire\Campaigns\Show`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Campaigns/Show.php`
  - `resources/views/livewire/campaigns/show.blade.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
