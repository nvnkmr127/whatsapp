# Chat Inbox

## 1. What is this page?
The Chat Inbox (Team Inbox) is the central workspace where customer support agents and managers communicate in real-time with customers over WhatsApp. It aggregates all inbound and outbound messages, coordinates agent assignments, and integrates automated tools.

## 2. Why is this page useful?
This is the core daily interface for customer-facing teams.
- **Why do users need it?** To view and reply to customer inquiries on WhatsApp.
- **What work does it make easier?** It consolidates conversations, automatically routes them, allows team collaboration via internal notes/transfers, and simplifies rich media sharing.
- **What business process does it support?** Customer Support (Help Desk), Sales (Conversational Commerce), and Customer Relationship Management (CRM).
- **What happens without it?** Agents would have to use a physical phone with the WhatsApp Business app, which cannot support multiple concurrent agents, CRM tagging, automated routing, or analytics.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Support Agent | To handle their assigned support chats, answer FAQs, and resolve customer issues. |
| Support Manager / Admin | To monitor SLAs, reassign conversations, perform bulk operations, and audit transcripts. |

## 4. What can users do here?
- **Conversation List (Left Pane):**
  - Search conversations by contact name, phone number, custom attributes, or message content (FULLTEXT).
  - Filter conversations by Assignment (Mine, Unassigned, All), Read Status, Opt-In Status, Blocked/Spam Status, Starred messages, or Tags.
  - Perform bulk operations on selected chats (Export transcripts, Mark as Read, Close, or Reassign).
  - Monitor live metrics (Active Chats, Unassigned, SLA Breaches).
- **Message Window (Center Pane):**
  - Send text messages and rich media (images, video, audio, documents up to 16MB).
  - Record and send Voice Notes (automatically transcoded to WhatsApp-compatible format).
  - Choose and send pre-approved Meta templates, filling in variable placeholders (e.g., `{{1}}`) with a live markdown preview.
  - Toggle "Note Mode" to post Internal Notes visible only to other agents.
  - Forward messages to other conversations, or reply directly to a specific message.
  - Close a conversation (triggers an automated CSAT customer survey) or reopen a closed one.
  - Mark conversations as spam or block contacts.
- **Contact Details (Right Pane - Lazy Loaded):**
  - View contact profile metadata, tags, and categories.
  - Edit contact attributes and update tags.

## 5. What is involved?
- **Conversation Model:** Groups messages between a contact and a team.
- **Message Model:** Individual messages, storing direction (inbound/outbound), type (text, image, audio, document, internal_note), and metadata.
- **Livewire Components:** `ChatDashboard` coordinates `ConversationList` (left sidebar), `MessageWindow` (center), and `ContactDetails` (right sidebar).
- **FFmpeg Engine:** Converts raw audio formats recorded in the browser (usually `.webm`) into `.ogg/opus` files before sending to WhatsApp.
- **CsatService:** Triggers and monitors Customer Satisfaction surveys when a conversation is closed.
- **BotHandoffService:** Automatically pauses AI bots or auto-responders when an agent sends a message, ensuring a smooth transition to human agent support.

## 6. How does it work?
1. A customer sends a message on WhatsApp.
2. The platform's webhook receives the message, creates a `Message` record, and updates the `Conversation` (e.g., updating `last_message_at`).
3. The Inbox dynamically refreshes. If the chat is unassigned, it increments the "Unassigned" counter.
4. An agent selects the conversation. The dashboard updates the URL with `?activeConversationId=X` so the state persists on refresh.
5. Inbound messages are marked as read in the database, and an async `MarkAsReadJob` is sent to Meta to update the "blue ticks" on the customer's phone.
6. The agent types a reply, uploads an attachment, or selects a template, and clicks send. The message is queued and dispatched to Meta via `SendMessageJob`.

## 7. What happens behind the scenes?
- **Browser WebM to Ogg Conversion:** Browsers record audio as WebM. Since Meta rejects WebM, the backend intercepts browser-recorded audio, invokes an `FFmpeg` shell process to transcode it to Ogg/Opus, saves it to storage (S3/Local), and dispatches the Ogg file.
- **Private S3 URL Signing:** If rich media is stored on a private S3/R2 cloud bucket, `SendMessageJob` temporarily generates a signed URL so Meta can fetch the file to deliver it to the contact.
- **Bot Handoff:** Whenever an agent performs actions (sending messages, transferring, etc.), the `BotHandoffService` tags the contact to prevent automated chatbots from firing and interrupting the human agent.
- **Fulltext Indexing:** Search queries longer than 3 characters utilize MySQL `FULLTEXT` indexing on message contents, allowing fast scans across thousands of chat messages.

## 8. Business Use Cases

**Use Case 1: Resolving a Billing Question**
- **Situation:** A customer messages the billing team asking about a charge.
- **How the feature is used:** The agent answers using an internal canned response `/billing_faq`. To explain the invoice, they drag-and-drop a PDF invoice.
- **Customer experience:** The customer receives a rapid text explanation alongside their PDF invoice.
- **Business outcome:** Faster resolution, lower SLA, and higher support efficiency.

**Use Case 2: Collaboration and Handoff**
- **Situation:** A tier-1 support agent receives a complex technical question they cannot resolve.
- **How the feature is used:** The agent toggles "Note Mode" and leaves an internal note: "User is having issues with API key replication." They click the transfer button and assign the conversation to the "Senior Tech Support" agent.
- **Customer experience:** The transition is seamless; the new agent inherits the context immediately without asking the customer to repeat their problem.
- **Business outcome:** High-quality collaborative support.

