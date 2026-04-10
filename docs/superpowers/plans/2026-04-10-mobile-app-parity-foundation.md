# Mobile App Parity Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the existing Flutter app in `mobile_app/` reliably usable against this Laravel backend by completing auth/tenant selection, realtime → local cache sync, and a reusable offline outbox foundation.

**Architecture:** Flutter consumes `/api/v1/mobile/*` REST endpoints and Reverb WebSockets. Device state is driven from a local Isar database; writes go through an outbox queue (idempotent mutation IDs) and reconcile with server events.

**Tech Stack:** Flutter (`flutter_bloc`, `dio`, `isar`, `flutter_secure_storage`, `laravel_echo`, `pusher_client`), Laravel 11 (`sanctum`, `reverb`, broadcasting).

---

## Repository Layout (Files to Touch)

**Backend (Laravel):**
- Existing: [routes/api.php](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/routes/api.php)
- Existing mobile controllers: `app/Http/Controllers/Api/Mobile/*`
- Add/Modify: tests under `tests/Feature/*`

**Mobile (Flutter):**
- Existing entry: `mobile_app/lib/main.dart`
- Existing networking: `mobile_app/lib/core/api_service.dart`
- Existing realtime: `mobile_app/lib/core/socket_service.dart`
- Existing DB: `mobile_app/lib/data/models/message.dart` (Isar)
- Add: local models (`conversation.dart`, `contact.dart`, `pending_mutation.dart`)
- Add: sync/outbox services (`sync_service.dart`, `outbox_service.dart`)
- Add: repositories (`conversation_repository.dart`, `contact_repository.dart`)

---

### Task 1: Harden Mobile Auth + Tenant Selection Contract

**Files:**
- Modify: `routes/api.php`
- Modify: `app/Http/Controllers/Api/Mobile/AuthController.php`
- Test: `tests/Feature/Mobile/MobileAuthTest.php`

- [ ] **Step 1: Add feature test for mobile login + team list**

```php
<?php

namespace Tests\Feature\Mobile;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_login_returns_token_and_teams(): void
    {
        $user = User::factory()->create([
            'email' => 'agent@example.com',
            'password' => bcrypt('password123'),
        ]);

        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->current_team_id = $team->id;
        $user->save();

        $res = $this->postJson('/api/v1/mobile/auth/login', [
            'email' => 'agent@example.com',
            'password' => 'password123',
        ]);

        $res->assertOk();
        $res->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email'],
            'teams' => [['id', 'name']],
        ]);
    }
}
```

- [ ] **Step 2: Run the test**

Run:

```bash
php artisan test --filter MobileAuthTest
```

Expected: PASS.

- [ ] **Step 3: Ensure auth routes are throttled and do not require tenant**

Confirm `routes/api.php` contains:

```php
Route::prefix('v1/mobile/auth')->middleware('throttle:api')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'me']);
        Route::post('/logout', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'logout']);
    });
});
```

- [ ] **Step 4: Commit**

```bash
git add routes/api.php app/Http/Controllers/Api/Mobile/AuthController.php tests/Feature/Mobile/MobileAuthTest.php
git commit -m "feat(mobile): add mobile auth contract test"
```

---

### Task 2: Add Local Isar Models for Conversations, Contacts, and Outbox

**Files:**
- Create: `mobile_app/lib/data/models/conversation.dart`
- Create: `mobile_app/lib/data/models/contact.dart`
- Create: `mobile_app/lib/data/models/pending_mutation.dart`
- Modify: `mobile_app/lib/main.dart`
- Modify: `mobile_app/pubspec.yaml` (if needed for codegen)

- [ ] **Step 1: Create `LocalConversation` model**

