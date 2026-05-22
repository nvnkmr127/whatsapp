import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:isar/isar.dart';
import 'package:path_provider/path_provider.dart';

import 'core/theme.dart';
import 'core/api_service.dart';
import 'core/cache_service.dart';
import 'core/notification_service.dart';
import 'core/outbox_service.dart';
import 'data/models/isar_schemas.dart';
import 'data/repositories/chat_repository.dart';
import 'logic/blocs/auth_bloc.dart';
import 'logic/blocs/chat_bloc.dart';
import 'logic/blocs/message_bloc.dart';
import 'logic/blocs/canned_bloc.dart';
import 'logic/blocs/settings_bloc.dart';
import 'presentation/screens/login_screen.dart';
import 'presentation/screens/chat_screen.dart';
import 'presentation/screens/tenant_select_screen.dart';
import 'presentation/screens/whatsapp_number_select_screen.dart';
import 'presentation/screens/main_shell.dart';

/// Shared navigator key used by both MaterialApp and NotificationService.
/// This is the fix for the FCM notification tap navigation bug.
final GlobalKey<NavigatorState> appNavigatorKey = GlobalKey<NavigatorState>();


void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  ApiService apiService = ApiService();
  ChatRepository chatRepo;

  if (kIsWeb) {
    // Web: Skip native initializations (Firebase/Isar/Local Notifications)
    chatRepo = ChatRepository(null, apiService);
  } else {
    // Mobile/macOS: Initialize full native stack
    final dir = await getApplicationDocumentsDirectory();
    try {
      await Firebase.initializeApp();
      FirebaseMessaging.onBackgroundMessage(_fcmBackgroundHandler);
    } catch (e) {
      debugPrint('⚠️ Warning: Firebase failed to initialize. Make sure you have your Firebase configuration files (google-services.json/GoogleService-Info.plist) or have run "flutterfire configure". Error: $e');
    }

    try {
      final isar = await Isar.open(getIsarSchemas(), directory: dir.path);
      CacheService.setInstance(isar);
      chatRepo = ChatRepository(isar, apiService);
      OutboxService(chatRepo).init();
    } catch (e) {
      chatRepo = ChatRepository(null, apiService);
    }
  }

  // 3. Launch App immediately (UI first)
  runApp(App(
    apiService: apiService,
    chatRepository: chatRepo,
  ));

  // 4. Background Initializations (Post-Launch)
  if (!kIsWeb) {
    // Initialize Push Notifications in background
    final notificationService = NotificationService(apiService, appNavigatorKey);
    notificationService.init().then((_) {
      debugPrint('[DEBUG] [INIT] NotificationService initialized.');
    }).catchError((e) {
      debugPrint('[DEBUG] [INIT] NotificationService failed: $e');
    });
    
    // Listen to notification taps and navigate
    NotificationService.navigationStream.listen((data) {
      final convId = data['conversation_id'] as int?;
      if (convId != null) {
        appNavigatorKey.currentState?.push(
          MaterialPageRoute(
            builder: (_) => ChatScreen(
              conversationId: convId,
              contactName: 'New Message',
            ),
          ),
        );
      }
    });
    
    // Handle foreground notifications
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      final convId = message.data['conversation_id'];
      if (convId != null && int.tryParse(convId.toString()) == ChatScreen.activeConversationId) {
        return;
      }
      notificationService.showForegroundNotification(message);
    });
  }
}

@pragma('vm:entry-point')
Future<void> _fcmBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}

class App extends StatelessWidget {
  final ApiService apiService;
  final ChatRepository chatRepository;

  const App({
    super.key,
    required this.apiService,
    required this.chatRepository,
  });

  @override
  Widget build(BuildContext context) {
    return MultiRepositoryProvider(
      providers: [
        RepositoryProvider.value(value: apiService),
        RepositoryProvider.value(value: chatRepository),
      ],
      child: MultiBlocProvider(
        providers: [
          BlocProvider(create: (context) => AuthBloc(apiService)..add(AppStarted())),
          BlocProvider(create: (context) => ChatBloc(apiService, chatRepository)),
          BlocProvider(create: (context) => MessageBloc(chatRepository)),
          BlocProvider(create: (context) => CannedBloc(apiService)),
          BlocProvider(create: (context) => SettingsBloc()..add(LoadSettings())),
        ],
        child: BlocListener<AuthBloc, AuthState>(
          listener: (context, state) {
            if (state is TenantSelectionRequired || state is WhatsAppNumberSelectionRequired) {
              // Reset navigation stack to show selection screen
              appNavigatorKey.currentState?.popUntil((route) => route.isFirst);
            }
          },
          child: BlocBuilder<SettingsBloc, SettingsState>(
            builder: (context, settings) {
              return MaterialApp(
                title: 'Watxio',
                debugShowCheckedModeBanner: false,
                theme: AppTheme.light,
                darkTheme: AppTheme.dark,
                themeMode: settings.themeMode,
                navigatorKey: appNavigatorKey,
                home: BlocBuilder<AuthBloc, AuthState>(
                  builder: (context, state) {
                    if (state is TenantSelectionRequired) {
                      return TenantSelectScreen(teams: state.teams);
                    }
                    if (state is WhatsAppNumberSelectionRequired) {
                      return WhatsAppNumberSelectScreen(numbers: state.numbers);
                    }
                    if (state is Authenticated) {
                      if (kDebugMode) {
                        debugPrint('[DEBUG] [NAV] User authenticated. Navigating to MainShell (Home).');
                      }
                      return const MainShell();
                    }
                    return const LoginScreen();
                  },
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}
