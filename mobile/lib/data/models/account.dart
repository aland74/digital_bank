class Account {
  final int id;
  final int userId;
  final String accountNumber;
  final String accountName;
  final String accountType;
  final String currency;
  final double balance;
  final double availableBalance;
  final String status;
  final bool isPrimary;

  const Account({
    required this.id,
    required this.userId,
    required this.accountNumber,
    required this.accountName,
    required this.accountType,
    required this.currency,
    required this.balance,
    required this.availableBalance,
    required this.status,
    required this.isPrimary,
  });

  factory Account.fromJson(Map<String, dynamic> json) {
    return Account(
      id: json['id'] as int,
      userId: json['user_id'] as int? ?? 0,
      accountNumber: json['account_number'] as String,
      accountName: json['account_name'] as String,
      accountType: json['account_type'] as String,
      currency: json['currency'] as String,
      balance: (json['balance'] as num).toDouble(),
      availableBalance: (json['available_balance'] as num).toDouble(),
      status: json['status'] as String,
      isPrimary: json['is_primary'] as bool? ?? false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'account_number': accountNumber,
      'account_name': accountName,
      'account_type': accountType,
      'currency': currency,
      'balance': balance,
      'available_balance': availableBalance,
      'status': status,
      'is_primary': isPrimary,
    };
  }
}
