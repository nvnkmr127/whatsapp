# Inbox Settings

## 1. What is this page?
The Inbox Settings page allows businesses to configure automated customer service behaviors for their WhatsApp channel. This includes setting business hours, configuring automatic welcome and away messages, and enabling the AI Assistant.

## 2. Why is this page useful?
Customers expect immediate responses on messaging platforms like WhatsApp, even outside of normal business hours.
- **Why do users need it?** To set customer expectations about when they will receive a response and to automate routine greetings.
- **What work does it make easier?** It saves human agents from manually typing "Welcome" or "We are closed" to every inbound conversation.
- **What business process does it support?** Customer service automation and SLA (Service Level Agreement) management.
- **What happens without it?** A customer messaging at 2:00 AM gets complete silence, leading to frustration and a poor brand experience.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To configure the global inbox behavior and link it to the company's official operating hours. |
| Customer Support Manager | To craft the specific text or templates used for automated greetings and away messages. |

## 4. What can users do here?
- Enable or disable **Read Receipts** (the blue ticks on WhatsApp).
- Enable or disable the **AI Assistant** to automatically answer questions based on the Knowledge Base.
- Turn on a **Welcome Message** sent automatically when a new customer initiates a chat.
- Turn on an **Away Message** sent automatically when a customer messages outside of defined business hours.
- Customize the auto-replies using either free-form text, rich media (images/video/documents), or pre-approved Meta WhatsApp templates.
- Define a weekly schedule of **Working Hours** (e.g., Monday 09:00 - 17:00, Sunday Closed).

## 5. What is involved?
- **Business Hours:** A schedule used to determine if the business is currently "open" or "closed".
- **Auto-Replies:** Configuration settings that store the message content or the specific Meta template to use.
- **Meta Templates:** Pre-approved message templates fetched directly from the WhatsApp Business API.
- **AI Engine:** The automated answering system (configured elsewhere, but activated here).

## 6. How does it work?
1. The Admin opens the Inbox Settings page.
2. They set their weekly Working Hours (e.g., Monday-Friday 9am to 6pm, Weekends closed).
3. They enable the "Away Message". They open the configuration modal and type: "Thanks for reaching out! We are currently closed and will respond when we open."
4. They click "Save All Settings".
5. When a customer sends a message to the business's WhatsApp number on a Saturday, the platform checks the Working Hours, sees the business is closed, and automatically sends the configured Away Message.

## 7. What happens behind the scenes?
- **Data Storage:** Settings are saved directly to the `Team` model (e.g., `business_hours`, `away_message_config`, `welcome_message_config`).
- **Template Fetching:** When the Admin opens the message configuration modal, the system calls the Meta Graph API (`WhatsAppService::getTemplates()`) to retrieve all templates in the "APPROVED" state, allowing the user to select them from a dropdown.
- **Message Execution:** When an inbound webhook arrives, the backend routing logic evaluates the timestamp against the stored `business_hours` array. If outside those hours, it triggers the away message job. If it's a new conversation during business hours, it triggers the welcome message job.

## 8. Business Use Cases

**Use Case 1: 24/7 AI Support Automation**
- **Situation:** A growing e-commerce brand cannot afford to staff human agents overnight, but customers constantly ask about shipping policies.
- **How the feature is used:** The manager toggles the "AI Assistant" to "On".
- **Customer experience:** A customer asks "Do you ship to Canada?" at 3:00 AM. The AI Assistant instantly replies with the shipping policy, resolving the query without human intervention.
- **Business outcome:** Reduced support ticket backlog and faster resolution times.

**Use Case 2: Setting After-Hours Expectations**
- **Situation:** A B2B software company only provides support during standard business hours.
- **How the feature is used:** The admin sets the Working Hours to Mon-Fri 9-5. They configure an Away Message template that reads: "Our support team is currently offline. We will review your message on the next business day. For emergencies, check our status page."
- **Customer experience:** A client messages on Saturday, receives the auto-reply, and understands they won't get help until Monday.
- **Business outcome:** Prevents customer frustration caused by being ignored.

