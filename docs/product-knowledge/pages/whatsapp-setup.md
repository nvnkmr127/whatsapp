# WhatsApp Configuration

## 1. What is this page?
The WhatsApp Configuration page (also known as the Account Hub) is the technical control center where a business connects their Meta WhatsApp Business Account (WABA) to the platform. It manages the connection credentials, displays system health, and allows businesses to edit their public WhatsApp profile.

## 2. Why is this page useful?
This page bridges the gap between the platform and Meta's infrastructure.
- **Why do users need it?** To authorize the platform to send and receive WhatsApp messages on their behalf.
- **What work does it make easier?** It simplifies the complex Meta integration process into a clear step-by-step flow (Link Facebook &rarr; Discover Account &rarr; Activate Setup).
- **What business process does it support?** Initial onboarding, technical troubleshooting, and brand identity management (business profile).
- **What happens without it?** The platform would have no connection to WhatsApp, rendering all messaging, automations, and CRM features useless.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To perform the initial Meta connection, monitor token validity, and update the business profile. |
| Support Agent (Internal) | To instruct customers to run "Setup Diagnostics" when troubleshooting connection issues. |

## 4. What can users do here?
- Connect their WhatsApp Business Account via Meta's Embedded Signup.
- Manually enter Meta API credentials (App ID, Secret, Token, WABA ID).
- View current connection status and system health (Token validity, Quality Rating, Usage percentage).
- Run Setup Diagnostics to generate a detailed troubleshooting trace.
- Send a test message to verify the connection.
- Update their public WhatsApp Business Profile (Photo, About, Address, Email, Websites).
- Update behavior settings (Timezone, WhatsApp Calling permissions).
- View active messaging credits and daily Meta limits.

## 5. What is involved?
- **Meta Graph API:** For validating tokens, subscribing to webhooks, and updating profiles.
- **Team Model:** Stores all the credentials and settings (`whatsapp_access_token`, `whatsapp_business_account_id`, etc.).
- **TeamWallet:** Used to display remaining messaging credits.
- **WhatsAppHealthMonitor:** Generates the health scores and blocking issues shown in the diagnostics.

## 6. How does it work?
1. An admin opens the page. If not connected, they see a 3-step progress tracker.
2. The user clicks to connect, which opens the Meta Embedded Signup window.
3. Upon success, Meta returns an access token and WABA ID to the platform.
4. The system automatically upgrades the token to a long-lived token, subscribes the webhook, and registers the phone number.
5. If the account is already connected, the page loads a skeleton UI first, then asynchronously fetches live data (Health, Profile) from Meta to prevent slow page loads.
6. The dashboard displays critical alerts (e.g., if the token is expiring or the app is in Development Mode).

## 7. What happens behind the scenes?
- **Token Governance:** The page proactively checks token expiry. If a token is older than 6 hours since last validation, it dispatches a background job (`ValidateWhatsAppTokens`) to ensure it's still valid.
- **Circuit Breaker:** If Meta API errors spike, the connection enters a `restricted` state. Admins can manually reset this circuit breaker from this page once they've resolved the issue.
- **Diagnostics Engine:** The "Run Diagnostics" button executes a deep health check (verifying scopes, webhook subscriptions, and last 24h failure rates) and outputs a JSON trace useful for L2 support.
- **Profile Updates:** Profile changes (including photo uploads) are pushed directly to the Meta Graph API and the local cache is invalidated.

## 8. Business Use Cases

**Use Case 1: Initial Account Onboarding**
- **Situation:** A new customer creates an account and needs to start sending messages.
- **How the feature is used:** The customer navigates to the WhatsApp Configuration page, follows the Setup Progress checklist, and links their Facebook account.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** The business is successfully provisioned and ready to use the platform.

**Use Case 2: Updating Brand Identity**
- **Situation:** A business moves to a new physical office and updates their support email.
- **How the feature is used:** The admin goes to the Business Profile section on this page, updates the address and email fields, and clicks save.
- **Customer experience:** WhatsApp users see the updated information when they view the business's profile in the WhatsApp app.
- **Business outcome:** Accurate public-facing contact information reduces customer confusion.

**Use Case 3: Troubleshooting Silent Failures**
- **Situation:** A business reports that inbound messages have stopped working, though they can still send messages.
- **How the feature is used:** An admin clicks "Run Diagnostics". The diagnostic trace reveals that the webhook subscription was dropped by Meta. The admin clicks "Force Re-subscribe" to fix it.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Rapid resolution of a critical channel outage without requiring engineering escalation.

