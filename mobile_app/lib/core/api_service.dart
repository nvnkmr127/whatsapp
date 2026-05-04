import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'config_service.dart';
import 'storage_keys.dart';

class ApiService {
  late final Dio _dio;
  
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  String? _currentTenantId;
  String? get currentTenantId => _currentTenantId;

  ApiService({Dio? dio}) {
    _dio = dio ?? Dio(BaseOptions(
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 10),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ));
    _initBaseUrl();
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        debugPrint('[DEBUG] [API] Request: ${options.method} ${options.baseUrl}${options.path}');
        try {
          final token = await _storage.read(key: StorageKeys.authToken);
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }

          final tenantId = await _storage.read(key: StorageKeys.tenantId);
          _currentTenantId = tenantId;
          if (tenantId != null) {
            options.headers['X-Tenant-ID'] = tenantId;
          }

          final whatsappNumberId = await _storage.read(key: StorageKeys.activeWhatsAppNumberId);
          if (whatsappNumberId != null) {
            options.headers['X-WhatsApp-Number-ID'] = whatsappNumberId;
          }

          final memberId = await _storage.read(key: StorageKeys.activeMemberId);
          if (memberId != null) {
            options.headers['X-Member-ID'] = memberId;
          }
        } catch (e) {
          debugPrint('[DEBUG] [API] Error reading storage in interceptor: $e');
        }
        return handler.next(options);
      },
      onError: (e, handler) async {
        debugPrint('[DEBUG] [API] Error: ${e.requestOptions.method} ${e.requestOptions.path} -> ${e.response?.statusCode} ${e.response?.data}');
        if (e.response?.statusCode == 401) {
          // Token expired - clear storage
          await _storage.delete(key: StorageKeys.authToken);
          await _storage.delete(key: StorageKeys.tenantId);
          // [Logic]: You might want to trigger a logout event in AuthBloc here via a stream
        }
        return handler.next(e);
      },
    ));
  }

  Future<Response> reactToMessage(int messageId, String emoji) async {
    return _dio.post('mobile/messages/$messageId/react', data: {'emoji': emoji});
  }

  Future<Response> login(String email, String password) async {
    return _dio.post('mobile/auth/login', data: {
      'email': email,
      'password': password,
    });
  }

  Future<Response> requestOtp(String mobile) async {
    return _dio.post('mobile/auth/send-otp', data: {'phone': mobile});
  }

  Future<Response> loginWithOtp(String mobile, String otp) async {
    return _dio.post('mobile/auth/login-otp', data: {
      'phone': mobile,
      'otp': otp,
    });
  }

  Future<Response> getTeamMembers() async {
    return _dio.get('mobile/team/members');
  }

  Future<Response> assignConversation(int conversationId, int? userId) async {
    return _dio.post('mobile/conversations/$conversationId/assign', data: {'user_id': userId});
  }

  Future<Response> getTeams() async {
    return _dio.get('mobile/auth/teams');
  }

  Future<Response> getWhatsAppNumbers() async {
    return _dio.get('mobile/auth/numbers');
  }

  Future<Response> updateProfile(Map<String, dynamic> data) async {
    return _dio.post('mobile/auth/profile', data: data);
  }

  Future<Response> switchTeam(int teamId) async {
    return _dio.post('mobile/auth/switch-team', data: {'team_id': teamId});
  }

  Future<Response> markConversationAsRead(int conversationId) async {
    return _dio.post('mobile/conversations/$conversationId/mark-read');
  }

  Future<Response> closeConversation(int conversationId) async {
    return _dio.post('mobile/conversations/$conversationId/close');
  }

  Future<Response> reopenConversation(int conversationId) async {
    return _dio.post('mobile/conversations/$conversationId/reopen');
  }

  /// Request an AI-generated reply suggestion based on conversation context.
  /// Returns { 'suggestion': 'text...' } on success.
  Future<Response> suggestAiReply(int conversationId) async {
    return _dio.post('mobile/conversations/$conversationId/ai-suggest');
  }

  Future<Response> getDashboardAnalytics({String? period}) async {
    return _dio.get(
      'mobile/analytics/dashboard',
      queryParameters: period == null ? null : {'period': period},
    );
  }

  Future<Response> getMe() async {
    return _dio.get('mobile/auth/me');
  }

  Future<Response> finalizeLogin() async {
    return _dio.post('mobile/auth/finalize');
  }

  Future<Response> getContact(int contactId) async {
    return _dio.get('mobile/contacts/$contactId');
  }

  Future<Response> getContactActivity(int contactId) async {
    return _dio.get('mobile/contacts/$contactId/activity');
  }

  Future<Response> registerFCMToken(String token) async {
    return registerFcmToken(token, 'unknown');
  }

  Future<Response> logout() async {
    return _dio.post('mobile/auth/logout');
  }

  // Conversation Endpoints
  Future<Response> getConversations({int page = 1, String filter = 'all', String? query}) async {
    return _dio.get('mobile/conversations', queryParameters: {'page': page, 'filter': filter, 'q': query});
  }

  Future<Response> getConversation(int conversationId) async {
    return _dio.get('mobile/conversations/$conversationId');
  }

  Future<Response> getMessages(int conversationId, {String? cursor}) async {
    return _dio.get(
      'mobile/conversations/$conversationId/messages',
      queryParameters: {'cursor': cursor},
    );
  }

  Future<Response> sendMessage(int conversationId, Map<String, dynamic> data) async {
    return _dio.post('mobile/conversations/$conversationId/messages', data: data);
  }

  Future<Response> toggleStar(int messageId) async {
    return _dio.post('mobile/messages/$messageId/star');
  }

  // Media Upload
  Future<Response> uploadMedia(String filePath, {void Function(int, int)? onSendProgress}) async {
    final formData = FormData.fromMap({
      'file': await MultipartFile.fromFile(filePath),
    });
    return _dio.post(
      'mobile/media/upload', 
      data: formData,
      onSendProgress: onSendProgress,
    );
  }


  // Presence & FCM
  Future<Response> reportPresence(int conversationId) async {
    return _dio.post('mobile/presence/heartbeat', data: {'conversation_id': conversationId});
  }

  Future<Response> registerFcmToken(String token, String platform) async {
    return _dio.post('mobile/auth/fcm-token', data: {
      'token': token,
      'platform': platform,
    });
  }

  // Automations
  Future<Response> getAutomations() async {
    return _dio.get('mobile/automations');
  }

  Future<Response> createAutomation(Map<String, dynamic> data) async {
    return _dio.post('mobile/automations', data: data);
  }

  Future<Response> updateAutomation(int id, Map<String, dynamic> data) async {
    return _dio.put('mobile/automations/$id', data: data);
  }

  Future<Response> toggleAutomation(int id) async {
    return _dio.post('mobile/automations/$id/toggle');
  }

  Future<Response> deleteAutomation(int id) async {
    return _dio.delete('mobile/automations/$id');
  }

  // Activity Logs
  Future<Response> getActivities({int page = 1, String? search, String? type}) async {
    return _dio.get('mobile/activities', queryParameters: {
      'page': page,
      'search': search,
      'type': type,
    });
  }

  // Analytics & Insight
  Future<Response> getAnalytics() async {
    return _dio.get('mobile/analytics/dashboard');
  }

  // Campaign Management
  Future<Response> getTemplates({String? search, String? status, int page = 1}) async {
    return _dio.get('mobile/templates', queryParameters: {
      'search': search,
      'status': status,
      'page': page,
    });
  }

  Future<Response> sendTemplate(int conversationId, int templateId, {List<dynamic>? variables}) async {
    return _dio.post('mobile/conversations/$conversationId/send-template', data: {
      'template_id': templateId,
      'variables': variables ?? [],
    });
  }

  Future<Response> getTemplate(int templateId) async {
    return _dio.get('mobile/templates/$templateId');
  }

  Future<Response> sendTemplateTest(int templateId, String phone, int numberId) async {
    return _dio.post('mobile/templates/$templateId/send-test', data: {
      'phone_number': phone,
      'whatsapp_number_id': numberId,
    });
  }

  // Bot Management
  Future<Response> getBots({int page = 1, String? search}) async {
    return _dio.get('mobile/bots', queryParameters: {'page': page, 'query': search});
  }

  Future<Response> toggleBot(int id) async {
    return _dio.post('mobile/bots/$id/toggle');
  }

  Future<Response> duplicateBot(int id) async {
    return _dio.post('mobile/bots/$id/duplicate');
  }

  Future<Response> updateBot(int id, Map<String, dynamic> data) async {
    return _dio.patch('mobile/bots/$id', data: data);
  }

  Future<Response> createBot(Map<String, dynamic> data) async {
    return _dio.post('mobile/bots', data: data);
  }

  Future<Response> deleteBot(int id) async {
    return _dio.delete('mobile/bots/$id');
  }

  // Template Enhancements
  Future<Response> createTemplateDraft(Map<String, dynamic> data) async {
    return _dio.post('mobile/templates', data: data);
  }

  Future<Response> updateTemplateDraft(int id, Map<String, dynamic> data) async {
    return _dio.patch('mobile/templates/$id', data: data);
  }

  Future<Response> toggleTemplate(int id) async {
    return _dio.post('mobile/templates/$id/toggle');
  }

  Future<Response> deleteTemplate(int id) async {
    return _dio.delete('mobile/templates/$id');
  }

  // Canned Messages
  Future<Response> getCannedMessages() async {
    return _dio.get('mobile/canned-messages');
  }

  Future<Response> getAiSettings() async {
    return _dio.get('mobile/ai/settings');
  }

  Future<Response> updateAiSettings(Map<String, dynamic> data) async {
    return _dio.post('mobile/ai/settings', data: data);
  }

  Future<Response> toggleConversationAi(int conversationId, bool enabled) async {
    return _dio.post('mobile/conversations/$conversationId/toggle-ai', data: {'enabled': enabled});
  }
  
  Future<Response> getContacts({int page = 1, String? search, String? sortBy, int? tagId}) async {
    return _dio.get('mobile/contacts', queryParameters: {
      'page': page,
      'query': search,
      'sort_by': sortBy,
      'tag_id': tagId,
    });
  }

  Future<Response> getTags() async {
    return _dio.get('mobile/contacts/tags');
  }

  Future<Response> toggleContactTag(int contactId, int tagId) async {
    return _dio.post('mobile/contacts/$contactId/toggle-tag', data: {'tag_id': tagId});
  }

  Future<Response> startCampaign(Map<String, dynamic> data) async {
    return _dio.post('mobile/campaigns', data: data);
  }

  Future<Response> getRecentCampaigns() async {
    return _dio.get('mobile/campaigns');
  }

  Future<Response> getCalls() async {
    return _dio.get('calls');
  }

  Future<Response> updateContact(int contactId, Map<String, dynamic> data) async {
    return _dio.post('mobile/contacts/$contactId', data: data);
  }

  Future<void> reloadConfig() async {
    await _initBaseUrl();
  }

  Future<void> _initBaseUrl() async {
    _dio.options.baseUrl = await ConfigService.getApiBaseUrl();
  }
}
