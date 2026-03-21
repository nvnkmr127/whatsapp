# Analytics Migration Plan
## What to Keep in MySQL vs Move to PostHog

> [!IMPORTANT]
> **Core Principle**: MySQL stays as the source of truth for all transactional data (message counts, delivery rates, campaign results). PostHog receives *behavioral* events (what users DO in the app). These are complementary — not a replacement.

---

## 🔍 Current Analytics Audit

Your system already has 7 analytics Livewire components. Here is what each one does and how it changes:

| Component | What it does now | After PostHog |
| :--- | :--- | :--- |
| [AnalyticsDashboard.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/AnalyticsDashboard.php) | SQL queries on `messages`, `tickets`, `wallet` | **Keep in MySQL.** Add PostHog for trend overlays |
| [CampaignFunnel.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/CampaignFunnel.php) | SQL: Sent → Delivered → Read → Replied → Orders | **Keep in MySQL.** Mirror the funnel in PostHog too |
| [AutomationAnalytics.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Automations/AutomationAnalytics.php) | SQL on `automation_runs`, `step_ledger` | **Keep in MySQL.** Very complex DB logic — not worth moving |
| [CohortAnalysis.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/CohortAnalysis.php) | SQL-based cohort grouping | **Move to PostHog.** PostHog's cohorts are far more powerful |
| [EventDashboard.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/EventDashboard.php) | Custom events from `customer_events` table | **Move to PostHog.** This is exactly what PostHog is for |
| [EventExplorer.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/EventExplorer.php) | Browse raw events from DB | **Move to PostHog.** PostHog's explorer is better |
| [TemplateHeatmap.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/TemplateHeatmap.php) | SQL on campaign delivery per template | **Keep in MySQL.** Business-critical metric, needs exact numbers |
| [ModuleInsights.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/ModuleInsights.php) | Usage stats per module | **Move to PostHog.** PostHog tracks this natively via `$pageview` |

---

## 📐 MIGRATION RULE: Which Data Lives Where

```
MySQL (Your DB) = WHAT happened (exact counts, money, statuses)
  ✅ Message count: 12,847 sent
  ✅ Campaign delivery rate: 94.2%
  ✅ Revenue attributed: ₹48,200
  ✅ Ticket resolved count: 32

PostHog = HOW users behave (actions, flows, drop-offs)
  ✅ "User opened Campaign Creator but never launched"
  ✅ "User switched templates 3 times before sending"
  ✅ "80% of teams drop off at Step 2 of onboarding"
  ✅ "Team has not logged in for 7 days"
```

---

## 📦 MILESTONE A — Keep These in MySQL (No Change Needed)

These components stay exactly as they are. **Zero work required.**

