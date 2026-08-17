# WhatsApp Flow Builder

## 1. What is this page?
The WhatsApp Flow Builder page is the visual layout designer and schema compiler of the platform. Located at `/flows/builder/{flowId?}`, it allows administrators and designers to configure step-by-step screen flows, add input fields (text inputs, checkboxes, dropdowns, calendars, media uploaders), preview form mockups in a phone simulator, and deploy the validated designs directly to Meta.

## 2. Why is this page useful?
Meta's WhatsApp Flows require a highly structured, strict JSON-based layout schema. Writing this JSON manually is difficult and prone to syntax rejections during API imports.
- **Why do users need it?** To design interactive forms without writing code, using a drag-and-drop style canvas with built-in rule validation.
- **What work does it make easier?** It offers predefined components, handles unique field ID generation, manages version rollback histories, and tests schemas against Meta's guidelines before compiling.
- **What business process does it support?** Interactive Form Engineering, Form Version Management, and Meta Flow Deployment.
- **What happens without it?** Admins would have to write complex JSON files by hand, upload them through Meta developer portals, and debug error codes in terminal logs.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To configure entry points (template, interactive, direct link), manage backend submission webhooks, and deploy published schemas to Meta. |
| Designer / Content Writer | To write form labels, create checkbox options, structure screens, and test layouts in the smartphone simulator. |

## 4. What can users do here?
- **Configure Flow Settings (Header):**
  - Edit internal titles and select flow categories (e.g. Appointment Booking, Survey).
  - Set **After Submit Actions:** Choose what happens to submitted inputs (None/Auto-Capture, Send to Webhook, or External API Sync).
  - Choose **Entry Points:** Toggle allowed entry methods (`template`, `interactive` automation bot, or `direct` link).
  - Toggle **Data Endpoint** connection.
- **Manage Form Steps (Left Sidebar):**
  - Add new screens to create multi-page wizard forms.
  - Switch between screens, rename step IDs, and delete unnecessary steps.
- **Visual Smartphone Canvas (Center Panel):**
  - Review an interactive smartphone simulation of the active screen.
  - Click on elements to select and configure them, or remove them with a single click.
- **Edit Component Properties (Right Sidebar):**
  - **Header & Labels:** Set form headings.
  - **Text Inputs & Areas:** Edit placeholders, names, and toggle "Required Field" rules.
  - **Select lists (Dropdowns, Radios, Checkboxes):** Define option lists, setting display labels and internal database values.
  - **Calendars (DateField):** Configure date selectors.
  - **Media Pickers (Photo & Document Pickers):** Add camera/gallery upload options and restrict file types.
  - **Images:** Add image components by pasting HTTPS image paths and adjusting heights.
  - **Footers:** Customize bottom buttons to trigger "Go to Next Screen" or "Complete & Submit".
- **Restore Historical Versions:**
  - Browse past versions (`WhatsAppFlowVersion` logs).
  - Restore previous schemas to the active builder canvas with one click.
- **Add Component Toolbox:** Select and append new fields onto the current active screen.

## 5. What is involved?
- **WhatsAppFlow Model:** Persists draft configurations, statuses, categories, and screen arrays (`design_data`).
- **WhatsAppFlowVersion Model:** Saves snapshots of published schemas for rollback history auditing.
- **FlowReadinessValidator:** Evaluates form layouts for compatibility issues before allowing Meta deployment.
- **WhatsAppFlowService:** Sends Compiled layout JSONs to Meta API.

## 6. How does it work?
1. The Designer opens a flow named `feedback_survey` inside `/flows/builder/5`.
2. In the Left Sidebar, they select "screen_welcome". They see a TextBody field and a TextInput field ("Your Name") inside the phone simulator.
3. They scroll the Right Sidebar and click "Checkbox". A new checkbox field appears above the footer.
4. They select the checkbox in the simulator. The properties sidebar updates to show properties.
5. They label it "What did you like?" and click "+ Add Option" to insert options: "Speed", "Quality", "Support".
6. In the header, they set After Submit Action to "Send to Webhook" and select their API hook.
7. They click "Save to Meta". The system validates the layout, compiles the JSON, and deploys it to the Meta Business API.

## 7. What happens behind the scenes?
- **Visual ID Safety Rules:** Component IDs (field names) must only contain letters and underscores to align with Meta API rules. The builder automatically generates valid strings (e.g. `rg_aBcdEfGhIj`) to prevent compile rejections.
- **Meta Screen Rules Enforcement:**
  - Max 3 images per screen.
  - Max 1 media picker (Photo or Document Picker) per screen.
  - If these limits are breached, the builder prevents additions and prints a warning alert.
