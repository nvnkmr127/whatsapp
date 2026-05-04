# Complete Features Design

## Overview
The goal is to replace all demo/mock implementations in "WhatsApp Pro" with the actual API integration. This involves wiring the `ChatRepository` natively across all platforms, handling the Web fallback for Isar gracefully, and making sure all UI components pull from the real backend instead of mocked hardcoded data.

## Architectural Changes

1. **Remove MockChatRepository**
   - Delete `mock_chat_repository.dart`.
   - Update `main.dart` to instantiate `ChatRepository` for both Mobile and Web. Since `Isar` is not supported natively on Web in this setup (throws ffi errors), the Web instantiation will pass `null` for the Isar instance, and the `ChatRepository` will handle `_isar == null` gracefully by relying purely on `ApiService`.

2. **Clean up ApiService Bypasses**
   - Remove all `if (EnvConfig.isDemoMode)` branches.
   - Replace with actual backend API calls: `getAnalytics`, `getTemplates`, `getCannedMessages`, `getTags`, `startCampaign`, `reportPresence`, `registerFcmToken`.

3. **Clean up Blocs**
   - `AuthBloc`: Remove demo user credentials hardcoding and web bypasses.
   - `ChatBloc`: Remove `if (kIsWeb)` demo bypass in `_onFetchConversations` and rely on actual `_apiService.getConversations()` or `_chatRepository.getConversations()`.

4. **Enhance ChatRepository**
   - Implement `getConversations({int page = 1})` properly.
     - Fetch from API.
     - Update local Isar database if available (for offline support).
     - Return the parsed data for the UI to consume.
   - Ensure all offline sync methods gracefully skip Isar writes/reads if `_isar == null` (which occurs on the Web platform).

5. **UI Wiring Verification**
   - Ensure `BroadcastingScreen`, `AnalyticsDashboardScreen`, and other screens consume the real API correctly.
   - The payload structures for the mocked data must match what the real API endpoints return to prevent mapping errors.

## Data Flow
- Web: `Bloc -> ApiService -> UI` (Bypasses Isar).
- Mobile: `Bloc -> ApiService -> Isar (local cache) -> UI` or `Bloc -> Isar (watch) -> UI`.

## Error Handling
- Failed API requests (like `getConversations`) on mobile will fall back to reading from `Isar` if implemented. Otherwise, they throw standard Dio errors caught by the Blocs.
- Empty states and loading indicators are already present but must be tested with real latency.
