import 'package:dio/dio.dart' show DioException;
import '../../core/network/api_client.dart';
import '../../core/network/api_response.dart';
import '../models/transaction.dart';

class TransactionRepository {
  final ApiClient _api = ApiClient();

  Future<ApiResponse<Map<String, dynamic>>> getTransactions({
    int page = 1,
    Map<String, dynamic>? filters,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        ...?filters,
      };
      final response = await _api.get(
        '/transactions',
        queryParameters: queryParams,
      );
      final rawData = response.data;
      final raw = rawData is Map<String, dynamic> ? rawData : <String, dynamic>{};
      return ApiResponse(
        success: true,
        data: raw,
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<Transaction>> getTransaction(int id) async {
    try {
      final response = await _api.get('/transactions/$id');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final map = data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
          return Transaction.fromJson(map);
        },
      );
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
