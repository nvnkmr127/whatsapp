# Visual Flow Builder

## 1. What is this page?
The Visual Flow Builder is the drag-and-drop programming workspace of the platform. Located at `/automations/builder/{id?}`, it allows administrators and marketers to visually map customer journeys, define variables, handle AI responses, and route conversations based on logical conditions.

## 2. Why is this page useful?
Setting up WhatsApp response trees using hard-coded code or complex config files is slow and requires developers.
- **Why do users need it?** To design interactive bots, CRM rules, and messaging delays within a visual canvas.
- **What work does it make easier?** It acts as a visual IDE where users can click to add nodes (steps), drag connections (edges), edit properties, validate errors, and publish versioned flows.
- **What business process does it support?** Interactive Conversation Design, Visual Scripting, and Release Version Management.
- **What happens without it?** Marketing and support teams cannot adjust auto-replies, lead qualification questions, or AI contexts without developer assistance.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To configure advanced steps (webhooks, OpenAI RAG sources) and publish tested configurations. |
| Marketing Manager | To build conversation campaigns, adjust trigger keywords, and schedule follow-up delays. |
| Support Manager | To design support triage trees, setup automated out-of-office responses, and define agent routing logic. |

## 4. What can users do here?
- **Workspace Navigation:** View and zoom/pan around a visual grid canvas containing trigger nodes, action nodes, and connecting arrows (edges).
- **Edit Triggers:** Configure how the flow starts:
  - **Keyword:** Launches on exact or partial matches (supports regex options, tag operations).
  - **User Starts Conversation:** Triggers on any inbound message.
  - **Tag Added:** Fired when a contact is assigned a specific tag.
  - **Scheduled:** Runs on a cron timer.
- **Manage Action Nodes:** Click to create, edit, duplicate, or delete steps:
  - **Text Node:** Sends a WhatsApp message (supports formatting, variables, simulated typing delay).
  - **Delay Node:** Pauses execution (seconds, minutes, hours, days).
  - **User Input Node:** Asks a question and saves the response into a contact attribute.
  - **Condition Node:** Splits paths based on dynamic criteria (e.g., matching tags, score ranges).
  - **Update Contact / Tag Contact Nodes:** CRM data updates.
  - **Create Deal / Create CRM Task Nodes:** Sales opportunity pipelines.
  - **Assign to Agent Node:** Round-robin chat assignments.
  - **Handover Node:** Stop bot executions and route to live support queues.
  - **Catalog Message Node:** Multi-product menus.
  - **OpenAI Node:** GPT-4o powered AI with Knowledge Base support (RAG).
  - **Webhook Node:** Triggers external API calls.
- **Compliance Risk Profile Analyzer:** Review dynamic alerts calculated on the page (e.g. warning if trigger will fire on every message, or if flow is too large).
- **Version Publishing (Modal):** Save a release note, publish a new official version, review the version log, and rollback the workspace to previous layouts.
- **Interactive Simulator:** Test active paths with target profiles before publishing.

## 5. What is involved?
- **Automation Model:** The database row containing the active layout and metadata.
- **HasNodes / HasNodeEditing Traits:** Backend helpers handling node additions, updates, and removals.
- **HasValidation / HasPersistence Traits:** Controls compliance checks, version counts, and DB storage operations.

## 6. How does it work?
1. The user goes to `/automations/builder` and selects a blank template.
2. They drag a "Text" node onto the canvas and connect the "Start" trigger node to it.
3. They click the Text node to open the inspector pane. They enter: "Hi {{name}}! Welcome to our store. How can we help?"
4. They add an "Interactive Button" node containing options: "1. View Products", "2. Talk to Agent".
5. They draw two arrows (edges) from the button node to separate target nodes: a "Catalog Message" node and a "Handover" node.
6. The user clicks "Publish", enters a release note ("v1.0 Release"), and activates the flow.

## 7. What happens behind the scenes?
- **JSON Serialization:** The builder maps the visual canvas coordinates ($x, y$), node properties (labels, values, custom parameters), and edge routes into a structured JSON payload saved in the `flow_data` column.
- **Version Backups:** When a flow is published, the current nodes and edges array are serialized and saved as a new record in the `publish_log` JSON column. If the user clicks "Rollback", the builder replaces the active workspace layout with the matching backup version, letting them restore previous designs instantly.
- **Dynamic Dependency Resolving:** For nodes referencing external components (e.g., Knowledge Base sources, campaigns, email templates, pipelines), the builder uses computed attributes to query current team assets, populating selection dropdowns dynamically.

