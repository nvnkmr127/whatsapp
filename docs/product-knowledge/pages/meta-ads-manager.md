# Meta Ads Manager

## 1. What is this page?
The Meta Ads Manager page (Ads Overview) is a unified reporting and management dashboard that integrates a business's Meta (Facebook/Instagram) advertising account directly with the platform. It tracks ad budgets, click performance, and conversation metrics specifically for Click-to-WhatsApp ad campaigns.

## 2. Why is this page useful?
Many businesses run Click-to-WhatsApp ads to drive leads directly into their WhatsApp inbox.
- **Why do users need it?** To see the direct relationship between their Facebook ad spend and the actual customer conversations started in their inbox.
- **What work does it make easier?** It allows marketers to pause underperforming ads or resume active ones directly from this platform, without logging into the complex Meta Ads Manager portal.
- **What business process does it support?** Paid Lead Generation, Ad Spend Optimization, and Conversational ROI tracking.
- **What happens without it?** Marketers would have to manually compare Facebook spend reports on Meta with incoming contact numbers in their database, making it extremely difficult to calculate the exact cost-per-lead.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To monitor advertising budgets, connect Facebook accounts, and review billing currencies. |
| Marketing Manager | To audit ad creatives, view click-through rates (CTR), read optimization tips, and toggle ad statuses. |

## 4. What can users do here?
- **Connect Meta Account:** Links their Facebook business page and advertising access token (via Embedded Signup).
- **Select Ad Account:** View and switch between multiple linked Meta Ad Accounts, displaying native currencies (USD, INR, EUR, etc.).
- **Monitor Global Metrics:**
  - **Money Spent:** Total budget spent on ads.
  - **Messages Started:** Total WhatsApp conversations initiated by clicking an ad.
  - **Times Seen:** Total impressions.
  - **Cost Per Click (CPC):** Average cost for each click.
- **Drill Down Ad Architecture:**
  - **Level 1 (Campaigns):** View campaign objective, spend, click rates, and ROAS.
  - **Level 2 (Ad Sets / Groups):** Click a campaign to view daily budgets, clicks, and group performance.
  - **Level 3 (Ads):** Click an ad group to view ad thumbnails, shareable links, and individual creative stats.
- **Toggle Ads Status:** Click simple toggle switches to immediately pause or resume campaigns, ad groups, or individual ads on Facebook.
- **Read Smart Tips:** View automated insight cards flagging low CTR warnings or expensive Cost-Per-Click alerts.
- **Apply Date Presets:** Filter campaign performance by Today, Yesterday, Last 7 Days, Last 30 Days, or Maximum range.

## 5. What is involved?
- **Integration Model:** Stores the access tokens and connection state for the `meta_marketing` integration.
- **MetaMarketingService:** The API service that communicates with Meta's Graph Marketing APIs to fetch and modify campaign structures.
- **Embedded Signup Component:** The wizard used to link the Facebook accounts.

## 6. How does it work?
1. The user navigates to the Ads Manager page. If not connected, they see a Facebook link screen.
2. Once connected, they pick an Ad Account from the left-hand sidebar.
3. The page queries Meta's API to fetch campaigns and matching insights for the selected date range.
4. It displays summary cards (Total spend, impressions, CPC, conversions) and a list of campaigns.
5. If the marketer sees a campaign is performing poorly (e.g. ROAS is low), they click the status toggle.
6. The backend makes an API call to Meta to pause the campaign, and refreshes the table.

## 7. What happens behind the scenes?
- **API mapping:** When fetching insights, the service maps data rows returned from Meta to campaign, ad group, or ad objects using IDs (`campaign_id`, `adset_id`, `ad_id`).
- **Dynamic Currency Mapping:** The system reads the ad account's default currency (e.g., INR, USD) from Meta and dynamically displays the correct symbol (`₹`, `$`) across all metrics.
- **Insight Rules Engine:** The page evaluates the data to generate "Smart Tips":
  - **Low Clicks Warning:** Triggered if a campaign is active, has spent over $5, but has a Click-Through-Rate (CTR) under 1.0%.
  - **High Cost Warning:** Triggered if cost-per-click (CPC) is greater than $2.50 on a campaign with over $10 spend.
- **ROAS Simulation:** Maps revenue generated from inbox conversions against the ad spend to calculate return on ad spend (ROAS).

## 8. Business Use Cases

**Use Case 1: Tracking Click-to-WhatsApp ROI**
- **Situation:** A retail brand spends $500 on Facebook ads pointing to their WhatsApp catalogue and wants to know if it's working.
- **How the feature is used:** They open the Ads Manager page, filter by the last 30 days, and look at the "Messages Started" and "ROAS" columns.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Direct insight showing that the $500 spend generated 250 messaging leads and a 3.5x return, proving the marketing channel's value.

