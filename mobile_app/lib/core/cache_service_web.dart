import 'package:isar/isar.dart';

class CacheService {
  static void setInstance(Isar? isar) {}

  static Future<void> cacheConversations(List<dynamic> data) async {}
  static Future<List<Map<String, dynamic>>> getCachedConversations() async => [];

  static Future<void> cacheMessages(int conversationId, List<dynamic> messages) async {}
  static Future<List<dynamic>> getCachedMessages(int conversationId) async => [];

  static Future<void> cacheTemplates(List<dynamic> templates) async {}
  static Future<List<dynamic>> getCachedTemplates() async => [];

  static Future<void> cacheContacts(List<dynamic> contacts) async {}
  static Future<List<dynamic>> getCachedContacts() async => [];

  static Future<void> cacheSetting(String key, dynamic value) async {}
  static Future<dynamic> getCachedSetting(String key) async => null;

  static Future<void> clearAllCache() async {}
}
