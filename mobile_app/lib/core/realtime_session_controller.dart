abstract class RealtimeSocket {
  void subscribeToTeam(int teamId);
  void subscribeToConversation(int conversationId);
  void whisperTyping(int conversationId, String userName, bool typing);
  void disconnect();
}

typedef RealtimeSocketFactory = RealtimeSocket Function({
  required String token,
  required String whatsappNumberId,
  required String tenantId,
});

class RealtimeSessionController {
  final RealtimeSocketFactory socketFactory;

  RealtimeSocket? _socket;
  String? _connectionKey;

  RealtimeSessionController({required this.socketFactory});

  void connect({
    required String token,
    required String whatsappNumberId,
    required String tenantId,
    required int teamId,
  }) {
    final nextConnectionKey = '$token|$whatsappNumberId|$tenantId|$teamId';
    if (_socket != null && _connectionKey == nextConnectionKey) {
      return;
    }

    _socket?.disconnect();

    final socket = socketFactory(
      token: token,
      whatsappNumberId: whatsappNumberId,
      tenantId: tenantId,
    );
    socket.subscribeToTeam(teamId);

    _socket = socket;
    _connectionKey = nextConnectionKey;
  }

  void disconnect() {
    _socket?.disconnect();
    _socket = null;
    _connectionKey = null;
  }
}
