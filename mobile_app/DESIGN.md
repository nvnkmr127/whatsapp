# 📱 WhatsApp Pro Mobile (Watxio) - Architecture & Design Specifications

This document outlines the design, architecture, and technology specs of the high-performance Flutter mobile application for **Watxio** (WhatsApp Pro).

---

## 🌟 Architectural Overview

Watxio Mobile follows a strict **Clean Architecture** combined with the **BLoC (Business Logic Component)** pattern to achieve unidirectional data flow, low latency, and a clear separation of concerns.

```mermaid
graph TD
    subgraph Presentation Layer [Presentation Layer (UI)]
        Screen[Screens / Pages] --> |Dispatch Events| Bloc[BLoC / State Managers]
        Bloc --> |Rebuild State| Screen
    end

    subgraph Logic Layer [Logic / State Management]
        Bloc --> |Invoke Methods| Repo[ChatRepository / Feature Repositories]
    end

    subgraph Core & Data Layer [Core, Repositories & Cache]
        Repo --> |Local Query/Write| Cache[Cache Service / Isar Database]
        Repo --> |HTTP REST Calls| API[API Service / Dio Interceptor]
        API --> |Secure Credentials| Storage[Flutter Secure Storage]
        Network[OutboxService / Connectivity] --> |Trigger Retries| Repo
    end

    subgraph External Systems
        API --> |v1/mobile/*| Laravel[Laravel backend]
        Push[FCM Push Notifications] --> |onMessageOpenedApp| NavStream[Navigation Stream]
        NavStream --> |Auto-Route| Screen
    end
```

---

## 🛠️ Technology Stack

| Layer | Component / Package | Purpose & Rationale |
| :--- | :--- | :--- |
| **Frontend UI** | **Flutter SDK** | Single codebase compiled natively for High Performance (Impeller engine). |
| **State Management** | **`flutter_bloc`** | Predictable state engine using events and states. |
| **Local Cache DB** | **`isar`** | Extremely fast NoSQL database for local storage (queries, offsets, background persistence). |
| **HTTP Client** | **`dio`** | Robust HTTP client supporting global interceptors, custom timeout limits, and headers. |
| **Secure KeyStore** | **`flutter_secure_storage`** | Keychain (iOS) and Keystore (Android) for JWT Sanctum authorization credentials. |
| **Push Notifications**| **`firebase_messaging`** | Cloud-based background push payload execution (FCM). |
| **Foreground Alerts** | **`flutter_local_notifications`**| Displaying incoming message previews as heads-up alerts when app is active. |
| **Typography** | **`google_fonts` (Inter)** | Modern sans-serif typography for visual clarity. |
| **Connectivity** | **`connectivity_plus`** | Network status observer to trigger offline sync retries. |

---

## 📁 Directory Structure

```directory
lib/
├── core/                         # Core infrastructure & global utilities
│   ├── api_service.dart          # Network client configuration & endpoints
│   ├── cache_service.dart        # Database abstraction layer
│   ├── cache_service_native.dart # Native Isar initialization
│   ├── cache_service_web.dart    # Web-fallback stub configuration
│   ├── config.dart               # Environment setup configuration
│   ├── config_service.dart       # API Server Host Configuration storage
│   ├── fcm_service.dart          # Low-level Firebase messaging hooks
│   ├── notification_service.dart # Local and Remote Push Notification routing
│   ├── outbox_service.dart       # Offline queue watcher and connectivity sync
│   ├── realtime_session_controller.dart # WebSocket state observer
│   ├── socket_service.dart       # Socket manager gateway
│   ├── storage_keys.dart         # Secure storage keys naming registry
│   └── theme.dart                # Visual tokens and light/dark ThemeData
│
├── data/                         # Data access layer (Models & Repositories)
│   ├── models/                   # JSON schemas, DB schemas, and serializations
│   │   ├── api_models.dart       # DTOs mapping raw API endpoints
│   │   ├── cache_models.dart     # Isar collections configuration schemas
│   │   ├── isar_schemas.dart     # Native database loader helper
│   │   └── message.dart          # Message entity models
│   └── repositories/             # SSOT Repository pattern implementation
│       ├── chat_repository.dart  # Data broker pattern resolver (Web vs Native)
│       └── feature_repositories.dart # Core support API data retrievers
│
├── logic/                        # Pure Business logic controllers
│   └── blocs/                    # BLoCs: Auth, Canned, Chat, Message, Settings
│
└── presentation/                 # Presentation layer (Widgets, Screens, Pages)
    └── screens/                  # 37+ operational screens (Inbox, Chat, Analytics, etc.)
```

