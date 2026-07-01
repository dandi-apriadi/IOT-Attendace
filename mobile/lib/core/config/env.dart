class Env {
  Env._();

  /// Base URL for the PresenSync API. Defaults to the production VPS so
  /// normal builds show real data without remembering a --dart-define.
  /// Override for local backend testing with:
  ///   flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
  static const String defaultApiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://elektropolimdo.com/api/v1',
  );
}