**Use Case 4: Testing the Connection**
- **Situation:** A user is unsure if their setup is completely correct before launching a campaign.
- **How the feature is used:** They enter their personal phone number in the "Test Connection" box and send a test message.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Confidence in the platform before spending money on a large broadcast.

## 9. Industry Use Cases
- **All Industries:** Every business using the platform must use this page to configure their core connectivity. It is industry-agnostic.

## 10. Real Customer Example
A boutique hotel signs up for the platform. The general manager goes to the WhatsApp Configuration page, logs into Facebook, and selects the hotel's WhatsApp number. Once connected, they notice the Quality Rating card shows "GREEN" and their limit is "1K". They scroll down to the Business Profile section, upload their hotel logo, add their website URL, and set their industry to "Hospitality". They send a test message to their own phone. Seeing the message arrive, they know the system is ready to handle reservations.

## 11. Customer Journey
Admin signs up &rarr; Navigates to WhatsApp Configuration &rarr; Completes Meta Embedded Signup &rarr; Platform provisions webhooks &rarr; Admin updates Business Profile &rarr; Admin sends Test Message &rarr; System is ready for use.

## 12. Inputs
- Meta credentials (Token, WABA ID)
- Profile data (Photo, Description, Email, Website)
- Behavior toggles (Timezone, Calling enabled)
- Test message recipient number

## 13. Outputs
- Active Meta Webhook subscription
- Updated Meta Graph Profile
- Sent WhatsApp text message
- Diagnostic JSON trace

## 14. Dependencies
- **Meta Graph API:** The entire page depends on Meta being reachable.
- **Team Model:** To store the credentials.
- **WhatsAppHealthMonitor:** For diagnostics and alerts.
- **WhatsAppAlerts (Livewire):** Embedded component to show active health warnings.

## 15. Permissions
- **Who can access this page:** Only users with `manage-settings` permission (Admins).
- **Who can view information:** Admins.
- **Who can edit:** Admins.
- **Who cannot access it:** Standard agents/reps.

## 16. Important Rules
- Profile updates must not send empty strings to Meta (they cause 400 errors); empty fields must be stripped or sent as `null`.
- Profile photos must be JPEG or PNG and under 5MB.
- Diagnostic data contains sensitive token previews and should be handled securely.

## 17. Common Problems
- **Problem:** Inbound messages aren't arriving.
  - **Possible reason:** The webhook subscription was lost or the Meta App is stuck in Development Mode.
  - **What the user should do:** Run Setup Diagnostics. If the app is in Dev Mode, switch it to Live in the Meta portal. If the webhook is missing, click "Force Re-subscribe".
- **Problem:** "WhatsApp Calling is not yet activated on your number" warning when saving settings.
  - **Possible reason:** The number is not enrolled in Cloud API Calling by Meta.
  - **What the user should do:** Enable it via the Meta Business Manager or contact Meta Support.
- **Problem:** Token expiry critical alert.
  - **Possible reason:** A system user token was not properly upgraded to a long-lived token, or the user's password changed.
  - **What the user should do:** Click "Re-Authenticate Now" to refresh the token via Facebook Login.

## 18. Simple Explanation for Sales
The WhatsApp Configuration page is where the magic starts. It takes the complicated process of connecting to Meta and turns it into a simple, guided checklist. In just a few clicks, businesses can link their number, set up their public profile, and start messaging.

## 19. Simple Explanation for Marketing
This page ensures that a brand looks professional on WhatsApp. It allows businesses to easily upload their logo, set their business hours, and link their website, ensuring every customer interacting with them sees a verified, complete profile.

## 20. Simple Explanation for Support
When a customer has connection issues, this is the first page to check. The "Run Diagnostics" button will instantly tell you if their token is expired, if they are blocked by Meta, or if their webhooks are failing, saving hours of manual troubleshooting.

## 21. Related Features
- [Deliverability](./deliverability.md)
- [Dashboard](./dashboard.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/whatsapp/setup`
- **Implementation:** `App\Livewire\Teams\WhatsappConfig`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Teams/WhatsappConfig.php`
  - `resources/views/livewire/teams/whatsapp-config.blade.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
