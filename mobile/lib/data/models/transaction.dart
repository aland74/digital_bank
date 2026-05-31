class Transaction {
  final int id;
  final int accountId;
  final String referenceNumber;
  final String type;
  final double amount;
  final String currency;
  final double? balanceBefore;
  final double? balanceAfter;
  final String status;
  final String? description;
  final String? recipientName;
  final String? channel;
  final String? completedAt;
  final String? createdAt;

  const Transaction({
    required this.id,
    required this.accountId,
    required this.referenceNumber,
    required this.type,
    required this.amount,
    required this.currency,
    this.balanceBefore,
    this.balanceAfter,
    required this.status,
    this.description,
    this.recipientName,
    this.channel,
    this.completedAt,
    this.createdAt,
  });

  factory Transaction.fromJson(Map<String, dynamic> json) {
    return Transaction(
      id: json['id'] as int,
      accountId: json['account_id'] as int,
      referenceNumber: json['reference_number'] as String,
      type: json['type'] as String,
      amount: (json['amount'] as num).toDouble(),
      currency: json['currency'] as String,
      balanceBefore: json['balance_before'] != null
          ? (json['balance_before'] as num).toDouble()
          : null,
      balanceAfter: json['balance_after'] != null
          ? (json['balance_after'] as num).toDouble()
          : null,
      status: json['status'] as String,
      description: json['description'] as String?,
      recipientName: json['recipient_name'] as String?,
      channel: json['channel'] as String?,
      completedAt: json['completed_at'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'account_id': accountId,
      'reference_number': referenceNumber,
      'type': type,
      'amount': amount,
      'currency': currency,
      'balance_before': balanceBefore,
      'balance_after': balanceAfter,
      'status': status,
      'description': description,
      'recipient_name': recipientName,
      'channel': channel,
      'completed_at': completedAt,
      'created_at': createdAt,
    };
  }

  /// Whether this transaction is a credit (incoming money).
  bool get isCredit =>
      type == 'deposit' || type == 'refund' || type == 'interest';
}
