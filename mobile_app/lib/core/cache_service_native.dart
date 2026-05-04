import 'dart:convert';
import 'package:isar/isar.dart';
import '../data/models/message.dart';
import '../data/models/cache_models.dart';

class CacheService {
  static late Isar _isar;
  static bool _isInitialized = false;

  static void setInstance(Isar isar) {
    _isar = isar;
    _isInitialized = true;
  }

  // --- Conversations ---
  static Future<void> cacheConversations(List<dynamic> data) async {
    if (!_isInitialized) return;
    await _isar.writeTxn(() async {
      await _isar.cachedConversations.clear();
      final items = data.map((d) => CachedConversation()
        ..remoteId = d['id']
        ..name = d['name'] ?? d['phone']
        ..phone = d['phone'] ?? ''
        ..lastMessageContent = d['last_message']?['content']
        ..lastMessageType = d['last_message']?['type']
        ..unreadCount = d['unread_count']
        ..status = d['status'] ?? 'open'
        ..lastInteraction = d['last_interaction']
        ..isWithin24Hours = d['is_within_24_hours']
        ..assigneeName = d['assignee']?['name']
        ..tagsJson = jsonEncode(d['tags'])
        ..cachedAt = DateTime.now()
      ).toList();
      await _isar.cachedConversations.putAll(items);
    });
  }

  static Future<List<Map<String, dynamic>>> getCachedConversations() async {
    if (!_isInitialized) return [];
    final cached = await _isar.cachedConversations.where().sortByLastInteractionDesc().findAll();
    return cached.map((c) => {
      'id': c.remoteId,
      'name': c.name,
      'phone': c.phone,
      'last_message': c.lastMessageContent != null ? {
        'content': c.lastMessageContent,
        'type': c.lastMessageType,
      } : null,
      'unread_count': c.unreadCount,
      'status': c.status,
      'last_interaction': c.lastInteraction,
      'is_within_24_hours': c.isWithin24Hours,
      'assignee': c.assigneeName != null ? {'name': c.assigneeName} : null,
      'tags': jsonDecode(c.tagsJson ?? '[]'),
      'is_offline': true,
    }).toList();
  }

  // --- Messages ---
  static Future<void> cacheMessages(int conversationId, List<dynamic> messages) async {
    await _isar.writeTxn(() async {
      final items = messages.map((m) => LocalMessage()
        ..remoteId = m['id']
        ..conversationId = conversationId
        ..teamId = 0 // Resolved from global context if needed
        ..direction = m['direction'] ?? 'inbound'
        ..type = m['type'] ?? 'text'
        ..content = m['content']
        ..mediaUrl = m['media_url']
        ..createdAt = DateTime.fromMillisecondsSinceEpoch((m['timestamp'] ?? 0) * 1000)
        ..status = m['status']
        ..metadata = jsonEncode(m['metadata'] ?? {})
      ).toList();
      await _isar.localMessages.putAll(items);
    });
  }

  static Future<List<LocalMessage>> getCachedMessages(int conversationId) async {
    return _isar.localMessages
      .filter()
      .conversationIdEqualTo(conversationId)
      .sortByCreatedAtDesc()
      .limit(50)
      .findAll();
  }

  // --- Templates ---
  static Future<void> cacheTemplates(List<dynamic> templates) async {
    await _isar.writeTxn(() async {
      await _isar.cachedTemplates.clear();
      final items = templates.map((t) => CachedTemplate()
        ..remoteId = t['id']
        ..name = t['name']
        ..category = t['category']
        ..status = t['status']
        ..language = t['language'] ?? 'en'
        ..componentsJson = jsonEncode(t['components'] ?? [])
        ..cachedAt = DateTime.now()
      ).toList();
      await _isar.cachedTemplates.putAll(items);
    });
  }

  static Future<List<dynamic>> getCachedTemplates() async {
    final cached = await _isar.cachedTemplates.where().findAll();
    return cached.map((t) => {
      'id': t.remoteId,
      'name': t.name,
      'category': t.category,
      'status': t.status,
      'language': t.language,
      'components': jsonDecode(t.componentsJson ?? '[]'),
      'is_offline': true,
    }).toList();
  }

  // --- Contacts ---
  static Future<void> cacheContacts(List<dynamic> contacts) async {
    await _isar.writeTxn(() async {
      final items = contacts.map((c) => CachedContact()
        ..remoteId = c['id']
        ..name = c['name'] ?? ''
        ..phone = c['phone_number'] ?? ''
        ..email = c['email']
        ..tagsJson = jsonEncode(c['tags'] ?? [])
        ..customAttributesJson = jsonEncode(c['custom_attributes'] ?? {})
        ..cachedAt = DateTime.now()
      ).toList();
      await _isar.cachedContacts.putAll(items);
    });
  }

  static Future<List<dynamic>> getCachedContacts() async {
    final cached = await _isar.cachedContacts.where().findAll();
    return cached.map((c) => {
      'id': c.remoteId,
      'name': c.name,
      'phone_number': c.phone,
      'email': c.email,
      'tags': jsonDecode(c.tagsJson ?? '[]'),
      'custom_attributes': jsonDecode(c.customAttributesJson ?? '{}'),
      'is_offline': true,
    }).toList();
  }

  // --- Settings ---
  static Future<void> cacheSetting(String key, dynamic value) async {
    await _isar.writeTxn(() async {
      await _isar.cachedSettings.put(CachedSetting()
        ..key = key
        ..value = jsonEncode(value)
        ..updatedAt = DateTime.now()
      );
    });
  }

  static Future<dynamic> getCachedSetting(String key) async {
    final setting = await _isar.cachedSettings.where().keyEqualTo(key).findFirst();
    if (setting == null) return null;
    return jsonDecode(setting.value);
  }

  static Future<void> clearAllCache() async {
    if (!_isInitialized) return;
    await _isar.writeTxn(() async {
      await _isar.cachedConversations.clear();
      await _isar.cachedTemplates.clear();
      await _isar.cachedContacts.clear();
      await _isar.localMessages.clear();
    });
  }
}
