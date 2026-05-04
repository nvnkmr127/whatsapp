import 'dart:convert';
import 'package:isar/isar.dart';
import 'package:dio/dio.dart';
import '../../core/api_service.dart';
import '../models/message.dart';

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

  Future<void> upsertRealtimeMessage(Map<String, dynamic> payload) async {
    if (_isar == null) return;
    final dynamic msg = payload['message'];
    if (msg is! Map) return;

    final remoteId = (msg['id'] as num?)?.toInt();
    final conversationId = (msg['conversation_id'] as num?)?.toInt();
    final teamId = (msg['team_id'] as num?)?.toInt();
    if (remoteId == null || conversationId == null || teamId == null) return;

    await _isar.writeTxn(() async {
      final existing = await _isar.localMessages.filter().remoteIdEqualTo(remoteId).findFirst();
      final local = existing ?? LocalMessage();

      local.remoteId = remoteId;
      local.conversationId = conversationId;
      local.teamId = teamId;
      local.direction = msg['direction']?.toString() ?? local.direction;
      local.type = msg['type']?.toString() ?? local.type;
      local.content = msg['content']?.toString();
      local.mediaUrl = msg['media_url']?.toString();
      local.status = msg['status']?.toString();
      local.metadata = msg['metadata'] != null ? jsonEncode(msg['metadata']) : null;

      final createdAt = msg['created_at'];
      if (createdAt is num) {
        local.createdAt = DateTime.fromMillisecondsSinceEpoch(createdAt.toInt() * 1000);
      } else if (createdAt is String) {
        local.createdAt = DateTime.tryParse(createdAt) ?? local.createdAt;
      }

      await _isar.localMessages.put(local);
    });
  }

  Stream<List<LocalMessage>> watchMessages(int conversationId) {
    if (_isar == null) return const Stream.empty();
    return _isar.localMessages
        .filter()
        .conversationIdEqualTo(conversationId)
        .sortByCreatedAtDesc()
        .watch(fireImmediately: true);
  }

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
              ..remoteId = m['id']
              ..conversationId = conversationId
              ..teamId = m['team_id']
              ..direction = m['direction']
              ..type = m['type']
              ..content = m['content']
              ..mediaUrl = m['media_url']
              ..replyToMessageId = m['reply_to_message_id']
              ..replyToContent = m['reply_to_content']
              ..createdAt = DateTime.parse(m['created_at'])
              ..status = m['status']
              ..metadata = m['metadata'] != null ? jsonEncode(m['metadata']) : null,
          )
          .toList();

      if (_isar != null) {
        await _isar.writeTxn(() async {
          for (final m in remoteMessages) {
            final remoteId = m['id'] as int?;
            if (remoteId == null) continue;

            final existing = await _isar.localMessages.filter().remoteIdEqualTo(remoteId).findFirst();
            final local = existing ?? LocalMessage();

            local.remoteId = remoteId;
            local.conversationId = conversationId;
            local.teamId = m['team_id'] ?? 0;
            local.direction = m['direction'] ?? local.direction;
            local.type = m['type'] ?? local.type;
            local.content = m['content'];
            local.mediaUrl = m['media_url'];
            local.replyToMessageId = m['reply_to_message_id'];
            local.replyToContent = m['reply_to_content'];
            local.createdAt = DateTime.parse(m['created_at']);
            local.status = m['status'];
            local.metadata = m['metadata'] != null ? jsonEncode(m['metadata']) : null;

            await _isar.localMessages.put(local);
          }
        });
      }
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
      ..localMediaPath = localMediaPath
      ..replyToMessageId = replyToMessageId
      ..createdAt = DateTime.now()
      ..status = 'queued';

    if (_isar != null) {
      await _isar.writeTxn(() => _isar.localMessages.put(localMsg));
    }

    await _trySendLocalMessage(localMsg);
    return null;
  }

  Future<void> sendTemplate(int conversationId, int templateId, {List<dynamic>? variables}) async {
    await _apiService?.sendTemplate(conversationId, templateId, variables: variables);
  }

  Future<void> _trySendLocalMessage(LocalMessage localMsg) async {
    try {
      await _isar?.writeTxn(() async {
        localMsg.status = 'sending';
        await _isar.localMessages.put(localMsg);
      });

      // 1. Handle Media Upload if needed
      if (localMsg.localMediaPath != null && localMsg.mediaUrl == null) {
        final uploadResponse = await _apiService?.uploadMedia(localMsg.localMediaPath!);
        if (uploadResponse?.statusCode == 200 && uploadResponse?.data['url'] != null) {
          localMsg.mediaUrl = uploadResponse!.data['url'];
        } else {
          throw Exception('Media upload failed');
        }
      }

      // 2. Send Message
      final Map<String, dynamic> payload = {
        'type': localMsg.type,
        'content': localMsg.content,
      };
      if (localMsg.mediaUrl != null) {
        payload['media_url'] = localMsg.mediaUrl;
      }
      if (localMsg.replyToMessageId != null) {
        payload['reply_to_message_id'] = localMsg.replyToMessageId;
      }
      final response = await _apiService?.sendMessage(localMsg.conversationId, payload);

      if (response?.data['success'] == true) {
        localMsg.status = response?.data['message']?['status']?.toString() ?? 'sent';
        localMsg.remoteId = response?.data['message']?['id'];
        // Clear local path once uploaded and sent
        localMsg.localMediaPath = null;
      } else {
        localMsg.status = 'failed';
      }
    } catch (e) {
      if (e is DioException) {
        if (e.response?.statusCode == 422 || e.response?.statusCode == 403) {
          localMsg.status = 'failed';
        } else {
          localMsg.status = 'queued'; // Temporary network issue, keep in queue
        }
      } else {
        localMsg.status = 'queued'; // Potential timeout or media failure, retry later
      }
    } finally {
      if (_isar != null) {
        await _isar.writeTxn(() async {
          if (localMsg.remoteId != null) {
             final existing = await _isar.localMessages.filter().remoteIdEqualTo(localMsg.remoteId!).findFirst();
             if (existing != null && existing.id != localMsg.id) {
                await _isar.localMessages.delete(localMsg.id);
                localMsg.id = existing.id;
             }
          }
          await _isar.localMessages.put(localMsg);
        });
      }
    }
  }

  Future<void> updateMessageStatus(int remoteId, String status) async {
    if (_isar == null) return;
    await _isar.writeTxn(() async {
      final msg = await _isar.localMessages.filter().remoteIdEqualTo(remoteId).findFirst();
      if (msg != null) {
        msg.status = status;
        if (status == 'read') {
          msg.readAt = DateTime.now();
        }
        await _isar.localMessages.put(msg);
      }
    });
  }

  Future<void> retryMessage(int localId) async {
    if (_isar == null) return;
    final msg = await _isar.localMessages.get(localId);
    if (msg != null && (msg.status == 'failed' || msg.status == 'queued')) {
      msg.status = 'queued';
      await _isar.writeTxn(() => _isar.localMessages.put(msg));
      await _trySendLocalMessage(msg);
    }
  }

  Future<void> retryFailedMessages() async {
    if (_isar == null) return;
    
    // Find outbound messages that haven't been successfully sent
    final pending = await _isar.localMessages
        .filter()
        .directionEqualTo('outbound')
        .remoteIdIsNull()
        .and()
        .group((q) => q.statusEqualTo('queued').or().statusEqualTo('failed'))
        .sortByCreatedAt() // Send in order
        .findAll();

    for (final msg in pending) {
      await _trySendLocalMessage(msg);
    }
  }
}
