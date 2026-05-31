import 'package:dio/dio.dart' show DioException;
import '../../core/network/api_client.dart';
import '../../core/network/api_response.dart';
import '../models/account.dart';

class AccountRepository {
  final ApiClient _api = ApiClient();

  Future<ApiResponse<List<Account>>> getAccounts() async {
    try {
      final response = await _api.get('/accounts');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final list = data is List ? data : [];
          return list
              .map((e) => Account.fromJson(
                  e is Map<String, dynamic> ? e : e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{}))
              .toList();
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<Account>> getAccount(int id) async {
    try {
      final response = await _api.get('/accounts/$id');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final map = data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
          return Account.fromJson(map);
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