**Use Case 3: Engaging Welcome Message (Rich Media)**
- **Situation:** A real estate agent wants to make a strong first impression.
- **How the feature is used:** They enable the Welcome Message, open the configuration modal, select "Image", provide a URL to a professional headshot, and add the caption: "Hi! I'm Sarah, your local property expert. How can I help you today?"
- **Customer experience:** A prospect clicks a WhatsApp link on a property listing. The moment they say "Hi", they receive a friendly, personalized greeting with a photo.
- **Business outcome:** Higher engagement and better relationship building from the very first interaction.

## 9. Industry Use Cases
- **Healthcare:** Clinics use strict working hours and an Away Message that instructs patients to dial emergency services if the clinic is closed.
- **Retail:** Stores use a Welcome Message to immediately offer a 10% discount code to anyone who initiates a chat.
- **Hospitality:** Hotels use the AI auto-reply feature to automatically answer common questions like "What time is check-in?" or "Do you have a pool?"

## 10. Real Customer Example
A local salon uses WhatsApp for bookings. They open from Tuesday to Saturday. The owner sets Sunday and Monday as "Closed" in the Working Hours section. They enable the Away Message and type: "We're currently taking a break! You can still book an appointment anytime using our online calendar at [link]. We'll reply to other questions on Tuesday." This ensures clients can still self-serve while the salon is closed.

## 11. Customer Journey
Admin sets hours and auto-replies &rarr; Customer sends message &rarr; System checks current time against Business Hours &rarr; System sends appropriate Welcome or Away message &rarr; Customer expectations are managed.

## 12. Inputs
- Read Receipts toggle
- AI Assistant toggle
- Welcome Message toggle & configuration (Text/Media/Template)
- Away Message toggle & configuration (Text/Media/Template)
- 7-day weekly schedule (Open/Close times or Closed)

## 13. Outputs
- Saved Team configuration in the database.

## 14. Dependencies
- **Meta Graph API:** Required to fetch approved templates for the configuration modal.
- **Team Model:** To store the configuration.
- **Knowledge Base / AI Engine:** Required for the AI auto-reply feature to function correctly (configured separately).

## 15. Permissions
- **Who can access this page:** Only users with `manage-settings` permission (Admins).
- **Who can view information:** Admins.
- **Who can edit:** Admins.
- **Who cannot access it:** Standard agents/reps.

## 16. Important Rules
- Pre-approved Meta templates cannot be edited on this page; they must be managed in the Meta Business Manager.
- If the AI Assistant is enabled, it may handle the conversation immediately after the Welcome Message is sent.

## 17. Common Problems
- **Problem:** The away message isn't sending after hours.
  - **Possible reason:** The "Away Message" toggle is off, or the business timezone (set in WhatsApp Configuration) does not match the Admin's local time, causing the hours to misalign.
  - **What the user should do:** Verify the toggle is on and check the account Timezone setting in the Account Hub.
- **Problem:** I can't select my new template for the welcome message.
  - **Possible reason:** The template has not been approved by Meta yet, or it was just approved and the platform's cache hasn't refreshed.
  - **What the user should do:** Wait for Meta approval or reload the page to fetch the latest templates.

## 18. Simple Explanation for Sales
Inbox Settings puts your customer service on autopilot. You can define exactly when your team is available and configure instant welcome greetings or after-hours messages, ensuring your customers never feel ignored, even if they message you in the middle of the night.

## 19. Simple Explanation for Marketing
Use the Welcome Message feature here to make a great first impression. Instead of a boring text reply, you can configure your welcome message to send a rich image or a promotional video the moment a prospect initiates a chat.

## 20. Simple Explanation for Support
If you're overwhelmed with messages outside of your shift, make sure your Working Hours are set correctly on this page. The system will automatically intercept after-hours messages with a polite away message so you don't have to worry about missing SLAs.

## 21. Related Features
- [WhatsApp Configuration](./whatsapp-setup.md)
- [Knowledge Base](./knowledge-base.md) (Used by the AI Assistant)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/whatsapp/inbox`
- **Implementation:** `App\Livewire\Teams\InboxSettings`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Teams/InboxSettings.php`
  - `resources/views/livewire/teams/inbox-settings.blade.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
