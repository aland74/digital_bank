import 'package:dio/dio.dart' show DioException, DioExceptionType;
import '../../core/network/api_client.dart';
import '../../core/network/api_response.dart';
import '../../core/storage/secure_storage.dart';
import '../models/user.dart';

class AuthRepository {
  final ApiClient _api = ApiClient();
  final SecureStorage _storage = SecureStorage();

  /// Login — returns a map with either {token, user} or {requires_otp, email}.
  Future<ApiResponse<Map<String, dynamic>>> login(
    String email,
    String password,
  ) async {
    try {
      final response = await _api.post('/auth/login', data: {
        'email': email,
        'password': password,
      });
      final apiResponse = ApiResponse.fromJson(
        response.data,
        (data) => data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{},
      );
      // Save token if login succeeded (non-OTP path)
      if (apiResponse.success && apiResponse.data != null) {
        final token = apiResponse.data!['token'] as String?;
        if (token != null) {
          await _storage.saveToken(token);
        }
      }
      return apiResponse;
    } on DioException catch (e) {
      // 202 = OTP required – this is a success case
      final responseData = e.response?.data;
      if (e.response?.statusCode == 202 && responseData is Map) {
        final data = responseData is Map<String, dynamic>
            ? responseData
            : Map<String, dynamic>.from(responseData);
        return ApiResponse<Map<String, dynamic>>(
          success: true,
          data: data,
          message: data['message'] as String?,
        );
      }
      return ApiResponse(
        success: false,
        message: _errorMessage(e),
      );
    } on Exception catch (e) {
      return ApiResponse(
        success: false,
        message: _errorMessage(e),
      );
    }
  }

  /// Register — creates a new account, returns token directly.
  Future<ApiResponse<Map<String, dynamic>>> register(
    Map<String, dynamic> data,
  ) async {
    try {
      final response = await _api.post('/auth/register', data: data);
      final apiResponse = ApiResponse.fromJson(
        response.data,
        (data) => data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{},
      );
      if (apiResponse.success && apiResponse.data != null) {
        final token = apiResponse.data!['token'] as String?;
        if (token != null) {
          await _storage.saveToken(token);
        }
      }
      return apiResponse;
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  /// Verify registration OTP.
  Future<ApiResponse<Map<String, dynamic>>> verifyOtp(
    String email,
    String code,
  ) async {
    try {
      final response = await _api.post('/auth/verify-otp', data: {
        'email': email,
        'code': code,
      });
      final apiResponse = ApiResponse.fromJson(
        response.data,
        (data) => data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{},
      );
      if (apiResponse.success && apiResponse.data != null) {
        final token = apiResponse.data!['token'] as String?;
        if (token != null) {
          await _storage.saveToken(token);
        }
      }
      return apiResponse;
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  /// Resend OTP code to the user's email.
  Future<ApiResponse<void>> resendOtp(String email) async {
    try {
      final response = await _api.post('/auth/resend-otp', data: {
        'email': email,
      });
      return ApiResponse.fromJson(
        response.data,
        null,
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> logout() async {
    try {
      await _api.post('/auth/logout');
      await _storage.deleteToken();
      return const ApiResponse(success: true, message: 'Logged out');
    } on Exception catch (e) {
      await _storage.deleteToken();
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<User>> getUser() async {
    try {
      final response = await _api.get('/auth/user');
      final data = response.data;
      final json = data is Map<String, dynamic> ? data : <String, dynamic>{};
      // Backend wraps user data in `{ user: { ... } }`
      final userData = json['user'] as Map<String, dynamic>? ?? json;
      return ApiResponse<User>(
        success: true,
        data: User.fromJson(userData),
        message: json['message'] as String?,
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<bool> isBiometricEnabled() async {
    final token = await _storage.getBiometricToken();
    return token != null && token.isNotEmpty;
  }

  Future<ApiResponse<Map<String, dynamic>>> biometricLogin(
    String token,
  ) async {
    try {
      final response = await _api.post('/auth/biometric-login', data: {
        'biometric_token': token,
      });
      final apiResponse = ApiResponse.fromJson(
        response.data,
        (data) => data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{},
      );
      if (apiResponse.success && apiResponse.data != null) {
        final authToken = apiResponse.data!['token'] as String?;
        if (authToken != null) {
          await _storage.saveToken(authToken);
        }
      }
      return apiResponse;
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  /// Forgot Password — sends a reset OTP to the user's email.
  /// Returns the email and (in sandbox mode) the OTP code in the response data.
  Future<ApiResponse<Map<String, dynamic>>> forgotPassword(String email) async {
    try {
      final response = await _api.post('/auth/forgot-password', data: {
        'email': email,
      });
      return ApiResponse.fromJson(
        response.data,
        (data) => data is Map<String, dynamic>
            ? data
            : data is Map
                ? Map<String, dynamic>.from(data)
                : <String, dynamic>{},
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  /// Reset Password — verifies OTP and sets a new password.
  Future<ApiResponse<void>> resetPassword({
    required String email,
    required String otp,
    required String password,
    required String passwordConfirmation,
  }) async {
    try {
      final response = await _api.post('/auth/reset-password', data: {
        'email': email,
        'otp': otp,
        'password': password,
        'password_confirmation': passwordConfirmation,
      });
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  String _errorMessage(Exception e) {
    if (e is DioException) {
      // Extract validation errors or message from the response body
      final responseData = e.response?.data;
      if (responseData is Map) {
        final data = responseData is Map<String, dynamic>
            ? responseData
            : Map<String, dynamic>.from(responseData);
        // Laravel validation errors come as {message: '...', errors: {field: [...]}}
        if (data.containsKey('errors') && data['errors'] is Map) {
          final errors = data['errors'] as Map;
          final messages = <String>[];
          for (final field in errors.values) {
            if (field is List) {
              messages.addAll(field.cast<String>());
            }
          }
          if (messages.isNotEmpty) return messages.join('\n');
        }
        return data['message'] as String? ?? 'Request failed';
      }
      if (e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.receiveTimeout) {
        return 'Connection timed out. Please try again.';
      }
      if (e.type == DioExceptionType.connectionError) {
        return 'Cannot connect to server. Check your network.';
      }
    }
    return 'An unexpected error occurred';
  }
}
