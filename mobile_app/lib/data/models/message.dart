import 'package:isar/isar.dart';

part 'message.g.dart';

@collection
class LocalMessage {
  Id id = Isar.autoIncrement;

  @Index(unique: true)
  int? remoteId;

  late int conversationId;
  late int teamId;
  late String direction; // inbound, outbound
  late String type; // text, image, doc
  String? content;
  String? mediaUrl;
  String? localMediaPath;
  
  @Index()
  late DateTime createdAt;
  
  String? status; // sent, delivered, read, failed
  
  int? replyToMessageId;
  String? replyToContent;

  DateTime? readAt;
  
  bool? isStarred;

  String? metadata;

  bool isOutbound() => direction == 'outbound';
}
