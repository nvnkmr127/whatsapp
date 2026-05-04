import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:whatsapp_pro_mobile/logic/blocs/message_bloc.dart';
import '../../helpers/native_test_helpers.mocks.dart';

void main() {
  group('MessageBloc Tests', () {
    late MessageBloc messageBloc;
    late dynamic mockChatRepository;

    setUp(() {
      mockChatRepository = MockChatRepository();
      messageBloc = MessageBloc(mockChatRepository);
    });

    tearDown(() {
      messageBloc.close();
    });

    test('initial state is MessageInitial', () {
      expect(messageBloc.state, isA<MessageInitial>());
    });

    test('calls sendMessage on repository when SendMessageToConversation event is added', () async {
      when(mockChatRepository.sendMessage(1, 'Hello', 'text', mediaUrl: null, replyToMessageId: null)).thenAnswer((_) async => null);

      messageBloc.add(SendMessageToConversation(1, 'Hello', type: 'text'));
      await Future.delayed(Duration.zero);
      verify(mockChatRepository.sendMessage(1, 'Hello', 'text', mediaUrl: null, replyToMessageId: null)).called(1);
    });

    test('calls sendMessage with media when SendMessageToConversation has mediaUrl', () async {
      when(mockChatRepository.sendMessage(1, 'Image', 'image', mediaUrl: 'http://test.com/img.jpg', replyToMessageId: null)).thenAnswer((_) async => null);

      messageBloc.add(SendMessageToConversation(1, 'Image', type: 'image', mediaUrl: 'http://test.com/img.jpg'));
      await Future.delayed(Duration.zero);
      verify(mockChatRepository.sendMessage(1, 'Image', 'image', mediaUrl: 'http://test.com/img.jpg', replyToMessageId: null)).called(1);
    });

    test('calls updateMessageStatus on repository when StatusChanged event is added', () async {
      when(mockChatRepository.updateMessageStatus(1, 'read')).thenAnswer((_) async {});

      messageBloc.add(StatusChanged(1, 'read'));
      await Future.delayed(Duration.zero);
      verify(mockChatRepository.updateMessageStatus(1, 'read')).called(1);
    });

    test('calls sendTemplate on repository when SendTemplate event is added', () async {
      when(mockChatRepository.sendTemplate(1, 10, variables: ['Var1'])).thenAnswer((_) async {});

      messageBloc.add(SendTemplate(1, 10, variables: ['Var1']));
      await Future.delayed(Duration.zero);
      verify(mockChatRepository.sendTemplate(1, 10, variables: ['Var1'])).called(1);
    });
  });
}
