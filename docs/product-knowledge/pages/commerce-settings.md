# Commerce Settings

## 1. What is this page?
The Commerce Settings page (Commerce Control) is the policy and communication router of the platform. Located at `/commerce/settings`, it allows administrators to define store currencies, guest checkouts, cart expiration thresholds, WhatsApp alert templates, internal agent notifications, and AI shop assistants.

## 2. Why is this page useful?
E-commerce operations require strict rules regarding currency, cart abandonment alerts, and status update notifications.
- **Why do users need it?** To connect transactional events (like package shipping) to WhatsApp notifications, configure cart recovery timers, and set safety boundaries for cash-on-delivery checkouts.
- **What work does it make easier?** It runs an automated **Readiness Engine** to verify catalog linkages, and flags configuration risks (like turning off COD without payment gateways) before saving.
- **What business process does it support?** Checkout Policy Enforcement, Cart Abandonment Timing, and Transactional Message Routing.
- **What happens without it?** Order state changes would be silent. Customers wouldn't get shipping alerts, abandoned carts wouldn't trigger recovery reminders, and currency formats would mismatch.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To authorize Meta policy terms, lock store currencies, map approved WhatsApp templates to fulfillment stages, and review system configuration risks. |

## 4. What can users do here?
- **Review Commerce Readiness:** Monitor the overall store score (0-100%) checking WABA verification, catalog linkage, policy approvals, and notification setups.
- **Configure Store Basics:**
  - Set the Store Currency (USD, EUR, GBP, INR, AED, SGD, SAR). Currency is locked once transaction records are registered.
  - Define a Minimum Order Value.
  - Toggle Allow Guest Checkout.
  - Toggle Enable Cash on Delivery (COD).
  - Accept Meta's Commerce Policies.
- **Tune Cart Intelligence:**
  - Set Cart Expiry duration (in minutes).
  - Set Cart Abandonment Reminder delay (minutes).
  - Choose Multi-Session behavior: Combine items (merge) or replace with the newest session (replace).
- **Map WhatsApp Status Templates:**
  - Link approved WhatsApp templates to specific order milestones (created, confirmed, paid, shipped, fulfilled, cancelled, returned).
  - Toggle customer alerts for each milestone.
- **Manage AI Shop Assistant:** Toggle the autonomous AI product recommender.
- **Configure Agent Alerts:** Toggle internal notification alerts for agents when orders are created, paid, cancelled, or returned.
- **Review Safety Risks (Modal):** Audits configuration changes before saving and outputs diagnostic warnings (Blocked, Critical, High Risk, Notice, Operational).

## 5. What is involved?
- **Team Model:** Persists store configurations in the `commerce_config` JSON column.
- **CommerceReadinessService:** Audits WABA and template configurations to calculate readiness scores.
- **Order Model:** Used to audit historical transactions before allowing currency changes.
- **Integration Model:** Audits connected payment systems.

## 6. How does it work?
1. The Admin goes to `/commerce/settings` to map their shipping alerts.
2. Under WhatsApp Alerts, they find the "Order Shipped" status.
3. They open the dropdown and select their approved template: `shipping_update_en`.
4. They scroll down to "Cart Intelligence" and set the expiry to 120 minutes and reminder delay to 30 minutes.
5. They click "Save Changes". The system checks the layout. If they mapped the template correctly, it saves.
6. Now, whenever an agent marks an order as "shipped" in the Order Manager, the system loads `shipping_update_en` and delivers the shipping notification to the customer. If a customer leaves their cart, a reminder fires 30 minutes later.

## 7. What happens behind the scenes?
- **Impact Evaluation Engine:** Before committing changes, the controller runs validation audits:
  - **Blocked:** Triggers if the AI assistant is enabled but the team is missing an OpenAI key.
  - **Critical:** Triggers if Cash on Delivery is disabled but no payment integrations (Stripe, PayPal, etc.) are active.
  - **High Risk:** Triggers if the currency is changed (existing product prices will not auto-convert).
  - **Operational:** Triggers if no confirmation template is mapped to new orders.
- **Currency Immutability:** If `Order::where('team_id', $id)->exists()` returns true, the currency select input is disabled in the UI and blocked in the backend validator to prevent order balance calculation mismatches.

## 8. Business Use Cases