```dart
import 'package:isar/isar.dart';

part 'conversation.g.dart';

@collection
class LocalConversation {
  Id id = Isar.autoIncrement;

  @Index(unique: true)
  late int remoteId;

  @Index()
  late int teamId;

  late int contactRemoteId;
  String? contactName;
  String? contactPhone;

  String? lastMessagePreview;
  String? lastMessageType;
  int unreadCount = 0;

  String status = 'open';
  int? assignedTo;

  @Index()
  late DateTime lastMessageAt;
}
```

- [ ] **Step 2: Create `LocalContact` model**

```dart
import 'package:isar/isar.dart';

part 'contact.g.dart';

@collection
class LocalContact {
  Id id = Isar.autoIncrement;

  @Index(unique: true)
  late int remoteId;

  @Index()
  late int teamId;

  String? name;
  String? phoneNumber;
  String? optInStatus;
}
```

- [ ] **Step 3: Create `PendingMutation` outbox model**

```dart
import 'package:isar/isar.dart';

part 'pending_mutation.g.dart';

@collection
class PendingMutation {
  Id id = Isar.autoIncrement;

  @Index(unique: true)
  late String mutationId;

  @Index()
  late int teamId;

  late String operation;
  late String endpoint;
  late String method;

  String? bodyJson;
  DateTime createdAt = DateTime.now();
  int attemptCount = 0;
  DateTime? nextRetryAt;
  String status = 'queued';
  String? lastError;
}
```

- [ ] **Step 4: Add schemas to Isar initialization**

Update `mobile_app/lib/main.dart` Isar open call:

```dart
final isar = await Isar.open(
  [
    LocalMessageSchema,
    LocalConversationSchema,
    LocalContactSchema,
    PendingMutationSchema,
  ],
  directory: dir.path,
);
```

- [ ] **Step 5: Generate Isar code**

Run:

```bash
cd mobile_app
flutter pub get
dart run build_runner build --delete-conflicting-outputs
```

Expected: `*.g.dart` files generated.

- [ ] **Step 6: Commit**

```bash
git add mobile_app/lib/data/models mobile_app/lib/main.dart mobile_app/pubspec.yaml mobile_app/pubspec.lock
git commit -m "feat(mobile): add local models for conversations, contacts, outbox"
```

---

### Task 3: Implement Sync Repositories (REST → Isar)

**Files:**
- Create: `mobile_app/lib/data/repositories/conversation_repository.dart`
- Create: `mobile_app/lib/data/repositories/contact_repository.dart`
- Modify: `mobile_app/lib/core/api_service.dart`

- [ ] **Step 1: Add conversation sync repository**

```dart
import 'package:isar/isar.dart';
import '../../core/api_service.dart';
import '../models/conversation.dart';

class ConversationRepository {
  final Isar _isar;
  final ApiService _api;

  ConversationRepository(this._isar, this._api);

  Stream<List<LocalConversation>> watchInbox() {
    return _isar.localConversations
        .where()
        .sortByLastMessageAtDesc()
        .watch(fireImmediately: true);
  }

  Future<void> syncInbox({int page = 1}) async {
    final res = await _api.getConversations(page: page);
    final List<dynamic> data = res.data['data'] ?? [];
    final mapped = data.map((c) {
      final lc = LocalConversation()
        ..remoteId = c['id']
        ..teamId = 0
        ..contactRemoteId = 0
        ..contactName = c['name']
        ..contactPhone = c['phone']
        ..unreadCount = c['unread_count'] ?? 0
        ..status = c['status'] ?? 'open'
        ..assignedTo = c['assigned_to']
        ..lastMessageAt = DateTime.fromMillisecondsSinceEpoch((c['last_interaction'] ?? 0) * 1000);
      final lm = c['last_message'];
      if (lm != null) {
        lc.lastMessagePreview = lm['content']?.toString();
        lc.lastMessageType = lm['type']?.toString();
      }
      return lc;
    }).toList();

    await _isar.writeTxn(() async {
      await _isar.localConversations.putAll(mapped);
    });
  }
}
```

- [ ] **Step 2: Add contacts repository skeleton**

