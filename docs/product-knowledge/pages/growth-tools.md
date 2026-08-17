# Growth Tools (WhatsApp Widgets)

## 1. What is this page?
The Growth Tools page (WhatsApp Widgets) is a design and code manager used to create floating WhatsApp chat buttons, popup lead capture forms, and custom QR codes for company websites and physical marketing materials.

## 2. Why is this page useful?
Converting website traffic into direct conversations is a primary marketing goal.
- **Why do users need it?** To capture website visitor names, emails, and phone numbers *before* routing them into a live WhatsApp chat.
- **What work does it make easier?** It generates a single copy-paste JavaScript code snippet to embed on websites, automatically handles popup timing triggers (exit-intent, scroll delays), and builds customizable QR codes.
- **What business process does it support?** Inbound Lead Generation, Website Conversion Optimization, and Offline-to-Online marketing.
- **What happens without it?** Businesses would have to manually code custom popups or hire developers to write API integrations to capture visitor emails before sending them to WhatsApp, leading to high bounce rates and lost leads.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To copy installation scripts, configure target domain settings, and verify billing limits. |
| Marketing Manager | To design widget layouts, customize branding logos, set up lead capture forms, configure display triggers (like exit-intent), and download QR codes for print media. |

## 4. What can users do here?
- **Create and Edit Widgets:** Design widgets across five tabs:
  - **Button Info:** Define internal widget names, floating button CTA labels, and default pre-filled messages.
  - **Content & Forms:** Toggle options to collect Visitor Name, Email, and Phone. Set custom input placeholders, welcome headers, and footer branding.
  - **Style & Brand:** Upload brand logos, write agent subtitles (e.g. "replies in minutes"), set corners (border-radius), choose position (bottom-right/left), set screen offsets, and toggle desktop/mobile visibility.
  - **Display Triggers:** Set page targeting paths (e.g., `/pricing` only), set page-load delays (seconds), enable exit-intent triggers, and configure weekly business availability hours.
  - **QR Customization:** Select custom foreground/background colors for the generated QR code.
- **Review Core Widget Analytics:**
  - **Popup Opens:** Number of times website visitors clicked the button to open the form.
  - **Converted Leads:** Number of visitors who successfully filled out the lead form.
  - **QR Scans:** Total scans of the generated QR code.
- **Mockup Preview:** Toggle between live Mobile and Desktop previews to simulate button behaviors and styling changes in real-time.
- **Copy Public Links:** Copy unique landing page URLs (`/qr/{slug}`) for campaigns.
- **Copy Embed Code:** Open the installation modal to copy the simple `<script>` snippet for website deployment.
- **Toggle Active Status:** Pause or activate widgets instantly.

## 5. What is involved?
- **LeadCaptureWidget Model:** Stores settings, custom styles, slugs, active states, and event counters.
- **growth-widget.js:** The client-side JavaScript engine loaded on the company's website that reads the widget configuration and dynamically renders the UI.
- **QR Code Generator:** Renders real-time QR matrices based on the custom colors defined.

## 6. How does it work?
1. The Marketer opens the Growth Tools page and clicks "Create".
2. They select theme color `#25D366` (WhatsApp green) and check "Collect Name" and "Collect Email".
3. They write a pre-filled message: "I'd like to book a demo."
4. They copy the generated JavaScript code snippet and paste it into the global footer of their website.
5. A visitor lands on their site. After 5 seconds, a floating WhatsApp button slides into view.
6. The visitor clicks the button. A popup panel opens containing fields for "Full Name" and "Email".
7. The visitor enters their details and clicks "Submit".
8. The JS widget registers the lead in the platform database (incrementing conversion counts and creating a Contact record) and redirects the visitor's browser to `api.whatsapp.com` with the pre-filled message loaded, launching their WhatsApp app.

## 7. What happens behind the scenes?
- **Auto-generated Slug:** When a widget is created, the system builds a unique URL slug based on the widget's name combined with a random 5-character string (e.g., `hero-button-a7x9f`) to prevent URL collisions.
- **Dynamic CSS Injection:** The embedded script reads position, offsets, and corner styles directly from the database and injects inline CSS onto the target website to render the widget without slowing down page load times.
- **Availability Checker:** The JS script checks the current day and time against the `business_hours` array. If the current time is outside the schedule, the widget automatically hides itself from the website to avoid receiving messages when agents are offline.

## 8. Business Use Cases

**Use Case 1: Exit-Intent Coupon Popup**
- **Situation:** An e-commerce store wants to capture visitors who are about to leave their cart.
- **How the feature is used:** They create a widget with "Exit Intent" turned on. They collect Name and Email, and set the pre-filled message to "Send me the 10% discount code."
- **Customer experience:** When the visitor moves their mouse towards the browser close button, a WhatsApp chat panel pops up offering a coupon.
- **Business outcome:** Reduced cart abandonment and new marketing leads captured.

