import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/annotations.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';
import 'package:whatsapp_pro_mobile/logic/blocs/auth_bloc.dart';
import 'package:whatsapp_pro_mobile/core/api_service.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'auth_bloc_test.mocks.dart';

@GenerateMocks([ApiService, FlutterSecureStorage])
void main() {
  group('AuthBloc Tests', () {
    late AuthBloc authBloc;
    late MockApiService mockApiService;
    
    setUp(() {
      mockApiService = MockApiService();
      authBloc = AuthBloc(mockApiService);

      // Disable storage access for testing
      FlutterSecureStorage.setMockInitialValues({});
    });

    tearDown(() {
      authBloc.close();
    });

    test('initial state is AuthInitial', () {
      expect(authBloc.state, isA<AuthInitial>());
    });

    test('emits AuthError on invalid login', () async {
      // Mock API Service to return invalid response
      when(mockApiService.login('test@example.com', 'password123')).thenAnswer((_) async => Response(
        requestOptions: RequestOptions(path: '/login'),
        data: {'token': null},
      ));

      final expectedStates = [
        isA<AuthLoading>(),
        isA<AuthError>(),
      ];

      expectLater(authBloc.stream, emitsInOrder(expectedStates));
      authBloc.add(LoginRequested('test@example.com', 'password123'));
    });

    test('emits Authenticated on valid login without multiple tenants', () async {
      final user = {'name': 'Jane Agent', 'email': 'jane@team.com'};
      final token = 'valid_token';
      
      when(mockApiService.login('test@example.com', 'password123')).thenAnswer((_) async => Response(
        requestOptions: RequestOptions(path: '/login'),
        data: {
          'token': token,
          'user': user,
          'teams': [{'id': 1, 'name': 'Sales'}],
        },
      ));

      final expectedStates = [
        isA<AuthLoading>(),
        isA<Authenticated>().having((s) => s.token, 'token', token).having((s) => s.user?['name'], 'name', 'Jane Agent'),
      ];

      expectLater(authBloc.stream, emitsInOrder(expectedStates));
      authBloc.add(LoginRequested('test@example.com', 'password123'));
    });
  });
}