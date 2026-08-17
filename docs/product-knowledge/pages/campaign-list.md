# Campaigns List (Message Studio)

## 1. What is this page?
The Campaigns page (Message Studio) is the central command center for reviewing, managing, and tracking bulk WhatsApp message broadcasts (campaigns) sent to target customer lists.

## 2. Why is this page useful?
WhatsApp marketing campaigns are a primary driver of customer engagement and revenue.
- **Why do users need it?** To monitor active broadcasts, review historical performance metrics, control sending queues (pause/resume), and build follow-up retargeting campaigns.
- **What work does it make easier?** It aggregates campaign analytics into a single dashboard and provides automated retargeting workflows.
- **What business process does it support?** Outbound Marketing, Lead Nurturing, and Customer Communications at scale.
- **What happens without it?** Businesses would have to send messages manually to contacts one by one, with no visibility into who read the message, who encountered delivery errors, or how to follow up efficiently.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To oversee message usage, monitor server load, and pause active broadcasts if needed. |
| Marketing Manager | To analyze open/read rates, clone successful campaigns, and launch retargeting sequences. |

## 4. What can users do here?
- **View High-Level Performance Metrics:**
  - Active campaigns currently sending.
  - Average Success Rate (Delivered vs. Sent).
  - Average Engagement Rate (Read vs. Sent).
  - Total messages sent across all campaigns.
- **Search and Page Campaigns:** Find past campaigns by name.
- **Trigger Campaign Actions:**
  - **Live/Pause/Resume:** Pause an active sending process or resume a paused one.
  - **Edit:** Modify drafts or scheduled campaigns before they send.
  - **View Report:** Open detailed analysis reports showing delivery status details.
  - **Clone:** Copy a successful campaign's settings to quickly recreate a similar broadcast.
  - **Retarget:** Target a segment of users from a past campaign (e.g., users who did not read, or users whose delivery failed) and launch a follow-up message to them.
  - **Delete:** Safely remove a campaign record from the workspace.

## 5. What is involved?
- **Campaign Model:** Stores campaign state (draft, queued, sending, processing, paused, completed, failed) and performance aggregates.
- **CampaignDetail Model:** Tracks delivery status (sent, delivered, read, failed) for each individual recipient.
- **Campaign Creator (Route):** The page where users are redirected when editing, cloning, or retargeting.

## 6. How does it work?
1. The Marketer opens the Campaigns page.
2. The page loads aggregate stats and a list of campaigns.
3. If they see a campaign that has completed, they can click "Retarget".
4. A modal appears asking who to retarget:
   - "Got it but didn't read"
   - "Didn't receive it"
   - "Read it"
   - "Had an error"
5. They select "Got it but didn't read" and click "Send Follow-up Message".
6. The backend queries the `CampaignDetail` table to grab the contact IDs of everyone who had a `delivered` status but not a `read` status.
7. The system saves these contact IDs in the user's session and redirects them to the Campaign Creator page with those target contacts pre-selected.

## 7. What happens behind the scenes?
- **Average Metrics Calculation:** Success and engagement rates are calculated on-the-fly using database averages: `AVG((del_count / sent_count) * 100)` and `AVG((read_count / sent_count) * 100)`.
- **Cloning Logic:** Cloning utilizes Eloquent's `replicateAndReset()` method to copy the campaign structure, resetting database fields like primary keys, status (back to `draft`), and counter metrics, ensuring the cloned copy starts clean.
- **Queue Interception:** Pausing updates the DB status to `paused`. The background message sending process checks this status flag before dispatching the next message batch in the queue, letting it safely halt execution mid-broadcast.

## 8. Business Use Cases

**Use Case 1: Pausing Campaign to Manage High Inbound Support**
- **Situation:** A marketing manager blasts a promotion to 10,000 customers. Within minutes, the support inbox is flooded with replies, overwhelming the agents.
- **How the feature is used:** The manager goes to the Campaigns page, finds the active campaign, and clicks "Pause".
- **Customer experience:** Inbound volume levels off, allowing agents to catch up on active support chats.
- **Business outcome:** Prevents customer service quality from dropping due to excessive concurrent inquiries.

