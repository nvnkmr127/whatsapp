# Flow Templates

## 1. What is this page?
Flow Templates are pre-configured message structures, delay times, and logical rules categorized by industry and use case. They are available inside the Automations and Flow Builder workspace, enabling businesses to deploy automated WhatsApp campaigns and chatbots instantly without building them from scratch.

## 2. Why is this page useful?
Designing a multi-step automation workflow (like cart recoveries or patient reminders) requires careful planning of delays, message wording, and CRM tags.
- **Why do users need it?** To leverage proven conversation patterns and industry best practices immediately, reducing configuration time.
- **What work does it make easier?** It loads a complete set of connected nodes (triggers, texts, delays, input fields) in a single click, which only need basic customization (like replacing links).
- **What business process does it support?** Rapid Bot Deployment, Campaign Standardisation, and Customer Journey Mapping.
- **What happens without it?** Teams must design and connect every node manually, leading to trial-and-error setups, configuration mistakes, and slow deployments.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Marketing Manager | To quickly launch e-commerce campaigns, surveys, and promotional reminders using standard templates. |
| Support Manager | To set up ticket confirmation flows and CSAT surveys using pre-built templates. |

## 4. What can users do here?
- **Browse Templates by Industry:** Select from standard categories:
  - **Ecommerce:** Shopping cart recoveries, shipping updates, loyalty signups, review requests.
  - **Real Estate:** Lead qualification, viewing schedules, rent reminders, valuation queries.
  - **Healthcare:** Appointment reminders, prescription ready alerts, weekly health tips.
  - **Hospitality & Travel:** Table bookings, guest surveys, flight/hotel confirmations.
  - **Education:** Course welcomes, exam alerts, parent bookings, attendance warnings.
  - **Fitness:** Class reminders, renewals, training offers.
  - **Automotive:** Service reminders, test drive bookings, delivery milestones.
  - **Beauty:** Booking confirmations, rebooking prompts, birthday promos.
  - **Professional Services:** Consultation requests, invoice follow-ups.
- **Preview Templates:** Review the descriptions, step counts, and nodes involved in a template before loading it into the builder.
- **Instant Flow Initialization:** Import the template structure directly into the Visual Flow Builder canvas.

## 5. What is involved?
- **TemplateLibrary Service (`TemplateLibrary.php`):** The repository housing the arrays of pre-defined node parameters, coordinates ($x, y$), and edge connections for each template key.
- **AutomationBuilder Component:** The parent Livewire workspace that imports, renders, and persists these presets.

## 6. How does it work?
1. The user goes to `/automations` and clicks "New from Template".
2. They browse to the E-Commerce section and select the "Abandoned Cart Recovery" template.
3. They review the flow nodes: Trigger (cart abandoned) &rarr; Delay (wait 1 hour) &rarr; Text (reminder message).
4. They click "Use Template". The system imports these nodes and connections onto their Flow Builder canvas.
5. The user edits the text message to customize their website link, then saves and activates the automation.

## 7. What happens behind the scenes?
- **Coordinate Mapping:** Every template defines specific $x$ and $y$ canvas coordinates for its nodes to ensure they render in an organized, readable structure when loaded.
- **Relational Placeholders:** High-value templates pre-configure default settings, such as naming input variables (e.g. `budget` in Real Estate) or setting trigger configurations.
- **Trait Integration:** The `HasTemplates` trait listens for template selections, copies the preset arrays, generates unique IDs for the nodes/edges, and binds them to the active editor state.

## 8. Selected Template Specifications

### 🛒 Abandoned Cart Recovery (Ecommerce)
- **Nodes:** Trigger (Cart Abandoned) &rarr; Delay (1 Hour) &rarr; Text Message (Recovery discount code).
- **Goal:** Drive shoppers back to complete purchase.

### 🛍️ Product Catalog Showcase (Ecommerce)
- **Nodes:** Trigger (Keyword: "products") &rarr; Text (Welcome) &rarr; Catalog Message (Multi-product display) &rarr; Delay (5 mins) &rarr; Interactive Button &rarr; Condition (Check response) &rarr; Create Deal / Handover.
- **Goal:** Promote products, capture interest, and route high-intent leads to agents.

### 🏠 Lead Qualification (Real Estate)
- **Nodes:** Trigger (Form Submitted) &rarr; User Input (Budget) &rarr; User Input (Location) &rarr; Handover (Assign Agent).
- **Goal:** Qualify property inquiries automatically before notifying sales.

