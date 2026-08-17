# System Settings

## 1. What is this page?
The System Settings page is the administrative control panel of the platform. Located at `/settings/system`, it allows administrators to configure workspace branding (logo, favicon, color schemes), set regional localization defaults (target markets, currencies, date formats), save system-level WhatsApp credentials, and toggle maintenance mode blocks.

## 2. Why is this page useful?
Messaging compliance and formatting rules vary significantly across countries.
- **Why do users need it?** To configure workspace timezones, upload branding assets, save system-level WhatsApp notification tokens, and view compliance alerts tailored to their primary operating market.
- **What work does it make easier?** It offers a **Smart Country Selector** that automatically configures country codes, local timezones, language profiles, and displays localized Meta policy warnings in one click.
- **What business process does it support?** White-Label Branding, Multi-Region Localization, and Compliance Enforcement.
- **What happens without it?** Formatting remains generic, system timezones drift, notifications fail due to unconfigured tokens, and administrators risk non-compliance with region-specific laws (GDPR, TCPA, CITC).

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To authorize branding assets, configure system API tokens, adjust pagination rules, and toggle maintenance mode blocks. |

## 4. What can users do here?
- **Manage Workspace Branding:**
  - Upload a Workspace Logo (saved to Cloudflare R2 storage; can be removed).
  - Upload a Favicon (.ico, .png, etc.).
  - Set Primary Accent Color (RGB picker).
- **Configure Regional & Localization Settings:**
  - **Select Country:** Choose country profiles to auto-configure timezones, currencies, default dial codes, and view Meta policy requirements (GDPR, TCPA, PDPA, CITC notices).
  - **System Language:** Override auto-configured languages (English, Hindi, Arabic, Spanish, Kurdish).
  - **Currency Symbol:** Customize transaction currencies.
  - **System Timezone:** Select local offsets to sync dashboard timelines.
  - **Date Format:** Choose between YYYY-MM-DD, DD/MM/YYYY, MM/DD/YYYY, or DD-MM-YYYY.
- **Configure System WhatsApp Channels:**
  - Save System WABA ID, Phone Number ID, and Access Token (used for transactional alerts).
- **Workspace Administration:**
  - Set Global App Name and Workspace Workspace Name.
  - Set Support Desk Email.
  - Set UI pagination limits.
- **Toggle System Maintenance Mode:**
  - Restrict user access during platform upgrades, allowing only admins to log in.

## 5. What is involved?
- **Team Model:** Persists workspace names, timezones, and logo file paths.
- **Setting Model:** The database table used to store app configurations (colors, app names, currencies, WABA credentials).
- **R2 Storage Disk:** Hosts logos and favicons.

## 6. How does it work?
1. The Admin opens `/settings/system` to set up a workspace for an Australian team.
2. In the Country dropdown, they select "Australia".
3. The system automatically:
   - Sets default country code to `+61`.
   - Sets timezone to `Australia/Sydney`.
   - Sets currency code to `AUD`.
   - Displays a compliance alert: "Australia Spam Act (2003) requires explicit opt-in and clear opt-out mechanisms."
4. They upload a PNG logo, set primary branding color to `#008080` (Teal), and click "Save Changes".
5. The dashboard redirects to refresh, rendering the new logo, timezone settings, and teal branding accents globally across all accounts.

## 7. What happens behind the scenes?
- **Cloud Storage Uploads:** Logo and favicon uploads bypass local servers and save directly to Cloudflare R2 buckets using unique hash names (`team-logos/...`, `system-favicons/...`), ensuring fast load times.
- **Smart Country Mapping:** Tapping the country input triggers a script query mapping key parameters. It updates the database settings (`default_country_code`, `currency_symbol`, `primary_language`) and synchronizes the active team timezone immediately.
- **Maintenance Gateways:** Activating Maintenance Mode sets the `maintenance_mode` setting to true. Middleware checks this setting; if true, any requests from non-admin accounts are blocked and redirected to a maintenance warning page.

## 8. Business Use Cases

**Use Case 1: Deploying Workspace Branding**
- **Situation:** A business wants to white-label the messaging CRM to match their agency's identity.
- **How the feature is used:** They upload their custom logo, upload a matching favicon, and set the primary color code to their brand's color.
- **Customer experience:** N/A (Internal white-labeling).
- **Business outcome:** Consistent, custom-branded interface for agents.

