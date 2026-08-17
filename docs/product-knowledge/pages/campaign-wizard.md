# Campaign Creator (Wizard)

## 1. What is this page?
The Campaign Creator (Wizard) is a multi-step guided setup page used to build, schedule, and launch bulk WhatsApp broadcasts or automated drip marketing campaigns.

## 2. Why is this page useful?
Creating a broadcast requires configuring multiple variables: picking recipients, choosing templates, filling variable parameters, adding headers, and setting up error handling.
- **Why do users need it?** To easily design complex messaging campaigns without making configuration mistakes.
- **What work does it make easier?** It breaks down the configuration into four clear stages (Setup, Audience, Message, Review) and checks deliverability health *before* letting the user send the campaign.
- **What business process does it support?** Customer acquisition campaigns, re-engagement drives, and automated drip marketing.
- **What happens without it?** Setting up bulk messaging would be prone to manual formatting errors, resulting in failed messages, wasted advertising credits, or Meta account suspension.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To configure high-value promotions, set retry configurations, and review pre-flight deliverability health. |
| Marketing Manager | To write campaign copy, select templates, map variables, target contact tags, and schedule campaigns. |

## 4. What can users do here?
- **Step 1: Setup**
  - Name the campaign.
  - Choose Campaign Type: **Broadcast** (one-off blast) or **Drip** (automated series of messages over time).
  - Select Drip triggers: **Instant** or **Tag Added** (starts when a specific tag is attached to a contact).
  - Configure **Retry Strategies** (e.g. exponential retry up to 3 times if delivery fails).
  - Schedule delivery: **Now** or **Later** (set a specific date/time).
- **Step 2: Audience**
  - Filter target contacts: **All Contacts**, contacts matching specific **Tags**, or a hand-picked list of **Individual Contacts**.
  - Review the estimated audience size in real-time.
- **Step 3: Message**
  - Select from approved Meta WhatsApp Templates.
  - Fill in text inputs to map variables in the template body (e.g. mapping `{{1}}` to name).
  - Upload or provide URLs for rich media headers (images, videos, documents) if required by the template format.
  - Configure subsequent steps and delays for Drip campaigns.
- **Step 4: Review**
  - View pre-flight deliverability warnings or blocks (e.g., if the token is invalid or messaging credits are empty).
  - Inspect a final preview of settings before launching.

## 5. What is involved?
- **DeliverabilityPreflight Service:** Automatically checks token health, credit balances, and quality limits, showing block flags to prevent launching broken campaigns.
- **PrepareCampaignJob:** A background job dispatched upon launching to query the target audience database, generate individual message rows, and place them in the queue.
- **Session Memory:** Allows the page to inherit a pre-filtered list of contact IDs if accessed via a "Retarget" action from a past campaign.

## 6. How does it work?
1. The user launches the Campaign Wizard.
2. They select a template (e.g., `event_invite`). The wizard detects the template has a header image and two body variables.
3. The wizard dynamically renders inputs for the header image (upload file or paste URL) and two text input boxes.
4. The user sets the audience to tag "Active Subscribers" (calculating a target audience of 500 contacts).
5. On the final step, the page executes a deliverability pre-flight check. If green, the user clicks "Launch".
6. The system schedules a `PrepareCampaignJob` to resolve the contact IDs and queue the 500 messages at the scheduled time.

## 7. What happens behind the scenes?
- **Dynamic Template Parsing:** When a template is selected, the backend parses its JSON configuration from the database, counts how many placeholders (e.g., `{{1}}`) are in the body, and alerts the UI to generate the matching number of input fields.
- **Preflight Blockers:** When sending a campaign "now", the pre-flight engine runs checks. If blocking errors are detected (like an expired Meta Token), the launch button is disabled and errors are displayed. If scheduled for later, it bypasses blocking checks since health might resolve by the run time.
- **Drip Processing:** If set to Drip/Tag Trigger, the campaign is flagged as `active` without scheduling a job. The system listens for tag events on contacts, dynamically enrolling them into the campaign steps.

## 8. Business Use Cases

**Use Case 1: Scheduling a Holiday Broadcast**
- **Situation:** A marketing manager wants to schedule a Christmas promo message to send on December 24th at 9:00 AM while they are offline.
- **How the feature is used:** They set the schedule mode to "Later", pick the date/time, select the target tags, insert the promo template, and click launch.
- **Customer experience:** Customers receive the Christmas message exactly at 9:00 AM on Christmas Eve.
- **Business outcome:** Streamlined campaign execution without requiring manual intervention on holidays.