| Component | Reason to Keep |
| :--- | :--- |
| [AnalyticsDashboard](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/AnalyticsDashboard.php#14-438) — message sent/received | Exact transactional counts. MySQL is authoritative. |
| [AnalyticsDashboard](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/AnalyticsDashboard.php#14-438) — delivery rate summary | Business-critical SLA metric. Needs exact DB numbers. |
| [CampaignFunnel](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/CampaignFunnel.php#12-120) — Sent/Delivered/Read/Replied | PostHog can't backfill past WhatsApp delivery states. |
| [AutomationAnalyticsService](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/AutomationAnalyticsService.php#12-528) — step timing | Complex DB joins, backfilled from `execution_history`. Keep in DB. |
| `TemplateHeatmap` — per-template delivery | Exact per-template CSV export needed. MySQL is easier. |

---

## 📦 MILESTONE B — Move These to PostHog (High Value)

### B-1: Replace [EventDashboard.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/EventDashboard.php) with PostHog
**What it does now**: Reads from `customer_events` table in MySQL.
**The problem**: Every new event type you want requires a new DB column or JSON blob expansion.
**PostHog Solution**: PostHog is literally a customer events database. You stop writing to `customer_events` and fire PostHog events instead. The PostHog Explorer replaces the entire `EventDashboard` UI.

**Micro-tasks:**
1. List all event types currently in `customer_events` table (query: `SELECT DISTINCT event_type FROM customer_events`)
2. Map each `event_type` to a PostHog event name (e.g., `flow_started` → `automation_triggered`)
3. Update code that writes to `customer_events` to also call `PostHogService::capture()`
4. Run both in parallel for 2 weeks (write to both MySQL and PostHog)
5. After 2 weeks, verify PostHog data matches MySQL data
6. Remove writes to `customer_events` table (keep the table, just stop writing)
7. Update [EventDashboard.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/EventDashboard.php) to show a link to PostHog instead of DB data

---

### B-2: Replace [CohortAnalysis.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/CohortAnalysis.php) with PostHog Cohorts
**What it does now**: SQL GROUP BY queries to find users with similar behaviors.
**The problem**: You have to write a new SQL query for every new cohort idea.
**PostHog Solution**: PostHog Cohorts are drag-and-drop. "Teams that launched 3+ campaigns AND have not logged in for 7 days" — done in 30 seconds.

**Micro-tasks:**
1. Identify the 5 most-used cohort types in [CohortAnalysis.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/CohortAnalysis.php) (read the component)
2. Recreate those 5 cohorts in PostHog UI (no code needed)
3. Add a "View Advanced Cohorts →" button in your [CohortAnalysis.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/CohortAnalysis.php) Livewire view that links to PostHog
4. Do NOT delete the SQL cohort component yet — keep it for teams who prefer in-app view
5. After 1 month of using PostHog cohorts, decide if the SQL component is still needed

---

### B-3: Replace [EventExplorer.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/EventExplorer.php) with PostHog Activity Feed  
**What it does now**: A paginated table of raw events from DB.
**PostHog Solution**: PostHog's "Live Events" and "Activity" viewers are realtime and more filterable.

**Micro-tasks:**
1. In [EventExplorer.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/EventExplorer.php) view, add a banner: *"Advanced event exploration available in PostHog"* with a link
2. Keep the DB-based explorer for basic users (some clients may not have PostHog access)
3. Gate the PostHog link behind a Super Admin check

---

### B-4: Replace [ModuleInsights.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/ModuleInsights.php) with PostHog Pageviews
**What it does now**: Counts how many times each module/page was accessed (from DB or logs).
**PostHog Solution**: PostHog auto-captures `$pageview` events with the URL. You get per-page usage heatmaps automatically as soon as you add the JS snippet.

**Micro-tasks:**
1. Add PostHog JS snippet to layout (already in M1 of the main plan)
2. In PostHog, go to Insights → Trends → Filter by `$pageview` + `current_url`
3. Build a "Most Used Pages" dashboard in PostHog
4. Optionally, retire [ModuleInsights.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/ModuleInsights.php) if PostHog covers the same data

---

## 📦 MILESTONE C — New Analytics Only PostHog Can Do

These are insights you **cannot build with SQL** right now:

### C-1: Onboarding Drop-off Funnel
PostHog shows: "70% of teams complete Step 1, only 40% complete Step 2."
This would require tracking intermediate onboarding steps in DB — PostHog does it with 1 event per step.

**Micro-tasks:**
1. Identify all onboarding steps in [OnboardingService.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/OnboardingService.php)
2. Add a `PostHog::capture('onboarding_step_viewed', ['step' => N])` call at each step
3. In PostHog, build a Funnel of all steps
4. Share funnel with team to identify where clients drop off

---

### C-2: Session Recording for UX Debugging
When a client reports "the campaign wizard is broken," you currently ask them to explain what happened. PostHog session recordings let you *watch exactly what they did*.

**Micro-tasks:**
1. Enable Session Recording in PostHog Project Settings (free: 5,000 sessions/month)
2. Add `session_recording: true` to the JS snippet config
3. In PostHog, filter recordings by `team_id` to watch a specific client's session

---

### C-3: "Sleeping Teams" Alert
Identify teams that signed up but haven't launched a campaign in 14 days.

**Micro-tasks:**
1. In PostHog, create a Cohort: "Teams that did `user_logged_in` but NOT `campaign_launched` in the last 14 days"
2. Export that cohort list
3. Use it to trigger a re-engagement WhatsApp message via your own Broadcast system

---

## ✅ Final Summary: Old vs New

| Analytics Type | Before | After |
| :--- | :--- | :--- |
| Message delivery stats | MySQL query in [AnalyticsDashboard](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/AnalyticsDashboard.php#14-438) | ✅ Still MySQL |
| Campaign funnel (Sent/Delivered/Read) | MySQL in [CampaignFunnel](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Livewire/Analytics/CampaignFunnel.php#12-120) | ✅ Still MySQL |
| Automation step stats | MySQL in [AutomationAnalyticsService](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/app/Services/AutomationAnalyticsService.php#12-528) | ✅ Still MySQL |
| Revenue attribution | MySQL + Orders table | ✅ Still MySQL |
| Raw customer events | `customer_events` MySQL table | 🔄 Migrate to PostHog |
| Cohort analysis | SQL GROUP BY | 🔄 PostHog Cohorts |
| Module page usage | Custom logging | 🔄 PostHog Pageviews (auto) |
| Onboarding funnel | Not tracked | 🆕 New in PostHog |
| Session recordings | Not possible | 🆕 New in PostHog |
| Sleeping team alerts | Manual SQL | 🆕 New in PostHog |

> [!TIP]
> **Week 1**: Do Milestones 1-3 from the main plan (Foundation + Events). Your MySQL analytics keep working with zero change.
> **Week 3**: Start Milestone B — migrate `EventDashboard` and `CohortAnalysis` to PostHog.
> **Month 2**: Milestone C gives you insights you've never had before.
