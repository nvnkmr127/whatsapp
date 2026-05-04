import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/annotations.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';
import 'package:whatsapp_pro_mobile/core/api_service.dart';

import 'package:flutter/services.dart';

@GenerateMocks([Dio])
import 'api_service_test.mocks.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  
  late ApiService apiService;
  late MockDio mockDio;

  const channel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');
  TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
      .setMockMethodCallHandler(channel, (MethodCall methodCall) async {
    if (methodCall.method == 'read') {
      return 'http://localhost:8000';
    }
    return null;
  });

  setUp(() {
    mockDio = MockDio();
    when(mockDio.options).thenReturn(BaseOptions());
    when(mockDio.interceptors).thenReturn(Interceptors());
    apiService = ApiService(dio: mockDio);
  });

  group('ApiService Tests', () {
    test('getConversations handles success', () async {
      when(mockDio.get(any, queryParameters: anyNamed('queryParameters')))
          .thenAnswer((_) async => Response(
                data: {'data': []},
                statusCode: 200,
                requestOptions: RequestOptions(path: ''),
              ));

      final result = await apiService.getConversations();
      expect(result.statusCode, 200);
      expect(result.data['data'], isA<List>());
    });

    test('getContacts handles failure', () async {
      when(mockDio.get(any, queryParameters: anyNamed('queryParameters')))
          .thenThrow(DioException(
        requestOptions: RequestOptions(path: ''),
        response: Response(statusCode: 500, requestOptions: RequestOptions(path: '')),
      ));

      expect(() => apiService.getContacts(), throwsA(isA<DioException>()));
    });

    test('getCalls handles success', () async {
      when(mockDio.get('calls'))
          .thenAnswer((_) async => Response(
                data: {'data': []},
                statusCode: 200,
                requestOptions: RequestOptions(path: 'calls'),
              ));

      final result = await apiService.getCalls();
      expect(result.statusCode, 200);
      verify(mockDio.get('calls')).called(1);
    });

    test('startCampaign handles success', () async {
      final campaignData = {'name': 'Test', 'template_id': 1};
      when(mockDio.post('mobile/campaigns', data: campaignData))
          .thenAnswer((_) async => Response(
                data: {'success': true},
                statusCode: 200,
                requestOptions: RequestOptions(path: 'mobile/campaigns'),
              ));

      final result = await apiService.startCampaign(campaignData);
      expect(result.data['success'], true);
      verify(mockDio.post('mobile/campaigns', data: campaignData)).called(1);
    });

    test('getDashboardAnalytics handles success', () async {
      when(mockDio.get('mobile/analytics/dashboard', queryParameters: {'period': 'today'}))
          .thenAnswer((_) async => Response(
                data: {'stats': {}},
                statusCode: 200,
                requestOptions: RequestOptions(path: 'mobile/analytics/dashboard'),
              ));

      final result = await apiService.getDashboardAnalytics(period: 'today');
      expect(result.statusCode, 200);
      verify(mockDio.get('mobile/analytics/dashboard', queryParameters: {'period': 'today'})).called(1);
    });
  });
}
