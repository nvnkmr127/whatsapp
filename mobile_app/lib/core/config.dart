class EnvConfig {
  static const String env = String.fromEnvironment('ENV', defaultValue: 'prod');

  static const String appUrl = String.fromEnvironment(
    'APP_URL',
    defaultValue: 'https://flow.watxio.com',
  );

  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: '$appUrl/api/v1',
  );

  static const String wsUrl = String.fromEnvironment(
    'WS_URL',
    defaultValue: 'flow.watxio.com',
  );

  static const String reverbKey = String.fromEnvironment('REVERB_APP_KEY', defaultValue: '');

  static const String reverbHost = String.fromEnvironment(
    'REVERB_HOST',
    defaultValue: wsUrl,
  );

  static const int reverbPort = int.fromEnvironment('REVERB_PORT', defaultValue: 443);

  static const String reverbScheme = String.fromEnvironment('REVERB_SCHEME', defaultValue: 'https');

  // Feature Flags
  static bool get isDemoMode => bool.fromEnvironment('DEMO_MODE', defaultValue: false);
  static bool get showDebugMenu => env != 'prod';
  static bool get enableAnalytics => env == 'prod';

  static bool get isDev => env == 'dev';
  static bool get isStage => env == 'stage';
  static bool get isProd => env == 'prod';
}
