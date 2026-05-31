import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureStorage {
  static SecureStorage? _instance;
  late final FlutterSecureStorage _storage;

  SecureStorage._() {
    _storage = const FlutterSecureStorage(
      aOptions: AndroidOptions(encryptedSharedPreferences: true),
    );
  }

  factory SecureStorage() {
    _instance ??= SecureStorage._();
    return _instance!;
  }

  // ── Auth token ────────────────────────────────────────────────

  static const _tokenKey = 'auth_token';
  static const _biometricTokenKey = 'biometric_token';

  Future<void> saveToken(String token) {
    return _storage.write(key: _tokenKey, value: token);
  }

  Future<String?> getToken() {
    return _storage.read(key: _tokenKey);
  }

  Future<void> deleteToken() {
    return _storage.delete(key: _tokenKey);
  }

  // ── Biometric token ──────────────────────────────────────────

  Future<void> saveBiometricToken(String token) {
    return _storage.write(key: _biometricTokenKey, value: token);
  }

  Future<String?> getBiometricToken() {
    return _storage.read(key: _biometricTokenKey);
  }

  Future<void> deleteBiometricToken() {
    return _storage.delete(key: _biometricTokenKey);
  }

  // ── Clear all ─────────────────────────────────────────────────

  Future<void> deleteAll() {
    return _storage.deleteAll();
  }
}
