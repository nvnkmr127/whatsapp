# Complete Unit Testing Plan

**Goal:** Provide comprehensive unit test coverage for the remaining BLoCs (`ChatBloc`, `MessageBloc`, `CannedBloc`) and Repositories (`ChatRepository`).

**Architecture:**
- Create mock classes for `ApiService` and `ChatRepository` using `mockito`.
- Test each Bloc state transition for various events (success, failure, loading).
- Since `Isar` is difficult to mock strictly due to its FFI bindings, we will use mock dependencies and assert that the correct calls are made.
- Create tests inside the `test/logic/blocs/` and `test/data/repositories/` directories.

**Tech Stack:** Flutter, `flutter_test`, `mockito`, `bloc_test`.

---

### Task 1: Generate Mocks

- [ ] **Step 1: Setup Mocks file**
  - Create a new file `test/helpers/test_helpers.dart` to host all `@GenerateMocks` annotations for `ApiService`, `ChatRepository`, and `Isar`.
  - Run `dart run build_runner build --delete-conflicting-outputs` to generate the mock classes.

### Task 2: Test ChatBloc

- [ ] **Step 1: Write tests for `ChatBloc`**
  - Create `test/logic/blocs/chat_bloc_test.dart`.
  - Write test: "emits ChatLoading then ConversationsLoaded when FetchConversations is added."
  - Write test: "emits MessagesLoaded when FetchMessages is added."
  - Write test: "updates typing agents correctly when AgentTypingStatusChanged is added."

### Task 3: Test MessageBloc

- [ ] **Step 1: Write tests for `MessageBloc`**
  - Create `test/logic/blocs/message_bloc_test.dart`.
  - Write test: "calls sendMessage on repository when SendMessage event is added."
  - Write test: "calls updateMessageStatus on repository when MarkMessageRead event is added."

### Task 4: Test CannedBloc

- [ ] **Step 1: Write tests for `CannedBloc`**
  - Create `test/logic/blocs/canned_bloc_test.dart`.
  - Write test: "emits CannedLoaded when FetchCannedMessages succeeds."

### Task 5: Test ChatRepository

- [ ] **Step 1: Write tests for `ChatRepository`**
  - Create `test/data/repositories/chat_repository_test.dart`.
  - Provide a null Isar instance (testing web/fallback path) and a mock ApiService.
  - Test `getConversations` returns parsed data when API succeeds.
  - Test `getConversations` returns empty list when API fails.