# Admin Analytics & Event Explorer Design

## 1. Overview
The **Event Explorer** is a high-power observability tool for Admins and Developers to inspect the system's nervous system. It serves two distinct personas:
1.  **Support Admins**: "Why did Order #500 fail?" (Requires Human-Readable Summaries).
2.  **Developers**: "Why did the webhook payload crash?" (Requires Raw JSON & Stack Traces).

## 2. Main View: The Event Feed
A dense, high-information density table showing the stream of system events.

### Columns
| Column | Data Source | Display Logic |
| :--- | :--- | :--- |
| **Timestamp** | `occurred_at` | Localized, relative time (e.g., "5 mins ago"). Hover for ISO. |
| **Severity** | `metadata.category` | Color-coded Badge. Business (Green), Ops (Blue), Op-Error (Red). |
| **Event Name** | Class Name | Formatted: `OrderPlaced` -> **Order Placed**. |
| **Summary** | `payload` | Dynamic extraction (e.g., "Order #123 for $50.00"). |
| **Source** | `source` | Icon (Commerce, Auth, System). |
| **Trace** | `trace_id` | "Calculated" link to Trace View (e.g., "Step 3/5"). |

### Filters (Sidebar)
*   **Module**: Commerce, CRM, System, WhatsApp.
*   **Significance**: `Is Signal?` (Default: Yes). Toggle to "Show System Noise".
*   **Entity Search**: "Find events for Contact: +1555000..." (searches `metadata.actor_id` or payload).
*   **Trace ID**: Paste a UUID to isolate a transaction.

## 3. Drill-Down: The Trace Explorer
When clicking a row or Trace ID, the user enters **Focus Mode**.

### A. Journey Timeline (Left Panel)
Visualizes the causality chain using `trace_id` and `parent_id`.
```
[10:00:01] Webhook Received (Root)
  └── [10:00:02] Message Persisted
       ├── [10:00:03] Auto-Reply Triggered
       │    └── [10:00:04] Message Sent (Bot)
       └── [10:00:03] Contact Updated (Lifecycle: Active)
```
*   **Visuals**: Gantt-style bars for duration if `finished_at` is available (or delta between events).

### B. Event Detail (Right Panel)
*   **Header**: Event Name + Absolute Timestamp.
*   **Context Cards**:
    *   **User/Actor**: Who triggered this? (Link to Contact Profile).
    *   **Related Entities**: Links to Order, Ticket, etc.
*   **The Payload Viewer**:
    *   **Tab 1: Summary (Default)** -> Key/Value grid of `payload`. Hides boring fields. Formats money/dates.
    *   **Tab 2: Raw JSON** -> Syntax-highlighted full dump of `payload` + `metadata`. Copy-to-clipboard button.

## 4. Human-Readable Descriptions
Reflects on the Event Class to generate text.
*   *Strategy*: Events implement a `summary()` method or use a generic fallback.
*   *Fallback*: `Key: Value, Key: Value...` (truncated).
*   *Implementation*: `EventPresenter` service acts as a View Model.

## 5. Technical Implementation (Livewire)

### Components
1.  `Analytics\EventExplorer`: The main datatable with filters.
2.  `Analytics\TraceVisualizer`: Recursive component for the tree view.
3.  `Analytics\PayloadViewer`: Dumb component for JSON folding.

### Performance Controls
*   **Paging**: Cursor-based pagination (Infinite Scroll).
*   **Lazy Loading**: The "Raw Payload" is only fetched when the specific row is expanded to save bandwidth.
