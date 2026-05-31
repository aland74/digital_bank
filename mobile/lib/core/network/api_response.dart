class ApiResponse<T> {
  final bool success;
  final T? data;
  final String? message;
  final int? statusCode;
  final Map<String, List<String>>? errors;

  const ApiResponse({
    required this.success,
    this.data,
    this.message,
    this.statusCode,
    this.errors,
  });

  /// Parse a JSON response from the backend.
  ///
  /// The backend returns flat JSON like `{message, user, token}` or
  /// `{message, requires_otp, email}` — there is no standard `success` /
  /// `data` wrapper.  We therefore:
  ///   1. Determine success from the optional `success` key if present,
  ///      otherwise default to `true` (Dio already throws on HTTP errors).
  ///   2. Look for a `data` key first; if absent, pass the entire json map
  ///      to `fromData` so callers can pick what they need.
  factory ApiResponse.fromJson(
    dynamic jsonInput,
    T Function(dynamic)? fromData,
  ) {
    // Safely coerce jsonInput into a Map<String, dynamic>.
    Map<String, dynamic> json;
    if (jsonInput is List) {
      json = {'data': jsonInput};
    } else if (jsonInput is Map<String, dynamic>) {
      json = jsonInput;
    } else if (jsonInput is Map) {
      // Dio can return Map<dynamic, dynamic> in some edge cases
      json = Map<String, dynamic>.from(jsonInput);
    } else {
      json = <String, dynamic>{};
    }

    // Determine success – backend may or may not include a `success` key.
    final bool isSuccess = json['success'] as bool? ?? true;

    // Try explicit `data` wrapper first, then fall back to the full json.
    dynamic rawData = json['data'];
    if (rawData == null) {
      // Check for top-level list values (paginated endpoints like accounts).
      for (final entry in json.entries) {
        if (entry.value is List) {
          rawData = entry.value;
          break;
        }
      }
      rawData ??= json;
    }

    T? parsedData;
    if (fromData != null && rawData != null) {
      try {
        parsedData = fromData(rawData);
      } catch (_) {
        // If fromData fails (e.g. List cast to Map), try passing the whole json
        try {
          parsedData = fromData(json);
        } catch (_) {
          parsedData = null;
        }
      }
    } else if (rawData != null) {
      try {
        parsedData = rawData as T?;
      } catch (_) {
        parsedData = null;
      }
    }

    return ApiResponse<T>(
      success: isSuccess,
      data: parsedData,
      message: json['message'] as String?,
      statusCode: json['status_code'] as int?,
      errors: json['errors'] != null
          ? _parseErrors(json['errors'])
          : null,
    );
  }

  /// Safely parse Laravel validation errors.
  static Map<String, List<String>>? _parseErrors(dynamic errorsRaw) {
    try {
      if (errorsRaw is Map) {
        return Map<String, List<String>>.from(
          errorsRaw.map(
            (key, value) => MapEntry(
              key.toString(),
              value is List
                  ? List<String>.from(value.map((e) => e.toString()))
                  : <String>[value.toString()],
            ),
          ),
        );
      }
    } catch (_) {}
    return null;
  }

  /// Convenience for success responses where data is a List.
  factory ApiResponse.listFromJson(
    Map<String, dynamic> json,
    T Function(dynamic) fromData,
  ) {
    return ApiResponse<T>(
      success: json['success'] as bool? ?? false,
      data: json['data'] != null ? fromData(json['data']) : null,
      message: json['message'] as String?,
      statusCode: json['status_code'] as int?,
    );
  }

  String get errorMessage {
    if (errors != null && errors!.isNotEmpty) {
      return errors!.values.expand((e) => e).join('\n');
    }
    if (message != null && message!.isNotEmpty) return message!;
    return 'An unexpected error occurred';
  }
}