**Use Case 2: Onboarding Drip Campaign**
- **Situation:** A gym wants to send a sequence of tips over 3 days to new members who sign up.
- **How the feature is used:** They create a Drip campaign triggered by "Tag Added: New Member". They configure Step 1 to send instantly, Step 2 after 1440 minutes (1 day), and Step 3 after 2880 minutes (2 days).
- **Customer experience:** When the user signs up, they receive daily helpful tips on their phone.
- **Business outcome:** High customer engagement and retention rates.

**Use Case 3: Retargeting Follow-Up**
- **Situation:** A B2B firm wants to offer a second chance discount to leads who read their pricing update message but didn't buy.
- **How the feature is used:** They click "Retarget" from a past report. The wizard loads with these specific contact IDs pre-populated in the audience selection step, allowing them to instantly write and send the follow-up.
- **Customer experience:** Leads receive a personalized offer.
- **Business outcome:** High-conversion retargeting flows.

## 9. Industry Use Cases
- **Retail:** Launching seasonal discount broadcasts with S3-hosted header images showing the products.
- **Events:** Sending RSVP reminders to specific contacts with variables mapping their unique ticket numbers.
- **Financial Services:** Scheduling policy updates with document headers (PDFs) detailing term changes.

## 10. Real Customer Example
A car dealership configures a campaign called "Oil Change Reminder". They select target tag "Due for Service" (120 contacts). They select a template that reads: "Hi {{1}}, your {{2}} is due for service." They map variable 1 to `Contact Name` and variable 2 to `Car Model`. They drag-and-drop a coupon image into the header section and select "Send Now". The pre-flight checks pass, they click launch, and the system queues the 120 personalized messages.

## 11. Customer Journey
Marketer opens wizard &rarr; Enters setup details &rarr; Filters target audience &rarr; Maps template variables &rarr; Uploads media header &rarr; Passes pre-flight check &rarr; Launcs Campaign.

## 12. Inputs
- Campaign Name, Type, and Schedule.
- Audience Filters (Tags, Contact IDs).
- Meta Template ID.
- Dynamic Variable parameters.
- Header media files or URLs.
- Retry Configuration settings.

## 13. Outputs
- Created `Campaign` database record.
- Dispatched `PrepareCampaignJob` in the system queue.

## 14. Dependencies
- **WhatsappTemplate Model:** To fetch approved templates.
- **Contact & ContactTag Models:** To resolve target lists.
- **DeliverabilityPreflight Engine:** To audit launch health.
- **PrepareCampaignJob:** Background processor.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission on plans including `campaigns`.
- **Who can view information:** Marketers/Admins.
- **Who can edit:** Marketers/Admins.
- **Who cannot access it:** Users lacking campaign permissions.

## 16. Important Rules
- You cannot launch an immediate campaign if the pre-flight check contains blocking health errors.
- Image/video/document headers uploaded via file input are saved to public disk storage and must be publicly accessible so Meta's API can fetch them.

## 17. Common Problems
- **Problem:** "Cannot send now" error when clicking launch.
  - **Possible reason:** The pre-flight deliverability check failed (e.g. S3 storage path is misconfigured, access token has expired, WABA has a low quality rating).
  - **What the user should do:** Review the errors listed on the page. If the token is expired, go to the Account Hub and re-authenticate. If WABA is restricted, contact support.
- **Problem:** "Audience size is 0" warning.
  - **Possible reason:** The selected tags do not have any contacts assigned, or the contacts under those tags have opted out.
  - **What the user should do:** Check the Contacts page to verify contacts are correctly tagged and have active "Opted In" statuses.

## 18. Simple Explanation for Sales
The Campaign Creator is where you set up your promotions. It guides you step-by-step: naming your promo, choosing your target audience list, selecting your message template, and reviewing the system health before sending.

## 19. Simple Explanation for Marketing
This is your campaign builder. You can construct one-off broadcasts or multi-step drip campaigns. It pulls approved templates from Meta, allows you to upload matching header images, and automatically checks for system errors before launching.

## 20. Simple Explanation for Support
If you get inquiries about promotional messages, you can view the templates on this page to see exactly what copy the marketing team is sending out.

## 21. Related Features
- [Campaigns List](./campaign-list.md)
- [Campaign Report](./campaign-report.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/campaigns/wizard`
- **Implementation:** `App\Livewire\Campaigns\Wizard`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Campaigns/Wizard.php`
  - `resources/views/livewire/campaigns/wizard.blade.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
