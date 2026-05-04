# Final UI Polish Plan

**Goal:** Complete the remaining UI/UX gaps: implement media uploads via the image picker, enable infinite scrolling for conversations, and fix avatar/media placeholders.

**Architecture:**
1. **Media Uploads:** Add a method `uploadMedia(String filePath)` to `ApiService` (using `FormData`). Update `ChatScreen`'s `_pickImage()` to upload the file, get the URL, and dispatch a `SendMessageToConversation` with `type: 'image'` and the URL.
2. **Pagination:** Update `InboxScreen` to use a `ScrollController`. When the user reaches the bottom, dispatch a `FetchConversations(page: currentPage + 1)`. Update `ChatBloc` to append new conversations to the existing list.
3. **Avatar Placeholders:** Replace `Container(color: Colors.grey[200])` in `media_gallery_screen.dart` and `chat_avatar.dart` with a more visually appealing `Icon(Icons.image, color: Colors.grey)` or initials-based placeholder.

**Tech Stack:** Flutter, Dio (FormData).

---

### Task 1: Media Upload Implementation

- [ ] **Step 1: Update ApiService**
  - Add `uploadMedia(String path)` to `lib/core/api_service.dart`.
  - Use `MultipartFile.fromFile` and send a `POST` request to `/mobile/media/upload`.

- [ ] **Step 2: Update ChatScreen**
  - In `lib/presentation/screens/chat_screen.dart`, update `_pickImage()`:
    - Call `ApiService.uploadMedia(image.path)`.
    - Retrieve the `media_url` from the response.
    - Dispatch `SendMessageToConversation(..., type: 'image')` (You will need to modify the event to accept `mediaUrl`).

- [ ] **Step 3: Update MessageBloc & ChatRepository**
  - Modify `SendMessageToConversation` and `sendMessage` to optionally accept `mediaUrl` and save it to Isar/API.

### Task 2: Pagination Logic

- [ ] **Step 1: Update ChatBloc**
  - Modify `ChatEvent` to support `FetchConversations({int page = 1})`.
  - In `_onFetchConversations`, if `page > 1`, append the new data to `state.conversations`.

- [ ] **Step 2: Update InboxScreen**
  - Add a `ScrollController` to `_InboxScreenState`.
  - Listen to `_scrollController` and trigger `FetchConversations(page: nextPage)` when near the bottom.

### Task 3: Avatar Placeholders

- [ ] **Step 1: Update Media Gallery & Avatar**
  - In `lib/presentation/screens/media_gallery_screen.dart`, change `Container(color: Colors.grey[200])` to a better fallback widget.
  - In `lib/presentation/widgets/chat_avatar.dart`, ensure `_buildPlaceholder` is used correctly for network errors.