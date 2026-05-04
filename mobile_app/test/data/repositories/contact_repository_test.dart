import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';
import 'package:whatsapp_pro_mobile/data/repositories/feature_repositories.dart';
import '../../helpers/native_test_helpers.mocks.dart';

void main() {
  late ContactRepository contactRepository;
  late MockApiService mockApiService;

  setUp(() {
    mockApiService = MockApiService();
    contactRepository = ContactRepository(mockApiService);
  });

  group('ContactRepository Tests', () {
    test('searchContacts returns list of contacts on success', () async {
      final mockResponse = {
        'data': [
          {'id': 1, 'name': 'John Doe', 'phone': '1234567890'},
          {'id': 2, 'name': 'Jane Doe', 'phone': '0987654321'},
        ]
      };

      when(mockApiService.getContacts(page: 1, search: 'John'))
          .thenAnswer((_) async => Response(
                data: mockResponse,
                statusCode: 200,
                requestOptions: RequestOptions(path: ''),
              ));

      final result = await contactRepository.searchContacts(query: 'John');

      expect(result.length, 2);
      expect(result[0].name, 'John Doe');
      expect(result[1].name, 'Jane Doe');
      verify(mockApiService.getContacts(page: 1, search: 'John')).called(1);
    });

    test('getContactDetails returns contact on success', () async {
      final mockResponse = {'id': 1, 'name': 'John Doe', 'phone': '1234567890'};

      when(mockApiService.getContact(1))
          .thenAnswer((_) async => Response(
                data: mockResponse,
                statusCode: 200,
                requestOptions: RequestOptions(path: ''),
              ));

      final result = await contactRepository.getContactDetails(1);

      expect(result, isNotNull);
      expect(result!.name, 'John Doe');
      verify(mockApiService.getContact(1)).called(1);
    });

    test('getContactDetails returns null on error', () async {
      when(mockApiService.getContact(1)).thenThrow(DioException(
        requestOptions: RequestOptions(path: ''),
        type: DioExceptionType.badResponse,
      ));

      final result = await contactRepository.getContactDetails(1);

      expect(result, isNull);
    });
  });
}
