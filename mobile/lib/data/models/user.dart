class User {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? branch;
  final String? role;
  final String? status;
  final bool twoFactorEnabled;
  final bool isKycVerified;
  final String? lastLoginAt;
  final String? createdAt;

  const User({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.branch,
    this.role,
    this.status,
    this.twoFactorEnabled = false,
    this.isKycVerified = false,
    this.lastLoginAt,
    this.createdAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      phone: json['phone'] as String?,
      branch: json['branch'] as String?,
      role: json['role'] as String?,
      status: json['status'] as String?,
      twoFactorEnabled: json['two_factor_enabled'] as bool? ?? false,
      isKycVerified: json['is_kyc_verified'] as bool? ?? false,
      lastLoginAt: json['last_login_at'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'phone': phone,
      'branch': branch,
      'role': role,
      'status': status,
      'two_factor_enabled': twoFactorEnabled,
      'is_kyc_verified': isKycVerified,
      'last_login_at': lastLoginAt,
      'created_at': createdAt,
    };
  }

  User copyWith({
    int? id,
    String? name,
    String? email,
    String? phone,
    String? branch,
    String? role,
    String? status,
    bool? twoFactorEnabled,
    bool? isKycVerified,
    String? lastLoginAt,
    String? createdAt,
  }) {
    return User(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      phone: phone ?? this.phone,
      branch: branch ?? this.branch,
      role: role ?? this.role,
      status: status ?? this.status,
      twoFactorEnabled: twoFactorEnabled ?? this.twoFactorEnabled,
      isKycVerified: isKycVerified ?? this.isKycVerified,
      lastLoginAt: lastLoginAt ?? this.lastLoginAt,
      createdAt: createdAt ?? this.createdAt,
    );
  }
}
