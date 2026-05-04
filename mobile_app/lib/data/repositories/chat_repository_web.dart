import 'dart:convert';
import 'package:isar/isar.dart';
import '../../core/api_service.dart';
import '../models/local_message.dart';

class ChatRepository {
  final Isar? _isar;
  final ApiService? _apiService;

  ChatRepository(this._isar, this._apiService);

  Isar? get isar => _isar;

  Future<List<dynamic>> getConversations({int page = 1}) async {
    if (_apiService == null) return [];
    try {
      final response = await _apiService.getConversations(page: page);
      return response.data['data'] ?? [];
    } catch (_) {
      return [];
    }
  }

  Future<void> upsertRealtimeMessage(Map<String, dynamic> payload) async {}

  Stream<List<LocalMessage>> watchMessages(int conversationId) => const Stream.empty();

  Future<({List<LocalMessage>? messages, bool isWithin24Hours, String? nextCursor})> syncMessages(int conversationId, {String? cursor}) async {
    try {
      final response = await _apiService?.getMessages(conversationId, cursor: cursor);
      if (response == null) return (messages: null, isWithin24Hours: true, nextCursor: null);
      final List<dynamic> remoteMessages = response.data['data'];

      final bool windowOpen = response.data['is_within_24_hours'] ?? true;
      final String? nextCursor = response.data['next_cursor'];

      final localMessages = remoteMessages
          .map(
            (m) => LocalMessage()
              ..remoteId = (m['id'] as num?)?.toInt()
              ..conversationId = conversationId
              ..teamId = (m['team_id'] as num?)?.toInt() ?? 0
              ..direction = m['direction']?.toString() ?? 'inbound'
              ..type = m['type']?.toString() ?? 'text'
              ..content = m['content']?.toString()
              ..mediaUrl = m['media_url']?.toString()
              ..replyToMessageId = (m['reply_to_message_id'] as num?)?.toInt()
              ..replyToContent = m['reply_to_content']?.toString()
              ..createdAt = DateTime.tryParse(m['created_at']?.toString() ?? '') ?? DateTime.now()
              ..status = m['status']?.toString()
              ..metadata = m['metadata'] != null ? jsonEncode(m['metadata']) : null,
          )
          .toList();

      return (messages: localMessages, isWithin24Hours: windowOpen, nextCursor: nextCursor);
    } catch (_) {
      return (messages: null, isWithin24Hours: true, nextCursor: null);
    }
  }

  Future<LocalMessage?> sendMessage(int conversationId, String content, String type, {String? mediaUrl, String? localMediaPath, int? replyToMessageId}) async {
    final localMsg = LocalMessage()
      ..conversationId = conversationId
      ..teamId = 0
      ..direction = 'outbound'
      ..type = type
      ..content = content
      ..mediaUrl = mediaUrl
      ..replyToMessageId = replyToMessageId
      ..createdAt = DateTime.now()
      ..status = 'queued';

    try {
      final payload = <String, dynamic>{
        'type': type,
        'content': content,
      };
      if (mediaUrl != null) {
        payload['media_url'] = mediaUrl;
      }
      if (replyToMessageId != null) {
        payload['reply_to_message_id'] = replyToMessageId;
      }
      final response = await _apiService?.sendMessage(conversationId, payload);
      if (response?.data['success'] == true) {
        localMsg.status = response?.data['message']?['status']?.toString() ?? 'sent';
        localMsg.remoteId = (response?.data['message']?['id'] as num?)?.toInt();
      }
    } catch (_) {
      localMsg.status = 'queued';
    }

    return localMsg;
  }

  Future<void> sendTemplate(int conversationId, int templateId, {List<dynamic>? variables}) async {
    await _apiService?.sendTemplate(conversationId, templateId, variables: variables);
  }

  Future<void> updateMessageStatus(int remoteId, String status) async {}

  Future<void> retryMessage(int localId) async {
    // Basic web implementation - potentially just log or show a toast
    // In a full web version, we might want to store failed messages in memory/LocalStorage
  }

  Future<void> retryFailedMessages() async {}
}
