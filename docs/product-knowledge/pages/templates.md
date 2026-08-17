# WhatsApp Templates

## 1. What is this page?
The WhatsApp Templates page is the asset manager for a business's pre-approved WhatsApp message templates. It allows managers to sync, design, preview, test, and submit message formats to Meta for official compliance approval.

## 2. Why is this page useful?
WhatsApp strictly regulates outbound messages to prevent spam.
- **Why do users need it?** Any message sent to a customer outside of an active 24-hour conversation window must use a template pre-approved by Meta.
- **What work does it make easier?** It provides a live visual preview as you write the template, automatically extracts variables, runs a compliance linter to flag policy violations *before* submission, and syncs status updates in one click.
- **What business process does it support?** Outbound messaging compliance, brand template management, and automated notifications.
- **What happens without it?** Users would have to jump back and forth between Facebook Business Manager and this platform, with no automated linting to prevent Meta rejections, and no local template library for campaigns.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To sync the local database with Meta, review template quality ratings, and delete outdated assets. |
| Marketing Manager | To create new promotional templates, configure call-to-action buttons, define variables, and submit them for Meta approval. |

## 4. What can users do here?
- **Template Inventory Dashboard:**
  - Search templates by name or category.
  - Filter by Category (Marketing, Utility, Authentication) and Status (Approved, Pending, Rejected, Paused).
  - Sync templates to fetch the latest Meta approval statuses, quality ratings, and readiness scores.
  - View core counts (Approved, Rejected, Total, and Media template ratio).
- **Inspect Template Details (Modal):**
  - Review an approved template's layout (Header type, Body, Footer, and Buttons).
  - Directly jump to the Meta WhatsApp Manager portal to edit a template.
- **Template Creator Wizard (Modal):**
  - Define name (alphanumeric and underscores only).
  - Select Category:
    - **Marketing:** For promotions, newsletters, and invites.
    - **Utility:** For order confirmations, transaction updates, and receipts.
    - **Authentication:** One-time passwords (OTP) with mandatory Copy Code button formats.
  - Set Header Type (None, Text, Image, Video, Document, Location). If text, supports header variables. If media, supports drag-and-drop file uploads to generate Meta media handles.
  - Edit message body with quick toolbar buttons (Bold `*`, Italic `_`, Strikethrough `~`, and Emojis).
  - Configure variables (`{{1}}`, `{{2}}`) and define sample fallbacks to generate a live preview.
  - Add Quick Action Buttons (Quick Reply, URL CTA, Phone Number, Copy Code). Enforces Meta's button mixing limitations.
  - Review **Compliance Warnings:** An automated checker (linter) scans the copy for policy violations (e.g., using "Sale" or promotional text in a "Utility" template) and halts submission for verification.

## 5. What is involved?
- **WhatsappTemplate Model:** Table storing template properties, status flags (`is_paused`), quality ratings, and structural components.
- **TemplateValidator:** Analyzes template strings for spam triggers, parameter syntax, and policy mismatches.
- **WhatsAppService & TemplateService:** Proxy interfaces for communicating with the Meta Graph API to sync, create, upload assets, and delete template templates.

## 6. How does it work?
1. The Marketer opens the Template Manager.
2. They click "Create" and name the template `order_shipped_alert`.
3. They select category "Utility", choose language "English (US)", and set the header to "None".
4. In the body, they type: "Hi {{1}}, your order #{{2}} has shipped! Track it here: {{3}}"
5. The wizard automatically detects three variables. It prompts the marketer to enter sample text for each (e.g. `{{1}}` = John, `{{2}}` = 9876, `{{3}}` = tracking_link).
6. The marketer adds a Call-to-Action button: "Track Order" redirecting to a URL.
7. Upon clicking save, the linter checks the copy. If it passes, the backend uploads the template structure to Meta.
8. Meta reviews the template. Once approved (usually within minutes), the marketer clicks "Sync" on the template list, and the status changes to "APPROVED", making it available in the campaign wizard.

## 7. What happens behind the scenes?
- **Meta Media Handlers:** When uploading images/videos for a template header, the file is sent directly to Meta's Resumable Upload API. Meta returns an upload handle token (`header_handle`), which is embedded in the template creation payload so Meta can cache the media.
- **Pre-submission Linting:** The `TemplateValidator` parses the component text. If the template is classified as "Utility" but contains marketing terms (e.g., "save", "discount", "free"), the validator generates a `WARNING` or `CRITICAL` risk status, halting submission to prevent Meta from permanently flagging the WABA account.
- **Button Constraints Validator:** The page validates button rules:
  - You cannot mix Quick Reply buttons with Call-to-Action buttons (Meta policy).
  - Quick Replies are limited to 3.
  - Call-to-Actions (URLs/Phone) are limited to 2.
  - Authentication templates must use OTP/Copy Code button structures.

## 8. Business Use Cases

