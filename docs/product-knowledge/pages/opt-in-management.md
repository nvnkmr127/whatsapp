# Opt-In Management

## 1. What is this page?
The Opt-In Management page allows businesses to configure how their contacts can automatically subscribe (opt-in) or unsubscribe (opt-out) from WhatsApp messages by sending specific keywords.

## 2. Why is this page useful?
WhatsApp strictly enforces anti-spam policies. Businesses must provide a way for customers to stop receiving marketing messages.
- **Why do users need it?** To stay compliant with Meta's messaging policies and local privacy laws by offering an automated opt-out mechanism.
- **What work does it make easier?** It automates subscription management. Instead of an agent manually marking a contact as "Unsubscribed" when they reply "Stop", the system handles it automatically.
- **What business process does it support?** Audience building (via opt-in keywords) and compliance/list hygiene (via opt-out keywords).
- **What happens without it?** A business would accidentally send marketing messages to people who asked to stop, leading to high block rates, a plummeting WhatsApp Quality Rating, and eventual account suspension by Meta.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To set up the default compliance rules during initial account configuration. |
| Marketing Manager | To create custom opt-in keywords for specific marketing campaigns (e.g., "PROMO2026") and set the automatic welcome replies. |

## 4. What can users do here?
- Add or remove specific words that trigger an Opt-In (e.g., START, JOIN, YES).
- Enable and write a custom automatic confirmation message sent when a user opts in.
- Add or remove specific words that trigger an Opt-Out (e.g., STOP, UNSUBSCRIBE, NO).
- Enable and write a custom automatic confirmation message sent when a user opts out.

## 5. What is involved?
- **Keywords:** The specific text strings the system listens for.
- **Confirmation Messages:** Auto-replies sent back to the customer upon a keyword match.
- **Team Settings:** The database where these configuration rules are stored for the business account.

## 6. How does it work?
1. The user navigates to the Opt-In Management page.
2. Under "Subscription", they type a keyword (e.g., "START") and click "Add".
3. They toggle the Confirmation Message switch to "On" and write: "Thanks for subscribing to our updates!"
4. They repeat the process for "Unsubscribe" using a keyword like "STOP".
5. They click "Save Settings".
6. When a customer sends "START" to the business's WhatsApp number, the platform's background webhook processor detects the keyword, marks the contact as opted-in, and automatically sends the configured welcome message.

## 7. What happens behind the scenes?
- **Data Storage:** The keywords and message templates are stored directly on the `Team` model in the database (`opt_in_keywords`, `opt_out_keywords`, `opt_in_message_enabled`, etc.).
- **Message Processing:** This UI only configures the rules. The actual execution happens in the webhook message processor. When an inbound message arrives, the processor checks the message text against these arrays of keywords. If a match is found, it triggers the subscription state change on the `Contact` model and fires the auto-reply.

## 8. Business Use Cases

**Use Case 1: Standard Compliance Setup**
- **Situation:** A business wants to ensure they don't get banned for spamming.
- **How the feature is used:** The admin adds "STOP" and "UNSUBSCRIBE" to the opt-out list. They enable the opt-out message and write: "You have been unsubscribed from all marketing messages. You will only receive critical account updates."
- **Customer experience:** A customer gets annoyed by a marketing blast, replies "STOP", immediately receives the confirmation, and is removed from the broadcast list.
- **Business outcome:** The business avoids a user complaint to Meta, protecting their deliverability rating.

**Use Case 2: In-Store Lead Generation**
- **Situation:** A coffee shop puts a sign on their counter: "WhatsApp the word COFFEE to 555-0199 for a free pastry."
- **How the feature is used:** The marketing manager adds "COFFEE" as an Opt-In keyword. They set the confirmation message to: "Welcome! Show this message at the counter for your free pastry."
- **Customer experience:** The customer texts the word, instantly gets the coupon, and is now subscribed to the coffee shop's weekly specials list.
- **Business outcome:** Rapid, frictionless audience growth without requiring customers to fill out a web form.

