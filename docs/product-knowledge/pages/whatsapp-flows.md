# WhatsApp Flows (Smart Forms)

## 1. What is this page?
The WhatsApp Flows (Smart Forms) page is the form designer and integration dashboard of the platform. Located at `/flows`, it allows managers to create, synchronize, and delete native interactive forms that open directly inside customer WhatsApp chats.

## 2. Why is this page useful?
Collecting structured information (like booking dates, email addresses, or product choices) via standard back-and-forth text messages is clunky and results in drop-offs.
- **Why do users need it?** To capture details using native smartphone input sheets (dropdowns, calendar pickers, text inputs, checkboxes) directly inside WhatsApp without redirecting the customer to an external website.
- **What work does it make easier?** It organizes forms, syncs schemas with Meta's servers, and checks for template links before allowing deletions to prevent breaking live campaigns.
- **What business process does it support?** Interactive Lead Intake, Appointment Booking, Customer Surveys, and Chat-based Account Verification.
- **What happens without it?** Businesses must link to external websites or ask questions sequentially in chat, reducing conversion rates.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To authorize API tokens, connect database endpoints, and sync layouts with Meta's developer suite. |
| Marketing / Support Manager | To design booking sheets, create satisfaction surveys, and build sign-up forms. |

## 4. What can users do here?
- **Flow Inventory Dashboard:**
  - View all forms with Name, Status (DRAFT, PUBLISHED), Meta ID, and Category.
  - Sync with Facebook: Imports existing flows and their associated screen schemas from Meta Graph APIs.
  - Delete forms (blocks deletion with an warning error if the form is linked inside active templates).
- **Create Flow (Modal):**
  - Define Form Name.
  - Select Category (e.g., SIGN_UP, BOOKING, OTHER).
  - Toggle **Dynamic Data (uses_data_endpoint):** Enables the form to communicate with your backend servers in real-time (e.g. checking live appointment slots or validating user accounts during input steps).
- **Edit Design:** Launch the **Flow Builder (`/flows/builder/{id}`)** to build visual screens and input elements.

## 5. What is involved?
- **WhatsAppFlow Model:** Stores form parameters, statuses, properties, and the JSON layout representation (`design_data`).
- **WhatsappTemplate Model:** Scanned for flow ID linkages to prevent deletion of in-use forms.
- **WhatsAppFlowService:** Connects to Meta's flow configuration endpoints to create, fetch, update, and download layouts.

## 6. How does it work?
1. A manager wants to collect bookings. They go to `/flows` and click "Create Flow".
2. They name it `haircut_booking`, select category `BOOKING`, and toggle on "Dynamic Data" to query live calendars.
3. Once created, they design input screens: Screen 1 (Name & Service dropdown) &rarr; Screen 2 (Calendar picker).
4. They publish the flow to Meta.
5. In the Template Creator, they add the published Flow ID to a template button.
6. When sent to a customer, clicking the template button slides open a native layout. The customer picks their slot and submits. The platform registers their booking instantly in the background.

## 7. What happens behind the scenes?
- **Schema Mapping & Sync:** Clicking "Sync with Facebook" fetches flow listings from Meta. For each flow, it queries Meta's layout endpoint (`getFlowJson()`), parses the schema, and normalizes it into the database (`convertMetaToInternal()`), ensuring the local builder matches what's deployed on Meta.
- **Active Dependency Verification:** Before deleting a flow, the system scans the `WhatsappTemplate` database table. If any approved template contains the flow's ID in its components JSON, the deletion halts and displays an error message, protecting live campaigns from breaking.
- **Data Exchange Webhooks:** If "Dynamic Data" is enabled, customer actions inside the form (like picking a date) trigger secure POST requests to the platform. The server calculates results (e.g. checking slots in the database) and returns options to display in the customer's form instantly.

## 8. Business Use Cases

