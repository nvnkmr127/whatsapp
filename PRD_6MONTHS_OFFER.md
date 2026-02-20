# Product Requirements Document: Six-Month Free "Launch Offer"

## 1. Executive Summary
The **Six-Month Free Launch Offer** is a strategic initiative designed to accelerate user acquisition and retention for the WhatsApp Business API platform by providing early adopters with a full-featured, zero-cost trial period. This offer eliminates the barrier to entry and allows users to experience the platform's premium capabilities before committing to a paid subscription.

---

## 2. Objectives
*   **User Acquisition:** Incentivize new sign-ups by offering significant value upfront.
*   **Product Exposure:** Allow users to explore all premium features (Automations, AI, Analytics) without restriction.
*   **Early Adopter Loyalty:** Build a base of power users who are deeply integrated into the system by the time the trial expires.
*   **Data Collection:** Gather usage patterns to refine pricing tiers and feature packaging.

---

## 3. Target Audience
*   New users registering during the promotional "Launch" phase.
*   Small to medium-sized businesses looking for a robust WhatsApp automation solution.

---

## 4. Key Features & Functionality

### 4.1. Automated Trial Activation
*   Upon account creation, the system automatically assigns the `trial` status to the team.
*   The `trial_ends_at` date is calculated as 6 months (configurable) from the date of registration.

### 4.2. Resource Limits (The "Offer" Tier)
During the trial, users are granted limits that exceed the standard 'Basic' plan:
*   **Monthly Message Limit:** 5,000 outbound messages.
*   **Agent Seats:** Up to 5 team members.
*   **WhatsApp Accounts:** Up to 5 connected phone numbers.

### 4.3. Premium Feature Access
All premium modules are unlocked for trial users, including:
*   AI Auto-Replies & Chatbot logic.
*   Advanced Automation Builder.
*   Detailed Analytics Dashboards.
*   Commerce Integration.
*   API & Webhook access.

### 4.4. Welcome Gift (Free Credits)
*   A one-time credit (default: $5.00) is deposited into the team's wallet immediately upon signup to cover initial conversation charges.

### 4.5. Billing Dashboard Experience
*   **Launch Offer Banner:** A high-visibility, premium-styled banner in the billing section.
*   **Countdown Timer:** Real-time calculation of days remaining in the offer.
*   **Transparency:** Clearly states "Launch Offer Active" and the specific expiration date.

---

## 5. Technical Configurations (Administrative Controls)
The offer is highly configurable via the system settings:
*   `offer_enabled`: Toggle to activate/deactivate the promotion globally.
*   `offer_trial_months`: Integer (Default: 6) defining the duration.
*   `offer_initial_credit`: Float (Default: 5.00) for the welcome gift.
*   `offer_message_limit`: Integer (Default: 5000).
*   `offer_agent_limit`: Integer (Default: 5).
*   `offer_included_features`: JSON array of slugs (e.g., `["ai", "automations", "analytics"]`).

---

## 6. Post-Trial Workflow
*   **Grace Period:** A 7-day grace period is provided if the user downgrades/expires to prevent immediate service disruption.
*   **Notification:** (Planned) Email and in-app alerts 30, 7, and 1 day(s) before expiration.
*   **Conversion:** Users are prompted to choose a paid plan (Starter/Pro/Enterprise) to retain their settings and data.

---

## 7. Success Metrics
*   **Conversion Rate:** Percentage of trial users who transition to a paid plan.
*   **Retention Rate:** Percentage of users active after the 6-month mark.
*   **Average Usage:** Message volume and feature adoption rates during the trial period.
