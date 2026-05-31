class Beneficiary {
  final int id;
  final String name;
  final String? nickname;
  final String? bankName;
  final String accountNumber;
  final String type;
  final String currency;
  final bool isFavorite;
  final bool isVerified;

  const Beneficiary({
    required this.id,
    required this.name,
    this.nickname,
    this.bankName,
    required this.accountNumber,
    required this.type,
    required this.currency,
    this.isFavorite = false,
    this.isVerified = false,
  });

  factory Beneficiary.fromJson(Map<String, dynamic> json) {
    return Beneficiary(
      id: json['id'] as int,
      name: json['name'] as String,
      nickname: json['nickname'] as String?,
      bankName: json['bank_name'] as String?,
      accountNumber: json['account_number'] as String,
      type: json['type'] as String,
      currency: json['currency'] as String,
      isFavorite: json['is_favorite'] as bool? ?? false,
      isVerified: json['is_verified'] as bool? ?? false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'nickname': nickname,
      'bank_name': bankName,
      'account_number': accountNumber,
      'type': type,
      'currency': currency,
      'is_favorite': isFavorite,
      'is_verified': isVerified,
    };
  }

  String get displayName => nickname ?? name;
}
