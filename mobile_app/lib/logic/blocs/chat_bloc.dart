import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import '../../core/api_service.dart';
import '../../core/cache_service.dart';
import '../../data/repositories/chat_repository.dart';

// --- Events ---
abstract class ChatEvent {}
class FetchConversations extends ChatEvent {
  final bool isRefresh;
  final int page;
  final String filter;
  final String? query;
  FetchConversations({this.isRefresh = false, this.page = 1, this.filter = 'all', this.query});
}
class FetchMessages extends ChatEvent {
  final int conversationId;
  final String? cursor;
  FetchMessages(this.conversationId, {this.cursor});
}
class RealTimeMessageReceived extends ChatEvent {
  final dynamic messagePayload;
  RealTimeMessageReceived(this.messagePayload);
}

class AgentTypingStatusChanged extends ChatEvent {
  final int id;
  final String userName;
  final bool isTyping;
  AgentTypingStatusChanged(this.id, this.userName, this.isTyping);
}

// --- States ---
abstract class ChatState {}
class ChatInitial extends ChatState {}
class ChatLoading extends ChatState {}
class ConversationsLoaded extends ChatState {
  final List<dynamic> conversations;
  final bool hasNextPage;
  final Map<int, String?> typingAgents;
  final String currentFilter;
  ConversationsLoaded(this.conversations, {this.hasNextPage = true, this.typingAgents = const {}, this.currentFilter = 'all'});
}
class MessagesLoaded extends ChatState {
  final List<dynamic> messages;
  final String? nextCursor;
  MessagesLoaded(this.messages, {this.nextCursor});
}
class ChatError extends ChatState {
  final String message;
  ChatError(this.message);
}
class RealTimeMessageNotification extends ChatState {
  final dynamic payload;
  RealTimeMessageNotification(this.payload);
}

// --- BLoC ---
class ChatBloc extends Bloc<ChatEvent, ChatState> {
  final ApiService _apiService;
  final ChatRepository _chatRepository;
  
  final _notificationController = StreamController<dynamic>.broadcast();
  Stream<dynamic> get notificationStream => _notificationController.stream;

  ChatBloc(this._apiService, this._chatRepository) : super(ChatInitial()) {
    on<FetchConversations>(_onFetchConversations);
    on<FetchMessages>(_onFetchMessages);
    on<RealTimeMessageReceived>(_onRealTimeMessageReceived);
    on<AgentTypingStatusChanged>(_onAgentTypingStatusChanged);

    Connectivity().onConnectivityChanged.listen((results) {
      if (results.any((r) => r != ConnectivityResult.none)) {
         add(FetchConversations(isRefresh: true));
      }
    });
  }

  void _onAgentTypingStatusChanged(AgentTypingStatusChanged event, Emitter<ChatState> emit) {
    if (state is ConversationsLoaded) {
      final current = state as ConversationsLoaded;
      final newMap = Map<int, String?>.from(current.typingAgents);
      if (event.isTyping) {
        newMap[event.id] = event.userName;
      } else {
        newMap.remove(event.id);
      }
      emit(ConversationsLoaded(current.conversations, typingAgents: newMap));
    }
  }

  Future<void> _onFetchConversations(FetchConversations event, Emitter<ChatState> emit) async {
    final Map<int, String?> currentTyping = state is ConversationsLoaded ? (state as ConversationsLoaded).typingAgents : {};
    final List<dynamic> existingConversations = (state is ConversationsLoaded && !event.isRefresh && event.page > 1) 
        ? (state as ConversationsLoaded).conversations 
        : [];

    if (!event.isRefresh && event.page == 1) {
       // 1. Try Loading Cache First for better UX
       final cached = await CacheService.getCachedConversations();
       if (cached.isNotEmpty) {
         emit(ConversationsLoaded(cached, hasNextPage: true, typingAgents: currentTyping, currentFilter: event.filter));
       } else {
         emit(ChatLoading());
       }
    }
    
    try {
      final response = await _apiService.getConversations(page: event.page, filter: event.filter, query: event.query);
      final newConversations = response.data['data'] ?? [];
      
      // 2. Persist to Cache
      if (event.page == 1 && event.filter == 'all' && event.query == null) {
        await CacheService.cacheConversations(newConversations);
      }

      emit(ConversationsLoaded(
        [...existingConversations, ...newConversations],
        hasNextPage: response.data['next_page_url'] != null,
        typingAgents: currentTyping,
        currentFilter: event.filter,
      ));
    } catch (e) {
      if (event.page == 1 && existingConversations.isEmpty) {
        // If we have cached data, don't show error, just keep cached
        if (state is! ConversationsLoaded) {
           emit(ChatError(e.toString()));
        }
      }
    }
  }

  Future<void> _onFetchMessages(FetchMessages event, Emitter<ChatState> emit) async {
    try {
      final response = await _apiService.getMessages(event.conversationId, cursor: event.cursor);
      emit(MessagesLoaded(
        response.data['data'],
        nextCursor: response.data['next_cursor'],
      ));
    } catch (e) {
      // emit(ChatError(e.toString()));
    }
  }

  Future<void> _onRealTimeMessageReceived(RealTimeMessageReceived event, Emitter<ChatState> emit) async {
    _notificationController.add(event.messagePayload);
    
    if (!kIsWeb) {
      try {
        await _chatRepository.upsertRealtimeMessage(Map<String, dynamic>.from(event.messagePayload as dynamic));
      } catch (_) {}
    }
    if (state is ConversationsLoaded) {
       add(FetchConversations(isRefresh: true));
    }
  }
}
