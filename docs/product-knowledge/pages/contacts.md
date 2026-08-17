# Audience Center (Contacts)

## 1. What is this page?
The Audience Center (Contacts) is the central CRM directory of the platform. It manages customer records, contact categorization (tags/categories), custom attributes, duplicate merging, and mass data imports/exports.

## 2. Why is this page useful?
WhatsApp communication requires structured, verified contact data.
- **Why do users need it?** To store customer details, track opt-in/opt-out status, build targeted lists for broadcasts, and view unified customer interaction profiles.
- **What work does it make easier?** It simplifies importing massive customer sheets via CSV, auto-detects and resolves duplicate numbers, and hosts custom fields (like "Birthday" or "Membership Tier").
- **What business process does it support?** Customer Database Management, Audience Segmentation, Data Hygiene, and Unified Customer Service Profile Lookup.
- **What happens without it?** The system has no record of customer identities. Outbox messages could only target raw phone numbers with no personalization (like name templates), no contact-level tagging, and no historical timeline auditing.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To manage database storage limits, configure custom contact fields, and audit bulk contact exports. |
| Marketing Manager | To upload targeted contact lists for marketing broadcasts and define audience tags. |
| Customer Support Agent | To search for a customer, inspect their historical timeline of activities, view shared media vaults, and verify their opt-in status. |

## 4. What can users do here?
- **Contact Database Management:**
  - Search contacts by Name, Phone Number, or Email.
  - Filter contact lists by Tags and Opt-In/Opt-Out status.
  - Create, edit, or permanently delete contacts.
- **Unified Profile View (Modal):**
  - **Profile Tab:** View core messaging statistics, tags, categories, raw metadata, and trigger WhatsApp calls.
  - **Timeline Tab:** View a chronological visual feed of all client events (sent messages, internal notes, e-commerce orders, deals, and automation runs).
  - **Files Tab:** Browse a "media vault" grid of all image, video, and document attachments exchanged with this customer.
  - **Engagement Tab:** View a 7x24 heatmap visualization mapping when this contact is most active based on past interactions.
- **Bulk Import Workflow:**
  - Upload CSV/XLSX contact lists (up to 10MB).
  - Map import columns to database fields (including custom fields).
  - Select duplicate strategies: **Update** existing contact details or **Skip** duplicates.
  - Select tag overwrite behaviors (Add new tags to existing contact or Replace all existing tags).
  - Review an import preview showing duplicate checks before executing, and review post-import reports.
- **Data Exporting:**
  - Export the filtered contact list to CSV.
  - Download a customized Sample CSV pre-loaded with the team's custom fields and default country code.
- **Custom Database Schema (Fields):**
  - Create custom fields (Text, Number, Date, or Dropdown Select type) that generate matching input boxes on all contact cards.
- **Data Deduplication (Merge):**
  - Merge a duplicate contact card into a target contact card, transferring all messages, notes, and tags before erasing the duplicate.

## 5. What is involved?
- **Contact Model:** Main table containing customer fields and a `custom_attributes` JSON column.
- **ContactField Model:** Schema definition table for the custom attributes.
- **ContactImportService:** Handles chunked reading, preview validation, duplicate sorting, and DB saves.
- **ContactMergeService:** Migrates relational rows (messages, timelines, tags) from a source model to a target model before deleting the source.
- **ContactTimelineService:** Gathers and formats chronological feeds, media objects, and interaction hour maps for the profile modal.
- **EntitlementService:** Audits team subscription plans to enforce maximum contact limit caps.

## 6. How does it work?
1. An admin wants to store a customer's "Membership ID".
2. They click "Fields", create a new field named "Member ID" of type "Number", and save.
3. They go to the contact list, click "Add Contact", fill in basic info, and see a new "Member ID" input field at the bottom.
4. If they want to import 1,000 customers from an Excel sheet, they click "Import", upload the CSV, and map their "Customer Code" column to the newly created "Member ID" field.
5. The importer scans the database. If it finds a phone number that already exists, it applies the duplicate strategy (e.g. overwriting the name with the Excel name).
6. Once saved, the contacts are added and labeled, ready to be targeted in campaigns.

## 7. What happens behind the scenes?
- **Timeline Aggregation:** When viewing a profile, `ContactTimelineService` queries multiple database tables (messages, notes, deals, logs), normalizes their properties (title, description, time), sorts them in descending order, and displays them as a chronological feed.
- **Heatmap Generator:** The service parses the timestamps of all historical customer messages. It groups them by day-of-week (1-7) and hour-of-day (0-23) to generate a numeric matrix, which is rendered as a color-intensity grid in the UI.
- **Merge Relational Transfer:** Merging runs inside a database transaction. It updates the foreign keys of all messages, timeline logs, and notes pointing to the source contact's ID to point to the target contact's ID, syncs combined tags, and deletes the source contact.

## 8. Business Use Cases

**Use Case 1: Importing Leads from a Conference**
- **Situation:** A marketing manager returns from a conference with a CSV list of 500 potential leads and wants to subscribe them to a WhatsApp promo broadcast.
- **How the feature is used:** They open the Import modal, upload the CSV, map the columns, and assign the tag "Conference-2026". They choose the duplicate strategy "Skip" to avoid messing up existing contacts.
- **Customer experience:** Leads receive their promo message correctly targeted.
- **Business outcome:** Rapid audience scaling without manual entry.

