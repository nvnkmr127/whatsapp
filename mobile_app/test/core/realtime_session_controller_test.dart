import 'package:flutter_test/flutter_test.dart';
import 'package:whatsapp_pro_mobile/core/realtime_session_controller.dart';

class _FakeRealtimeSocket implements RealtimeSocket {
  int subscribeCalls = 0;
  int disconnectCalls = 0;
  int? subscribedTeamId;

  @override
  void disconnect() {
    disconnectCalls++;
  }

  @override
  void subscribeToTeam(int teamId) {
    subscribeCalls++;
    subscribedTeamId = teamId;
  }

  @override
  void subscribeToConversation(int conversationId) {}

  @override
  void whisperTyping(int conversationId, String userName, bool typing) {}
}

void main() {
  group('RealtimeSessionController', () {
    test('reuses existing socket when credentials have not changed', () {
      final createdSockets = <_FakeRealtimeSocket>[];
      final controller = RealtimeSessionController(
        socketFactory: ({
          required String token,
          required String whatsappNumberId,
          required String tenantId,
        }) {
          final socket = _FakeRealtimeSocket();
          createdSockets.add(socket);
          return socket;
        },
      );

      controller.connect(
        token: 'token-1',
        whatsappNumberId: 'wa-1',
        tenantId: '7',
        teamId: 7,
      );
      controller.connect(
        token: 'token-1',
        whatsappNumberId: 'wa-1',
        tenantId: '7',
        teamId: 7,
      );

      expect(createdSockets, hasLength(1));
      expect(createdSockets.single.subscribeCalls, 1);
      expect(createdSockets.single.disconnectCalls, 0);
    });

    test('disconnects previous socket and reconnects when credentials change', () {
      final createdSockets = <_FakeRealtimeSocket>[];
      final controller = RealtimeSessionController(
        socketFactory: ({
          required String token,
          required String whatsappNumberId,
          required String tenantId,
        }) {
          final socket = _FakeRealtimeSocket();
          createdSockets.add(socket);
          return socket;
        },
      );

      controller.connect(
        token: 'token-1',
        whatsappNumberId: 'wa-1',
        tenantId: '7',
        teamId: 7,
      );
      controller.connect(
        token: 'token-2',
        whatsappNumberId: 'wa-2',
        tenantId: '8',
        teamId: 8,
      );

      expect(createdSockets, hasLength(2));
      expect(createdSockets.first.disconnectCalls, 1);
      expect(createdSockets.last.subscribeCalls, 1);
      expect(createdSockets.last.subscribedTeamId, 8);
    });

    test('disconnect clears current socket', () {
      final createdSockets = <_FakeRealtimeSocket>[];
      final controller = RealtimeSessionController(
        socketFactory: ({
          required String token,
          required String whatsappNumberId,
          required String tenantId,
        }) {
          final socket = _FakeRealtimeSocket();
          createdSockets.add(socket);
          return socket;
        },
      );

      controller.connect(
        token: 'token-1',
        whatsappNumberId: 'wa-1',
        tenantId: '7',
        teamId: 7,
      );
      controller.disconnect();

      expect(createdSockets.single.disconnectCalls, 1);

      controller.connect(
        token: 'token-1',
        whatsappNumberId: 'wa-1',
        tenantId: '7',
        teamId: 7,
      );

      expect(createdSockets, hasLength(2));
    });
  });
}
