import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';
import 'package:whatsapp_pro_mobile/logic/blocs/chat_bloc.dart';
import '../../helpers/native_test_helpers.mocks.dart';

import 'package:flutter/services.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  const channel = MethodChannel('dev.fluttercommunity.plus/connectivity');
  TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
      .setMockMethodCallHandler(channel, (MethodCall methodCall) async {
    if (methodCall.method == 'check') {
      return 'wifi';
    }
    return null;
  });

  group('ChatBloc Tests', () {
    late ChatBloc chatBloc;
    late MockApiService mockApiService;
    late dynamic mockChatRepository;

    setUp(() {
      mockApiService = MockApiService();
      mockChatRepository = MockChatRepository();
      chatBloc = ChatBloc(mockApiService, mockChatRepository);
    });

    tearDown(() {
      chatBloc.close();
    });

    test('initial state is ChatInitial', () {
      expect(chatBloc.state, isA<ChatInitial>());
    });

    test('emits ChatLoading then ConversationsLoaded on FetchConversations', () async {
      when(mockApiService.getConversations()).thenAnswer((_) async => Response(
        requestOptions: RequestOptions(path: '/conversations'),
        data: {
          'data': [{'id': 1, 'name': 'John Doe'}],
          'next_page_url': null,
        },
      ));

      final expectedStates = [
        isA<ChatLoading>(),
        isA<ConversationsLoaded>().having((s) => s.conversations.length, 'length', 1),
      ];

      expectLater(chatBloc.stream, emitsInOrder(expectedStates));
      chatBloc.add(FetchConversations());
    });

    test('emits MessagesLoaded on FetchMessages', () async {
      when(mockApiService.getMessages(1, cursor: null)).thenAnswer((_) async => Response(
        requestOptions: RequestOptions(path: '/messages'),
        data: {
          'data': [{'id': 1, 'content': 'Hello'}],
          'next_cursor': 'abc',
        },
      ));

      final expectedStates = [
        isA<MessagesLoaded>().having((s) => s.messages.length, 'length', 1).having((s) => s.nextCursor, 'cursor', 'abc'),
      ];

      expectLater(chatBloc.stream, emitsInOrder(expectedStates));
      chatBloc.add(FetchMessages(1));
    });

    test('updates typing agents correctly on AgentTypingStatusChanged', () async {
      // First load conversations
      when(mockApiService.getConversations()).thenAnswer((_) async => Response(
        requestOptions: RequestOptions(path: '/conversations'),
        data: {'data': [], 'next_page_url': null},
      ));

      // Wait for ConversationsLoaded
      chatBloc.add(FetchConversations());
      await Future.delayed(Duration.zero);

      // Now test typing status
      chatBloc.add(AgentTypingStatusChanged(1, 'Jane', true));
      await Future.delayed(Duration.zero);
      expect((chatBloc.state as ConversationsLoaded).typingAgents[1], 'Jane');

      chatBloc.add(AgentTypingStatusChanged(1, 'Jane', false));
      await Future.delayed(Duration.zero);
      expect((chatBloc.state as ConversationsLoaded).typingAgents.containsKey(1), isFalse);
    });
  });
}
