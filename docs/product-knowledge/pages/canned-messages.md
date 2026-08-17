# Canned Responses Management

## 1. What is this page?
The Canned Responses page allows businesses to create, edit, and manage a library of pre-written text snippets that agents can quickly insert into live chats using short commands (shortcuts).

## 2. Why is this page useful?
Support agents often answer the same questions dozens of times a day.
- **Why do users need it?** To standardize communication, eliminate typos, and drastically speed up agent response times.
- **What work does it make easier?** It removes the need for agents to maintain their own personal "copy-paste" documents of frequently used answers.
- **What business process does it support?** Customer Support Operations and Agent Productivity (lowering Average Handle Time - AHT).
- **What happens without it?** Agents waste valuable time typing out the same explanations over and over, leading to slower resolution times and inconsistent messaging across the support team.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin / Support Manager | To curate the official list of approved responses and ensure company policies are communicated accurately. |
| (Agents) | *Agents do not use this management page directly, but they are the primary consumers of the data created here when they are in the Chat interface.* |

## 4. What can users do here?
- Search the existing library of canned messages by shortcut or content.
- Create new canned responses by defining a `shortcut` and the full `content`.
- Edit existing canned responses to update outdated information.
- Delete canned responses that are no longer needed.

## 5. What is involved?
- **Shortcuts:** A short, memorable keyword (e.g., `pricing`, `hello`, `address`).
- **Content:** The full text string that will replace the shortcut when triggered.
- **CannedMessage Model:** The database table storing these records, scoped to the specific business (Team).

## 6. How does it work?
1. The Support Manager navigates to the Canned Responses page.
2. They click "Create Response" to open the creation modal.
3. They enter a shortcut: `refund_policy`.
4. They enter the content: "We offer a 30-day money-back guarantee on all unused items. Please provide your order number to begin the return process."
5. They click "Save Response".
6. Later, when an agent is talking to a customer in the Inbox, the agent types `/refund_policy` into the chat composer, and the system instantly expands it into the full paragraph.

## 7. What happens behind the scenes?
- **Duplicate Prevention:** The backend checks the `CannedMessage` table to ensure that no two shortcuts are identical within the same Team. If a manager tries to create a duplicate shortcut, the system returns an error.
- **Real-time Search:** As the user types in the search bar, Livewire dynamically filters the Eloquent query to find matches in either the shortcut or the message content, updating the UI instantly without a full page reload.

## 8. Business Use Cases

**Use Case 1: Standardizing Company Policies**
- **Situation:** A company updates its shipping policy and needs all 50 support agents to start communicating the new timelines immediately.
- **How the feature is used:** The Support Manager goes to this page, edits the existing `/shipping` canned response, updates the text, and saves it.
- **Customer experience:** Customers receive accurate, up-to-date information regardless of which agent they speak to.
- **Business outcome:** Prevents misinformation and customer complaints caused by agents relying on old memory.

**Use Case 2: Handling High-Volume Inquiries**
- **Situation:** An e-commerce store runs a massive Black Friday sale and expects hundreds of people asking "Where is my order?".
- **How the feature is used:** Before the sale, the manager creates a `/wismo` (Where Is My Order) shortcut containing a polite apology for delays and instructions on how to use the tracking portal.
- **Customer experience:** Customers receive immediate, professional replies during peak times.
- **Business outcome:** Agents can handle a much higher volume of concurrent chats because they aren't bogged down typing long explanations.

**Use Case 3: Complex Technical Support**
- **Situation:** A software company frequently asks customers to find their router's IP address.
- **How the feature is used:** The manager creates a `/find_ip` shortcut containing a multi-step, bulleted instructional guide.
- **Customer experience:** The customer receives a clear, perfectly formatted instructional guide rather than a hasty explanation.
- **Business outcome:** Higher First Contact Resolution (FCR) rate.

## 9. Industry Use Cases
- **Real Estate:** Agents use `/directions` to quickly send parking instructions for their office.
- **Healthcare:** Clinics use `/intake` to ask new patients to fill out a specific secure web form before their appointment.
- **SaaS:** Support teams use `/bug` to acknowledge a reported software issue and promise to notify the customer when it's patched.

## 10. Real Customer Example
A boutique travel agency has three agents. The manager realizes agents are spending 5 minutes per chat typing out the visa requirements for their most popular destination, Bali. The manager creates a canned response with the shortcut `balivisa` containing all the latest government links and requirements. Now, agents answer the visa question in 2 seconds, freeing them up to focus on up-selling tour packages.

## 11. Customer Journey
Manager identifies a frequently asked question &rarr; Manager creates a Canned Response on this page &rarr; Agent encounters the question in the inbox &rarr; Agent types `/shortcut` &rarr; Customer receives a fast, accurate reply.

## 12. Inputs
- Shortcut string (e.g., "intro")
- Message Content (up to 1000 characters)
- Search query

## 13. Outputs
- Saved `CannedMessage` record in the database.
- A paginated table view of existing responses.

## 14. Dependencies
- **CannedMessage Model:** The underlying database structure.
- **Chat Inbox:** The actual place where these shortcuts are utilized by end-users.

## 15. Permissions
- **Who can access this page:** Only users with `manage-settings` permission (Admins/Managers).
- **Who can view information:** Admins/Managers (Agents view them indirectly via the chat composer).
- **Who can edit:** Admins/Managers.
- **Who cannot access it:** Standard agents.

## 16. Important Rules
- Shortcuts must be unique per team.
- Shortcuts are typically used with a leading forward slash (`/`) in the chat composer, though the slash is automatically prepended visually on this management page.
- Content is limited to 1000 characters.

## 17. Common Problems
- **Problem:** An agent complains that typing a shortcut does nothing in the chat.
  - **Possible reason:** The shortcut was deleted, the agent is typing it wrong, or they aren't using the `/` trigger prefix.
  - **What the user should do:** Use the search bar on this page to verify the shortcut exists and check its exact spelling.
- **Problem:** I get an error saying "This shortcut is already used."
  - **Possible reason:** Another manager already created a canned response with that exact shortcut name.
  - **What the user should do:** Search for the existing shortcut and edit it, or pick a different shortcut name (e.g., use `policy_refund` instead of `refund`).

## 18. Simple Explanation for Sales
The Canned Responses page is your team's phrasebook. It lets you pre-write perfect answers to your most common questions. When customers ask those questions, your agents just type a quick shortcut and the full answer instantly appears, making your team incredibly fast and consistent.

## 19. Simple Explanation for Marketing
This tool ensures your brand voice remains consistent across all support interactions. You can pre-write the exact messaging for promotions or company policies, guaranteeing that every customer receives the exact approved wording you desire.

## 20. Simple Explanation for Support
Stop typing the same answers over and over. Use this page to build a library of shortcuts. Instead of typing a whole paragraph explaining your return policy, just create a shortcut called `/returns`. Next time someone asks, type `/returns` in the chat, hit enter, and you're done!

## 21. Related Features
- [Inbox Settings](./inbox-settings.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/settings/canned-messages`
- **Implementation:** `App\Livewire\Settings\CannedMessageManager`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Settings/CannedMessageManager.php`
  - `resources/views/livewire/settings/canned-message-manager.blade.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