- **Schema Validation & Deployment Flow:** Clicking "Save to Meta" runs the `FlowReadinessValidator`. If formatting warnings are found (e.g. orphaned screens), it saves the local draft but displays a warning notice. If valid, the system calls `updateFlowDesign` on `WhatsAppFlowService`, updates Meta's servers, and publishes the flow, creating a new record in `WhatsAppFlowVersion`.

## 8. Business Use Cases

**Use Case 1: Building a Dynamic Order Search**
- **Situation:** A business wants customers to check order statuses directly in chat by typing their order number.
- **How the feature is used:** They create a flow containing an order search step, and configure the footer to trigger a webhook query.
- **Customer experience:** The customer enters their order ID, clicks search, and the form updates with live shipping details.
- **Business outcome:** Fast automated customer support.

**Use Case 2: Restoring Broken Configurations**
- **Situation:** An admin deploys changes that break a live survey's formatting.
- **How the feature is used:** They open the builder, scroll to the Version History panel on the right, find the stable version from yesterday, and click "Restore".
- **Customer experience:** Customers see the restored, clean form.
- **Business outcome:** Rapid rollback with minimal downtime.

**Use Case 3: Capturing Customer File Uploads**
- **Situation:** A service center needs pictures of broken appliances before dispatching technicians.
- **How the feature is used:** They build a single-screen form containing a "Photo Uploader" element restricted to camera/gallery inputs.
- **Customer experience:** Tapping the form button launches their phone camera, allowing them to snap a photo and submit it.
- **Business outcome:** Rich context captured before dispatching teams.

## 9. Industry Use Cases
- **Insurance:** Building claim filing forms with document uploads.
- **Automotive:** Designing service booking forms.
- **Education:** Designing registration templates.

## 10. Real Customer Example
A dental office designs a 3-step appointment booking flow. Step 1 collects patient name and phone. Step 2 presents a calendar selection. Step 3 asks for comments. The office sets the submit action to hit their CRM webhook. They click deploy, and the flow is registered on Meta. If their receptionist makes an mistake editing the layout, they can roll back to a stable version from the history log.

## 11. Customer Journey
Open designer &rarr; Select step screen &rarr; Add fields & labels &rarr; Configure submit options &rarr; Preview on phone simulator &rarr; Validate layouts &rarr; Deploy to Meta.

## 12. Inputs
- Step titles and IDs.
- Field labels, types, and values.
- Selected validation rules.
- Meta credentials.

## 13. Outputs
- Schema draft saves.
- Compiled Meta JSON layouts.
- Saved version logs.

## 14. Dependencies
- **WhatsAppFlow & Version Models:** DB storage.
- **FlowReadinessValidator:** Visual syntax checks.
- **WhatsAppFlowService:** Meta deployment API.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission on plans including `flows`.
- **Who can view information:** Marketers/Admins.
- **Who can edit/deploy:** Admins.
- **Who cannot access it:** Standard support agents.

## 16. Important Rules
- Each screen is limited to a maximum of 3 images and 1 media picker.
- Field names must contain only letters and underscores.

## 17. Common Problems
- **Problem:** "Cannot deploy: Screen ID missing" error.
  - **Possible reason:** A step screen does not have a unique, valid identifier assigned to it.
  - **What the user should do:** Select the step in the Left Sidebar, look at the Header's "Step Unique ID" field, make sure it is not empty, and save.
- **Problem:** "Only one media picker is allowed" warning.
  - **Possible reason:** You tried to add a Photo Picker and a Document Picker on the same screen.
  - **What the user should do:** Remove one of the pickers, or create a second screen to host the other picker.

## 18. Simple Explanation for Sales
The Flow Builder is a visual tool to design forms for WhatsApp. You can add text fields, calendar pickers, dropdowns, and file upload buttons, preview what they look like on a phone, and publish them with one click.

## 19. Simple Explanation for Marketing
Design interactive flows for your campaigns. Add multi-screen surveys, allow file uploads, test layouts on the phone mockup, and deploy designs directly to Meta without coding.

## 20. Simple Explanation for Support
If customers report errors completing forms, admins can use this builder to check properties, verify field inputs, or restore earlier versions from the history log.

## 21. Related Features
- [WhatsApp Flows Inventory](./whatsapp-flows.md)
- [WhatsApp Templates](./templates.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/flows/builder/{flowId?}`
- **Implementation:** `App\Livewire\Flows\FlowBuilder`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Flows/FlowBuilder.php`
  - `resources/views/livewire/flows/flow-builder.blade.php`
  - `app/Validators/FlowReadinessValidator.php`
  - `app/Models/WhatsAppFlow.php`
  - `app/Models/WhatsAppFlowVersion.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
