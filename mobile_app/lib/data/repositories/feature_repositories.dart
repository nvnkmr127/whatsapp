import '../../core/api_service.dart';
import '../../core/cache_service.dart';
import '../models/api_models.dart';

/// Repository for Dashboard & Analytics
class AnalyticsRepository {
  final ApiService _api;
  AnalyticsRepository(this._api);

  Future<AnalyticsData?> getDashboardStats({String period = 'today'}) async {
    try {
      final response = await _api.getDashboardAnalytics(period: period);
      return AnalyticsData.fromJson(response.data);
    } catch (_) {
      return null;
    }
  }
}

/// Repository for Contact Management
class ContactRepository {
  final ApiService _api;
  ContactRepository(this._api);

  Future<List<Contact>> searchContacts({String? query, int page = 1}) async {
    try {
      final response = await _api.getContacts(page: page, search: query);
      final List data = response.data['data'] ?? [];
      
      if (page == 1 && (query == null || query.isEmpty)) {
        await CacheService.cacheContacts(data);
      }
      
      return data.map((item) => Contact.fromJson(item)).toList();
    } catch (_) {
      if (page == 1 && (query == null || query.isEmpty)) {
        try {
          final cached = await CacheService.getCachedContacts();
          return cached.map((item) => Contact.fromJson(item)).toList();
        } catch (e) {
          return [];
        }
      }
      return [];
    }
  }

  Future<Contact?> getContactDetails(int id) async {
    try {
      final response = await _api.getContact(id);
      return Contact.fromJson(response.data);
    } catch (_) {
      return null;
    }
  }
}

/// Repository for Message Templates
class TemplateRepository {
  final ApiService _api;
  TemplateRepository(this._api);

  Future<List<Template>> getTemplates() async {
    try {
      final response = await _api.getTemplates();
      final List data = response.data['data'] ?? [];
      return data.map((item) => Template.fromJson(item)).toList();
    } catch (_) {
      return [];
    }
  }
}

/// Repository for System Logs
class LogRepository {
  final ApiService _api;
  LogRepository(this._api);

  Future<List<LogEntry>> getLogs({int page = 1, String? type}) async {
    try {
      // Endpoint likely: mobile/logs
      final response = await _api.getDashboardAnalytics(); // Placeholder if endpoint missing
      final List data = response.data['logs'] ?? []; 
      return data.map((item) => LogEntry.fromJson(item)).toList();
    } catch (_) {
      return [];
    }
  }
}

/// Repository for AI Assistant features
class AiAssistantRepository {
  final ApiService _api;
  AiAssistantRepository(this._api);

  Future<AiResponse?> getReplySuggestion(String messageContext) async {
    try {
      // Endpoint likely: mobile/ai/suggest
      final response = await _api.getDashboardAnalytics(); // Placeholder
      return AiResponse.fromJson(response.data['ai'] ?? {});
    } catch (_) {
      return null;
    }
  }
}
