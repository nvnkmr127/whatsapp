import 'package:firebase_messaging/firebase_messaging.dart';
import '../core/api_service.dart';
import 'package:flutter/foundation.dart';

class FCMService {
  final ApiService _apiService;

  FCMService(this._apiService);

  Future<void> initialize() async {
    if (kIsWeb) return;

    final FirebaseMessaging messaging = FirebaseMessaging.instance;

    // 1. Request Permission (iOS requires this)
    final NotificationSettings settings = await messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    if (settings.authorizationStatus == AuthorizationStatus.authorized) {
      debugPrint('User granted notification permission');
      
      // 2. Get the Token
      final String? token = await messaging.getToken();
      if (token != null) {
        debugPrint('FCM Token: $token');
        // 3. Register with backend
        await _apiService.registerFCMToken(token);
      }
    }
    
    // 4. Token Refresh Listener
    messaging.onTokenRefresh.listen((newToken) {
      _apiService.registerFCMToken(newToken);
    });
  }
}
