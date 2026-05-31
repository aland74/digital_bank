class ApiConstants {
  // When using ADB Reverse, 'localhost' on the device maps to the host machine's localhost
  static const String baseUrl = 'http://localhost:8000/api/v1';
  static const String androidEmulatorUrl = 'http://10.0.2.2:8000/api/v1';
  static const String iosBaseUrl = 'http://localhost:8000/api/v1';
  static const Duration connectTimeout = Duration(seconds: 15);
  static const Duration receiveTimeout = Duration(seconds: 30);
}
