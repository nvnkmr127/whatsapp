import 'dart:convert';
import 'package:laravel_echo/laravel_echo.dart';
import 'package:pusher_client_fixed/pusher_client_fixed.dart';
import 'package:flutter/foundation.dart';
import 'config.dart';
import 'realtime_session_controller.dart';
import '../../logic/blocs/chat_bloc.dart';
import '../../logic/blocs/message_bloc.dart';
import '../data/models/local_message.dart';
import '../../presentation/screens/chat_screen.dart';
import 'dart:collection';

class SocketService implements RealtimeSocket {
  late Echo _echo;
  final ChatBloc _chatBloc;
  final MessageBloc _messageBloc;
  final _processedEventIds = HashSet<String>();

  SocketService(this._chatBloc, this._messageBloc, {
    required String token, 
    required String whatsappNumberId,
    required String tenantId,
  }) {
    final PusherOptions options = PusherOptions(
      host: EnvConfig.reverbHost,
      wsPort: EnvConfig.reverbPort,
      wssPort: EnvConfig.reverbPort,
      encrypted: EnvConfig.reverbScheme == 'https',
      auth: PusherAuth(
        '${EnvConfig.apiBaseUrl}/mobile/broadcasting/auth',
        headers: {
          'Authorization': 'Bearer $token',
          'X-WhatsApp-Number-ID': whatsappNumberId,
          'X-Tenant-ID': tenantId,
        },
      ),
    );

    final PusherClient pusher = PusherClient(EnvConfig.reverbKey, options, autoConnect: true);
    
    pusher.onConnectionStateChange((state) {
      debugPrint('🌐 WebSocket Connection: ${state?.currentState}');
    });

    pusher.onConnectionError((error) {
      debugPrint('❌ WebSocket Error: ${error?.message}');
    });

    _echo = Echo(
      broadcaster: EchoBroadcasterType.Pusher,
      client: pusher,
    );

    if (kDebugMode) {
      debugPrint('🌐 [SOCKET] SocketService initialized with token: ${token.substring(0, 10)}...');
    }
  }

  @override
  void subscribeToTeam(int teamId) {
    _echo.private('teams.$teamId')
      .listen('.MessageReceived', (e) => _handleIncomingEvent(e, teamId))
      .listen('.MessageSent', (e) => _handleIncomingEvent(e, teamId));
    
    if (kDebugMode) {
      debugPrint('📡 [SOCKET] Subscribing to team channel: teams.$teamId');
    }
  }

  void _handleIncomingEvent(dynamic e, int teamId) {
    if (kDebugMode) {
      debugPrint('📥 [SOCKET] Received Real-time event: $e');
    }
    final eventId = e['message']?['id']?.toString() ?? e['id']?.toString();
    if (eventId != null && _processedEventIds.contains(eventId)) return;
    if (eventId != null) _processedEventIds.add(eventId);
    
    // 1. Global Sync for Inbox
    _chatBloc.add(RealTimeMessageReceived(e));

    // 2. Local Sync for Active Chat Screen
    final messageData = e['message'];
    if (messageData != null) {
      final convId = (messageData['conversation_id'] as num?)?.toInt();
      if (convId != null && convId == ChatScreen.activeConversationId) {
        // Map to LocalMessage
        final msg = LocalMessage()
          ..remoteId = (messageData['id'] as num?)?.toInt()
          ..conversationId = convId
          ..teamId = (messageData['team_id'] as num?)?.toInt() ?? 0
          ..direction = messageData['direction']?.toString() ?? 'inbound'
          ..type = messageData['type']?.toString() ?? 'text'
          ..content = messageData['content']?.toString()
          ..mediaUrl = messageData['media_url']?.toString()
          ..createdAt = messageData['created_at'] is String 
              ? DateTime.parse(messageData['created_at']) 
              : DateTime.now()
          ..status = messageData['status']?.toString()
          ..metadata = messageData['metadata'] != null ? jsonEncode(messageData['metadata']) : null;
        
        _messageBloc.add(NewMessageReceived(msg));
      }
    }
  }

  @override
  void subscribeToConversation(int conversationId) {
    _echo.join('conversation.$conversationId')
      .listen('.MessageStatusUpdated', (e) {
        if (kDebugMode) {
          debugPrint('📉 [SOCKET] Received MessageStatusUpdated event: $e');
        }
        if (e['message'] != null) {
          _messageBloc.add(StatusChanged(e['message']['id'], e['message']['status']));
        }
      })
      .listenForWhisper('typing', (e) {
        if (kDebugMode) {
          debugPrint('⌨️ [SOCKET] Received whisper typing: $e');
        }
        _chatBloc.add(AgentTypingStatusChanged(conversationId, e['user_name'], e['typing']));
      });

    if (kDebugMode) {
      debugPrint('📡 [SOCKET] Joined conversation channel: conversation.$conversationId');
    }
  }

  @override
  void whisperTyping(int conversationId, String userName, bool typing) {
    (_echo.join('conversation.$conversationId') as dynamic).whisper('typing', {
      'user_name': userName,
      'typing': typing,
    });
  }

  @override
  void disconnect() {
    _processedEventIds.clear();
    _echo.disconnect();
  }
}
