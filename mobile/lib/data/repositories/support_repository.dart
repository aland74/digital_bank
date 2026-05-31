import 'package:dio/dio.dart' show DioException;
import '../../core/network/api_client.dart';
import '../../core/network/api_response.dart';
import '../models/support_ticket.dart';

class SupportRepository {
  final ApiClient _api = ApiClient();

  Future<ApiResponse<Map<String, dynamic>>> getTickets({
    int page = 1,
    String? status,
  }) async {
    try {
      final queryParams = <String, dynamic>{'page': page};
      if (status != null) queryParams['status'] = status;

      final response = await _api.get(
        '/support/tickets',
        queryParameters: queryParams,
      );
      final rawData = response.data;
      final raw = rawData is Map<String, dynamic> ? rawData : <String, dynamic>{};
      return ApiResponse(
        success: raw['success'] as bool? ?? false,
        data: raw['data'] as Map<String, dynamic>?,
        message: raw['message'] as String?,
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<SupportTicket>> getTicket(int id) async {
    try {
      final response = await _api.get('/support/tickets/$id');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final map = data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
          return SupportTicket.fromJson(map);
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<SupportTicket>> createTicket(
    Map<String, dynamic> data,
  ) async {
    try {
      final response = await _api.post('/support/tickets', data: data);
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final map = data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
          return SupportTicket.fromJson(map);
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> replyToTicket(int id, String message) async {
    try {
      final response = await _api.post('/support/tickets/$id/reply', data: {
        'message': message,
      });
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> closeTicket(int id) async {
    try {
      final response = await _api.post('/support/tickets/$id/close');
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  String _errorMessage(Exception e) {
    if (e is DioException) {
      if (e.response?.data is Map) {
        return (e.response!.data as Map)['message'] as String? ?? 'Request failed';
      }
    }
    return 'An unexpected error occurred';
  }
}
