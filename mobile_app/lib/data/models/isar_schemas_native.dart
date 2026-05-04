import 'package:isar/isar.dart';
import 'message.dart';
import 'cache_models.dart';

List<CollectionSchema> getIsarSchemas() => [
  LocalMessageSchema,
  CachedConversationSchema,
  CachedContactSchema,
  CachedTemplateSchema,
  CachedSettingSchema,
];

