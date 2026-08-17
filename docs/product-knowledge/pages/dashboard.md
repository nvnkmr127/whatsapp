# Dashboard

## 1. What is this page?
The Main Dashboard is the central command center for the WhatsApp Business API application. It is the first page users see after logging in. It provides a quick summary of account activity, message volume, active campaigns, and quick links to the most important features in the platform.

## 2. Why is this page useful?
The dashboard solves the problem of needing to check multiple pages to understand the current state of the business's WhatsApp communications. 
- **Why do users need it?** It provides instant visibility into message volume and the status of contacts and campaigns.
- **What work does it make easier?** It offers quick action buttons to start campaigns, manage orders, train AI, and configure settings without navigating through complex menus.
- **What business process does it support?** Daily monitoring, operational oversight, and starting new communication workflows.
- **What happens without it?** Users would be forced to navigate to individual modules (Contacts, Campaigns, Settings) just to get a basic understanding of their daily activity, wasting time and reducing visibility.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To monitor overall message usage, check onboarding progress, and access the account configuration hub. |
| Manager | To view message speeds, see how many campaigns and templates are active, and access quick actions. |
| Marketing Team | To quickly jump into creating a new broadcast and monitor active campaigns. |
| Super Admin | To access system-wide controls like the Tenant Manager and Platform Settings. |

## 4. What can users do here?
- View high-level statistics (Total Messages, Total Contacts, Active Campaigns, Active Templates).
- Filter message volume charts by time range (Today, Week, Month).
- Complete the onboarding setup checklist.
- Start a new broadcast campaign.
- Navigate to the sales hub to manage orders.
- Navigate to the knowledge base to train AI.
- Navigate to the WhatsApp configuration hub.
- (Super Admins only) Access system controls like Tenant Manager, Platform Settings, Mail Engine, and Billing Plans.

## 5. What is involved?
- **Messages:** Used to calculate message speed and daily message counts.
- **Contacts:** To show the total number of contacts in the system.
- **Campaigns:** To display the number of active/processing campaigns.
- **Templates:** To show the count of approved WhatsApp templates.
- **Onboarding Checklist:** A component to guide new users through account setup.

## 6. How does it work?
1. User logs in and opens the Dashboard.
2. The system fetches the total counts for messages, contacts, campaigns, and templates for the user's specific team.
3. The system fetches message volume data based on the selected time range (defaulting to 'Today').
4. The chart renders the message speed (inbound and outbound trends).
5. The user can click quick action cards to navigate to other key areas of the application.
6. If the user is a Super Admin, a special "System Controls" panel is displayed.

## 7. What happens behind the scenes?
- **Database Queries:** The `Dashboard` Livewire component runs optimized queries counting records in the `messages`, `contacts`, `campaigns`, and `whatsapp_templates` tables, strictly scoped to the user's `team_id`.
- **Chart Data Generation:** Message creation timestamps are grouped by hour or day (depending on the time range filter) to generate data points for the frontend charting library (ApexCharts).
- **Livewire Updates:** Changing the time range filter triggers a Livewire request that recalculates the message stats and dispatches a browser event to update the chart dynamically without a full page reload.
- **Role Checks:** The system checks if the authenticated user has Super Admin privileges (`isSuperAdmin()`) to conditionally render the system controls panel.

## 8. Business Use Cases

**Use Case 1: Daily Morning Review**
- **Situation:** A customer support manager wants to know how busy the WhatsApp channel is today.
- **How the feature is used:** They log into the dashboard and check the "Message Speed" chart and the "Messages Today" statistic.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** The manager can quickly allocate more agents if the inbound message volume is unusually high.

**Use Case 2: Quick Campaign Launch**
- **Situation:** A marketing agent has a new promotion to announce immediately.
- **How the feature is used:** The agent clicks the "New Broadcast" quick action card on the dashboard.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Friction is reduced, allowing the team to launch campaigns faster without navigating through multiple sidebar menus.

**Use Case 3: Onboarding a New Account**
- **Situation:** A newly registered business needs to set up their WhatsApp API connection.
- **How the feature is used:** The business owner follows the interactive Onboarding Checklist displayed on the dashboard.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Higher activation rates as users are guided step-by-step through the mandatory setup process.