**Use Case 2: Pausing Expired Ad Creatives**
- **Situation:** A seasonal campaign (e.g. Summer Sale) has ended, but some ads are still running and costing money on Facebook.
- **How the feature is used:** The manager opens this page, filters by "Active Only", finds the summer campaigns, and clicks the toggle switches to turn them off.
- **Customer experience:** Facebook users stop seeing the outdated summer ads.
- **Business outcome:** Prevents wasted budget without needing to navigate Meta's heavy advertising dashboard.

**Use Case 3: Optimizing Expensive Ad Sets**
- **Situation:** A marketer notices a "High Cost" tip in the sidebar indicating that their "Boston Audience Group" ad is charging $3.00 per click.
- **How the feature is used:** They read the Smart Tip, drill down into the ad set to verify the budget limit, and decide to pause that ad set while keeping the cheaper "New York" group active.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Lowered average customer acquisition cost.

## 9. Industry Use Cases
- **Real Estate:** Monitoring budgets for local housing ads, tracking how many people click the ad to text agents.
- **Automotive:** Stopping expensive vehicle listing ads once the vehicles are sold.
- **E-Commerce:** Tracking direct ROAS from product catalog ads to measure store revenue.

## 10. Real Customer Example
A local dentist connects their Facebook account to the platform. They run a "Free Teeth Whitening Consultation" ad. They check this page daily. The dashboard shows they have spent $50, got 20 clicks, and started 15 WhatsApp chats (meaning a 75% click-to-chat conversion rate). The Cost-Per-Click is $2.50, and their smart tips sidebar shows "Doing Well - No action needed". The dentist continues running the ad, confident that they are getting high-quality bookings for their spend.

## 11. Customer Journey
Admin connects Facebook &rarr; Selects Ad Account &rarr; Reviews summary spend & WhatsApp conversions &rarr; Reads Smart Tips warnings &rarr; Drills down into ads &rarr; Pauses expensive ads directly from dashboard.

## 12. Inputs
- Meta Facebook integration credentials.
- Selected date range preset.
- Search term queries.
- Ad status toggles.
- Bulk check selections.

## 13. Outputs
- API calls to Meta to fetch stats.
- Meta status changes (Active/Paused).
- Generated Smart Tips alerts.

## 14. Dependencies
- **Integration Model:** Database connection table.
- **Meta Marketing API:** Meta's external server.
- **Meta Marketing Service:** Backend proxy.
- **Meta Embedded Signup:** Auth routing.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission on plans including `campaigns`.
- **Who can view information:** Marketers/Admins.
- **Who can edit:** Marketers/Admins.
- **Who cannot access it:** Standard agents.

## 16. Important Rules
- Ad budget metrics (daily budget limits) returned from Meta are typically stored in cents/paise and are divided by 100 on this page for display.
- To execute status changes, the integration must have active edit permissions from Meta's Ads API.

## 17. Common Problems
- **Problem:** "Failed to load Ad Accounts" or empty list.
  - **Possible reason:** The Facebook integration has expired, user changed their Facebook password, or the integration does not have the necessary `ads_management` API permission.
  - **What the user should do:** Disconnect the integration and complete the Meta Embedded Signup again, ensuring all permissions are checked.
- **Problem:** Budget currency shows strange symbols.
  - **Possible reason:** The currency returned by Meta is not in the system's mapping list.
  - **What the user should do:** Let technical support know so they can add the currency code (e.g. CAD, AUD) to the currency symbol map in `MetaAdsManager.php`.

## 18. Simple Explanation for Sales
The Meta Ads Manager page brings your Facebook and Instagram ad metrics right inside our platform. It shows you exactly how much you're spending on ads and how many WhatsApp chats those ads are bringing in, letting you pause or resume ads with one click.

## 19. Simple Explanation for Marketing
Track your click-to-chat ROI easily. You can drill down from campaigns to ad groups to creatives, see cost-per-click values, toggle active ads, and read automatic tips on how to improve your ad performance.

## 20. Simple Explanation for Support
If you get a customer who says "I clicked your Facebook ad to talk to you", this is the page where the marketing team sets up and manages those ad triggers.

## 21. Related Features
- [Campaigns List](./campaign-list.md)
- [WhatsApp Configuration](./whatsapp-setup.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/ads/manager`
- **Implementation:** `App\Livewire\Ads\MetaAdsManager`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Ads/MetaAdsManager.php`
  - `resources/views/livewire/ads/meta-ads-manager.blade.php`
  - `app/Services/Integrations/MetaMarketingService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