**Use Case 1: Booking Appointments**
- **Situation:** A clinic wants patients to book appointments without calling support.
- **How the feature is used:** They create a booking flow with calendar and service dropdowns, and link it to their welcome templates.
- **Customer experience:** Patients tap the message button, pick their time in the form, and get confirmed instantly.
- **Business outcome:** Reduced support calls and higher booking rates.

**Use Case 2: Lead Generation Forms**
- **Situation:** A sales team wants to qualify inbound leads before routing them to agents.
- **How the feature is used:** They set up a sign-up form to collect company name, budget, and email, and send it when leads trigger pricing keywords.
- **Customer experience:** Leads complete the simple form directly in WhatsApp.
- **Business outcome:** High-volume lead capture with zero manual entry.

**Use Case 3: Customer Satisfaction Feedback**
- **Situation:** A service brand wants to gather detailed feedback after resolving issues.
- **How the feature is used:** They build a multi-page survey (rating stars, feedback textbox) and trigger it on ticket closure.
- **Customer experience:** Customers submit reviews easily within their chat thread.
- **Business outcome:** High feedback response rates.

## 9. Industry Use Cases
- **Retail:** Gathering return details and tracking numbers for exchange requests.
- **Finance:** Building loan calculators that verify client incomes.
- **Logistics:** Collecting delivery addresses and reschedule date options.

## 10. Real Customer Example
A dental clinic creates a flow named `appointment_picker`. They toggle on Dynamic Data so patients can see real-time schedules. Once published, they map the flow to a template button. When clients trigger the bot, they fill out their details and pick a slot. The clinic database updates the slot, and the patient receives a confirmation card. If the clinic tries to delete the flow, the dashboard blocks it because it is active in their booking campaign templates.

## 11. Customer Journey
Admin creates flow layout &rarr; Sets dynamic endpoint checks &rarr; Designs form pages &rarr; Syncs with Meta Graph &rarr; Links flow to templates &rarr; Customer fills form in chat.

## 12. Inputs
- Form name and category.
- Dynamic data toggle.
- Target templates lookup.
- Screen JSON layouts.

## 13. Outputs
- Saved `WhatsAppFlow` templates.
- Synchronized Meta schemas.
- Completed customer form submissions.

## 14. Dependencies
- **WhatsAppFlow & WhatsappTemplate Models:** DB tables.
- **WhatsAppFlowService:** API connector.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission on plans including `flows`.
- **Who can view information:** Marketers/Admins.
- **Who can edit:** Marketers/Admins.
- **Who cannot access it:** Standard support agents.

## 16. Important Rules
- You cannot delete a flow if it is active inside approved templates.
- Flow names must be unique.

## 17. Common Problems
- **Problem:** "Cannot delete Flow" warning.
  - **Possible reason:** The flow ID is currently linked in active message templates.
  - **What the user should do:** Edit your templates, remove the flow button reference, and retry deletion.
- **Problem:** Forms show blank pages when opened on customer phones.
  - **Possible reason:** The flow schema is in "Draft" status on Meta, or contains formatting errors.
  - **What the user should do:** Open the builder, check that the status is set to "PUBLISHED" on Meta, and test it in the simulator.

## 18. Simple Explanation for Sales
WhatsApp Flows are smart forms that open inside the chat. Customers can book times, fill surveys, or input details in a clean form layout without leaving their WhatsApp app.

## 19. Simple Explanation for Marketing
Create native interactive forms. Gather emails, customer ratings, or booking dates using calendars and dropdowns, and sync the designs with Meta instantly.

## 20. Simple Explanation for Support
If you want to gather customer details before starting a chat, use the flows page to design a form that collects names and emails before the chat reaches your queue.

## 21. Related Features
- [WhatsApp Templates](./templates.md)
- [Automations Builder](./automation-builder.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/flows`
- **Implementation:** `App\Livewire\Flows\FlowManager`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Flows/FlowManager.php`
  - `app/Models/WhatsAppFlow.php`
  - `app/Services/WhatsAppFlowService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
