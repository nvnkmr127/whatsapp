# Automations & Flow Builder

## 1. What is this page?
The Automations & Flow Builder module is the automated logic and conversation designer of the platform. It consists of the **Automations Dashboard (`/automations`)** for managing active bots, the **Visual Flow Builder (`/automations/builder`)** for drag-and-drop workflow configuration, and **Flow Templates** for pre-packaged industry solutions.

## 2. Why is this page useful?
Manually responding to repetitive questions, qualifying leads, or coordinating reminders drains agent hours.
- **Why do users need it?** To build automated interactive WhatsApp chatbots, schedule message drip campaigns, automate CRM task updates, and construct dynamic AI-driven responders (RAG) that work 24/7.
- **What work does it make easier?** It visualizes complex programming logic as a node diagram (edges, conditions, actions) that any marketer or admin can edit without writing code.
- **What business process does it support?** Customer Support Triaging, Marketing Automation, CRM Lead Routing, and AI Chatbot Deflection.
- **What happens without it?** Support agents are overwhelmed by repetitive queries, leads go cold during off-hours, and transactional confirmations (like order updates) must be triggered manually.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To configure complex API webhooks, publish major workflow versions, review bot run logs, and allocate AI Knowledge Bases. |
| Marketing Manager | To create promotional drip campaigns, build NPS review surveys, and optimize trigger keywords. |
| Support Manager | To set up automated triage flows that route complex questions to live human support agents (handovers). |

## 4. What can users do here?
- **Automations Listing Dashboard (`/automations`):**
  - Search automations by name.
  - Track metrics: **Total Automations**, **Active Bots**, **Total Runs** (execution counts), and **Completion Rate** percentage.
  - Duplicate workflows (creates a copy, disabled by default for safety).
  - Export workflows as JSON files for backups or cross-team sharing.
  - Delete unused automations.
- **Visual Flow Builder (`/automations/builder`):**
  - **Configure Triggers:** Define how the flow starts (e.g. matching keywords, regex queries, new conversation arrivals, tag additions, or cron schedules).
  - **Drag and Connect Nodes:** Add actions and connect them with arrows (edges).
  - **Node Editing Inspector:** Configure specific properties for selected steps (typing simulators, wait times, variables, operators).
  - **Compliance Risk Profile Analyzer:** Evaluates flow structures and displays warnings (e.g. warning if a trigger targets every incoming message, or if a webhook depends on external APIs).
  - **Publishing & Version Control:** Write release notes, publish updates, view version histories, and roll back the editor layout to previous versions.
  - **Testing Simulator:** Test the node paths with selected test contacts before going live.
- **Select Flow Templates:**
  - Launch pre-built flows categorized by Industry (E-Commerce, Real Estate, Healthcare, Hospitality, Education, Fitness, Automotive, Beauty) and Use Case (Sales, Notifications, Feedback, Onboarding).

## 5. What is involved?
- **Automation Model:** Stores workflow metadata, trigger configs, versions, and JSON representations of node grids and connections (`flow_data`).
- **AutomationRun Model:** Database execution logs mapping individual contact paths, active node steps, and run statuses (completed, failed).
- **TemplateLibrary Service:** Houses the library of predefined flow nodes and edges.
- **Livewire Components:** `AutomationList` (dashboard index), `AutomationBuilder` (node editor), and `AutomationAnalytics` (reporting dashboard).

## 6. How does it work?
1. An admin wants to automate customer feedback. They go to `/automations` and click "Create".
2. They select the "NPS Survey Flow" template from the library.
3. The Flow Builder opens, displaying a start node connected to an introductory message, a text collection step, a logic split (condition), and two routing branches.
4. The admin edits the text collection node, setting it to save the response to a CRM field named `nps_score`.
5. They verify the condition splits: if `nps_score >= 9`, route to a Google review link node; else, route to a ticket creator.
6. They test the flow in the simulator. Once confirmed, they click "Publish" to save version 1.
7. They toggle the active switch. Whenever a customer triggers the keyword "feedback", the bot executes this exact sequence.

## 7. What happens behind the scenes?
- **Node Execution Loop:** When a customer sends a message matching a keyword trigger, the workflow engine starts a new transaction in the `AutomationRun` table. It walks from the Start node down the active edges. When it hits a `delay` node, it pauses execution and registers a queue job to wake up and resume the run when the duration expires.
- **Interactive Multi-Product Messages (Catalog Node):** This node packages inventory IDs (e.g. SKU-001) into a WhatsApp Interactive Catalog payload. Meta renders this as a mini-store display inside the customer's chat. When the customer clicks a product, Meta returns the product ID to the builder, letting the flow route execution down matching branches.
- **RAG & AI Deflection (OpenAI Node):** The builder passes the customer's message to the OpenAI API along with matching text chunks retrieved from selected Knowledge Base sources. The AI generates a contextual answer and passes it back to the text node to reply to the user.

## 8. Node Specifications
| Node Type | What it does |
|---|---|
| **Text** | Sends a WhatsApp message. Supports variable replacement (`{{name}}`) and typing simulators. |
| **Delay** | Pauses the workflow path for a specific duration (seconds, minutes, hours, days). |
| **User Input** | Asks a question and saves the response into a custom attribute. |
| **Condition** | Splits paths using logic comparison rules (e.g. `contains`, `equals`, `greater than`). |
| **Update Contact** | Sets or edits database attributes on the contact. |
| **Tag Contact** | Adds or removes labels from the contact record. |
| **Create Deal** | Generates a sales opportunity card in the Pipeline dashboard. |
| **Assign to Agent** | Assigns the chat ownership to support staff (supports round-robin routing). |
| **Handover** | Suspends bot executions on the thread, routing the customer to the live inbox queue. |
| **Catalog Message** | Sends interactive multi-product store views. |
| **OpenAI (AI Agent)** | Uses LLMs (GPT-4o) and Knowledge Base text search (RAG) to generate replies. |
| **Webhook** | Fires an HTTP request (POST/GET) to external APIs. |