**Use Case 2: Offline Store QR Signs**
- **Situation:** A real estate agency wants to capture leads from physical yard signs.
- **How the feature is used:** They create a widget called "Property Signage", customize the QR code to match their brand colors, print the QR code on signs, and set a pre-filled message: "Send me the listing details for this house."
- **Customer experience:** A buyer drives past a house, scans the QR code, enters their email, and instantly has the agent's WhatsApp profile loaded with their listing query.
- **Business outcome:** Automated offline lead routing.

**Use Case 3: Targeting High-Value Pages**
- **Situation:** A software firm wants to display their chat widget only on their pricing page.
- **How the feature is used:** They set the Page Targeting input to `/pricing`.
- **Customer experience:** The WhatsApp widget only shows when visitors are viewing pricing, keeping normal pages distraction-free.
- **Business outcome:** High-intent leads routed directly to the sales team.

## 9. Industry Use Cases
- **Retail:** Placing widget QR codes on package inserts to drive customers to WhatsApp for feedback and discount codes.
- **Healthcare:** Using scheduling widgets to collect name and insurance info before opening a chat to book an appointment.
- **SaaS:** Placing floating widgets on help desk pages to offer instant customer support.

## 10. Real Customer Example
A local gym installs a widget on their homepage. They toggle on name/phone collection. The branding shows their gym logo and subtitle "Replies in 5 minutes". Over a weekend, 50 visitors click the floating button. 35 visitors complete the form and launch WhatsApp. The gym instantly receives 35 new contact records in their Audience Center with their names and emails attached, alongside 35 incoming WhatsApp messages.

## 11. Customer Journey
Marketer designs widget &rarr; Pastes code snippet on website &rarr; Visitor fills out form &rarr; CRM creates contact card &rarr; Browser redirects visitor to WhatsApp chat &rarr; Conversation begins with lead data saved.

## 12. Inputs
- Internal name.
- CTA button text.
- Prefilled message.
- Collection toggles (Name, Email, Phone).
- Position and offset margins.
- Page targeting, delay, and exit-intent rules.
- Weekday business hour limits.
- Custom QR foreground/background hex codes.

## 13. Outputs
- Saved `LeadCaptureWidget` records.
- Copied JavaScript installation snippet.
- Downloadable QR code images.
- Incremented click, conversion, and scan counts.

## 14. Dependencies
- **LeadCaptureWidget Model:** Core database.
- **growth-widget.js:** Client loader.
- **QR Code Generator:** Image renderer.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission (Admins/Marketers).
- **Who can view information:** Admins/Marketers.
- **Who can edit:** Admins/Marketers.
- **Who cannot access it:** Standard agents.

## 16. Important Rules
- Prefilled messages cannot contain links or characters that violate Meta's messaging policies.
- Business hours must conform to 24h ISO format to prevent JS rendering errors on client websites.

## 17. Common Problems
- **Problem:** Widget is not appearing on my website after pasting the code.
  - **Possible reason:** The widget's "Active" switch is turned off, the page targeting rules do not match the current URL, or the current time is outside the defined business hours.
  - **What the user should do:** Go to the manager page, check that the status is green (Active), verify if business hours are active, or clear targeting rules to test.
- **Problem:** QR Code colors look distorted or unscannable.
  - **Possible reason:** The contrast between the selected QR Dots color and the QR Background color is too low (e.g. yellow on white).
  - **What the user should do:** Choose highly contrasting colors (like black/dark blue dots on a white background) and verify scans using a phone.

## 18. Simple Explanation for Sales
Growth Tools are your website's welcome mat. They let you design WhatsApp buttons that visitors click to start a chat. Before they connect, the button pops up a form to grab their name and email, creating a hot lead in your system automatically.

## 19. Simple Explanation for Marketing
Generate leads directly from your site traffic. Customize floating widgets, set them to pop up when a user is about to close the page (exit-intent), and download custom QR codes to place on flyers or signage.

## 20. Simple Explanation for Support
If a new chat arrives in your inbox with a visitor's name and email already filled in, it means they contacted you through one of the website widgets configured on this page.

## 21. Related Features
- [Contacts Center](./contacts.md)
- [Inbox Settings](./inbox-settings.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/growth-tools`
- **Implementation:** `App\Livewire\LeadCapture\WidgetManager`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/LeadCapture/WidgetManager.php`
  - `resources/views/livewire/lead-capture/widget-manager.blade.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
