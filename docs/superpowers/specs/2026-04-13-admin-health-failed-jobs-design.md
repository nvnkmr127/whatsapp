# Admin Health: Failed Jobs List (Last 24h)

## Goal

Expose a full, actionable list of failed queue jobs from the last 24 hours directly on `/admin/health`, so super admins can identify spikes (e.g. “High Job Failure Rate: 181 failures in the last 24h”) and drill into individual failures (including searching by a pasted `trace_id`).

## Scope

### In

- Display a paginated, searchable list of failed jobs (DB-backed `failed_jobs`) scoped to the last 24 hours on `/admin/health`.
- Support row-level actions:
  - View full failure details (modal/drawer).
  - Retry a single failed job (by uuid).
  - Forget/delete a single failed job (by uuid).
- Provide search that matches substrings inside `exception` and `payload` so pasting a trace id finds relevant failures (when present).
- Keep existing global actions (“Retry all failed jobs”), and optionally add “Clear failed jobs” since the route exists.

### Out

- Long-term storage/analytics beyond `failed_jobs`.
- Cross-day filters or time range selection (last 24h only).
- New alerting rules or notification delivery changes.

## Users & Permissions

- Only Super Admins can access `/admin/health` and the failed jobs list (already enforced by `EnsureUserIsSuperAdmin` middleware around the route group).

## UX / UI

### Placement

- Embed a new “Failed Jobs (Last 24h)” section into the existing `/admin/health` page.
- Replace the current “Recent Errors” group-by card (or keep it and add the table below it) depending on layout constraints.

### Table Columns (default)

- Failed at
- Team (derived; fallback to “System/Unknown”)
- Queue / connection
- Job (derived from payload; fallback to “Unknown”)
- Exception (single line, truncated)
- Actions: View, Retry, Forget

### Details Modal/Drawer

Show:
- uuid
- failed_at
- connection / queue
- extracted team id + team name (if present)
- job class / display name (if derivable)
- full exception (preformatted)
- payload preview (collapsed by default)

### Search

- A search input at top of table.
- Matching behavior: `exception LIKE %q% OR payload LIKE %q%`, scoped to last 24h.

### Refresh Behavior

The page currently performs a full page reload every 60 seconds. This should be removed or replaced with component-only polling, otherwise pagination/search state will be lost while investigating failures.

## Data & Querying

### Source

- Database table: `failed_jobs`.

### Default Scope

- `failed_at >= now()->subDay()`
- Sort: `failed_at desc`
- Pagination: 25 per page (configurable)

### Derived Fields

- Team id / team name:
  - Attempt extraction from the serialized `payload.data.command` blob (same approach already used in `SystemHealthController::getBackgroundJobSnapshots()`).
  - When no team id is found or team is missing: “System/Unknown”.
- Job name/class:
  - From JSON payload keys where available (e.g. displayName / job class inside the payload).
  - Fallback: “Unknown”.

## Operations

### Retry Single Job

- Use `queue:retry {uuid}`.

### Forget Single Job

- Use `queue:forget {uuid}`.

### Existing Global Actions

- Retry all failed jobs: `queue:retry all` (already exists).
- Clear all failed jobs: `queue:flush` (route exists; UI can expose it).

## Security & Privacy

- Payloads may include user data; ensure only super admins can see details.
- Do not log payload/exception contents during viewing actions.

## Performance

- Always apply the last-24h filter before text search to avoid scanning the full `failed_jobs` table.
- Prefer loading only truncated exception/payload content for the table and fetch full details only when opening the modal (optional optimization).

## Implementation Notes

- Recommended implementation: embed a Livewire component in the existing Blade view for `/admin/health` to support pagination, search, modal, and row actions without full page reloads.
- Consider extracting the existing team-id parsing helpers from `SystemHealthController` into a reusable utility so the controller and new component share the same logic.

## Acceptance Criteria

- `/admin/health` shows a paginated “Failed Jobs (Last 24h)” list.
- Searching by a pasted trace id filters results based on substring match in `exception` or `payload`.
- “View details” shows full exception and key metadata for a row.
- “Retry” retries a single failed job by uuid and shows success feedback.
- “Forget” removes a single failed job entry by uuid and shows success feedback.
- No full-page auto-refresh disrupts pagination/search state.

