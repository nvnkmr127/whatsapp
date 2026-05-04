// Typed models for the WhatsApp Pro Mobile API

class AnalyticsData {
  final double credits;
  final Map<String, dynamic> conversations;
  final Map<String, dynamic> summary;
  final Map<String, dynamic> messages30d;
  final Map<String, dynamic> messageActivity;

  AnalyticsData({
    required this.credits,
    required this.conversations,
    required this.summary,
    required this.messages30d,
    required this.messageActivity,
  });

  factory AnalyticsData.fromJson(Map<String, dynamic> json) {
    return AnalyticsData(
      credits: (json['credits'] ?? 0.0).toDouble(),
      conversations: Map<String, dynamic>.from(json['conversations'] ?? {}),
      summary: Map<String, dynamic>.from(json['summary'] ?? {}),
      messages30d: Map<String, dynamic>.from(json['messages_30d'] ?? {}),
      messageActivity: Map<String, dynamic>.from(json['message_activity'] ?? {}),
    );
  }
}


class Contact {
  final int id;
  final String name;
  final String phone;
  final String? email;
  final List<String> tags;

  Contact({
    required this.id,
    required this.name,
    required this.phone,
    this.email,
    this.tags = const [],
  });

  factory Contact.fromJson(Map<String, dynamic> json) {
    return Contact(
      id: json['id'],
      name: json['name'] ?? 'Unknown',
      phone: json['phone'] ?? '',
      email: json['email'],
      tags: List<String>.from(json['tags']?.map((t) => t['name']) ?? []),
    );
  }
}

class Template {
  final int id;
  final String name;
  final String category;
  final String status;
  final String language;
  final String? content;

  Template({
    required this.id,
    required this.name,
    required this.category,
    required this.status,
    required this.language,
    this.content,
  });

  factory Template.fromJson(Map<String, dynamic> json) {
    return Template(
      id: json['id'],
      name: json['name'] ?? '',
      category: json['category'] ?? 'UTILITY',
      status: json['status'] ?? 'PENDING',
      language: json['language'] ?? 'en',
      content: json['content'],
    );
  }
}

class LogEntry {
  final int id;
  final String type;
  final String message;
  final DateTime createdAt;
  final Map<String, dynamic>? metadata;

  LogEntry({
    required this.id,
    required this.type,
    required this.message,
    required this.createdAt,
    this.metadata,
  });

  factory LogEntry.fromJson(Map<String, dynamic> json) {
    return LogEntry(
      id: json['id'],
      type: json['type'] ?? 'info',
      message: json['message'] ?? '',
      createdAt: DateTime.parse(json['created_at']),
      metadata: json['metadata'],
    );
  }
}

class AiResponse {
  final String content;
  final List<String> suggestions;

  AiResponse({required this.content, this.suggestions = const []});

  factory AiResponse.fromJson(Map<String, dynamic> json) {
    return AiResponse(
      content: json['content'] ?? '',
      suggestions: List<String>.from(json['suggestions'] ?? []),
    );
  }
}
