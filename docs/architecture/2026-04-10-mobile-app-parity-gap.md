# Mobile App Parity Gap (Web → Mobile)

Date: 2026-04-10

This document maps the current Flutter implementation in `mobile_app/` against the web application feature surface (routes + modules) and identifies the remaining work required to reach full parity.

## Current Mobile App Surface (Observed)

Flutter screens currently present:

- Auth: login screen + workspace selection
- Inbox: conversation list
- Chat: message list + send text (local cache via Isar)
- Analytics: dashboard summary
- Broadcasting: launch a campaign (basic)
- Contact profile: UI-only placeholder
- Starred messages + media gallery: local-only views backed by Isar

Key mobile infrastructure present:

- REST client (Dio) with bearer token storage (Secure Storage)
- Tenant header support (`X-Tenant-ID`)
- WebSocket client (laravel_echo + pusher_client) and presence subscription
- Local DB (Isar) currently storing messages only

## Backend Mobile API Surface (Already Available)

Primary mobile routes exist under `/api/v1/mobile/*` in [api.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/routes/api.php#L67-L117):

- Inbox: conversations list/show/read/assign/close
- Chat: messages list/send/delete/forward/star/react, templates list/send-template
- Notes: list/create per conversation
- Canned messages list
- Media upload
- Contacts: tags/search/show/update/toggle-tag
- Analytics dashboard
- Campaigns index/create
- Presence heartbeat/leave
- FCM token register/remove

Additional APIs relevant to parity (outside `/mobile`):

- Calls APIs under `/api/v1/calls/*`
- WhatsApp call settings & permission APIs under `/api/v1/whatsapp/*`

## Parity Matrix

Legend:

- ✅ Implemented (end-to-end)
- 🟡 Partial (UI exists or API exists, but not fully wired / missing key behaviors)
- ❌ Missing

| Module | Web Feature Surface | Backend API Readiness | Mobile App Status | Notes / Next Work |
|---|---|---:|---:|---|
| Auth | Fortify/Jetstream + passwordless OTP + OAuth | 🟡 | 🟡 | Mobile currently supports token-based login; needs OTP/passwordless parity, token refresh/expiry handling, and “login via email OTP” UX. |
| Tenant / Teams | Multi-team workspace + permissions | ✅ | 🟡 | Tenant selection UI exists; needs team list to come from backend in a stable contract + full “switch workspace” UX from settings. |
| Dashboard | Web dashboard widgets | 🟡 | ❌ | Need mobile dashboard screens and APIs to power widgets (or reuse analytics + summary endpoints). |
| Inbox (Conversations) | `/chat` + routing + assignment | ✅ | 🟡 | Conversation list loads; needs filters (open/closed/assigned), search server-side, unread correctness, and pagination UX. |
| Chat (Messages) | realtime, optimistic UI, status updates | ✅ | 🟡 | Local Isar message cache exists; needs full message types (image/doc/template), realtime apply-to-cache, cursor paging correctness, and “gap recovery”. |
| Multi-agent Presence | presence, typing, soft locks | 🟡 | 🟡 | Typing whisper wired; needs presence roster UI, lock/heartbeat/takeover parity and API surface for mobile (web currently uses separate endpoints). |
| Internal Notes | note timeline per conversation | ✅ | ❌ | UI toggle exists but not wired; needs notes list/create and rendering. |
| Canned Replies | `/settings/canned-messages` + picker | ✅ | 🟡 | Fetch exists and suggestion bar exists; needs full picker, shortcuts, and server-side management screens (or WebView). |
| Contact Management | `/contacts` + tags + fields | ✅ | 🟡 | Contact profile screen is placeholder; needs real fetch/update/tags toggle + contact search + editing of custom fields. |
| Templates | web templates UI | ✅ | 🟡 | Broadcasting pulls templates; needs full templates list/preview and send-template flow from chat. |
| Broadcasting / Campaigns | wizard + live campaign dashboards | ✅ | 🟡 | Can create campaign; needs list/history, campaign “live progress” screen (WebSocket: `private-campaign.{id}.progress`). |
| Automations | builder + runs/logs | ❌ | ❌ | Requires new mobile UI and likely additional mobile APIs to browse/create/edit workflows safely. |
| Flows | flow builder + execution | ❌ | ❌ | Requires new mobile UI; might initially use WebView for builder parity. |
| Analytics | dashboards/explorer/cohorts/heatmaps | 🟡 | 🟡 | Only summary dashboard exists; deep analytics parity needs new endpoints/screens or WebView-based parity for complex explorers. |
| Notifications (In-app) | notification dropdown + database notifications | 🟡 | ❌ | Needs a notifications center screen and APIs to list/mark read. |
| Push Notifications | FCM device tokens | ✅ | 🟡 | Firebase init exists; needs registration to backend, deep linking into conversations/campaigns, and notification preferences. |
| Calls | call history/analytics/settings + live overlay | ✅ | ❌ | Needs call module UI and realtime call events wiring (CallOffered, CallAnswered, etc.). |
| Commerce | products/orders/settings/checkout | 🟡 | ❌ | Depends on existing commerce APIs; likely staged delivery. |
| Settings | settings hub, categories, routing, system | 🟡 | 🟡 | Profile screen exists but not wired; needs settings modules, plus admin/dev in WebView. |
| Developer Portal | api tokens, webhook sources, logs | ✅ (web) | 🟡 | Planned via WebView SSO handoff. |
| Super Admin | tenant management/impersonation/audit | ✅ (web) | 🟡 | Planned via WebView SSO handoff. |

## High-Impact Gaps for “Parity + Sync”

1. Offline-first beyond messages:
   - queued mutations (outbox) for assignments, notes, contact updates, campaign actions
   - persistent retry policy and visibility into pending actions
2. Realtime correctness:
   - apply broadcast events into the local DB (not only refresh lists)
   - reconnect + “gap sync” with cursor/timestamp deltas
3. Auth/tenant robustness:
   - OTP/passwordless parity
   - workspace switching and local data partitioning per tenant
4. Missing mobile endpoints for parity:
   - notification list/mark read
   - conversation locking APIs usable with Sanctum + tenant header