**Use Case 4: AI Training Access**
- **Situation:** An admin notices the bot is struggling to answer certain questions and needs to upload new documents.
- **How the feature is used:** They click the "Train AI" quick action from the dashboard to go directly to the knowledge base.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Faster iteration on AI capabilities, leading to better automated customer support.

**Use Case 5: System Administration**
- **Situation:** The platform owner needs to adjust billing plans.
- **How the feature is used:** The Super Admin logs in, sees the "System Controls" panel, and clicks "Billing Plans".
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Secure and rapid access to critical platform configuration for system operators.

## 9. Industry Use Cases
- **Ecommerce:** Store owners use the quick links to jump directly into the Sales Hub to manage new incoming WhatsApp orders.
- **Marketing Agencies:** Account managers monitor the "Active Campaigns" metric to ensure all client broadcasts are currently running.
- **Real Estate:** Agents monitor the daily message volume to track incoming leads from property listings.

## 10. Real Customer Example
A retail business owner logs in on Monday morning. They see that they have 1,200 total contacts and 0 active campaigns. They notice the "Messages Today" count is low. Realizing they haven't sent out their weekly promotion, they click the "New Broadcast" button directly from the dashboard, select their audience, and launch the campaign to drive sales for the day.

## 11. Customer Journey
Admin logs into Platform &rarr; Views Dashboard &rarr; Checks Daily Message Volume &rarr; Clicks Quick Action (e.g., New Broadcast) &rarr; Completes Task in corresponding module &rarr; Business Outcome achieved faster.

## 12. Inputs
- Selected Time Range (Today, Week, Month)
- Refresh action (clicking the refresh button)

## 13. Outputs
- Visual charts (Message Speed)
- Navigation to other pages

## 14. Dependencies
- **Messages:** Required to show volume and speed charts.
- **Contacts:** Required to show the total audience size.
- **Campaigns:** Required to show active marketing efforts.
- **WhatsApp Templates:** Required to show approved messaging assets.
- **ApexCharts (Frontend):** Required to render the message speed graph.
- **Onboarding Component:** Embedded to assist new users.

## 15. Permissions
- **Who can access this page:** All authenticated users assigned to a team.
- **Who can view information:** Any user with dashboard access (scoped to their team's data).
- **Who can see System Controls:** Only users where `isSuperAdmin()` returns true.

## 16. Important Rules
- All statistics (messages, contacts, campaigns, templates) are strictly filtered by the current user's `team_id`.
- The Super Admin command center is completely hidden from regular tenant users.

## 17. Common Problems
- **Problem:** Chart is not displaying data for today.
  - **Possible reason:** No messages have been sent or received yet today, or the cron jobs/webhooks processing messages are down.
  - **What the user should do:** Send a test message to their WhatsApp number and click the refresh button on the dashboard.
- **Problem:** Quick action buttons are visible but lead to unauthorized errors.
  - **Possible reason:** The user has dashboard access but lacks specific module permissions (e.g., they can see the "New Broadcast" button but don't have campaign creation permissions).
  - **What the user should do:** Contact their team admin to update their role permissions.

## 18. Simple Explanation for Sales
The Dashboard is the command center for your WhatsApp business. It gives you an instant, visual overview of your message volume, audience size, and active campaigns the moment you log in. It saves you time by providing quick links to launch broadcasts, manage orders, and train your AI in one click.

## 19. Simple Explanation for Marketing
The Dashboard provides immediate visibility into your communication health. It highlights your active campaigns and audience size at a glance, and reduces the friction of launching new marketing efforts by keeping the most important actions front and center.

## 20. Simple Explanation for Support
When a customer logs in, the dashboard is their home base. If they are confused about where to start, you can direct them to the quick action buttons or the onboarding checklist on the dashboard to get them oriented quickly.

## 21. Related Features
- [Contacts](./contacts.md)
- [Campaigns](./campaigns.md)
- [WhatsApp Configuration](./whatsapp-configuration.md)
- [Knowledge Base](./knowledge-base.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/dashboard`
- **Implementation:** `App\Livewire\Dashboard\Dashboard`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Dashboard/Dashboard.php`
  - `resources/views/livewire/dashboard/dashboard.blade.php`
  - `resources/views/dashboard.blade.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
