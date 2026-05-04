import '../../logic/blocs/chat_bloc.dart';
import '../../logic/blocs/message_bloc.dart';
import 'package:flutter/foundation.dart';
import 'realtime_session_controller.dart';

class SocketService implements RealtimeSocket {
  SocketService(ChatBloc chatBloc, MessageBloc messageBloc, {
    required String token, 
    required String whatsappNumberId,
    required String tenantId,
  }) {
    if (kDebugMode) {
      print('🌐 [SOCKET] Web stub initialized (Real-time disabled on web)');
    }
  }

  @override
  void subscribeToTeam(int teamId) {
    if (kDebugMode) {
      print('📡 [SOCKET] Web stub: subscribeToTeam($teamId)');
    }
  }

  @override
  void subscribeToConversation(int conversationId) {
    if (kDebugMode) {
      print('📡 [SOCKET] Web stub: subscribeToConversation($conversationId)');
    }
  }

  @override
  void whisperTyping(int conversationId, String userName, bool typing) {
    // No-op on web
  }

  @override
  void disconnect() {
    // No-op on web
  }
}
