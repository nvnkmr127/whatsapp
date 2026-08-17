# Widget Creation Wizard

## 1. What is this page?
The Widget Creation Wizard is the tabbed configuration interface (embedded as a modal on the Growth Tools page) used to design, brand, and set target rules for website floating chat buttons and lead capture cards.

## 2. Why is this page useful?
A generic WhatsApp link doesn't capture visitor leads before redirecting them away from a website, and isn't branded.
- **Why do users need it?** To customize the look, behavior, triggers, and fields of their lead capture popups to match their brand, website layout, and support schedules.
- **What work does it make easier?** It organizes styling, lead fields, and scheduling logic into five clean tabs, while offering a live Desktop/Mobile preview so the user doesn't have to code or publish to test layout modifications.
- **What business process does it support?** Inbound Lead Generation, Web Interface Branding, and Automation Scheduling.
- **What happens without it?** Users would have to code style files, write JavaScript trigger listeners, and manually link fields to database forms to create a functional chat popup on their site.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Marketing Manager | To write default messages, toggle email/name inputs, customize CSS offsets, set display page paths, and edit schedules. |
| Admin | To setup brand colors and audit styling values. |

## 4. What can users do here?
- **Configure Widget Settings across five tabs:**
  - **Button Info Tab:**
    - Set the **Internal Widget Name** for indexing.
    - Customize the **Floating Button Text** (e.g., "Chat with support").
    - Enter the **WhatsApp Pre-filled Message** (text sent automatically when WhatsApp launches).
  - **Content & Forms Tab:**
    - Toggle **Collect Name**, **Collect Email**, and **Collect Phone** checkboxes to prompt visitors for details.
    - Set custom placeholders for the enabled input fields.
    - Write the **Chat Window Welcome Text** (greeting at the top of the popup card).
    - Add **Footer Branding** reference text.
  - **Style & Brand Tab:**
    - Input the **Display Name** and **Subtitle / Status Text** (e.g. "replies in 10m").
    - Enter a **Brand Logo Image URL** for the agent avatar.
    - Select a custom **Theme Color** via a color picker.
    - Adjust **Corner Radius (px)**, **Screen Position** (Bottom Left or Right), and offsets.
    - Toggle screen visibility for Mobile and Desktop devices.
  - **Display Triggers Tab:**
    - Add **Page Targeting Patterns** (e.g. show only on `/pricing` or `/product/*`).
    - Set a **Display Delay** in seconds (e.g. wait 5 seconds before showing).
    - Toggle **Exit Intent Trigger** to open the popup when visitors move their mouse to exit the website.
    - Define an **Availability Schedule** specifying active days and hours.
  - **QR Customization Tab:**
    - Set custom foreground dots and background colors for the widget's QR code.
    - Preview the colored QR code layout.
- **Live Simulator:** Check how the button looks on desktop and mobile mockups, simulating input form steps before saving.

## 5. What is involved?
- **LeadCaptureWidget Model:** Holds the schema attributes.
- **Preview Blade Partial (`preview-widget.blade.php`):** Renders the simulated popup bubble dynamically based on form bindings.
- **WidgetManager Livewire Component:** Controls state resets, edits, validations, and saving.

## 6. How does it work?
1. The marketer clicks "Create New Widget" to open the creation card.
2. Under "Button Info", they type `Demo Button` and set the floating CTA text to `Request Demo`.
3. In "Content & Forms", they toggle "Collect Email" and write a welcome text: "Let's get your demo started!"
4. In "Style & Brand", they choose their brand's color `#4F46E5` (Indigo) and enter their company logo URL.
5. In "Display Triggers", they set a 3-second display delay.
6. The simulator pane displays a mock mobile screen. After 3 seconds, a simulated Indigo button appears. Clicking it shows the logo, welcome header, and email input.
7. Satisfied with the preview, the marketer clicks "Save Widget", registering the configurations.

## 7. What happens behind the scenes?
- **Slug Generation:** When saved, the system slugifies the widget name and appends a random string (e.g. `request-demo-e8f9a`) to serve as the public landing page routing token.
- **Conditional Visibility Scripting:** The display trigger configurations (page targets, delay, exit intent) are compiled into JSON. The JavaScript widget loaded on the website reads this JSON and registers scroll listeners, mouseout events (for exit intent), and current time checks to handle showing or hiding the widget frame.
- **Database Sanitization:** The wizard validates that the name is present and at least 3 characters. Colors are stored in standard hexadecimal format.

## 8. Business Use Cases

