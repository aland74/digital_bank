import 'package:dio/dio.dart' show DioException;
import '../../core/network/api_client.dart';
import '../../core/network/api_response.dart';
import '../models/pending_transfer.dart';
import '../models/exchange_rate.dart';

class TransferRepository {
  final ApiClient _api = ApiClient();

  Future<ApiResponse<Map<String, dynamic>>> getPendingTransfers() async {
    try {
      final response = await _api.get('/transfers/pending');
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

  Future<ApiResponse<Map<String, dynamic>>> createTransfer(
    Map<String, dynamic> data,
  ) async {
    try {
      final response = await _api.post('/transfers', data: data);
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

  Future<ApiResponse<void>> acceptTransfer(int id) async {
    try {
      final response = await _api.post('/transfers/$id/accept');
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> declineTransfer(int id) async {
    try {
      final response = await _api.post('/transfers/$id/decline');
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> cancelTransfer(int id) async {
    try {
      final response = await _api.post('/transfers/$id/cancel');
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> convertCurrency(
    String from,
    double amount,
  ) async {
    try {
      final response = await _api.get(
        '/transfers/convert',
        queryParameters: {'from': from, 'amount': amount},
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

  Future<ApiResponse<ExchangeRate>> getExchangeRate() async {
    try {
      final response = await _api.get('/transfers/exchange-rate');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final map = data is Map<String, dynamic>
              ? data
              : data is Map
                  ? Map<String, dynamic>.from(data)
                  : <String, dynamic>{};
          return ExchangeRate.fromJson(map);
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
