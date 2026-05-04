# Complete Features Implementation Plan

**Goal:** Replace all demo/mock implementations in "WhatsApp Pro" with the actual API integration and wire the `ChatRepository` natively across all platforms.

**Architecture:** We will remove `MockChatRepository` and rely entirely on `ChatRepository`. For web, we will pass `null` for the `Isar` instance and rely on the API directly. We will remove all `isDemoMode` bypasses in `ApiService` and the Blocs.

**Tech Stack:** Flutter, Dio, Isar, Bloc.

---

### Task 1: Remove MockChatRepository and Wire Main

- [ ] **Step 1: Delete mock_chat_repository.dart**
  - Delete `lib/data/repositories/mock_chat_repository.dart`.

- [ ] **Step 2: Update main.dart**
  - Remove import for `mock_chat_repository.dart`.
  - In `main()`, replace `chatRepo = MockChatRepository();` with `chatRepo = ChatRepository(null, apiService);`.
  - Remove Isar fallback to `MockChatRepository()` and replace with `chatRepo = ChatRepository(null, apiService);`.

### Task 2: Clean up ApiService Bypasses

- [ ] **Step 1: Remove `EnvConfig.isDemoMode` from `ApiService`**
  - Edit `lib/core/api_service.dart`.
  - In `reportPresence`, `registerFcmToken`, `getAnalytics`, `getTemplates`, `getCannedMessages`, `getTags`, `startCampaign`, remove the `if (EnvConfig.isDemoMode) { ... }` blocks.

### Task 3: Clean up AuthBloc and ChatBloc

- [ ] **Step 1: Update AuthBloc**
  - Edit `lib/logic/blocs/auth_bloc.dart`.
  - In `_onAppStarted`, remove the `EnvConfig.isDemoMode && kIsWeb` check for the token.
  - In `_onLoginRequested`, remove the `if (EnvConfig.isDemoMode)` block completely.

- [ ] **Step 2: Update ChatBloc**
  - Edit `lib/logic/blocs/chat_bloc.dart`.
  - In `_onFetchConversations`, remove the `if (kIsWeb) { ... }` Phase 21 demo bypass block.

### Task 4: Enhance ChatRepository

- [ ] **Step 1: Implement `getConversations` in `ChatRepository`**
  - Edit `lib/data/repositories/chat_repository.dart`.
  - Change `getConversations` to call `_apiService?.getConversations(page: page)`.
  - Return `response?.data['data'] ?? []`.
