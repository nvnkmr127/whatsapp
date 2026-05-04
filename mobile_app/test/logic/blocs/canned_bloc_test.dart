import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';
import 'package:whatsapp_pro_mobile/logic/blocs/canned_bloc.dart';
import '../../helpers/test_helpers.mocks.dart';

void main() {
  group('CannedBloc Tests', () {
    late CannedBloc cannedBloc;
    late MockApiService mockApiService;

    setUp(() {
      mockApiService = MockApiService();
      cannedBloc = CannedBloc(mockApiService);
    });

    tearDown(() {
      cannedBloc.close();
    });

    test('initial state is CannedInitial', () {
      expect(cannedBloc.state, isA<CannedInitial>());
    });

    test('emits CannedLoaded when FetchCannedMessages succeeds', () async {
      when(mockApiService.getCannedMessages()).thenAnswer((_) async => Response(
        requestOptions: RequestOptions(path: '/canned-messages'),
        data: [{'id': 1, 'shortcut': '/hello', 'content': 'Hello there'}],
      ));

      final expectedStates = [
        isA<CannedLoaded>().having((s) => s.messages.length, 'length', 1),
      ];

      expectLater(cannedBloc.stream, emitsInOrder(expectedStates));
      cannedBloc.add(FetchCannedMessages());
    });
  });
}