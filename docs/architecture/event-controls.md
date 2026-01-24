# Event Controls: Signal vs. Noise

## 1. Classification Strategy
We categorize every event into one of three buckets to determine its lifecycle, storage, and visibility.

| Category | Description | Examples | Analytics | Retention | UI Visibility |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Business (Signal)** | Critical domain occurrences driving value. | `OrderPlaced`, `MessageReceived`, `ContactOptedIn` | **Yes** | 7 Years (Cold Store) | **Visible** |
| **Operational (System)** | Health and performance indicators. | `CampaignProgress`, `JobFailed`, `TokenExpiring` | No | 30 Days | Admin Only |
| **Debug (Noise)** | Low-level tracing for development. | `PayloadDump`, `QueryExecuted`, `CacheHit` | No | 7 Days (or 0) | Hidden |

## 2. Analytics Eligibility
An event is "Analytics Eligible" (Counted in Dashboards) ONLY if:
1.  **Category** is `Business`.
2.  **Status** (if applicable) is strictly defined (e.g., `MessageSent` is a signal, but `MessageQueued` is operational noise).

## 3. Sampling Rules
Sampling is strictly forbidden for **Business** events. It is applied to High-Volume Operational/Debug events.

*   **Strategy**: Deterministic Sampling (based on Trace ID hash) ensures strictly linked events are either ALL kept or ALL dropped, preventing broken traces.
    *   *Rule*: `if (hash(trace_id) % 100 < sampling_rate) keep()`
*   **Rates**:
    *   Business: 100%
    *   Operational (Errors): 100%
    *   Operational (Success): 10%
    *   Debug: 1% (On-demand)

## 4. Retention Policies
Implemented via Database Partitioning or Time-to-Live (TTL) indexes.

1.  **Hot Storage (Redis/Cache)**: 24 Hours. All events for real-time UI.
2.  **Warm Storage (RDBMS - `events_table`)**:
    *   Business: 1 Year
    *   Operational: 30 Days
3.  **Cold Storage (Data Lake/S3)**:
    *   Business: Indefinite
    *   Operational: 90 Days

## 5. UI Visibility Controls
The Frontend consumes events via Websockets.
*   **Activity Feed**: Filters for `metadata.category = 'business'`.
*   **System Status**: Filters for `metadata.category = 'operational'` AND `metadata.severity = 'critical'`.
*   **Debug Console**: Shows all events if Developer Mode is valid.

## 6. Implementation
The `DomainEvent` class adds:
*   `category()`: Returns the classification.
*   `isSignal()`: Helper to check if it's high-value.
*   `shouldLog()`: Applies sampling logic.
