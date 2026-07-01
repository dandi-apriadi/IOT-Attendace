import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Thin wrapper around flutter_secure_storage so the rest of the app never
/// touches the storage package directly.
class SecureStorageService {
  SecureStorageService(this._storage);

  final FlutterSecureStorage _storage;

  static const _tokenKey = 'auth_token';
  static const _userNameKey = 'auth_user_name';
  static const _userEmailKey = 'auth_user_email';
  static const _userRoleKey = 'auth_user_role';
  static const _userIdKey = 'auth_user_id';
  static const _baseUrlKey = 'api_base_url';

  Future<void> saveSession({
    required String token,
    required int userId,
    required String name,
    required String email,
    required String role,
  }) async {
    await Future.wait([
      _storage.write(key: _tokenKey, value: token),
      _storage.write(key: _userIdKey, value: userId.toString()),
      _storage.write(key: _userNameKey, value: name),
      _storage.write(key: _userEmailKey, value: email),
      _storage.write(key: _userRoleKey, value: role),
    ]);
  }

  Future<String?> readToken() => _storage.read(key: _tokenKey);

  Future<Map<String, String?>> readUser() async {
    final values = await Future.wait([
      _storage.read(key: _userIdKey),
      _storage.read(key: _userNameKey),
      _storage.read(key: _userEmailKey),
      _storage.read(key: _userRoleKey),
    ]);

    return {
      'id': values[0],
      'name': values[1],
      'email': values[2],
      'role': values[3],
    };
  }

  Future<void> clearSession() async {
    await Future.wait([
      _storage.delete(key: _tokenKey),
      _storage.delete(key: _userIdKey),
      _storage.delete(key: _userNameKey),
      _storage.delete(key: _userEmailKey),
      _storage.delete(key: _userRoleKey),
    ]);
  }

  Future<String?> readBaseUrl() => _storage.read(key: _baseUrlKey);

  Future<void> saveBaseUrl(String url) => _storage.write(key: _baseUrlKey, value: url);
}
