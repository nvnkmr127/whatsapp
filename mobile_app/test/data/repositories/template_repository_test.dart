import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';
import 'package:whatsapp_pro_mobile/data/repositories/feature_repositories.dart';
import '../../helpers/native_test_helpers.mocks.dart';

void main() {
  late TemplateRepository templateRepository;
  late MockApiService mockApiService;

  setUp(() {
    mockApiService = MockApiService();
    templateRepository = TemplateRepository(mockApiService);
  });

  group('TemplateRepository Tests', () {
    test('getTemplates returns list of templates on success', () async {
      final mockResponse = {
        'data': [
          {'id': 1, 'name': 'welcome', 'body': 'Welcome {{1}}'},
          {'id': 2, 'name': 'promo', 'body': 'Special offer {{1}}'},
        ]
      };

      when(mockApiService.getTemplates())
          .thenAnswer((_) async => Response(
                data: mockResponse,
                statusCode: 200,
                requestOptions: RequestOptions(path: ''),
              ));

      final result = await templateRepository.getTemplates();

      expect(result.length, 2);
      expect(result[0].name, 'welcome');
      expect(result[1].name, 'promo');
      verify(mockApiService.getTemplates()).called(1);
    });

    test('getTemplates returns empty list on error', () async {
      when(mockApiService.getTemplates()).thenThrow(DioException(
        requestOptions: RequestOptions(path: ''),
      ));

      final result = await templateRepository.getTemplates();

      expect(result, isEmpty);
    });
  });
}