## 8. Business Use Cases

**Use Case 1: Designing an AI Lead Qualifier**
- **Situation:** A sales team wants to qualify inbound leads using AI and automatically create deals.
- **How the feature is used:** They place an OpenAI node on the canvas, map it to a "Product FAQ" Knowledge Base source, set it to answer queries, and follow it with a "Create Deal" node.
- **Customer experience:** Visitors get detailed product answers from an AI assistant.
- **Business outcome:** High-intent leads are auto-routed to CRM pipelines.

**Use Case 2: Multi-Option Support Tree**
- **Situation:** A support team wants to filter queries before they reach human agents.
- **How the feature is used:** They build an interactive button menu, routing billing queries to account details, and technical issues directly to a Handover node.
- **Customer experience:** Customers quickly navigate options to find the right resource.
- **Business outcome:** Lower response times and structured support routing.

**Use Case 3: Reverting an Error-Prone Flow**
- **Situation:** A newly published template has a broken link in its welcome message.
- **How the feature is used:** The manager opens the version logs in the builder, selects the previous version, and clicks "Rollback".
- **Customer experience:** Customers immediately stop seeing the broken message block.
- **Business outcome:** Rapid incident mitigation without starting from scratch.

## 9. Industry Use Cases
- **Retail:** Building catalog-message trees with checkout redirects.
- **Finance:** Constructing multi-step loops to verify account IDs before revealing balance balances.
- **Logistics:** Setting up tracking-code input fields that call shipping status webhooks.

## 10. Real Customer Example
A local clinic designs a flow called `dentist_booking`. The trigger is the keyword "book". The flow asks for the patient's name, then asks for their preferred date. Next, it routes to a Handover node to notify the receptionist to confirm the slot. The manager tests this in the simulator using their own contact, verifies the edges link correctly, and publishes the version as `v2.0 Stable`.

## 11. Customer Journey
Designer opens builder &rarr; Configures trigger conditions &rarr; Places and connects nodes &rarr; Configures steps in the inspector &rarr; Audits compliance alerts &rarr; Publishes active release version.

## 12. Inputs
- Node types and coordinates.
- Connections (edges).
- Node parameters (text, delays, agents, variables).
- Publish notes.
- Rollback target version number.

## 13. Outputs
- Saved draft `flow_data` JSON.
- Published release log structures.
- Compliance warning diagnostics.

## 14. Dependencies
- **Automation Model:** Core schema.
- **HasPersistence / HasValidation Traits:** Core behaviors.
- **WhatsappTemplate / ContactTag Models:** Dynamic filters.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission on plans including `automations`.
- **Who can view information:** Marketers/Admins.
- **Who can edit:** Marketers/Admins.
- **Who cannot access it:** Standard support agents.

## 16. Important Rules
- You must name the automation before saving.
- If a node is left disconnected from the start trigger path, the linter will block activation.

## 17. Common Problems
- **Problem:** Canvas is locked or nodes cannot be dragged.
  - **Possible reason:** A JavaScript render error occurred, or the current automation ID is invalid/unauthorized.
  - **What the user should do:** Refresh the browser. If the issue persists, verify that the automation belongs to your current team.
- **Problem:** Webhook node is throwing errors during execution.
  - **Possible reason:** The destination URL is incorrect or requires authorization headers that are missing.
  - **What the user should do:** Open the Webhook node, double-check the URL and method (GET/POST), and test it using a test contact.

## 18. Simple Explanation for Sales
The Flow Builder is the control center for your chatbots. It's a drag-and-drop grid where you can place steps like "Send Message", "Wait 1 hour", or "Ask AI", connect them with arrows, and build automated customer support journeys.

## 19. Simple Explanation for Marketing
Visual logic builder for WhatsApp. You can set keywords, add interactive buttons, save client replies, and create advanced conditional campaigns without touching code.

## 20. Simple Explanation for Support
If you need to change how the bot qualifies a ticket or hands over a chat, open the builder, modify the nodes, and publish the new version to deploy updates.

## 21. Related Features
- [Automations Index](./automations.md)
- [Flow Templates](./flow-templates.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/automations/builder/{id?}`
- **Implementation:** `App\Livewire\Automations\AutomationBuilder`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Automations/AutomationBuilder.php`
  - `app/Livewire/Automations/Traits/HasNodes.php`
  - `app/Livewire/Automations/Traits/HasNodeEditing.php`
  - `app/Livewire/Automations/Traits/HasValidation.php`
  - `app/Livewire/Automations/Traits/HasPersistence.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
