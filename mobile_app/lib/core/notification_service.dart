import 'dart:io';
import 'dart:async';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import '../core/api_service.dart';

class NotificationService {
  static const String _channelId = 'high_importance_channel_v2';
  static const String _channelName = 'High Importance Notifications';
  final ApiService _apiService;
  final FlutterLocalNotificationsPlugin _localNotifications = FlutterLocalNotificationsPlugin();
  final GlobalKey<NavigatorState> navigatorKey;

  static final StreamController<Map<String, dynamic>> _navStream = StreamController<Map<String, dynamic>>.broadcast();
  static Stream<Map<String, dynamic>> get navigationStream => _navStream.stream;

  NotificationService(this._apiService, this.navigatorKey);

  Future<void> init() async {
    // 1. Request Permissions
    final NotificationSettings settings = await FirebaseMessaging.instance.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    if (settings.authorizationStatus == AuthorizationStatus.authorized) {
      // 2. Get Token & Register
      final String? token = await FirebaseMessaging.instance.getToken();
      if (token != null) {
        await _apiService.registerFcmToken(token, Platform.isIOS ? 'ios' : 'android');
      }

      // 3. Listen for token refresh
      FirebaseMessaging.instance.onTokenRefresh.listen((newToken) {
        _apiService.registerFcmToken(newToken, Platform.isIOS ? 'ios' : 'android');
      });

      // 4. Setup Local Notifications for Foreground
      const AndroidNotificationChannel channel = AndroidNotificationChannel(
        _channelId,
        _channelName,
        description: 'This channel is used for important notifications.',
        importance: Importance.max,
        playSound: true,
      );

      const AndroidInitializationSettings initializationSettingsAndroid = AndroidInitializationSettings('@mipmap/ic_launcher');
      const DarwinInitializationSettings initializationSettingsIOS = DarwinInitializationSettings(
        requestAlertPermission: true,
        requestBadgePermission: true,
        requestSoundPermission: true,
      );
      
      const InitializationSettings initializationSettings = InitializationSettings(
        android: initializationSettingsAndroid,
        iOS: initializationSettingsIOS,
      );
      
      await _localNotifications
          .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
          ?.createNotificationChannel(channel);

      await _localNotifications.initialize(
        settings: initializationSettings,
        onDidReceiveNotificationResponse: (NotificationResponse details) {
          _onNotificationTapped(details);
        },
      );

      // 5. Background / Killed State handling
      FirebaseMessaging.onMessageOpenedApp.listen(_handleMessage);
      
      // Check for initial message (if app was killed)
      final RemoteMessage? initialMessage = await FirebaseMessaging.instance.getInitialMessage();
      if (initialMessage != null) {
        _handleMessage(initialMessage);
      }
    }
  }

  void _handleMessage(RemoteMessage message) {
    final convId = message.data['conversation_id'];
    final teamId = message.data['team_id'];
    if (convId != null) {
      _navStream.add({
        'conversation_id': int.parse(convId.toString()),
        'team_id': teamId != null ? int.parse(teamId.toString()) : null,
      });
    }
  }

  void _onNotificationTapped(NotificationResponse response) {
    if (response.payload != null) {
      // Assuming payload is "convId:teamId"
      final parts = response.payload!.split(':');
      _navStream.add({
        'conversation_id': int.parse(parts[0]),
        'team_id': parts.length > 1 ? int.parse(parts[1]) : null,
      });
    }
  }

  Future<void> showForegroundNotification(RemoteMessage message) async {
    const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
      _channelId,
      _channelName,
      importance: Importance.max,
      priority: Priority.high,
      playSound: true,
      enableVibration: true,
    );
    const DarwinNotificationDetails iosDetails = DarwinNotificationDetails(
      presentAlert: true,
      presentBadge: true,
      presentSound: true,
    );
    const NotificationDetails platformDetails = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );
    
    await _localNotifications.show(
      id: message.hashCode,
      title: message.notification?.title,
      body: message.notification?.body,
      notificationDetails: platformDetails,
      payload: "${message.data['conversation_id']}:${message.data['team_id']}",
    );
  }
}
