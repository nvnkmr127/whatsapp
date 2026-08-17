# Chat Routing Settings

## 1. What is this page?
The Chat Routing Settings page is the conversation distribution engine of the platform. Located at `/settings/chat-routing`, it allows administrators to define ticket assignment rules, toggle sticky assignments, configure user round-robin queues, set auto-resolution status rules, and test routing parameters using an assignment simulator.

## 2. Why is this page useful?
Unassigned or incorrectly routed customer chats increase response times and hurt customer satisfaction.
- **Why do users need it?** To automate how incoming customer messages are distributed to support agents, manage team workloads, auto-resolve inactive tickets, and test routing behavior.
- **What work does it make easier?** It runs background triggers that route chats based on tags, sources, or country codes, and provides a simulation modal to test rules before deploying.
- **What business process does it support?** Queue Management, Agent Workload Allocation, and Inactive Chat Auto-Resolution.
- **What happens without it?** All incoming customer conversations fall into a single unassigned list, requiring support leads to manually triage every chat.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To authorize custom routing conditions, manage round-robin agent list toggles, and configure ticket resolution schedules. |
| Customer Support Lead | To monitor agent assignment statuses and test routing rules inside the simulator. |

## 4. What can users do here?
- **Configure Custom Routing Rules:**
  - Create sequential rules containing multiple conditional filters (If contact has a specific Tag, Source, or Phone Prefix).
  - Define assignment targets: Route to a role (Agent, Manager, Admin) or assign directly to a specific team user.
  - Toggle **Sticky Assignment:** Prioritizes routing returning contacts to the agent who handled their previous chat.
- **Manage Round-Robin Queues (User Grid):**
  - Search team members by name or email.
  - Toggle each user's ticket queue status on/off.
  - Review role alerts (shows "Recommended" for Agent roles, and "Role Mismatch" warnings for Admin roles on the assignment list).
  - **Active Ticket Safeguard (Modal):** If an admin toggles off assignment for an agent with unresolved tickets, a warning modal displays their active count, requiring confirmation before disabling.
- **Set Chat Status Rules (Auto-Resolution):**
  - Define rules to automatically update chat statuses based on inactivity (e.g., automatically moving chats from `open` to `resolved` after 3 days of no activity).
- **Run Assignment Simulations (Test Routing):**
  - Input simulated phone numbers, source channels, and tags.
  - Run tests to see which agent would be assigned and which rule matched, without modifying actual live data.

## 5. What is involved?
- **Team Model:** Persists status rules arrays (`chat_status_rules`) and assignment configs (`chat_assignment_config`).
- **Contact Model:** Checked for assignment histories during sticky routing calculations.
- **Team-User Membership Pivot:** Toggles the `receives_tickets` flag for round-robin.
- **AssignmentService:** The engine that runs rules and outputs assignment simulation reports.

## 6. How does it work?
1. The Admin opens `/settings/chat-routing` to automate VIP customer routing.
2. Under "Custom Routing Rules", they click "+ Add Routing Rule".
3. They set the condition: If Tag equals `VIP`.
4. They set the assignment target to "Specific User" and choose their VIP Sales lead.
5. Under Round-Robin, they toggle "receives_tickets" to active for their general support agents.
6. Under "Chat Status Rules", they add a rule: If status is `pending`, after `2` days, set status to `resolved`.
7. They open "Test Routing", input a mock phone number and the tag `VIP`, and run the simulation. The engine outputs: "Assigned Successfully to VIP Sales Lead (Rule 1 Match)."
8. They save. Now, when a customer tagged as `VIP` sends a message, they bypass the general queue and land directly in the VIP lead's inbox.

## 7. What happens behind the scenes?
- **Sticky Assignment Logic:** If sticky routing is active, the engine checks the contact's `assigned_to` database history before running other rules. If an active owner is found, they are assigned the chat. If they are offline or assignment is disabled for them, the engine falls back to standard rules.
- **Sequential Rule Processing:** The engine evaluates routing rules in priority order. If a contact matches Rule 1's criteria, they are assigned and evaluation stops. If not, the engine checks Rule 2, and so on. If no rules match, the contact is routed to the general round-robin support queue.
- **Active Ticket Safeguard:** Toggling off ticket assignment for a user checks for active conversations assigned to their ID. If unresolved tickets exist, the system blocks the immediate toggle and displays the warning modal, preventing tickets from becoming orphaned without the admin's knowledge.

## 8. Business Use Cases

