# Retargeting Campaigns

## What is it?
**Retargeting** allows you to instantly create a follow-up campaign targeting a specific subset of users based on how they interacted with a previous broadcast. You can re-engage users who didn't read your message, retry failed deliveries, or send a specific offer to those who showed interest (read the message).

## Why is it useful?
-   **Boost Conversions**: People often miss the first message. Sending a friendly reminder to those who "Didn't Read" significantly increases engagement.
-   **Clean Your List**: Identify and filter out "Failed" numbers.
-   **Segment Interested Users**: Send a VIP offer only to those who "Read" your previous announcement.

## Interface Guide
-   **Campaign List Actions**: Every sent campaign has a **Retarget** button in the list view.
-   **Retargeting Modal**: A popup that lets you choose who to target:
    -   **Didn't Read**: Sent & Delivered, but not Read.
    -   **Didn't Receive**: Failed or Sent but not Delivered.
    -   **Read**: Users who opened the message.
    -   **Failed**: Specific error cases.

## Use Cases
1.  **The "Nudge"**: You sent a webinar invite. 24 hours later, you target everyone who **Didn't Read** with a new message: "In case you missed this..."
2.  **The "Correction"**: You realized a template had a typo. You target **Read** users with: "Oops, we meant 50% off, not 5%!".
3.  **The "Retry"**: A network error caused 100 messages to Fail. You target **Failed** contacts to try sending again later.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Retarget** | Admin | Low open rate on campaign | 1. Admin clicks **Retarget** on "Summer Sale" campaign.<br>2. Selects **Didn't Read**.<br>3. System extracts 500 contacts who ignored the message.<br>4. Redirects to Campaign Wizard with those 500 contacts pre-selected.<br>5. Admin sends new "Reminder" template. | 20-30% more opens; Maximized ROI on broadcast costs. |

## How to Use
1.  Navigate to **Engagement > Broadcasting**.
2.  Locate a campaign that has finished sending.
3.  Click the **Retarget** link on the right side of the campaign row.
4.  In the popup, select your target segment (e.g., **Didn't Read**).
5.  Click **Create Retargeting Campaign**.
6.  You will be taken to the **New Campaign** page.
    -   **Audience**: The contacts matching your criteria are already selected.
    -   **Name**: Auto-filled (e.g., "Retarget: Summer Sale (not read)").
7.  Select a new Template and click **Launch Campaign**.