**Use Case 2: Following Up with Non-Readers**
- **Situation:** A B2B firm broadcasts an invitation to an upcoming webinar. After 3 days, 60% of recipients have received the message but haven't read it.
- **How the feature is used:** The marketer clicks "Retarget", selects "Got it but didn't read", and creates a new follow-up campaign with a secondary reminder template.
- **Customer experience:** The customer receives a polite second notice about the webinar.
- **Business outcome:** Higher webinar registration rates and maximized reach.

**Use Case 3: Retrying Failed Messages**
- **Situation:** A campaign completes, but 5% of messages failed to deliver due to carrier network glitches.
- **How the feature is used:** The manager clicks "Retarget", selects "Had an error", and resends the message to just that group.
- **Customer experience:** Customers receive their message after a slight delay.
- **Business outcome:** Better list coverage and cleaner campaign execution.

## 9. Industry Use Cases
- **Retail:** Cloned campaigns are used to quickly run repeating weekly promos without configuring them from scratch.
- **Education:** Schools retarget parents who "didn't receive" an alert to ensure critical information is successfully delivered.
- **Real Estate:** Agents pause campaigns during peak call hours to prevent their phone lines from being flooded with callback inquiries.

## 10. Real Customer Example
A SaaS provider runs an annual upgrade promotion. They broadcast the discount to all 2,000 free trial users. Two days later, the marketer checks this page. The metrics show: Success Rate 98%, Engagement (Read Rate) 70%. The marketer clicks "Retarget", selects "Got it but didn't read" (affecting ~600 users), and redirects to the creator. They write a message: "Don't miss out, our upgrade discount expires tomorrow!" Within 24 hours, they convert an additional 15 customers.

## 11. Customer Journey
Marketer checks stats &rarr; Identifies campaign performance &rarr; Pauses active queues or retargets segments &rarr; Pre-loads target audience &rarr; Launches follow-up sequence &rarr; Drives conversion.

## 12. Inputs
- Search query.
- Pause / Resume clicks.
- Retarget criteria selections (not_read, not_delivered, read, failed).

## 13. Outputs
- Saved DB status changes (e.g. `paused`, `processing`).
- Plucked list of contact IDs in the user's session for the Campaign Creator page.
- Cloned Campaign draft.

## 14. Dependencies
- **Campaign & CampaignDetail Models:** Database schemas storing analytics and statuses.
- **Campaign Creator:** Target page for editing, cloning, and retargeting workflows.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission on a plan that includes `campaigns`.
- **Who can view information:** Marketers/Admins.
- **Who can edit:** Marketers/Admins.
- **Who cannot access it:** Users lacking campaign permissions.

## 16. Important Rules
- You cannot pause completed or failed campaigns.
- Retargeting will result in a redirect to the Campaign Creator with session parameters loaded.

## 17. Common Problems
- **Problem:** Clicking Retarget says "No contacts found matching criteria."
  - **Possible reason:** All messages in the campaign succeeded and were read, or the campaign was sent to an empty list.
  - **What the user should do:** Try a different retarget criteria or check the campaign report to see the delivery breakdown.
- **Problem:** The campaign status is stuck in "sending" but no messages are arriving.
  - **Possible reason:** The background queue worker is down.
  - **What the user should do:** Run Setup Diagnostics or verify with a tech admin that the queue workers are processing jobs.

## 18. Simple Explanation for Sales
The Campaigns page is where you check your team's broadcast results. It shows who received your messages, who opened them, and lets you automatically follow up with the people who haven't read them yet.

## 19. Simple Explanation for Marketing
This is your campaign dashboard. It provides clear analytics on read rates and deliverability. You can easily duplicate successful broadcasts with the "Clone" button, or nudge non-responders by sending a target follow-up using "Retarget".

## 20. Simple Explanation for Support
If you're noticing a massive wave of incoming chats, check this page. A marketer might have launched a campaign. If it's too much to handle, you can ask them to temporarily "Pause" the campaign right here to give your team breathing room.

## 21. Related Features
- [Campaign Creator](./campaign-creator.md)
- [Deliverability](./deliverability.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/campaigns`
- **Implementation:** `App\Livewire\Campaigns\CampaignList`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Campaigns/CampaignList.php`
  - `resources/views/livewire/campaigns/campaign-list.blade.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