**Use Case 3: Multi-Language Support**
- **Situation:** A business operates in Miami and serves both English and Spanish speakers.
- **How the feature is used:** The admin adds "START" and "ALTA" to the opt-in list, and "STOP" and "BAJA" to the opt-out list.
- **Customer experience:** Spanish speakers can text "BAJA" to stop messages naturally, rather than having to guess the English keyword.
- **Business outcome:** Better customer experience and compliance across demographic segments.

## 9. Industry Use Cases
- **Retail:** Using custom opt-in keywords for seasonal sales (e.g., "BLACKFRIDAY").
- **Real Estate:** Putting a sign outside a house saying "Text HOME to receive the floorplan."
- **Healthcare:** Ensuring strict compliance by making it very easy for patients to opt-out of appointment reminders.

## 10. Real Customer Example
A gym wants to build a list of people interested in personal training. They run a Facebook ad saying "Reply TRAIN to get our pricing guide." The marketing manager goes to the Opt-In Management page, adds "TRAIN" as an opt-in keyword, and sets the confirmation message to include a link to the PDF guide. A week later, 50 new people have automatically opted in, received the guide, and are now tagged in the CRM as interested leads, all without a human agent lifting a finger.

## 11. Customer Journey
Admin configures keywords &rarr; Customer sends keyword via WhatsApp &rarr; System detects keyword &rarr; System updates Customer's subscription status &rarr; System sends auto-reply &rarr; Business maintains a clean, compliant contact list.

## 12. Inputs
- Opt-In Keywords (array of strings)
- Opt-Out Keywords (array of strings)
- Opt-In Message toggle (boolean) and text
- Opt-Out Message toggle (boolean) and text

## 13. Outputs
- Saved Team configuration in the database.

## 14. Dependencies
- **Team Model:** To store the configuration.
- **Webhook Processor (Backend):** The backend logic that actually intercepts incoming messages and applies these rules.

## 15. Permissions
- **Who can access this page:** Only users with `manage-settings` permission (Admins).
- **Who can view information:** Admins.
- **Who can edit:** Admins.
- **Who cannot access it:** Standard agents/reps.

## 16. Important Rules
- Keywords are typically processed as case-insensitive by the backend (e.g., "stop", "STOP", and "Stop" will all trigger the rule).
- Keywords must be exact matches of the inbound message text (excluding whitespace). For example, a customer sending "Please stop sending messages" will NOT trigger the keyword "STOP".

## 17. Common Problems
- **Problem:** Customers complain they replied "stop" but still receive broadcasts.
  - **Possible reason:** The admin forgot to add the word "stop" to the Opt-Out keywords list, or the customer sent a full sentence instead of just the keyword.
  - **What the user should do:** Add variations of the keyword to the list, and ensure marketing broadcasts explicitly instruct users to "Reply STOP to unsubscribe".
- **Problem:** The confirmation message isn't sending.
  - **Possible reason:** The keyword is working to unsubscribe them, but the toggle switch for the Confirmation Message is disabled on this page.
  - **What the user should do:** Turn on the toggle switch and click Save.

## 18. Simple Explanation for Sales
The Opt-In Management page handles your compliance automatically. You simply define words like "START" or "STOP", and the system will automatically subscribe or unsubscribe contacts who send those words, protecting your WhatsApp rating from spam complaints.

## 19. Simple Explanation for Marketing
Use this page to create custom keywords for your campaigns. If you're running a promotion, set up a keyword like "PROMO", and the system will automatically welcome anyone who texts that word and add them to your marketing list.

## 20. Simple Explanation for Support
If a customer asks how to stop receiving messages, you can tell them to simply reply with one of the keywords listed on this page (like "STOP"). The system will handle the rest automatically.

## 21. Related Features
- [WhatsApp Configuration](./whatsapp-setup.md)
- [Contacts](./contacts.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/whatsapp/opt-in`
- **Implementation:** `App\Livewire\Teams\OptInManagement`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Teams/OptInManagement.php`
  - `resources/views/livewire/teams/opt-in-management.blade.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
