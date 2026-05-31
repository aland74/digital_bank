import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

class LocalStorage {
  static LocalStorage? _instance;
  late final SharedPreferences _prefs;

  LocalStorage._();

  factory LocalStorage() {
    _instance ??= LocalStorage._();
    return _instance!;
  }

  Future<void> init() async {
    _prefs = await SharedPreferences.getInstance();
  }

  // ── Theme ─────────────────────────────────────────────────────

  static const _themeKey = 'theme_mode';

  Future<void> saveThemeMode(String mode) {
    return _prefs.setString(_themeKey, mode);
  }

  String? getThemeMode() {
    return _prefs.getString(_themeKey);
  }

  // ── Locale ────────────────────────────────────────────────────

  static const _localeKey = 'locale_code';

  Future<void> saveLocale(String code) {
    return _prefs.setString(_localeKey, code);
  }

  String? getLocale() {
    return _prefs.getString(_localeKey);
  }

  // ── Dashboard cache ───────────────────────────────────────────

  static const _dashboardCacheKey = 'cached_dashboard';
  static const _dashboardCacheTimeKey = 'cached_dashboard_time';

  Future<void> cacheDashboard(Map<String, dynamic> data) async {
    await _prefs.setString(_dashboardCacheKey, jsonEncode(data));
    await _prefs.setInt(
      _dashboardCacheTimeKey,
      DateTime.now().millisecondsSinceEpoch,
    );
  }

  Map<String, dynamic>? getCachedDashboard() {
    final raw = _prefs.getString(_dashboardCacheKey);
    if (raw == null) return null;

    // Check staleness — 5 minutes
    final cachedAt = _prefs.getInt(_dashboardCacheTimeKey);
    if (cachedAt != null) {
      final age = DateTime.now().millisecondsSinceEpoch - cachedAt;
      if (age > 5 * 60 * 1000) {
        clearDashboardCache();
        return null;
      }
    }

    final decoded = jsonDecode(raw);
    return decoded is Map<String, dynamic>
        ? decoded
        : decoded is Map
            ? Map<String, dynamic>.from(decoded)
            : null;
  }

  Future<void> clearDashboardCache() async {
    await _prefs.remove(_dashboardCacheKey);
    await _prefs.remove(_dashboardCacheTimeKey);
  }

  // ── Clear all cache ───────────────────────────────────────────

  Future<void> clearCache() async {
    await _prefs.remove(_dashboardCacheKey);
    await _prefs.remove(_dashboardCacheTimeKey);
  }
}
