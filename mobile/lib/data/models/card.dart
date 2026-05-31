class BankCard {
  final int id;
  final int accountId;
  final int userId;
  final String cardType;
  final String cardBrand;
  final String last4;
  final String cardholderName;
  final String expiry;
  final String status;
  final double dailyLimit;
  final double monthlyLimit;
  final bool isContactless;
  final bool isOnlineEnabled;
  final bool isInternationalEnabled;
  final String? accountNumber;

  const BankCard({
    required this.id,
    required this.accountId,
    required this.userId,
    required this.cardType,
    required this.cardBrand,
    required this.last4,
    required this.cardholderName,
    required this.expiry,
    required this.status,
    required this.dailyLimit,
    required this.monthlyLimit,
    required this.isContactless,
    required this.isOnlineEnabled,
    required this.isInternationalEnabled,
    this.accountNumber,
  });

  factory BankCard.fromJson(Map<String, dynamic> json) {
    return BankCard(
      id: json['id'] as int,
      accountId: json['account_id'] as int? ?? 0,
      userId: json['user_id'] as int? ?? 0,
      cardType: json['card_type'] as String,
      cardBrand: json['card_brand'] as String,
      last4: json['last4'] as String,
      cardholderName: json['cardholder_name'] ?? (json['cardholder'] as String? ?? 'N/A'),
      expiry: json['expiry'] as String,
      status: json['status'] as String,
      dailyLimit: (json['daily_limit'] as num?)?.toDouble() ?? 0,
      monthlyLimit: (json['monthly_limit'] as num?)?.toDouble() ?? 0,
      isContactless: json['is_contactless'] as bool? ?? false,
      isOnlineEnabled: json['is_online_enabled'] as bool? ?? false,
      isInternationalEnabled: json['is_international_enabled'] as bool? ?? false,
      accountNumber: json['account_number'] as String? ?? (json['account'] as String?),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'account_id': accountId,
      'user_id': userId,
      'card_type': cardType,
      'card_brand': cardBrand,
      'last4': last4,
      'cardholder_name': cardholderName,
      'expiry': expiry,
      'status': status,
      'daily_limit': dailyLimit,
      'monthly_limit': monthlyLimit,
      'is_contactless': isContactless,
      'is_online_enabled': isOnlineEnabled,
      'is_international_enabled': isInternationalEnabled,
      'account_number': accountNumber,
    };
  }

  bool get isFrozen => status == 'frozen';
  bool get isActive => status == 'active';
  bool get isPendingApproval => status == 'pending_approval';
  bool get isRejected => status == 'rejected';
}