**Use Case 3: Re-engaging Inactive Leads**
- **Situation:** A marketing lead hasn't responded in 48 hours. The 24-hour customer service window is closed (meaning free-form messages are blocked by Meta).
- **How the feature is used:** The sales agent selects a pre-approved template "follow_up_promo", fills in the placeholders (e.g. `{{1}}` for name, `{{2}}` for the discount code), and sends it.
- **Customer experience:** The customer receives a structured notification on their phone.
- **Business outcome:** Legal compliance with Meta's messaging policies while maintaining lead engagement.

## 9. Industry Use Cases
- **E-Commerce:** Agents use the inbox to quickly send product photos, resolve tracking issues, and close abandoned carts.
- **Logistics:** Drivers and dispatchers send voice notes to coordinate drop-off locations when hands-free communication is required.
- **SaaS:** Support teams use internal notes and agent assignments to route bugs to engineers.

## 10. Real Customer Example
A travel agency has 5 agents. An inbound message arrives: "My flight is cancelled!" The conversation is immediately flagged as an SLA Breach because it contains urgent keywords. The supervisor sees this, selects the chat, and assigns it to "Agent John" who specializes in flights. John posts an internal note: "Handling flight replacement now." He records a 10-second voice note telling the customer their options. Once the customer selects an option, John sends the new boarding pass as a PDF, closes the ticket, and the customer automatically receives a CSAT survey asking them to rate John's help.

## 11. Customer Journey
Customer messages &rarr; Webhook receives &rarr; Inbox alerts agents &rarr; Agent claims chat &rarr; Conversation marked as read &rarr; Agent replies with text/voice/media/templates &rarr; Chat resolved &rarr; Chat closed &rarr; CSAT survey sent.

## 12. Inputs
- Inbound WhatsApp webhook messages.
- Text input in the composer.
- Files (images, videos, audio, documents).
- Browser audio recordings.
- Template variables.
- Assignment changes.

## 13. Outputs
- Outbound WhatsApp messages sent to Meta.
- Webhook Read Receipt notifications.
- Converted audio files (Ogg/Opus).
- Triggered CSAT surveys.
- CSAT records.

## 14. Dependencies
- **Meta Graph API:** For sending messages and templates, and setting read receipts.
- **FFmpeg:** Needed on the server to transcode WebM audio recordings to Ogg/Opus.
- **S3 / Cloud Storage:** To store and serve media attachments.
- **Pusher / WebSockets:** Used to sync the dashboard state in real-time.

## 15. Permissions
- **Who can access this page:** Users with `chat-access` permission on a plan that includes `chat`.
- **Who can view information:** Agents/Admins.
- **Who can edit:** Assigned agents and Admins.
- **Who cannot access it:** Guest users or users lacking `chat-access`.

## 16. Important Rules
- Outbound free-form messages can only be sent within 24 hours of the customer's last message. Outside this window, agents MUST use a Meta-approved template.
- File uploads are validated to a maximum of 16MB to stay within Meta's upload limits.
- Closing a conversation triggers a CSAT survey (if configured).

## 17. Common Problems
- **Problem:** Voice notes fail to send or show an error.
  - **Possible reason:** FFmpeg is not installed or configured correctly on the hosting server.
  - **What the user should do:** Contact their systems administrator to install FFmpeg or set the correct `FFMPEG_PATH` environment variable.
- **Problem:** WhatsApp "blue ticks" aren't showing up for the customer.
  - **Possible reason:** "Read Receipts" are disabled in the inbox settings, or Meta's API is experiencing latency.
  - **What the user should do:** Check the Team's Inbox Settings to ensure read receipts are toggled on.
- **Problem:** Outbound messages get stuck in the "queued" status.
  - **Possible reason:** The background queue worker (`queue:work`) is down, or the WhatsApp access token has expired.
  - **What the user should do:** Run Setup Diagnostics in the Account Hub or contact system admin to verify Laravel queue workers are running.

## 18. Simple Explanation for Sales
The Chat Inbox is the command center for your agents. It pulls all customer messages into one screen, allows you to assign conversations, share files, send voice notes, and use templates. It's built to keep your sales and support conversations fast, organized, and collaborative.

## 19. Simple Explanation for Marketing
This is where your marketing campaigns convert. When customer broadcasts prompt replies, those replies route here. Your sales reps can instantly answer queries, send rich product catalogs, and guide leads to conversion.

## 20. Simple Explanation for Support
Manage all your chats in one place. You can filter by unassigned tickets, assign chats to specific reps, and drop internal notes for team collaboration. If you need to send a policy document or record a voice explanation, it's all built directly into the composer.

## 21. Related Features
- [WhatsApp Configuration](./whatsapp-setup.md)
- [Canned Responses](./canned-messages.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/chat`
- **Implementation:** `App\Livewire\Chat\ChatDashboard`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Chat/ChatDashboard.php`
  - `resources/views/livewire/chat/chat-dashboard.blade.php`
  - `app/Livewire/Chat/ConversationList.php`
  - `resources/views/livewire/chat/conversation-list.blade.php`
  - `app/Livewire/Chat/MessageWindow.php`
  - `resources/views/livewire/chat/message-window.blade.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
