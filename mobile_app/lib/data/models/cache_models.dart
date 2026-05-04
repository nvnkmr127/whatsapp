import 'package:isar/isar.dart';

part 'cache_models.g.dart';

@collection
class CachedConversation {
  Id id = Isar.autoIncrement;

  @Index(unique: true)
  late int remoteId;

  late String name;
  late String phone;
  String? lastMessageContent;
  String? lastMessageType;
  int? unreadCount;
  late String status;
  int? lastInteraction;
  bool? isWithin24Hours;
  String? assigneeName;
  String? tagsJson;
  
  late DateTime cachedAt;
}

@collection
class CachedContact {
  Id id = Isar.autoIncrement;

  @Index(unique: true)
  late int remoteId;

  late String name;
  late String phone;
  String? email;
  String? tagsJson;
  String? customAttributesJson;
  
  late DateTime cachedAt;
}

@collection
class CachedTemplate {
  Id id = Isar.autoIncrement;

  @Index(unique: true)
  late int remoteId;

  late String name;
  late String category;
  late String status;
  late String language;
  String? componentsJson;
  
  late DateTime cachedAt;
}

@collection
class CachedSetting {
  Id id = Isar.autoIncrement;

  @Index(unique: true)
  late String key;

  late String value;
  
  late DateTime updatedAt;
}