---

## 🔐 Authentication & Multi-Tenancy

Authentication utilizes **Laravel Sanctum** token verification. The mobile app implements dynamic interceptors inside [api_service.dart](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/mobile_app/lib/core/api_service.dart) to append tenant scopes seamlessly.

### Header Scoping Engine
```json
{
  "Authorization": "Bearer {secured_token}",
  "X-Tenant-ID": "{active_tenant_uuid}",
  "X-WhatsApp-Number-ID": "{active_number_id}",
  "X-Member-ID": "{active_member_id}"
}
```

1. **Token Persistence**: Stored via `FlutterSecureStorage` using Keystore/Keychain protection.
2. **Dynamic Team Switching**: Triggered in `AuthBloc` on user selection, persisting the selected `X-Tenant-ID` header.
3. **Automatic Logouts**: The `Dio` Interceptor captures HTTP `401 Unauthorized` responses and immediately wipes local credentials to force re-authentication.

---

## 💾 Cache Synchronization & Offline Outbox

To guarantee uninterrupted work in areas with weak cellular coverage, the system implements a **Single Source of Truth (SSOT)** model using **Isar Database**.

### Message Lifecycle

```
[User Types Message] ──> [Save to Local DB as 'queued'] ──> [Trigger Queue Sync]
                                                                   │
    ┌─────────────────────────── Retry / Send ─────────────────────┘
    ▼
[Upload Media (if present)] ──> [Call API: sendMessage] ───> [Success?]
                                                               │
                       ┌───────────────────────────────────────┴───────┐
                       ▼ YES                                           ▼ NO
           [Set DB to 'sent'/'delivered']                 [Status = 'queued'/'failed']
```

1. **Queue Management**: Message is stored with status `queued` in the local DB and the sending action is initiated.
2. **Connectivity Watcher**: [OutboxService](file:///Users/naveenadicharla/Documents/Whatsapp%20Business%20API/mobile_app/lib/core/outbox_service.dart) listens to `connectivity_plus` updates. When a network connection becomes available, it automatically triggers a retry of all pending messages.
3. **Media Upload**: Outbound media is processed sequentially—local paths are uploaded first, the resulting URL is resolved, and then the text payload is delivered.

---

## 🔔 Notifications & Dynamic Deep-Linking

FCM notifications trigger background payload parsing, bypassing unnecessary UI delays and routing the user to the correct conversation instantly.

1. **Device Registration**: The device fetches its FCM token on startup and registers it via `POST /mobile/auth/fcm-token` using the active device type (`android` or `ios`).
2. **Foreground Alert Interceptor**: If the app is active, `FirebaseMessaging.onMessage` intercepts the notification. If the user is currently viewing the active conversation (`ChatScreen.activeConversationId == payload.conversationId`), the sound/alert is suppressed to avoid disruption. Otherwise, a high-priority preview is displayed via the `FlutterLocalNotificationsPlugin`.
3. **Deep Link Stream Handler**: When a notification is tapped, `NotificationService` routes data via `navigationStream`. This triggers the App's MaterialApp Navigator Key to push the appropriate `ChatScreen` onto the stack:

```dart
NotificationService.navigationStream.listen((data) {
  final convId = data['conversation_id'];
  if (convId != null) {
    appNavigatorKey.currentState?.push(
      MaterialPageRoute(
        builder: (_) => ChatScreen(
          conversationId: convId,
          contactName: 'Incoming Message',
        ),
      ),
    );
  }
});
```

---

## 🎨 Theme & Visual Tokens

The user interface is built on **Material 3** guidelines, providing a responsive experience that automatically respects the device's light and dark mode preferences.

- **Primary Brand Color**: `0xFF128C7E` (Classic WhatsApp Teal)
- **Secondary Accent**: `0xFF25D366` (Vibrant green for FABs/status badges)
- **Outbound Message Bubble**: Light: `0xFFE7FFDB` / Dark: `0xFF1F2C34`
- **Inbound Message Bubble**: Light: `Colors.white` / Dark: `0xFF121B22`
- **Typography**: Inter (via Google Fonts text theme provider)

---

## 🌐 Platform Compatibility Fallback

The app is built to support both native mobile platforms and modern web browsers:

- **Isar Database Fallback**: On Web, FFI binders are unavailable. The app dynamically handles this by passing `null` to the `ChatRepository`, which automatically bypasses local database caching and routes data requests directly through the `ApiService`.
- **Notification Fallback**: Web platforms gracefully bypass native PushKit initialization steps to prevent crash errors.
