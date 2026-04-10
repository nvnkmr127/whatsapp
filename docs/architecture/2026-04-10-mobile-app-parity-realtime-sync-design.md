# Mobile App (Flutter) — Web Parity + Real‑Time Sync Design

Date: 2026-04-10

## Summary

Build an iOS/Android Flutter application that reaches functional parity with the existing Laravel web app while adding robust real‑time synchronization and offline-first behavior. The mobile app consumes existing REST APIs (notably `/api/v1/mobile/*`) and the existing WebSocket/broadcasting layer (Laravel Reverb), and introduces a client-side sync engine with an outbox queue, delta sync, and conflict handling.

Super Admin and Developer Portal features are included via authenticated WebView (functional parity, not native UI).

## Goals

- Match web application core capabilities on mobile across modules:
  - Dashboard
  - Inbox/Chat (multi-agent collaboration)
  - Contacts/CRM
  - Campaigns/Broadcasting
  - Templates
  - Automations + Flows
  - Analytics/Reporting
  - Settings
  - Notifications
  - Calls
  - Commerce
- Implement real-time sync between web and mobile using the existing Reverb WebSocket broadcasting system.
- Provide offline-first UX:
  - Local data cache for browsing.
  - Offline mutation queue (outbox) for writes with retries and idempotency.
  - Automatic reconciliation on reconnect.
- Native integrations:
  - Camera/media upload
  - Push notifications
  - GPS where web workflows require location data
- Resilient error handling for intermittent networks, reconnects, and message gap recovery.

## Non-Goals (initially)

- Replacing the existing web UI.
- Re-architecting backend domain logic. Backend changes should be additive: new endpoints, delta sync, and idempotency support.
- Building a complete native UI for Super Admin + Developer Portal (WebView used).

## Current Backend Surfaces (Observed)

- Backend stack: Laravel 11 + Sanctum + Jetstream/Fortify + Livewire 3.
- Real-time: Laravel Reverb with Echo client.
- Tenanting in API: enforced via `X-Tenant-ID` header against user teams (`EnsureTenantContext` middleware).
- Mobile API routes exist under `/api/v1/mobile/*` with controllers in `app/Http/Controllers/Api/Mobile/*`.
- WebSocket channels are defined in `routes/channels.php`:
  - `private-teams.{teamId}`
  - `presence-conversation.{conversationId}`
  - `private-campaign.{campaignId}.progress`

Feature inventory reference: `docs/feature-mapping.md`.

## Proposed Mobile Architecture

### High-Level Layers

- Presentation: Flutter UI (screens, navigation, view models/state).
- Domain: use-cases per workflow (send message, assign, close, create campaign, etc.).
- Data:
  - REST API client
  - Realtime client (Reverb WebSocket)
  - Local store (SQLite)
  - Sync engine (delta sync + outbox)

### Module Boundaries (Mobile)

- Auth + Tenant Selection
- Dashboard
- Inbox/Chat
- Contacts
- Campaigns
- Templates
- Automations
- Flows
- Analytics
- Calls
- Commerce
- Settings
- Notifications
- WebView Hub (Super Admin + Developer Portal)

## Authentication & Tenancy

### Auth

- Mobile uses token-based auth (Sanctum personal access tokens or equivalent API token flow already exposed).
- Token stored securely:
  - iOS Keychain
  - Android Keystore

### Tenant Selection / Switching

- Mobile must include `X-Tenant-ID: <team_id>` on every API request.
- On login, if the user belongs to multiple teams, mobile prompts for active team, then:
  - Uses that team ID for REST + WebSocket subscriptions.
  - Partitions local storage by `team_id` (recommended) to avoid cross-tenant leakage.

## Real-Time Sync (WebSockets)

### Transport

- Use Reverb WebSocket endpoints and authenticate using the same broadcasting auth mechanism as web.
- Subscribe per team to `private-teams.{teamId}` and per opened conversation to `presence-conversation.{conversationId}`.
- Subscribe to campaign progress channels for live campaign dashboards as needed.

### Event Processing

- Mobile receives events (message received/status updates, conversation lifecycle, campaign progress, call events).
- Each event is applied to the local database in an idempotent way (dedupe by event ID where available, otherwise by a stable composite key).
- UI reacts to local DB changes (single source of truth on device).

### Reconnect & Gap Recovery

- Maintain cursors per entity stream:
  - `team_events_cursor`
  - `conversation_events_cursor` (per conversation)
  - `campaign_progress_cursor` (per campaign)
