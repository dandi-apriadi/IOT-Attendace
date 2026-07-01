import 'package:dio/dio.dart';

import '../config/env.dart';
import '../storage/secure_storage_service.dart';

/// Builds a configured [Dio] instance: attaches the bearer token to every
/// request and calls [onUnauthorized] once when the server responds 401 so
/// the app can clear the session and redirect to login.
class ApiClient {
  ApiClient(this._storage, {required this.onUnauthorized});

  final SecureStorageService _storage;
  final Future<void> Function() onUnauthorized;

  Dio? _dio;

  Future<Dio> instance() async {
    if (_dio != null) return _dio!;

    final baseUrl = await _storage.readBaseUrl() ?? Env.defaultApiBaseUrl;

    final dio = Dio(BaseOptions(
      baseUrl: baseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 10),
      headers: {'Accept': 'application/json'},
    ));

    dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.readToken();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (error, handler) async {
        if (error.response?.statusCode == 401) {
          await onUnauthorized();
        }
        handler.next(error);
      },
    ));

    _dio = dio;
    return dio;
  }

  /// Rebuilds the underlying Dio client, e.g. after the base URL changes
  /// in settings.
  void reset() => _dio = null;
}
