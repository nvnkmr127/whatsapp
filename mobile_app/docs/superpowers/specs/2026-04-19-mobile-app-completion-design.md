# Mobile App Completion Design (A → B → C)

## Goal
Make the Flutter mobile app production-complete “as per system” by:
1) finishing client-side features that already have backend support,
2) adding missing backend endpoints required by existing mobile UI,
3) doing a final pass to harden behavior and remove remaining stubs.

This document uses “A then B then C” as the execution order.

## Scope

### In scope
- Fix client ↔ backend API contract mismatches (routes + response shapes).
- Complete core operational flows:
  - Inbox list, search, pagination, open chat.
  - Contact search/pick → open chat.
  - Bulk resolve/close conversations.
  - Contact tags add/remove.
- Bring calls screen/log from “stub” to “functional”.
- Make media viewer actions functional (download/share) when feasible.
- Analytics screen: make period selector and chart data come from backend, or remove misleading placeholders.
- Replace remaining simulated UI actions with real operations or explicitly disable/hide.

### Out of scope (unless explicitly requested later)
- Real VoIP calling stack (WebRTC/CallKit/PushKit) beyond server-driven call logging and basic UI.
- Full offline-first replication beyond current Isar usage.

## Current System Constraints (Observed)
- Backend routes are defined under `routes/api.php` with prefix `/api/v1`.
- Several mobile endpoints exist, but some mobile UI currently calls different paths or expects different JSON shapes.
- Mobile registration is not currently exposed under `/v1/mobile/auth/*`.

## Approach Summary

### A) Client-only completion (do first)
Objective: make the app reliably work against the current backend without changing backend behavior.

Work items:
- Align `ApiService` endpoints with `routes/api.php`.
- Fix response shape assumptions in screens (list vs `{data: ...}`).
- Finish all UI flows that can be completed with existing endpoints.
- Disable/hide UI flows that cannot be supported yet (with clear user-facing messages).

Acceptance:
- “Start chat from contacts”, “bulk resolve”, “toggle contact tags”, “calls list loads”, “analytics loads” work without simulated snackbars.
- No screen relies on hardcoded demo data when backend can provide it.

### B) Full system completion (do second)
Objective: add backend endpoints required for the existing mobile UI to be fully functional.

Work items (backend):
- Add a mobile-friendly endpoint to create/open a conversation for a contact when none exists.
- Add a profile update endpoint for mobile (or re-use existing user update policies safely).
- Extend analytics endpoint to support a `period` parameter and return a time-series suitable for the chart.
- Add campaign detail endpoint if the UI requires drill-down from broadcast history.

Acceptance:
- No major user-facing action remains “simulated” due to missing backend support.

### C) Final hardening pass (do third)
Objective: clean stubs, improve error handling, and ensure a consistent UX.

Work items:
- Replace remaining TODO/empty callbacks with working implementations or remove the UI entry point.
- Ensure consistent empty states, loading states, and error messages.
- Add basic smoke tests for the key flows (at minimum: analyzer clean + widget tests where practical).

Acceptance:
- No TODO-based user action remains in the shipped UX.

## API Contract Alignment (A)

### Endpoints to align (mobile app)
- Conversations:
  - List: `GET /v1/mobile/conversations` (supports `page`, `filter`, `q`).
  - Messages: `GET /v1/mobile/conversations/{conversation}/messages`.
  - Send message: `POST /v1/mobile/conversations/{conversation}/messages`.
  - Mark read: `POST /v1/mobile/conversations/{conversation}/mark-read`.
  - Assign: `POST /v1/mobile/conversations/{conversation}/assign`.
  - Close: `POST /v1/mobile/conversations/{conversation}/close`.

- Contacts:
  - Search: `GET /v1/mobile/contacts/search?query=...` (returns a list).
  - Show: `GET /v1/mobile/contacts/{contact}`.
  - Toggle tag: `POST /v1/mobile/contacts/{contact}/toggle-tag`.
  - Available tags: `GET /v1/mobile/contacts/tags`.

- Templates:
  - List: `GET /v1/mobile/templates`.
  - Send template: `POST /v1/mobile/conversations/{conversation}/send-template`.

- Campaigns:
  - List: `GET /v1/mobile/campaigns` (returns a list).

- Calls:
  - Calls are under `/v1/calls/*` (not `/v1/mobile/calls`).

### Response shape normalization
Where backend returns a plain JSON list, the app must not read `.data['data']`.

## Key UX Flows

### Contact → Chat
- Tap contact:
  - Fetch contact detail.
  - If `activeConversation` exists, navigate to chat.
  - If not, show “No chat yet” in A; create/open conversation in B.

### Bulk Resolve
- For selected conversations, call “close” endpoint.
- Refresh list and show a summary of success/fail.

### Contact Tags
- Show available tags picker.
- Toggle tag and refresh tags shown.

### Analytics
- In A: ensure displayed numbers come from API and remove misleading chart placeholders.
- In B: add period + series output and wire the chart to real data.

## Risks / Notes
- Registration in mobile app requires backend support; if not added, the UI should be removed/disabled.
- Media download/share requires platform permissions and additional Flutter packages.

## Definition of Done
- App can be used end-to-end for core inbox/chat flows without any “simulation” placeholders.
- All existing primary navigation paths lead to functional screens.
- Backend and mobile app agree on endpoints and JSON shapes.
