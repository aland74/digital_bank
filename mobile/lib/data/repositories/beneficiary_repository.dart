import 'package:dio/dio.dart' show DioException;
import '../../core/network/api_client.dart';
import '../../core/network/api_response.dart';
import '../models/beneficiary.dart';

class BeneficiaryRepository {
  final ApiClient _api = ApiClient();

  Future<ApiResponse<List<Beneficiary>>> getBeneficiaries() async {
    try {
      final response = await _api.get('/beneficiaries');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final list = data is List ? data : [];
          return list
              .map((e) => Beneficiary.fromJson(
                  e is Map<String, dynamic> ? e : e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{}))
              .toList();
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<Beneficiary>> addBeneficiary(
    Map<String, dynamic> data,
  ) async {
    try {
      final response = await _api.post('/beneficiaries', data: data);
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final map = data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
          return Beneficiary.fromJson(map);
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<Beneficiary>> updateBeneficiary(
    int id,
    Map<String, dynamic> data,
  ) async {
    try {
      final response = await _api.put('/beneficiaries/$id', data: data);
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final map = data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
          return Beneficiary.fromJson(map);
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> deleteBeneficiary(int id) async {
    try {
      final response = await _api.delete('/beneficiaries/$id');
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> toggleFavorite(int id) async {
    try {
      final response = await _api.post('/beneficiaries/$id/toggle-favorite');
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
