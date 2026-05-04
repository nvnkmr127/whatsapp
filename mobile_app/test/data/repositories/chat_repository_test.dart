import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';
import 'package:whatsapp_pro_mobile/data/repositories/chat_repository.dart';
import '../../helpers/test_helpers.mocks.dart';

void main() {
  group('ChatRepository Tests', () {
    late ChatRepository chatRepository;
    late MockApiService mockApiService;

    setUp(() {
      mockApiService = MockApiService();
      // Test web fallback scenario by passing null Isar
      chatRepository = ChatRepository(null, mockApiService);
    });

    test('getConversations returns parsed data when API succeeds', () async {
      when(mockApiService.getConversations(page: 1)).thenAnswer((_) async => Response(
        requestOptions: RequestOptions(path: '/conversations'),
        data: {'data': [{'id': 1, 'name': 'John Doe'}]},
      ));

      final result = await chatRepository.getConversations(page: 1);
      expect(result.length, 1);
      expect(result[0]['name'], 'John Doe');
    });

    test('getConversations returns empty list when API fails', () async {
      when(mockApiService.getConversations(page: 1)).thenThrow(DioException(
        requestOptions: RequestOptions(path: '/conversations'),
      ));

      final result = await chatRepository.getConversations(page: 1);
      expect(result, isEmpty);
    });

    test('syncMessages correctly maps metadata from API', () async {
      final mockData = {
        'data': [
          {
            'id': 101,
            'team_id': 1,
            'direction': 'inbound',
            'type': 'interactive',
            'content': '[Flow] Form Submitted',
            'created_at': '2024-04-24T12:00:00Z',
            'status': 'delivered',
            'metadata': {'interactive': {'type': 'nfm_reply', 'nfm_reply': {'body': 'Survey'}}}
          }
        ],
        'is_within_24_hours': true
      };

      when(mockApiService.getMessages(1, cursor: null)).thenAnswer((_) async => Response(
        requestOptions: RequestOptions(path: '/conversations/1/messages'),
        data: mockData,
        statusCode: 200,
      ));

      final result = await chatRepository.syncMessages(1);
      
      expect(result.messages?.length, 1);
      expect(result.messages?[0].metadata, contains('"interactive"'));
      expect(result.messages?[0].metadata, contains('"nfm_reply"'));
    });
  });
}