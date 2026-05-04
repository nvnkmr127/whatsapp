import 'dart:async';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../data/models/local_message.dart';
import '../../data/repositories/chat_repository.dart';

// --- Events ---
abstract class MessageEvent {}
class LoadMessages extends MessageEvent {
  final int conversationId;
  LoadMessages(this.conversationId);
}
class LoadMoreMessages extends MessageEvent {
  final int conversationId;
  LoadMoreMessages(this.conversationId);
}
class SendMessageToConversation extends MessageEvent {
  final int conversationId;
  final String content;
  final String type;
  final String? mediaUrl;
  final String? localMediaPath;
  final int? replyToMessageId;
  SendMessageToConversation(this.conversationId, this.content, {this.type = 'text', this.mediaUrl, this.localMediaPath, this.replyToMessageId});
}

class RetryMessage extends MessageEvent {
  final int localId;
  RetryMessage(this.localId);
}
class SendTemplate extends MessageEvent {
  final int conversationId;
  final int templateId;
  final List<dynamic>? variables;
  SendTemplate(this.conversationId, this.templateId, {this.variables});
}
class MessagesUpdated extends MessageEvent {
  final List<LocalMessage> messages;
  final bool isWithin24Hours;
  MessagesUpdated(this.messages, {this.isWithin24Hours = true});
}

class StatusChanged extends MessageEvent {
  final int remoteId;
  final String status;
  StatusChanged(this.remoteId, this.status);
}

class NewMessageReceived extends MessageEvent {
  final LocalMessage message;
  NewMessageReceived(this.message);
}

// --- States ---
abstract class MessageState {}
class MessageInitial extends MessageState {}
class MessageLoading extends MessageState {}
class MessageLoaded extends MessageState {
  final List<LocalMessage> messages;
  final bool isWithin24Hours;
  final String? nextCursor;
  final bool isLoadingMore;
  
  MessageLoaded(this.messages, {this.isWithin24Hours = true, this.nextCursor, this.isLoadingMore = false});

  MessageLoaded copyWith({List<LocalMessage>? messages, bool? isWithin24Hours, String? nextCursor, bool? isLoadingMore}) {
    return MessageLoaded(
      messages ?? this.messages,
      isWithin24Hours: isWithin24Hours ?? this.isWithin24Hours,
      nextCursor: nextCursor ?? this.nextCursor,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
    );
  }
}
class MessageError extends MessageState {
  final String error;
  MessageError(this.error);
}

// --- BLoC ---
class MessageBloc extends Bloc<MessageEvent, MessageState> {
  final ChatRepository _repository;
  StreamSubscription? _subscription;

  MessageBloc(this._repository) : super(MessageInitial()) {
    on<LoadMessages>(_onLoadMessages);
    on<LoadMoreMessages>(_onLoadMoreMessages);
    on<SendMessageToConversation>(_onSendMessage);
    on<RetryMessage>(_onRetryMessage);
    on<MessagesUpdated>(_onMessagesUpdated);
    on<StatusChanged>(_onStatusChanged);
    on<NewMessageReceived>(_onNewMessageReceived);
    on<SendTemplate>(_onSendTemplate);
  }

  Future<void> _onSendTemplate(SendTemplate event, Emitter<MessageState> emit) async {
    await _repository.sendTemplate(event.conversationId, event.templateId, variables: event.variables);
  }

  void _onNewMessageReceived(NewMessageReceived event, Emitter<MessageState> emit) {
    if (state is MessageLoaded) {
      final current = state as MessageLoaded;
      final index = current.messages.indexWhere((m) => m.remoteId == event.message.remoteId);
      
      if (index != -1) {
        // Update existing message (e.g., when media finishes downloading)
        final updatedList = List<LocalMessage>.from(current.messages);
        updatedList[index] = event.message;
        _webFallbackMessages = updatedList;
        emit(current.copyWith(messages: updatedList));
        return;
      }
      
      final updatedList = [event.message, ...current.messages];
      _webFallbackMessages = updatedList;
      emit(current.copyWith(messages: updatedList));
    }
  }

  Future<void> _onStatusChanged(StatusChanged event, Emitter<MessageState> emit) async {
    await _repository.updateMessageStatus(event.remoteId, event.status);
  }

  List<LocalMessage> _webFallbackMessages = [];
  bool _lastKnownWindowStatus = true;
  String? _nextCursor;

  Future<void> _onLoadMessages(LoadMessages event, Emitter<MessageState> emit) async {
    emit(MessageLoading());
    
    await _subscription?.cancel();
    _subscription = _repository.watchMessages(event.conversationId).listen((msgs) {
      add(MessagesUpdated(msgs, isWithin24Hours: _lastKnownWindowStatus));
    });

    final result = await _repository.syncMessages(event.conversationId);
    if (result.messages != null) {
      _webFallbackMessages = result.messages!;
      _lastKnownWindowStatus = result.isWithin24Hours;
      _nextCursor = result.nextCursor;
      add(MessagesUpdated(List.from(_webFallbackMessages), isWithin24Hours: _lastKnownWindowStatus));
    }
  }

  Future<void> _onLoadMoreMessages(LoadMoreMessages event, Emitter<MessageState> emit) async {
    if (_nextCursor == null || state is! MessageLoaded || (state as MessageLoaded).isLoadingMore) return;
    
    final currentState = state as MessageLoaded;
    emit(currentState.copyWith(isLoadingMore: true));

    final result = await _repository.syncMessages(event.conversationId, cursor: _nextCursor);
    if (result.messages != null) {
      _webFallbackMessages.addAll(result.messages!);
      _nextCursor = result.nextCursor;
      emit(currentState.copyWith(
        messages: List.from(_webFallbackMessages),
        nextCursor: _nextCursor,
        isLoadingMore: false,
      ));
    } else {
      emit(currentState.copyWith(isLoadingMore: false));
    }
  }


  void _onMessagesUpdated(MessagesUpdated event, Emitter<MessageState> emit) {
    emit(MessageLoaded(event.messages, isWithin24Hours: event.isWithin24Hours, nextCursor: _nextCursor));
  }


  Future<void> _onSendMessage(SendMessageToConversation event, Emitter<MessageState> emit) async {
    final sentMsg = await _repository.sendMessage(
      event.conversationId, 
      event.content, 
      event.type, 
      mediaUrl: event.mediaUrl,
      localMediaPath: event.localMediaPath,
      replyToMessageId: event.replyToMessageId,
    );
    
    if (sentMsg != null) {
      _webFallbackMessages.insert(0, sentMsg);
      add(MessagesUpdated(List.from(_webFallbackMessages), isWithin24Hours: _lastKnownWindowStatus));
    }
  }

  Future<void> _onRetryMessage(RetryMessage event, Emitter<MessageState> emit) async {
    await _repository.retryMessage(event.localId);
  }


  @override
  Future<void> close() {
    _subscription?.cancel();
    return super.close();
  }
}