**Use Case 1: Routing Leads by Country Code**
- **Situation:** A business operates in India and Australia, and wants local agents to handle queries from their respective markets.
- **How the feature is used:** They create two rules: Rule 1 matches Phone Prefix `+61` and assigns to the Australia support group; Rule 2 matches Prefix `+91` and assigns to the India group.
- **Customer experience:** Customers automatically reach agents in their local time zone.
- **Business outcome:** Faster localized support.

**Use Case 2: Auto-Resolving Inactive Tickets**
- **Situation:** Agents forget to close resolved tickets, cluttering the dashboard inbox.
- **How the feature is used:** The admin sets a status rule: If chat is `pending` (waiting for user response), after `3` days, auto-resolve.
- **Customer experience:** Inactive chats close automatically, cleaning up their screen layout.
- **Business outcome:** Clean agent workspaces and accurate active ticket reporting.

**Use Case 3: Sticky Agent Assignments**
- **Situation:** High-value clients build relationships with specific agents, and routing them to random agents on repeat visits hurts experience.
- **How the feature is used:** The admin toggles on Sticky Assignment.
- **Customer experience:** Returning customers are automatically reconnected with their previous agent.
- **Business outcome:** Better customer relationships and continuity.

## 9. Industry Use Cases
- **Real Estate:** Routing leads to agents based on property tags.
- **Healthcare:** Routing emergency tags to supervisor user IDs immediately.
- **E-commerce:** Routing chats from webhook order failures to fulfillment managers.

## 10. Real Customer Example
A logistics agency toggles on Sticky Assignment so clients talk to the same dispatcher. They create a custom rule: if tag is `US-Shipping`, route to the US logistics manager. They set up an auto-resolution rule to resolve open chats after 5 days of inactivity. When a US client tags their chat, the simulator shows they route to the US manager. If the admin tries to toggle off ticket assignment for a dispatcher with 10 active tickets, the dashboard displays the active ticket warning modal before proceeding.

## 11. Customer Journey
Admin sets sticky routing toggles &rarr; Defines custom rules based on tags or country codes &rarr; Manages round-robin agent lists &rarr; Configures auto-resolution timers &rarr; Simulates routing rules &rarr; Saves configurations.

## 12. Inputs
- Sticky assignment toggle.
- Rule priority, triggers (tag, source, country prefix), and target agents/roles.
- Agent round-robin toggles.
- Auto-resolution rules (Status, Days, Target Status).
- Simulation parameters (Phone, tags, sources).

## 13. Outputs
- Saved `chat_status_rules` database records.
- Saved `chat_assignment_config` parameters.
- Updated user pivot properties (`receives_tickets`).
- Simulated assignment reports.

## 14. Dependencies
- **Team Model:** Persists settings.
- **Contact Model:** Logs owner histories.
- **AssignmentService:** Evaluates rules.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permissions.
- **Who can view information:** Admins/Managers.
- **Who can edit:** Admins.
- **Who cannot access it:** Standard support agents.

## 16. Important Rules
- Deactivating ticket assignment for an agent does not close their existing active tickets.
- Status rules will only trigger changes for chats matching the exact "Status In" field.

## 17. Common Problems
- **Problem:** Custom routing rules are being ignored.
  - **Possible reason:** Sticky routing is active, overriding custom rules for returning contacts.
  - **What the user should do:** Disable Sticky Assignment to test, or clear the contact's previous owner history in the Audience Center.
- **Problem:** Simulator shows "No Agent Assigned".
  - **Possible reason:** The test parameters did not match any of your custom rules, and no agents are active in the general round-robin queue.
  - **What the user should do:** Toggle "receives_tickets" to active for at least one agent, or create a catch-all fallback rule.

## 18. Simple Explanation for Sales
Chat Routing is how the system distributes customer chats to your team. You can configure rules to route VIPs to specific managers, assign returning customers to the agent they spoke with last, and automatically close inactive chats.

## 19. Simple Explanation for Marketing
Segment and route leads dynamically. You can create rules to route incoming chats to specific sales groups based on customer tags or their country code.

## 20. Simple Explanation for Support
If you need to stop receiving new chats (e.g. going on break), your admin can toggle off your assignment status on this page, while leaving your active chats assigned to you.

## 21. Related Features
- [Inbox Agent Workspace](./chat-dashboard.md)
- [Audience CRM](./contacts.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/settings/chat-routing`
- **Implementation:** `App\Livewire\Settings\ChatRouting`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Settings/ChatRouting.php`
  - `resources/views/livewire/settings/chat-routing.blade.php`
  - `app/Services/AssignmentService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
