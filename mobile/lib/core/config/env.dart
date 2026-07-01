class Env {
  Env._();

  /// Base URL for the PresenSync API, e.g. http://10.0.2.2:8000/api/v1
  /// Override at build/run time with:
  ///   flutter run --dart-define=API_BASE_URL=http://192.168.1.10:8000/api/v1
  static const String defaultApiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );
}
