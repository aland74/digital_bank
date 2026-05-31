import 'dart:io';
import 'package:dio/dio.dart' show DioException, MultipartFile, FormData;
import '../../core/network/api_client.dart';
import '../../core/network/api_response.dart';
import '../models/user.dart';

class ProfileRepository {
  final ApiClient _api = ApiClient();

  Future<ApiResponse<User>> getProfile() async {
    try {
      final response = await _api.get('/profile');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final map = data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
          return User.fromJson(map);
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<User>> updateProfile(Map<String, dynamic> data) async {
    try {
      final response = await _api.put('/profile', data: data);
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final map = data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
          return User.fromJson(map);
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> changePassword(Map<String, dynamic> data) async {
    try {
      final response = await _api.post('/profile/change-password', data: data);
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> uploadKyc({
    required String documentType,
    required File file,
    required String documentNumber,
  }) async {
    try {
      final formData = FormData.fromMap({
        'document_type': documentType,
        'document_number': documentNumber,
        'document_file': await MultipartFile.fromFile(file.path),
      });
      final response = await _api.post('/profile/kyc', data: formData);
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getKycStatus() async {
    try {
      final response = await _api.get('/profile/kyc/status');
      return ApiResponse.fromJson(
        response.data,
        (data) => data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{},
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<List<Map<String, dynamic>>>> getSecurityLogs() async {
    try {
      final response = await _api.get('/profile/security-logs');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          if (data is List) {
            return data.map((e) => e is Map<String, dynamic> ? e : e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{}).toList();
          }
          return <Map<String, dynamic>>[];
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> get2faStatus() async {
    try {
      final response = await _api.get('/profile/2fa');
      return ApiResponse.fromJson(
        response.data,
        (data) => data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{},
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> enable2fa(String code, String secret) async {
    try {
      final response = await _api.post('/profile/2fa/enable', data: {
        'code': code,
        'secret': secret,
      });
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> disable2fa(String password) async {
    try {
      final response = await _api.post('/profile/2fa/disable', data: {
        'password': password,
      });
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  String _errorMessage(Exception e) {
    if (e is DioException) {
      final responseData = e.response?.data;
      if (responseData is Map) {
        return responseData['message'] as String? ?? 'Request failed';
      }
    }
    return 'An unexpected error occurred';
  }
}