```dart
import 'package:isar/isar.dart';
import '../../core/api_service.dart';
import '../models/contact.dart';

class ContactRepository {
  final Isar _isar;
  final ApiService _api;

  ContactRepository(this._isar, this._api);

  Stream<LocalContact?> watchContact(int contactRemoteId) {
    return _isar.localContacts
        .filter()
        .remoteIdEqualTo(contactRemoteId)
        .watch(fireImmediately: true)
        .map((list) => list.isEmpty ? null : list.first);
  }
}
```

- [ ] **Step 3: Commit**

```bash
git add mobile_app/lib/data/repositories mobile_app/lib/core/api_service.dart
git commit -m "feat(mobile): add repositories for inbox and contacts cache"
```

---

### Task 4: Add Outbox Worker + Idempotent Mutation IDs

**Files:**
- Create: `mobile_app/lib/core/outbox_service.dart`
- Modify: `mobile_app/lib/core/api_service.dart`
- Modify: `mobile_app/lib/data/repositories/chat_repository.dart`

- [ ] **Step 1: Add idempotency header support in ApiService**

```dart
import 'package:dio/dio.dart';

Future<Response> sendMessage(
  int conversationId,
  Map<String, dynamic> data, {
  String? idempotencyKey,
}) async {
  return _dio.post(
    '/mobile/conversations/$conversationId/messages',
    data: data,
    options: Options(headers: idempotencyKey == null ? null : {'Idempotency-Key': idempotencyKey}),
  );
}

Future<Response> rawRequest(
  String method,
  String endpoint, {
  dynamic body,
  String? idempotencyKey,
}) {
  return _dio.request(
    endpoint,
    data: body,
    options: Options(
      method: method,
      headers: idempotencyKey == null ? null : {'Idempotency-Key': idempotencyKey},
    ),
  );
}
```

- [ ] **Step 2: Create OutboxService that flushes queued mutations**

```dart
import 'dart:convert';
import 'package:isar/isar.dart';
import '../data/models/pending_mutation.dart';
import 'api_service.dart';

class OutboxService {
  final Isar _isar;
  final ApiService _api;

  OutboxService(this._isar, this._api);

  Future<void> flush() async {
    final now = DateTime.now();
    final queued = await _isar.pendingMutations
        .filter()
        .statusEqualTo('queued')
        .and()
        .group((q) => q.nextRetryAtIsNull().or().nextRetryAtLessThan(now))
        .findAll();

    for (final m in queued) {
      await _process(m);
    }
  }

  Future<void> _process(PendingMutation m) async {
    try {
      final body = m.bodyJson == null ? null : jsonDecode(m.bodyJson!);
      await _api.rawRequest(m.method, m.endpoint, body: body, idempotencyKey: m.mutationId);
      m.status = 'sent';
      m.lastError = null;
    } catch (e) {
      m.attemptCount += 1;
      m.lastError = e.toString();
      m.nextRetryAt = DateTime.now().add(const Duration(seconds: 10));
    } finally {
      await _isar.writeTxn(() async {
        await _isar.pendingMutations.put(m);
      });
    }
  }
}
```

- [ ] **Step 3: Wire message sending to enqueue outbox + reconcile**

Add `uuid` dependency (used to generate stable mutation IDs):

```yaml
dependencies:
  uuid: ^4.5.3
```

Update `mobile_app/lib/data/repositories/chat_repository.dart` `sendMessage()` to create a mutation row and reconcile it:

