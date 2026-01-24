# Knowledge Base Indexing & Readiness Engine

This engine ensures that business information provided to the AI is correctly processed, indexed, and available for retrieval only when "Ready". It prevents the AI from providing stale or incomplete information by enforcing a strict readiness lifecycle.

## 1. Indexing Lifecycle
Information sources (Files, URLs, Raw Text) follow a deterministic state machine:

- **Pending (`pending`)**: The source is added but the extraction job hasn't started yet.
- **Processing (`processing`)**: The background worker is currently extracting text or crawling the URL.
- **Ready (`ready`)**: Content has been successfully extracted and is indexed for search.
- **Failed (`failed`)**: Extraction failed (e.g., 404 URL, encrypted PDF). The error is captured in `error_message`.

### State Transitions:
- `pending` -> `processing` (Job starts)
- `processing` -> `ready` (Success)
- `processing` -> `failed` (Error)
- Any state -> `pending` (Manual re-index trigger)

## 2. Re-indexing Triggers
The engine supports multiple ways to refresh information:

- **Manual Trigger**: Users can click the "Refresh" icon in the **Business Brain** UI to force a re-crawl or re-extraction.
- **Auto-Sync (Planned)**: Scheduled tasks to re-crawl URL sources weekly.
- **Content Update**: Editing a raw text source automatically marks it as `ready` and updates the timestamp.

## 3. Enforcement & Blocking
AI components are prohibited from using the knowledge base if it is not in a "Ready" state.

### Implementation:
The `KnowledgeBaseService` provides an `isReady(teamId)` helper:
- It returns `true` only if there's at least one source with `ready` status.
- In `AutomationService`, any AI node that has `use_knowledge_base` enabled will throw an exception if `isReady` returns `false`. This prevents the AI from hallucinating or using insufficient data.

## 4. UI Indicators
The **Business Brain** manager provides real-time feedback:
- **Green Badge (Ready)**: Information is actively used by the AI.
- **Amber Pulse (Pending/Syncing)**: AI is currently learning this information.
- **Red Badge (Failed)**: Information is ignored; hover to see the error.

## 5. Technical Stack
- **Data Model**: `KnowledgeBaseSource`
- **Background Processing**: `ProcessKnowledgeBaseSourceJob` (Laravel Queue)
- **Search Engine**: Keyword-based ranking with fallback logic (Vector DB planned for Phase 2).
- **Service Layer**: `KnowledgeBaseService` handling extraction and search orchestration.

## 6. Scope Control
To improve AI accuracy and data privacy, the engine supports granular scope control.

### Assignment
- **All Sources (Default)**: The AI searches across all "Ready" information for the team.
- **Selected Sources**: Users specify exactly which files or URLs the AI should access for a particular flow or bot.

### Safety Behavior
- **Strict Isolation**: If an AI node is configured with a restricted scope, it is physically blocked from retrieving data from sources outside that scope.
- **Null-Set Protection**: If a node is set to "Selected" scope but no sources are provided, the engine throws a configuration error instead of falling back to "All Sources", ensuring intentional data usage.
- **Readiness Check per Scope**: The AI check will fail if the *specific* sources in the scope are not "Ready", even if other sources in the team's KB are ready.

### UX for Scope Management
Scope is managed directly within the **Automation Builder**:
1. Open an **OpenAI** node.
2. Toggle **Use Business Brain**.
3. Select **Search Scope** (All or Selected).
4. If "Selected", pick from the list of verified "Ready" sources.