- On reconnect:
  - Resume WebSocket subscriptions.
  - Trigger REST delta fetch using last cursor/timestamp to backfill missed updates.
  - Reconcile any optimistic/local pending state.

## Offline-First: Local Store + Outbox

### Local Store

- SQLite schema stores normalized entities and indexes for fast inbox + message list rendering.
- Store only what’s required for UX:
  - recent messages per conversation
  - conversation metadata (assignment, status, unread count)
  - contacts + tags
  - campaigns/templates/automations/flows/analytics cache (scoped by team)

### Outbox Queue

All writes are captured as queued mutations:

- Fields: `mutation_id (UUID)`, `team_id`, `entity_type`, `entity_id (nullable)`, `temp_entity_id (nullable)`, `operation`, `payload`, `created_at`, `attempt_count`, `next_retry_at`, `status`, `last_error`.
- Idempotency: client sends `Idempotency-Key: mutation_id` (or in payload) so server can dedupe retries.
- Retry policy:
  - exponential backoff with jitter
  - pause while offline
  - surface terminal failures (401/403/422) to user and stop retrying until user action

### Optimistic UI

- Writes immediately update the local DB as “pending”.
- When server confirms, reconcile:
  - temp IDs → server IDs
  - pending → confirmed
  - update related aggregates (unread count, last message preview)

## Delta Synchronization (REST)

### Required Patterns

- For each major entity, support a delta query:
  - `GET /...?...updated_since=<timestamp>` or `?cursor=<opaque>` for incremental sync.
- For cold start or cache clear:
  - `GET /...?...limit=...` for bulk hydration plus cursor.

### Backend Additions (Expected)

Even though a mobile API exists, achieving full parity + offline correctness typically requires adding:

- Delta endpoints for key lists (conversations, messages, contacts, campaigns, templates, automations, flows).
- Idempotent write support for outbox replay.
- Optional: batch endpoints to reduce round trips on cold start.

## Conflict Resolution

### Principles

- Server remains source-of-truth for authoritative state (message delivery status, assignments, locks).
- Client resolves most conflicts automatically and only prompts user when needed.

### Strategies

- Messages & conversation state:
  - server events overwrite client state
  - optimistic messages reconcile via server response mapping
- Editable records (contacts/settings):
  - default last-write-wins using server timestamps
  - when conflict is detected, server returns `409` with both versions; mobile provides a merge UI for that record

## Notifications

### Push

- Use FCM/APNs with server-side token registration already represented by `UserFcmToken`.
- Deep links:
  - conversation notification → open conversation
  - campaign alerts → open campaign dashboard
  - system alerts → open alerts/settings

### In-App Notifications

- Mirror database notifications used by the web UI and surface them in a mobile notifications center.
- Keep a local “seen” cursor and sync on startup/reconnect.

## Native Integrations

- Camera/media upload:
  - capture/select media locally
  - upload via mobile media endpoint
  - send message referencing uploaded media
- GPS:
  - request permission only when a workflow requires location data
  - store minimal location data required by backend fields
- Background behavior:
  - best-effort sync on app foreground
  - push-triggered refresh where supported by platform constraints

## WebView Parity (Super Admin + Developer Portal)

- Provide a “Web Tools” area in the mobile app.
- Authenticate WebView via an SSO handoff:
  - exchange mobile token for a short-lived web session token
  - open WebView with that token
- Deep links from WebView back to native screens where it improves UX (e.g., opening a conversation).

## Security & Compliance

- Never store secrets in logs.
- Secure token storage (Keychain/Keystore).
- Enforce tenant partitioning on device (team-scoped data).
- Validate TLS and pinning policy per organization requirements.
- Respect server-side authorization gates (permissions and plan-feature checks).

## Testing & Verification

- Unit tests:
  - outbox enqueue/dequeue, retry policy, idempotency handling
  - delta sync cursor logic
  - conflict resolution flows
- Integration tests:
  - login + tenant switching
  - WebSocket connect/reconnect + gap recovery
  - offline send + later sync
  - push token registration + notification open/deep link
- Manual test matrix:
  - poor network, airplane mode, rapid reconnect
  - multi-agent presence and lock handoff
  - large conversation (virtualized list performance targets)

## Rollout Plan (Phased Implementation, Continuous Delivery)

- Build the mobile app as a first-class client of existing domain logic.
- Implement the highest-churn workflows first (Inbox/Chat, Contacts, Notifications, Settings).
- Expand parity module-by-module, using `docs/feature-mapping.md` as the checklist.
- Add backend deltas/idempotency endpoints as required to make offline and reconnect semantics correct.

