class LocalMessage {
  int? id;
  int? remoteId;
  int conversationId = 0;
  int teamId = 0;
  String direction = 'inbound';
  String type = 'text';
  String? content;
  String? mediaUrl;
  DateTime createdAt = DateTime.fromMillisecondsSinceEpoch(0);
  String? status;

  int? replyToMessageId;
  String? replyToContent;

  DateTime? readAt;
  bool? isStarred;
  String? metadata;

  bool isOutbound() => direction == 'outbound';
}