**Use Case 1: Activating Cart Recovery Reminders**
- **Situation:** An online store has a high rate of unfinished shopping carts and wants to nudge buyers.
- **How the feature is used:** They set the Cart Expiry to 60 minutes and Cart Reminder to 30 minutes. They assign their `abandoned_cart_template` to the "Created" order state.
- **Customer experience:** A customer adds items to their cart in chat but leaves. 30 minutes later, they get a friendly reminder with a checkout link.
- **Business outcome:** Increased sales conversions from abandoned checkouts.

**Use Case 2: Preventing Checkout Blockages**
- **Situation:** A manager wants to disable COD to encourage digital payments, but the Stripe gateway integration is temporarily down.
- **How the feature is used:** They toggle off COD and click save. The Impact Evaluation modal appears, flagging a **Critical Risk**: "Disabling COD without an active Digital Payment Gateway will prevent 100% of your customers from completing orders."
- **Customer experience:** N/A (Internal safety block).
- **Business outcome:** The manager cancels the save, avoiding a checkout outage.

**Use Case 3: Setting Up AI Product Assistants**
- **Situation:** A business wants an AI to answer product questions and recommend items from the catalog.
- **How the feature is used:** They toggle on the AI Shop Assistant switch and link their OpenAI key in the AI portal.
- **Customer experience:** Customers messaging the store get instant product suggestions from the AI.
- **Business outcome:** Continuous sales assistance.

## 9. Industry Use Cases
- **Retail:** Mapping templates for confirmations, shipping, and cancellations.
- **Healthcare:** Disabling guest checkouts to ensure patient records are verified.
- **Services:** Setting low cart expiration limits (e.g. 15 minutes) for booking time slots.

## 10. Real Customer Example
A clothing brand maps their Meta catalog on this page. They agree to Meta's policies and get a 100% Readiness Score. They set their currency to EUR and map their `delivery_alert_fr` template to the "Shipped" status. They set cart reminder alerts to 20 minutes. When a buyer abandons their cart, they get a nudge 20 minutes later. When they buy, the order is packed, marked as shipped, and the customer gets their tracking alert in French.

## 11. Customer Journey
Admin reviews readiness score &rarr; Sets currency and checkout rules &rarr; Sets cart reminder delays &rarr; Maps WhatsApp status templates &rarr; Passes risk reviews &rarr; Store settings saved.

## 12. Inputs
- Currency selection.
- Minimum order value.
- Checkout permissions (guest, COD toggles).
- Policy approvals.
- Cart expiry and reminder timers.
- Selected WhatsApp templates for statuses.
- Notification toggles (customer alerts, agent notifications).

## 13. Outputs
- Saved `commerce_config` settings in Team record.
- Risk Evaluation warnings.

## 14. Dependencies
- **Team Model:** Persists settings.
- **Order Model:** Transaction history audit.
- **Integration Model:** Payment gateway check.
- **CommerceReadinessService:** Readiness auditor.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permission on plans including `commerce`.
- **Who can view information:** Admins/Managers.
- **Who can edit:** Admins.
- **Who cannot access it:** Standard support agents.

## 16. Important Rules
- You cannot change the store currency after orders have been created in the database.
- Cart reminder times must be shorter than cart expiration times.

## 17. Common Problems
- **Problem:** Currency dropdown is grayed out and cannot be changed.
  - **Possible reason:** The store already has order transactions logged, locking the currency for accounting consistency.
  - **What the user should do:** If you must change currencies, contact support, or create a new team workspace.
- **Problem:** AI Assistant switch won't toggle on.
  - **Possible reason:** You haven't added an OpenAI API Key in your AI settings.
  - **What the user should do:** Go to AI Settings, input your API key, and return to activate the assistant.

## 18. Simple Explanation for Sales
Commerce Settings is where you set the rules for your store. It manages your currency, checkout options (like guest checkout or pay-on-delivery), cart reminders, and decides which templates are sent to customers when their order status updates.

## 19. Simple Explanation for Marketing
Set up automated customer alerts. Choose when to trigger abandoned cart reminders, and map approved WhatsApp templates to shipping updates.

## 20. Simple Explanation for Support
If customers aren't receiving order confirmations, notify your admin. They can check this page to make sure templates are mapped to order updates.

## 21. Related Features
- [Product Catalog Manager](./products.md)
- [Order Fulfillment Manager](./orders.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/commerce/settings`
- **Implementation:** `App\Livewire\Commerce\CommerceSettings`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Commerce/CommerceSettings.php`
  - `resources/views/livewire/commerce/commerce-settings.blade.php`
  - `app/Services/CommerceReadinessService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