## 9. Business Use Cases

**Use Case 1: E-Commerce Abandoned Cart Recovery**
- **Situation:** Customers add items to their shopping cart but leave before checking out.
- **How the feature is used:** The "Abandoned Cart Recovery" flow triggers when a cart is abandoned. It waits 1 hour (delay node), checks if the order is still unpaid, and sends a discount code.
- **Customer experience:** The customer receives a helpful message 1 hour later with a link to checkout instantly.
- **Business outcome:** Recovers lost sales and boosts cart conversions.

**Use Case 2: Out-of-Hours Triage**
- **Situation:** Customers message the business at 11:00 PM when no support agents are online.
- **How the feature is used:** An automation triggers on all new messages. It uses a condition node to check business hours. If offline, it replies with an away message and opens an internal support ticket.
- **Customer experience:** The customer gets immediate confirmation that their query is logged.
- **Business outcome:** Protects response time SLAs and manages customer expectations.

**Use Case 3: Customer Satisfaction Feedback (CSAT)**
- **Situation:** A support manager wants to measure team performance after tickets are resolved.
- **How the feature is used:** When a ticket status updates to "resolved", the flow fires, waits 10 minutes, and asks the customer to rate their experience from 1 to 5.
- **Customer experience:** The customer easily taps a quick reply button to leave their score.
- **Business outcome:** High-volume, structured feedback loops logged directly to customer records.

## 10. Real Customer Example
A wellness center launches an automation called `appointment_followup`. It triggers whenever a booking is completed. First, it tags the contact as "active-customer". Then, it delays for 24 hours. The next day, it sends a text: "How was your session? Tap below." It provides two buttons: "Loved it" and "Could be better". If they tap "Loved it", it routes to an AI assistant that suggests booking their next session. If they tap "Could be better", it triggers a Handover node, moving the thread to a supervisor's inbox.

## 11. Customer Journey
Trigger event occurs &rarr; Automation run initialized &rarr; Node sequence starts walking &rarr; Delays and inputs handled dynamically &rarr; Logic splits route paths &rarr; Handover to human agent or run completed.

## 12. Inputs
- Trigger settings (keywords, regex patterns, schedules).
- Visual node graph configurations.
- Node attribute data (text strings, delays, conditions, agents).
- Version publication notes.
- Test contact numbers.

## 13. Outputs
- Saved workflow logic structures in DB.
- Exported `.json` automation schemas.
- Active message outboxes, tags, and pipeline updates.
- Execution history audit logs.

## 14. Dependencies
- **Automation & AutomationRun Models:** Persistence layout.
- **Contact & ContactTag Models:** Target profiles.
- **OpenAI & Meta API Interfaces:** Dynamic actions.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission on plans including `automations`.
- **Who can view information:** Marketers/Admins/Support Managers.
- **Who can edit:** Marketers/Admins/Support Managers.
- **Who cannot access it:** Standard support agents (preventing workflow edits).

## 16. Important Rules
- You cannot publish a flow with loose nodes (every node must connect to a parent path starting at the Trigger).
- Flow changes are drafts until you click "Publish".
- Hard-deleting an automation deletes all historical run logs associated with it.

## 17. Common Problems
- **Problem:** Keyword trigger isn't starting the automation.
  - **Possible reason:** The automation is paused (inactive), the keyword doesn't match the customer message casing/format, or another automation triggered first.
  - **What the user should do:** Go to `/automations` and verify the status switch is green (Active). If using strict keywords, check for typos or switch to regex mode.
- **Problem:** AI OpenAI node returns blank answers or says "source missing".
  - **Possible reason:** The selected Knowledge Base source is still indexing, or has been deleted.
  - **What the user should do:** Open the OpenAI node in the builder, check that a valid, indexed Knowledge Base source is selected under the RAG config, and ensure your OpenAI API credits are active.

## 18. Simple Explanation for Sales
Automations allow you to build automated chat journeys. You can design custom step-by-step guides using a drag-and-drop builder, collect customer data, ask questions, run AI helpers, and seamlessly pass the conversation to a human rep when needed.

## 19. Simple Explanation for Marketing
Create automated broadcast follow-ups and survey loops without writing code. Use ready-made templates for newsletters, cart recoveries, and NPS rating requests, connect steps with simple arrows, and publish versions instantly.

## 20. Simple Explanation for Support
Automations do the heavy lifting. The bot can qualify lead budgets, answer common FAQs using an AI Knowledge Base, and only alert you (handover) when the client requests human assistance or has a complex issue.

## 21. Related Features
- [Audience Center](./contacts.md)
- [Inbox Settings](./inbox-settings.md)
- [Campaign Creator](./campaign-wizard.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/automations` & `/automations/builder`
- **Implementation:** `App\Livewire\Automations\AutomationList` & `App\Livewire\Automations\AutomationBuilder`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Automations/AutomationList.php`
  - `app/Livewire/Automations/AutomationBuilder.php`
  - `app/Models/Automation.php`
  - `app/Models/AutomationRun.php`
  - `app/Services/Automations/TemplateLibrary.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
