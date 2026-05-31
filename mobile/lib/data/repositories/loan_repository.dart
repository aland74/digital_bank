import 'package:dio/dio.dart' show DioException;
import '../../core/network/api_client.dart';
import '../../core/network/api_response.dart';
import '../models/loan.dart';

class LoanRepository {
  final ApiClient _api = ApiClient();

  Future<ApiResponse<List<Loan>>> getLoans() async {
    try {
      final response = await _api.get('/loans');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final list = data is List ? data : [];
          return list
              .map((e) => Loan.fromJson(
                  e is Map<String, dynamic> ? e : e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{}))
              .toList();
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<Loan>> getLoan(int id) async {
    try {
      final response = await _api.get('/loans/$id');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final map = data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
          return Loan.fromJson(map);
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> applyLoan(
    Map<String, dynamic> data,
  ) async {
    try {
      final response = await _api.post('/loans/apply', data: data);
      return ApiResponse.fromJson(
        response.data,
        (data) => data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{},
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> payLoan(int id, double amount) async {
    try {
      final response = await _api.post('/loans/$id/pay', data: {
        'amount': amount,
      });
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
