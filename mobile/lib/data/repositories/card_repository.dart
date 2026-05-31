import 'package:dio/dio.dart' show DioException;
import '../../core/network/api_client.dart';
import '../../core/network/api_response.dart';
import '../models/card.dart';

class CardRepository {
  final ApiClient _api = ApiClient();

  Future<ApiResponse<List<BankCard>>> getCards() async {
    try {
      final response = await _api.get('/cards');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final list = data is List ? data : [];
          return list
              .map((e) => BankCard.fromJson(
                  e is Map<String, dynamic> ? e : e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{}))
              .toList();
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<BankCard>> getCard(int id) async {
    try {
      final response = await _api.get('/cards/$id');
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final map = data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
          return BankCard.fromJson(map);
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<BankCard>> createCard(Map<String, dynamic> data) async {
    try {
      final response = await _api.post('/cards', data: data);
      return ApiResponse.fromJson(
        response.data,
        (data) {
          final map = data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
          return BankCard.fromJson(map);
        },
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> freezeCard(int id) async {
    try {
      final response = await _api.post('/cards/$id/freeze');
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> unfreezeCard(int id) async {
    try {
      final response = await _api.post('/cards/$id/unfreeze');
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> revealCard(
    int id,
    String pin,
  ) async {
    try {
      final response = await _api.post('/cards/$id/reveal', data: {'pin': pin});
      return ApiResponse.fromJson(
        response.data,
        (data) => data is Map<String, dynamic> ? data : data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{},
      );
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> updateLimits(
    int id,
    double daily,
    double monthly,
  ) async {
    try {
      final response = await _api.put('/cards/$id/limits', data: {
        'daily_limit': daily,
        'monthly_limit': monthly,
      });
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> toggleContactless(int id) async {
    try {
      final response = await _api.post('/cards/$id/toggle-contactless');
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> toggleOnline(int id) async {
    try {
      final response = await _api.post('/cards/$id/toggle-online');
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> toggleInternational(int id) async {
    try {
      final response = await _api.post('/cards/$id/toggle-international');
      return ApiResponse.fromJson(response.data, null);
    } on Exception catch (e) {
      return ApiResponse(success: false, message: _errorMessage(e));
    }
  }

  Future<ApiResponse<void>> requestPinChange(
    int id,
    String reason,
    String description,
  ) async {
    try {
      final response = await _api.post('/cards/$id/request-pin-change', data: {
        'reason': reason,
        'description': description,
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