```dart
import 'dart:convert';
import 'package:uuid/uuid.dart';
import '../models/pending_mutation.dart';

Future<void> sendMessage(int conversationId, String content, String type) async {
  final mutationId = const Uuid().v4();

  final localMsg = LocalMessage()
    ..conversationId = conversationId
    ..teamId = 0
    ..direction = 'outbound'
    ..type = type
    ..content = content
    ..createdAt = DateTime.now()
    ..status = 'queued';

  if (_isar != null) {
    await _isar.writeTxn(() async {
      await _isar.localMessages.put(localMsg);
      await _isar.pendingMutations.put(PendingMutation()
        ..mutationId = mutationId
        ..teamId = 0
        ..operation = 'send_message'
        ..endpoint = '/mobile/conversations/$conversationId/messages'
        ..method = 'POST'
        ..bodyJson = jsonEncode({'type': type, 'content': content})
      );
    });
  }

  try {
    final response = await _apiService?.sendMessage(
      conversationId,
      {'type': type, 'content': content},
      idempotencyKey: mutationId,
    );
    if (response?.data['success'] == true) {
      localMsg.remoteId = response?.data['message']?['id'];
      localMsg.status = response?.data['message']?['status']?.toString() ?? 'queued';
    }
  } catch (_) {
  } finally {
    if (_isar != null) {
      await _isar.writeTxn(() async => _isar.localMessages.put(localMsg));
    }
  }
}
```

- [ ] **Step 4: Commit**

```bash
git add mobile_app/lib/core/outbox_service.dart mobile_app/lib/core/api_service.dart mobile_app/lib/data/repositories/chat_repository.dart
git commit -m "feat(mobile): add outbox queue and idempotent message sends"
```

---

### Task 5: Apply Realtime Events into Isar (Not Just UI Refresh)

**Files:**
- Modify: `mobile_app/lib/core/socket_service.dart`
- Modify: `mobile_app/lib/logic/blocs/chat_bloc.dart`
- Modify: `mobile_app/lib/data/repositories/chat_repository.dart`

- [ ] **Step 1: On `MessageReceived`, upsert message into Isar**

Implement a repository method:

```dart
Future<void> upsertRealtimeMessage(Map<String, dynamic> payload) async {
  final m = payload['message'] as Map<String, dynamic>?;
  if (m == null) return;
  final lm = LocalMessage()
    ..remoteId = m['id']
    ..conversationId = m['conversation_id']
    ..teamId = m['team_id']
    ..direction = m['direction']
    ..type = m['type']
    ..content = m['content']
    ..mediaUrl = m['media_url']
    ..createdAt = DateTime.fromMillisecondsSinceEpoch(m['created_at'] * 1000)
    ..status = m['status'];
  await _isar.writeTxn(() async => _isar.localMessages.put(lm));
}
```

- [ ] **Step 2: On `MessageStatusUpdated`, update local status**

Update realtime handling so the `MessageReceived` payload is written into Isar via the existing repository (instead of only refreshing the inbox list). Keep `SocketService` dispatching the event, and implement the write in the bloc/repository layer.

Example change in `mobile_app/lib/logic/blocs/chat_bloc.dart` (current architecture already has `ChatRepository` available there):

```dart
Future<void> _onRealTimeMessageReceived(RealTimeMessageReceived event, Emitter<ChatState> emit) async {
  final payload = Map<String, dynamic>.from(event.messagePayload as dynamic);
  await _chatRepository.upsertRealtimeMessage(payload);
}
```

- [ ] **Step 3: Commit**

```bash
git add mobile_app/lib/core/socket_service.dart mobile_app/lib/logic/blocs/chat_bloc.dart mobile_app/lib/data/repositories/chat_repository.dart
git commit -m "feat(mobile): apply realtime message events into local cache"
```

---

## Self-Review Checklist (Run After Writing Code)

- Confirm mobile requests include `Authorization` and `X-Tenant-ID`.
- Confirm WebSocket private/presence auth uses `/api/v1/mobile/broadcasting/auth`.
- Confirm offline sends do not duplicate messages on retry.
- Confirm realtime events update Isar and UI updates without manual refresh.

## Local Verification Commands

**Flutter analysis (Trae sandbox requires state override):**

```bash
cd mobile_app
ANALYZER_STATE_LOCATION_OVERRIDE="$PWD/.dartServer" flutter analyze
```

**Laravel tests:**

```bash
php artisan test --filter MobileAuthTest
```