**Use Case 2: Regional Compliance Mapping**
- **Situation:** A marketing team wants to launch a broadcast in the United States and needs to confirm compliance guidelines.
- **How the feature is used:** The admin selects "United States" in the country list. The Compliance Monitor displays: "USA requires strict adherence to TCPA/CTIA. STOP/UNSUBSCRIBE keywords are mandatory."
- **Customer experience:** N/A (Internal guideline notice).
- **Business outcome:** The team configures mandatory unsubscribe keywords in their opt-out list before starting the campaign.

**Use Case 3: Scheduling Server Maintenance**
- **Situation:** An IT administrator needs to update database tables without users modifying data.
- **How the feature is used:** They toggle on "System Maintenance Mode" and click save.
- **Customer experience:** Normal support agents see a polite "System under maintenance" screen if they try to access the dashboard.
- **Business outcome:** Safe schema migrations without data collisions.

## 9. Industry Use Cases
- **B2B Agencies:** White-labeling color schemes and logos for individual client portals.
- **Global E-commerce:** Automatically setting local timezones and regional currency formats.
- **Healthcare Providers:** Restricting system access during upgrades.

## 10. Real Customer Example
A delivery company in Dubai logs in and selects "United Arab Emirates" as their country. The page displays the UAE compliance alert, auto-saves their timezone to `Asia/Dubai`, and updates their transaction symbol to `AED`. They upload their logo to Cloudflare R2, save their system WhatsApp API token, and set the date format to `DD/MM/YYYY`. The interface adjusts instantly, allowing their dispatcher to send local delivery confirmations formatted with correct timezones.

## 11. Customer Journey
Admin reviews branding inputs &rarr; Selects target country profile &rarr; Verifies regional compliance rules &rarr; Saves system WABA tokens &rarr; Toggles maintenance states if needed &rarr; Saves changes &rarr; Layout refreshes.

## 12. Inputs
- Team logo and favicon images.
- Primary branding color hex codes.
- Target Country, timezone, and language.
- Transaction currencies.
- Date formatting layouts.
- System WABA ID, Phone ID, and tokens.
- Global App and Workspace titles.
- Support emails.
- Pagination limits.

## 13. Outputs
- Saved workspace logos on R2.
- Updated `Setting` records.
- Updated `Team` timezones.
- Active system redirects.

## 14. Dependencies
- **Setting & Team Models:** DB records.
- **R2 Storage Disk:** File storage.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permissions.
- **Who can view information:** Admins.
- **Who can edit:** Admins.
- **Who cannot access it:** Managers, Support Agents.

## 16. Important Rules
- Maintenance mode blocks access for all non-admin accounts.
- Brand colors must be valid hex values (e.g. `#FFFFFF`).

## 17. Common Problems
- **Problem:** Newly uploaded logos do not appear after saving.
  - **Possible reason:** Your browser has cached the older logo URL, or Cloudflare R2 credentials are down.
  - **What the user should do:** Clear your browser cache (Ctrl+F5 / Cmd+Shift+R) or confirm your R2 storage integration parameters.
- **Problem:** Changing countries overrides custom timezones.
  - **Possible reason:** The country profile selector automatically overrides timezones to match the selected region's capital timezone.
  - **What the user should do:** Select your country first, wait for the page to update, and manually select your preferred timezone override from the dropdown afterward.

## 18. Simple Explanation for Sales
System Settings is where you set up your workspace. You can upload your company logo, set your primary brand color, choose your timezone, and set up your target country so the system automatically adjusts currency and date formats.

## 19. Simple Explanation for Marketing
Admins use this page to set timezones and regional currency formats, ensuring campaign logs and templates show local delivery times.

## 20. Simple Explanation for Support
If customer support messages show incorrect dates or times, notify your admin. They can check this page to confirm the workspace timezone is set correctly.

## 21. Related Features
- [WhatsApp Configuration](./whatsapp-setup.md)
- [Fulfillment Configuration](./commerce-settings.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/settings/system`
- **Implementation:** `App\Livewire\Settings\SystemSettings`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Settings/SystemSettings.php`
  - `resources/views/livewire/settings/system-settings.blade.php`
  - `app/Models/Setting.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