**Use Case 2: Support Audit via Profile Timeline**
- **Situation:** A customer claims they never received a refunds update they were promised three days ago.
- **How the feature is used:** The support agent searches the customer's name, opens the Unified Profile modal, and clicks "Timeline". They see a record showing an outbound message was successfully read on Tuesday, and an internal note left by another agent confirming the refund was approved.
- **Customer experience:** The agent provides immediate, accurate verification of the timeline.
- **Business outcome:** High customer service transparency and fast dispute resolution.

**Use Case 3: Cleaning Up Duplicate Database Contacts**
- **Situation:** A CRM integration error created duplicate contact records for a customer under slightly different phone formats.
- **How the feature is used:** The admin clicks "Merge" on the duplicate card, selects the primary card as the target, and clicks merge.
- **Customer experience:** The customer has a single clean chat history.
- **Business outcome:** Clean customer records and accurate inbox indexing.

## 9. Industry Use Cases
- **Real Estate:** Storing custom fields like "Budget" and "Preferred Neighborhood" on buyer contacts.
- **Healthcare:** Tracking patient appointment histories in the visual timeline and storing intake PDFs in the media vault.
- **E-Commerce:** Importing customer files from Shopify, mapping purchase categories, and targeting them with promotional broadcasts.

## 10. Real Customer Example
A retail store manager wants to reward VIP customers. They open the Fields manager and add a dropdown field called "Customer Tier" with options: VIP, Gold, Regular. They edit their top customer, mark them as "VIP", and assign the tag "Local". When the marketing team creates a campaign, they target contacts tag "Local" where custom attribute "Customer Tier" equals "VIP". Later, a support rep opens the customer's visual profile, views the Files tab to review receipt screenshots the customer sent, and processes a return instantly.

## 11. Customer Journey
Admin defines custom fields &rarr; Marketer imports CSV lead list &rarr; Importer handles duplicate phone checks &rarr; Agents view customer timeline and vault &rarr; Clean database segmentation.

## 12. Inputs
- Basic contact card details.
- Custom Attributes values.
- CSV/XLSX files for import.
- Column mapping schemas.
- Duplicate and Tag strategies.
- Source/Target IDs for merging.
- Search queries and tag filters.

## 13. Outputs
- Contact records in database.
- Normalised event timelines, media vault arrays, and engagement heatmaps.
- Exported contacts CSV file.
- Streamed download sample CSV template.

## 14. Dependencies
- **Contact, ContactField, ContactTag Models:** Database tables.
- **ContactImportService / ContactMergeService:** Database processing services.
- **ContactTimelineService:** Timeline normalizer.
- **EntitlementService:** Plan limits verifier.

## 15. Permissions
- **Who can access this page:** Users with `manage-contacts` permission on plans including `contacts`.
- **Who can view information:** Agents/Marketers/Admins.
- **Who can edit:** Agents/Marketers/Admins.
- **Who cannot access it:** Guest users.

## 16. Important Rules
- Phone numbers must contain country codes (e.g. +91) for WhatsApp API delivery to succeed.
- If a team's plan has a contact limit entitlement (e.g. 5,000 contacts), creating new contacts will block if that limit is exceeded.
- Merging contacts is permanent and cannot be undone.

## 17. Common Problems
- **Problem:** CSV Import fails or shows parsing errors.
  - **Possible reason:** The CSV is not UTF-8 encoded, or it lacks phone numbers in the mapped phone column.
  - **What the user should do:** Download the Sample CSV from this page, copy their data into that template, and upload again.
- **Problem:** Importing says "Limit exceeded."
  - **Possible reason:** The team has reached the maximum allowed contacts for their subscription tier.
  - **What the user should do:** Delete old contacts, export and prune lists, or upgrade their plan.
- **Problem:** Merging contacts throws an error.
  - **Possible reason:** The source contact and target contact are the same ID.
  - **What the user should do:** Choose two distinct contact records to merge.

## 18. Simple Explanation for Sales
The Audience Center is your address book. It stores all customer names, emails, and phone numbers. You can assign custom labels like "VIP" or "Lead", and view a visual timeline of all past interactions when you look up a customer's profile.

## 19. Simple Explanation for Marketing
This is where you manage your broadcast lists. You can import thousands of leads from an Excel sheet, map them to tags, check for duplicate numbers, and export lists to clean up your database.

## 20. Simple Explanation for Support
When a customer complains, find them here. The profile modal gives you a complete timeline of their history, a list of all files they have sent you, and a chart showing when they are most active online.

## 21. Related Features
- [Inbox Settings](./inbox-settings.md)
- [Canned Responses](./canned-messages.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/contacts`
- **Implementation:** `App\Livewire\Contacts\ContactManager`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Contacts/ContactManager.php`
  - `resources/views/livewire/contacts/contact-manager.blade.php`
  - `app/Services/ContactImportService.php`
  - `app/Services/ContactMergeService.php`
  - `app/Services/ContactTimelineService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
