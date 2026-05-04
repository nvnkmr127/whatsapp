import 'package:flutter_test/flutter_test.dart';
import 'package:whatsapp_pro_mobile/data/models/api_models.dart';

void main() {
  group('Model Parsing Tests', () {
    test('AnalyticsData parsing', () {
      final json = {
        'credits': 150.50,
        'summary': {'sent_today': 10, 'total_30d': 500},
        'conversations': {'active': 5, 'unread': 2},
        'message_activity': {
          'labels': ['Mon', 'Tue'],
          'values': [50, 60]
        },
        'messages_30d': {
          'delivery_rate': 98,
          'read_rate': 85
        }
      };

      final data = AnalyticsData.fromJson(json);
      expect(data.credits, 150.50);
      expect(data.summary['sent_today'], 10);
      expect(data.conversations['active'], 5);
      expect(data.messageActivity['labels'], contains('Mon'));
    });
  });
}