### 📋 NPS Survey Flow (General)
- **Nodes:** Trigger (Survey) &rarr; Text (Intro) &rarr; User Input (Collect Score 0-10) &rarr; Condition (Is Score &ge; 9?) &rarr; Branch A: Text (Promoter Review link) / Branch B: User Input (Detractor Reason) &rarr; Update Contact.
- **Goal:** Track NPS and drive promoters to Google review cards.

### 📅 Property Viewing Scheduler (Real Estate)
- **Nodes:** Trigger (Keyword: "view") &rarr; User Input (Ask Date) &rarr; User Input (Ask Time) &rarr; Text (Confirm Appointment).
- **Goal:** Automate property tour bookings.

## 9. Business Use Cases

**Use Case 1: Deploying a Customer Review Loop**
- **Situation:** A retail brand wants to collect reviews 7 days after delivery.
- **How the feature is used:** They open Flow Templates, select "Product Review Request", and modify the review URL to point to their Trustpilot link.
- **Customer experience:** Customers get a polite review request exactly 7 days after their package arrives.
- **Business outcome:** Increased customer reviews with zero daily manual effort.

**Use Case 2: Standardizing Onboarding Journeys**
- **Situation:** An online school wants to welcome new students and send links to orientation slides.
- **How the feature is used:** They import the "Course Enrollment Welcome" template, connect their database trigger, and customize the welcome text.
- **Customer experience:** Students receive their orientation links immediately upon registration.
- **Business outcome:** High student satisfaction and reduced welcome email support queries.

**Use Case 3: Capturing Valuation Leads**
- **Situation:** A real estate brokerage wants to capture property addresses from Facebook ads.
- **How the feature is used:** They select the "Home Valuation Lead Gen" template and link it to their Facebook Ad Referral trigger.
- **Customer experience:** Ad viewers scan a code and are asked for their home details.
- **Business outcome:** Automated seller lead generation.

## 10. Industry Use Cases
- **Retail:** Using winback templates to target customers who haven't ordered in 90 days.
- **Hospitality:** Sending Wi-Fi details automatically using check-in templates.
- **Healthcare:** Delivering refill reminders using prescription ready templates.

## 11. Customer Journey
Marketer browses template library &rarr; Selects industry/use-case preset &rarr; Workspace populates with visual nodes &rarr; Marketer customizes variables and copy &rarr; Flow saved.

## 12. Inputs
- Selected template key (e.g. `ec_abandoned_cart`).
- Industry/Use-case filter.

## 13. Outputs
- Loaded node coordinates and edges in builder canvas.

## 14. Dependencies
- **TemplateLibrary.php:** Core preset dictionary.
- **HasTemplates Trait:** Loader helper.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission on plans including `automations`.
- **Who can view information:** Marketers/Admins.
- **Who cannot access it:** Standard support agents.

## 16. Important Rules
- Templates must be loaded into a new flow or overwrite a draft layout. You cannot append a template directly onto an active version.

## 17. Common Problems
- **Problem:** Template doesn't load when I click it.
  - **Possible reason:** The template configuration file has formatting syntax errors, or your team plan restricts certain advanced nodes (like OpenAI).
  - **What the user should do:** Reload the page. If the template contains AI nodes, verify your subscription tier supports AI Knowledge Bases.
- **Problem:** The catalog or deal templates are showing validation warnings.
  - **Possible reason:** The template pre-populates variables, but your CRM is missing the corresponding custom tags or deal pipelines.
  - **What the user should do:** Go to CRM settings, ensure the matching tags (e.g., `hot-lead`) or pipeline stages exist, or edit the node to use your own pipeline settings.

## 18. Simple Explanation for Sales
Flow Templates are pre-built chatbot designs. Instead of building customer journeys from scratch, you can pick a template for your industry (like real estate valuation or dental booking), adjust the text, and launch it instantly.

## 19. Simple Explanation for Marketing
Skip the design phase. Browse templates for cart recoveries, surveys, and price drops, select one, customize the details in the visual builder, and activate it.

## 20. Simple Explanation for Support
If you need a feedback loop or appointment reminders, check the templates. They provide pre-built templates for check-ins, confirmations, and reminders that you can deploy with a single click.

## 21. Related Features
- [Automations Index](./automations.md)
- [Visual Flow Builder](./automation-builder.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/automations` (Template library modal)
- **Implementation:** `App\Services\Automations\TemplateLibrary`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Services/Automations/TemplateLibrary.php`
  - `app/Livewire/Automations/Traits/HasTemplates.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
