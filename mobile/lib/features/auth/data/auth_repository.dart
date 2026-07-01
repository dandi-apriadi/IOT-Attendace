import 'package:dio/dio.dart';

import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/storage/secure_storage_service.dart';
import '../models/user_model.dart';

class AuthException implements Exception {
  AuthException(this.message);
  final String message;

  @override
  String toString() => message;
}

class AuthRepository {
  AuthRepository(this._apiClient, this._storage);

  final ApiClient _apiClient;
  final SecureStorageService _storage;

  Future<UserModel> login({required String email, required String password}) async {
    final dio = await _apiClient.instance();

    try {
      final response = await dio.post(ApiEndpoints.login, data: {
        'email': email,
        'password': password,
      });

      final token = response.data['token'] as String;
      final user = UserModel.fromJson(response.data['user'] as Map<String, dynamic>);

      await _storage.saveSession(
        token: token,
        userId: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
      );

      return user;
    } on DioException catch (e) {
      throw AuthException(_extractMessage(e));
    }
  }

  Future<void> logout() async {
    final dio = await _apiClient.instance();
    try {
      await dio.post(ApiEndpoints.logout);
    } on DioException {
      // Ignore network errors on logout — we still clear the local session below.
    }
    await _storage.clearSession();
  }

  /// Clears local session state without calling the server (used when the
  /// server already rejected the token with 401).
  Future<void> clearLocalSession() => _storage.clearSession();

  Future<UserModel?> restoreSession() async {
    final String? token;
    try {
      token = await _storage.readToken();
    } catch (_) {
      // Secure storage unavailable (e.g. platform channel not ready) --
      // treat as "no session" rather than blocking app startup forever.
      return null;
    }
    if (token == null) return null;

    final dio = await _apiClient.instance();
    try {
      final response = await dio.get(ApiEndpoints.me);
      return UserModel.fromJson(response.data['user'] as Map<String, dynamic>);
    } on DioException {
      await _storage.clearSession();
      return null;
    }
  }

  String _extractMessage(DioException e) {
    final data = e.response?.data;
    if (data is Map && data['errors'] is Map) {
      final errors = data['errors'] as Map;
      final firstList = errors.values.first;
      if (firstList is List && firstList.isNotEmpty) {
        return firstList.first.toString();
      }
    }
    if (data is Map && data['message'] is String) {
      return data['message'] as String;
    }
    return 'Tidak dapat terhubung ke server. Periksa koneksi Anda.';
  }
}
