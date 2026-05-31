import 'dart:async';
import 'dart:io';
import 'package:dio/dio.dart';
import 'package:pretty_dio_logger/pretty_dio_logger.dart';
import 'package:uuid/uuid.dart';
import '../constants/api_constants.dart';
import '../storage/secure_storage.dart';

/// Global logout event stream for 401 handling.
final _logoutController = StreamController<void>.broadcast();
Stream<void> get onLogout => _logoutController.stream;

class ApiClient {
  static ApiClient? _instance;
  late final Dio _dio;
  final SecureStorage _secureStorage = SecureStorage();

  ApiClient._() {
    _dio = Dio(
      BaseOptions(
        baseUrl: Platform.isIOS ? ApiConstants.iosBaseUrl : ApiConstants.baseUrl,
        connectTimeout: ApiConstants.connectTimeout,
        receiveTimeout: ApiConstants.receiveTimeout,
        // Accept all 2xx responses as success (201 for register, 202 for OTP)
        validateStatus: (status) => status != null && status >= 200 && status < 300,
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );

    _dio.interceptors.addAll([
      // Auth + language + idempotency interceptor
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          // Bearer token
          final token = await _secureStorage.getToken();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }

          // Accept-Language — default to English, callers can override
          options.headers['Accept-Language'] ??= 'en';

          // Idempotency key for mutating requests
          if (options.method == 'POST' ||
              options.method == 'PUT' ||
              options.method == 'DELETE') {
            options.headers['Idempotency-Key'] = const Uuid().v4();
          }

          handler.next(options);
        },
        onError: (error, handler) async {
          if (error.response?.statusCode == 401) {
            // Token expired or invalid — trigger global logout
            await _secureStorage.deleteToken();
            _logoutController.add(null);
          }
          handler.next(error);
        },
      ),

      // Logger in debug mode
      PrettyDioLogger(
        requestHeader: true,
        requestBody: true,
        responseBody: true,
        responseHeader: false,
        error: true,
        compact: true,
      ),
    ]);
  }

  factory ApiClient() {
    _instance ??= ApiClient._();
    return _instance!;
  }

  Dio get dio => _dio;

  // ── Convenience HTTP methods ──────────────────────────────────

  Future<Response<T>> get<T>(
    String path, {
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
  }) {
    return _dio.get<T>(
      path,
      queryParameters: queryParameters,
      options: options,
      cancelToken: cancelToken,
    );
  }

  Future<Response<T>> post<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
  }) {
    return _dio.post<T>(
      path,
      data: data,
      queryParameters: queryParameters,
      options: options,
      cancelToken: cancelToken,
    );
  }

  Future<Response<T>> put<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
  }) {
    return _dio.put<T>(
      path,
      data: data,
      queryParameters: queryParameters,
      options: options,
      cancelToken: cancelToken,
    );
  }

  Future<Response<T>> delete<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
  }) {
    return _dio.delete<T>(
      path,
      data: data,
      queryParameters: queryParameters,
      options: options,
      cancelToken: cancelToken,
    );
  }
}