**Use Case 1: Displaying Pricing Assistants**
- **Situation:** A marketing team wants to offer assistance to visitors comparing subscriptions on their pricing page.
- **How the feature is used:** They create a widget, set Page Targeting to `/pricing`, set the button text to "Help me choose", and customize theme offsets so it doesn't cover other UI elements.
- **Customer experience:** Visitors on the pricing page get a prompt helping them discuss plans.
- **Business outcome:** High-value pricing page bounces are reduced.

**Use Case 2: Out of Office Auto-Hide**
- **Situation:** A support team has no agents available on weekends and wants to avoid visitors sending messages that won't be replied to immediately.
- **How the feature is used:** Under "Display Triggers", they set the availability schedule to Monday-Friday, 9:00 AM - 5:00 PM.
- **Customer experience:** Over the weekend, the WhatsApp floating button does not render on the website.
- **Business outcome:** Customer frustration from unanswered messages is avoided.

**Use Case 3: Capturing Contact Sheets**
- **Situation:** A lead generation page wants to qualify leads by email before routing to sales agents.
- **How the feature is used:** They check "Collect Name" and "Collect Email" and customize input placeholders.
- **Customer experience:** Visitors must input their name and email to open WhatsApp.
- **Business outcome:** Qualified lead cards are instantly created in the CRM.

## 9. Industry Use Cases
- **E-Commerce:** Using exit-intent triggers to capture users before they close the tab by offering support discounts.
- **Automotive:** Placing widgets on inventory pages with pre-filled messages: "I'd like to test drive this model."
- **Services:** Gathering zip codes (using name placeholders) to confirm area availability before chatting.

## 10. Real Customer Example
A dental clinic creates a widget named `booking_helper`. They set the avatar to the lead dentist's photo and check "Collect Name" and "Collect Phone". Under display triggers, they set the schedule to only show during clinic hours. When visitors look up booking hours, they input their name, and click chat. The clinic's dashboard logs the lead phone number immediately, while routing the visitor into WhatsApp with the pre-filled string: "I'd like to schedule an audit."

## 11. Customer Journey
Marketer clicks Create &rarr; Inputs settings and brand styling &rarr; Triggers and schedules set &rarr; Live Desktop/Mobile preview checked &rarr; Widget saved and code generated.

## 12. Inputs
- Internal name.
- CTA button text.
- WhatsApp pre-filled copy.
- Lead collection checkboxes and placeholders.
- Display header, subtitle, logo URL.
- Theme hex color, offsets, radius, position.
- Target path, delay time, exit-intent toggle.
- Business hours grid.
- QR Dot/Bg colors.

## 13. Outputs
- Stored `LeadCaptureWidget` model in database.
- Generated unique slug.
- Live simulated mobile/desktop mock previews.

## 14. Dependencies
- **LeadCaptureWidget Model:** DB save target.
- **preview-widget.blade.php:** Mock view engine.
- **WidgetManager livewire:** Page controller.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission on plans including `campaigns`.
- **Who can view information:** Marketers/Admins.
- **Who can edit:** Marketers/Admins.
- **Who cannot access it:** Support agents.

## 16. Important Rules
- Display name and CTA button text are required fields.
- Logo URLs must point to valid image hosts (SSL supported) for avatars to render correctly on external sites.

## 17. Common Problems
- **Problem:** The preview mockup doesn't update when I change fields.
  - **Possible reason:** Livewire model bindings are updating, but the network request is still pending, or JavaScript script errors blocked rendering.
  - **What the user should do:** Wait a moment for the preview request to complete, or type a letter inside the field to force a Livewire cycle refresh.
- **Problem:** Custom business hours aren't saving.
  - **Possible reason:** The time input format is missing start or end hours.
  - **What the user should do:** Ensure both fields (start and end times) are filled for each checked day of the week.

## 18. Simple Explanation for Sales
The Widget Creation Wizard is the designer tool for your chat buttons. It lets you customize the text, colors, fields, and timing rules for website WhatsApp widgets, with a live preview showing how it looks on mobile and desktop screens.

## 19. Simple Explanation for Marketing
Design high-converting popups in minutes. Toggle visitor email inputs, configure triggers like page delays or exit intent, upload your branding logo, and watch the visual simulator update in real-time as you tweak configurations.

## 20. Simple Explanation for Support
If a widget isn't showing up during your weekend shift, it is because support hours were restricted inside this creation card to prevent customers from seeing the widget when you are offline.

## 21. Related Features
- [Growth Tools Dashboard](./growth-tools.md)
- [Contacts Center](./contacts.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/growth-tools` (Create Modal)
- **Implementation:** `App\Livewire\LeadCapture\WidgetManager`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/LeadCapture/WidgetManager.php`
  - `resources/views/livewire/lead-capture/widget-manager.blade.php`
  - `resources/views/livewire/lead-capture/preview-widget.blade.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
