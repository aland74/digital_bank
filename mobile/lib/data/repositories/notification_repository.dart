import 'package:dio/dio.dart' show DioException;
import '../../core/network/api_client.dart';
import '../../core/network/api_response.dart';
import '../models/notification.dart';

class NotificationRepository {
  final ApiClient _api = ApiClient();

  Future<ApiResponse<Map<String, dynamic>>> getNotifications({
    int page = 1,
  }) async {
    try {
      final response = await _api.get(
        '/notifications',
        queryParameters: {'page': page},
      );
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

  Future<ApiResponse<void>> markAsRead(int id) async {
    try {
      final response = await _api.post('/notifications/$id/read');
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> markAllAsRead() async {
    try {
      final response = await _api.post('/notifications/read-all');
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<int>> getUnreadCount() async {
    try {
      final response = await _api.get('/notifications/unread-count');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final map = data is Map ? data : {};
          return map['count'] as int? ?? 0;
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<List<AppNotification>>> getLatest() async {
    try {
      final response = await _api.get('/notifications/latest');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final list = data is List ? data : [];
          return list
              .map((e) => AppNotification.fromJson(
                  e is Map<String, dynamic> ? e : e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{}))
              .toList();
        },
      );
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