**Use Case 1: Preparing a Broadcast Template**
- **Situation:** A marketing manager is planning a promotional campaign and needs to send a coupon image to active subscribers.
- **How the feature is used:** They create a "Marketing" template, select "Image" header type, upload the coupon JPEG, write the body copy, and submit it.
- **Customer experience:** Once approved and sent via Campaign Creator, customers receive a high-quality coupon card directly on WhatsApp.
- **Business outcome:** High-engagement marketing assets.

**Use Case 2: Setting Up Authentication Verification**
- **Situation:** A B2C app needs to send OTP verification codes to users logging in.
- **How the feature is used:** They create an "Authentication" template. The body automatically locks to a secure format, and the Copy Code action button is generated.
- **Customer experience:** The user receives a message and can click a single "Copy Code" button to copy their OTP.
- **Business outcome:** Streamlined, secure user authentication.

**Use Case 3: Resolving Rejections**
- **Situation:** A marketer submits a shipping update template under category "Utility" but Meta rejects it.
- **How the feature is used:** The marketer looks at the template list, sees status "REJECTED". They edit the template, check the text, and realize they wrote "Thanks for shopping with us!" (which is considered promotional). They delete the template and create a clean version.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Faster template approval turnarounds.

## 9. Industry Use Cases
- **Finance:** Creating utility templates for monthly bank statements or fraud alerts.
- **Retail:** Designing product catalogs with Quick Reply buttons for instant ordering.
- **Travel:** Submitting boarding passes as Document headers (PDFs).

## 10. Real Customer Example
A SaaS platform wants to notify customers about billing failures. The manager goes to the Template page, clicks create, selects category "Utility", and writes: "Hi {{1}}, payment for invoice {{2}} has failed. Please update your details." They add a URL button labeled "Update Payment" linking to their billing portal. The linter validates it, they submit it, sync the page 5 minutes later, and see the template is "APPROVED" and ready to use in billing workflows.

## 11. Customer Journey
Marketer designs template layout &rarr; Fills variable samples &rarr; Uploads media assets &rarr; Automated linter audits copy &rarr; Payload sent to Meta &rarr; Marketer clicks Sync &rarr; Template is ready for campaigns.

## 12. Inputs
- Template Name.
- Category (Marketing, Utility, Authentication).
- Language selection.
- Header format type (None, Text, Image, Video, Document, Location).
- Media files (for headers).
- Message Body content.
- Message Footer content.
- Button array actions (CTA, URL, Copy Code, Catalog, MPM).
- Variable configuration samples.

## 13. Outputs
- Dispatched Meta Template Creation API requests.
- Saved local `WhatsappTemplate` records.
- Pruned database templates.

## 14. Dependencies
- **WhatsappTemplate Model:** Database layout.
- **WhatsAppService / TemplateService:** API handlers.
- **TemplateValidator:** Linting engine.

## 15. Permissions
- **Who can access this page:** Users with `manage-templates` permission on plans including `templates`.
- **Who can view information:** Marketers/Admins.
- **Who can edit:** Marketers/Admins.
- **Who cannot access it:** Support agents.

## 16. Important Rules
- Names can only contain lowercase letters, numbers, and underscores (spaces or hyphens will throw errors).
- Standard templates are limited to 1024 characters in the body.
- Senders cannot mix Call-to-Action and Quick Reply buttons.

## 17. Common Problems
- **Problem:** Meta returns "Duplicate template name" error.
  - **Possible reason:** A template with the exact same name already exists in the Facebook Business account, even if it is not visible locally.
  - **What the user should do:** Click "Sync" to ensure the local database matches Meta, or choose a new unique name (e.g. `promo_spring_v2`).
- **Problem:** Template status is stuck in "Pending".
  - **Possible reason:** Meta is still reviewing the template, or the platform hasn't synced the status yet.
  - **What the user should do:** Wait a few minutes, then click the "Sync" button on the template list page to update the status.

## 18. Simple Explanation for Sales
The Templates page is where you build the message formats you want to send to customers. Because WhatsApp requires approval for outbound messages, this page guides you through designing templates, checking them for errors, and submitting them to Meta so you can start broadcasting.

## 19. Simple Explanation for Marketing
Design beautiful, rich media templates with ease. You can upload header images, add interactive buttons (like phone calls or website links), write copy with emojis, and preview exactly what the customer will see on their mobile screen before submitting.

## 20. Simple Explanation for Support
If you need to send an approved response to a customer after 24 hours of inactivity, this page is where you check what approved templates are available for your team to use.

## 21. Related Features
- [Campaign Creator](./campaign-wizard.md)
- [Inbox Settings](./inbox-settings.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/templates`
- **Implementation:** `App\Livewire\Templates\TemplateList`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Templates/TemplateList.php`
  - `resources/views/livewire/templates/template-list.blade.php`
  - `app/Services/TemplateService.php`
  - `app/Validators/TemplateValidator.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
