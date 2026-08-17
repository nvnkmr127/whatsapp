# API Access Token Manager

## 1. What is this page?
The API Access Token Manager is the programmatic authentication console of the platform. Located at `/developer/api-tokens`, it allows developers and administrators to create and revoke Personal Access Tokens (PATs) for external script authentication, set IP whitelists, configure token expiration schedules, and manage API scopes.

## 2. Why is this page useful?
To connect your internal databases, automated customer systems, or desktop workflows to WhatsApp, you must authenticate programmatic requests securely.
- **Why do users need it?** To authorize external systems (like sending a delivery alert from a warehouse server) without sharing system login credentials, and to revoke access if a token is compromised.
- **What work does it make easier?** It generates secure authorization tokens, tracks token usage, and restricts API keys to whitelisted server IPs.
- **What business process does it support?** Programmatic Integration Authorization, API Security Enforcement, and Access Control Auditing.
- **What happens without it?** Systems must hardcode primary login passwords in scripts, which is insecure and lacks granular control.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To authorize credentials for integration servers, monitor last-used metrics, and revoke compromised tokens. |
| Software Developer | To generate credentials for custom scripts and copy API tokens to environment variables. |

## 4. What can users do here?
- **Create API Tokens:**
  - Enter a descriptive Name (e.g. "Shopify App Integration").
  - Set **IP Whitelists:** Restrict API calls using a comma-separated list of allowed IPs.
  - Set **Expiration Schedules:** Select dates after which the token is automatically invalidated.
  - Check **Ability Scopes:** Assign granular permissions (read, write, delete) to limit token access.
- **Token Reveal (Modal):**
  - Displays the plaintext token once upon creation.
  - Click "Copy Token" to save to clipboard.
- **Monitor Active Tokens:**
  - View existing tokens, ability badges, and last-used timestamps.
- **Revoke Tokens (Delete):**
  - Instantly delete tokens, invalidating them immediately.

## 5. What is involved?
- **PersonalAccessToken Model:** Jetstream's database model storing hashed token structures, names, abilities, and custom metadata fields (`ip_whitelist`, `expires_at`).
- **Jetstream Auth Engine:** Handles key generation and cryptographic verification.

## 6. How does it work?
1. The Developer goes to `/developer/api-tokens` to connect an external dashboard.
2. They input a Name: "Company ERP", specify an IP whitelist: `203.0.113.50`, and check the "read" and "write" permissions.
3. They click "Generate Token".
4. The Token Reveal modal displays the plaintext token. They click "Copy Token" and paste it into their ERP configuration file.
5. In their script header, they attach the authorization parameter: `Authorization: Bearer <token>`.
6. When the ERP queries the API, the platform verifies the token hash matches, confirms the ERP server IP is whitelisted, and processes the request.

## 7. What happens behind the scenes?
- **Hashed Token Storage:** For security, the platform does not store the plaintext token database records. It stores a SHA-256 hash. When a request arrives, the system hashes the incoming token and matches it against database entries, protecting keys if database tables are compromised.
- **Granular Ability Enforcement:** API requests are intercepted by auth middleware. If a token only has the `read` ability and tries to delete a contact, the gateway blocks the request and returns a `403 Forbidden` response.
- **IP Whitelist Mappings:** Tapping a comma-separated IP string splits values into a JSON array stored in the access token record. When API endpoints receive requests, the system compares the client's IP against the whitelisted array, blocking unauthorized IPs.

## 8. Business Use Cases

**Use Case 1: Restricting ERP Access to Local Servers**
- **Situation:** A business wants to allow their local office server to update contact tags, but wants to block external requests.
- **How the feature is used:** They create a token with `write` permissions and add their office static IP to the IP Whitelist.
- **Customer experience:** N/A (Internal security).
- **Business outcome:** Secure access limited to local office servers.

**Use Case 2: Auto-Expiring Temporary Partner Tokens**
- **Situation:** A developer hired a contractor to sync contact databases and wants their access token to expire automatically when the contract ends.
- **How the feature is used:** They set the token's expiration date to the project completion date.
- **Customer experience:** N/A (Internal contractor management).
- **Business outcome:** Secure, temporary access without manual revocation.

**Use Case 3: Revoking Compromised Keys**
- **Situation:** An agency developer leaks an API token on GitHub.
- **How the feature is used:** The admin finds the leaked token in the list and clicks delete.
- **Customer experience:** N/A (Internal system security).
- **Business outcome:** Leaked tokens are invalidated immediately.

## 9. Industry Use Cases
- **Retail:** Authenticating automated product feed updates.
- **Healthcare:** Restricting patient data synchronization to static clinic server IPs.
- **Finance:** Scheduling token expirations for monthly audit reporting tools.

## 10. Real Customer Example
A developer creates an API token for their ERP system. They configure an IP whitelist to limit access to their server, set an expiration date, and select "read" and "write" abilities. They copy the token from the reveal modal and paste it into their ERP system. When they verify the integration is working, they monitor the last-used timestamp in the active tokens list.

## 11. Customer Journey
Developer names token &rarr; Sets optional IP Whitelists &rarr; Selects expiration date &rarr; Selects abilities &rarr; Generates token &rarr; Copies plaintext token &rarr; Verifies token activity logs.

## 12. Inputs
- Token Name.
- IP Whitelists.
- Expiration dates.
- Ability checkbox list.

## 13. Outputs
- Plaintext Token string (one-time display).
- Hashed personal access token DB record.
- Saved IP JSON strings.
- Active token grids.

## 14. Dependencies
- **Jetstream API Engine:** Cryptographic validator.
- **PersonalAccessToken Model:** DB records.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permissions on plans including `api_access`.
- **Who can view information:** Admins/Developers.
- **Who can edit:** Admins/Developers.
- **Who cannot access it:** Managers, Support Agents.

## 16. Important Rules
- API tokens are only displayed once. If lost, they must be deleted and recreated.
- API tokens must be included in request headers: `Authorization: Bearer <your_token>`.

## 17. Common Problems
- **Problem:** API calls return `403 Forbidden` after whitelisting IPs.
  - **Possible reason:** The server IP whitelisted does not match the public IP routing the request (often due to load balancers).
  - **What the user should do:** Confirm your server's public outbound IP, or clear the IP whitelist field temporarily to diagnose connection issues.
- **Problem:** Token was leaked or lost.
  - **Possible reason:** Plaintext keys are not retrievable from the database.
  - **What the user should do:** Delete the token from the active list to revoke it, and generate a new token.

## 18. Simple Explanation for Sales
The API Tokens page is where you generate secure passwords for your other business software (like your CRM or ERP) to link and sync data with this platform.

## 19. Simple Explanation for Marketing
Admins use this page to create secure access keys that allow your external tools to pull contact lists and campaign logs automatically.

## 20. Simple Explanation for Support
If external CRM syncing stops working, ask your administrator to verify that the API token has not expired or been revoked.

## 21. Related Features
- [Developer Portal](./developer-portal.md)
- [Outbound Webhooks](./outbound-webhooks.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/developer/api-tokens`
- **Implementation:** `App\Livewire\Developer\ApiTokenManager`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Developer/ApiTokenManager.php`
  - `resources/views/livewire/developer/api-token-manager.blade.php`
  - `config/sanctum.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
