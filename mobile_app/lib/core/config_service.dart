import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'config.dart';
import 'storage_keys.dart';

class ConfigService {
  static const _storage = FlutterSecureStorage();

  static Future<String> getBaseUrl() async {
    final customUrl = await _storage.read(key: StorageKeys.customBaseUrl);
    return customUrl ?? EnvConfig.appUrl;
  }

  static Future<String> getApiBaseUrl() async {
    String baseUrl = await getBaseUrl();
    // Ensure the URL ends with a slash for Dio relative paths
    if (!baseUrl.endsWith('/')) {
      baseUrl = '$baseUrl/';
    }
    
    // If the URL already contains /api/v1, return it as is
    if (baseUrl.contains('/api/v1')) {
      return baseUrl;
    }
    
    // Otherwise, append the default API path
    return '${baseUrl}api/v1/';
  }

  static Future<void> saveConfig(Map<String, dynamic> config) async {
    if (config['baseUrl'] != null) {
      await _storage.write(key: StorageKeys.customBaseUrl, value: config['baseUrl']);
    }
    if (config['email'] != null) {
      await _storage.write(key: StorageKeys.savedEmail, value: config['email']);
    }
    if (config['teamId'] != null) {
      await _storage.write(key: StorageKeys.savedTeamId, value: config['teamId'].toString());
    }
    if (config['token'] != null) {
      await _storage.write(key: StorageKeys.setupToken, value: config['token']);
    }
  }

  static Future<String?> getSetupToken() async {
    return _storage.read(key: StorageKeys.setupToken);
  }

  static Future<void> clearSetupToken() async {
    await _storage.delete(key: StorageKeys.setupToken);
  }

  static Future<String?> getSavedEmail() async {
    return _storage.read(key: StorageKeys.savedEmail);
  }

  static Future<String?> getSavedTeamId() async {
    return _storage.read(key: StorageKeys.savedTeamId);
  }

  static Future<void> clearConfig() async {
    await _storage.delete(key: StorageKeys.customBaseUrl);
    await _storage.delete(key: StorageKeys.savedEmail);
    await _storage.delete(key: StorageKeys.savedTeamId);
  }
}
