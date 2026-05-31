class PendingTransfer {
  final int id;
  final String referenceNumber;
  final int senderAccountId;
  final int receiverAccountId;
  final int senderUserId;
  final int receiverUserId;
  final double amount;
  final String currency;
  final double? exchangeRate;
  final String? description;
  final String status;
  final String? expiresAt;
  final String? senderName;
  final String? receiverName;

  const PendingTransfer({
    required this.id,
    required this.referenceNumber,
    required this.senderAccountId,
    required this.receiverAccountId,
    required this.senderUserId,
    required this.receiverUserId,
    required this.amount,
    required this.currency,
    this.exchangeRate,
    this.description,
    required this.status,
    this.expiresAt,
    this.senderName,
    this.receiverName,
  });

  factory PendingTransfer.fromJson(Map<String, dynamic> json) {
    return PendingTransfer(
      id: json['id'] as int,
      referenceNumber: json['reference_number'] as String? ?? '',
      senderAccountId: json['sender_account_id'] as int? ?? 0,
      receiverAccountId: json['receiver_account_id'] as int? ?? 0,
      senderUserId: json['sender_user_id'] as int? ?? 0,
      receiverUserId: json['receiver_user_id'] as int? ?? 0,
      amount: (json['amount'] as num).toDouble(),
      currency: json['currency'] as String? ?? 'USD',
      exchangeRate: json['exchange_rate'] != null
          ? (json['exchange_rate'] as num).toDouble()
          : null,
      description: json['description'] as String?,
      status: json['status'] as String,
      expiresAt: json['expires_at'] as String?,
      senderName: json['sender_name'] as String?,
      receiverName: json['receiver_name'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'reference_number': referenceNumber,
      'sender_account_id': senderAccountId,
      'receiver_account_id': receiverAccountId,
      'sender_user_id': senderUserId,
      'receiver_user_id': receiverUserId,
      'amount': amount,
      'currency': currency,
      'exchange_rate': exchangeRate,
      'description': description,
      'status': status,
      'expires_at': expiresAt,
      'sender_name': senderName,
      'receiver_name': receiverName,
    };
  }
}
