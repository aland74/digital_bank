class LoanRepayment {
  final int id;
  final double amount;
  final String status;
  final String? paidAt;

  const LoanRepayment({
    required this.id,
    required this.amount,
    required this.status,
    this.paidAt,
  });

  factory LoanRepayment.fromJson(Map<String, dynamic> json) {
    return LoanRepayment(
      id: json['id'] as int,
      amount: (json['amount'] as num).toDouble(),
      status: json['status'] as String,
      paidAt: json['paid_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'amount': amount,
      'status': status,
      'paid_at': paidAt,
    };
  }
}

class Loan {
  final int id;
  final int userId;
  final int accountId;
  final String loanNumber;
  final String loanType;
  final double amount;
  final double interestRate;
  final int termMonths;
  final double monthlyPayment;
  final double totalInterest;
  final double totalPaid;
  final double remainingBalance;
  final String status;
  final String? purpose;
  final double? progressPercentage;
  final List<LoanRepayment>? repayments;
  final String? appliedAt;
  final String? maturityDate;
  final String currency;

  const Loan({
    required this.id,
    required this.userId,
    required this.accountId,
    required this.loanNumber,
    required this.loanType,
    required this.amount,
    required this.interestRate,
    required this.termMonths,
    required this.monthlyPayment,
    required this.totalInterest,
    required this.totalPaid,
    required this.remainingBalance,
    required this.status,
    this.purpose,
    this.progressPercentage,
    this.repayments,
    this.appliedAt,
    this.maturityDate,
    this.currency = 'USD',
  });

  factory Loan.fromJson(Map<String, dynamic> json) {
    // Safely retrieve currency from the nested account object if present
    final String currencyCode = json['account']?['currency'] as String? ?? 'USD';

    return Loan(
      id: json['id'] as int,
      userId: json['user_id'] as int? ?? 0,
      accountId: json['account_id'] as int? ?? 0,
      loanNumber: json['loan_number'] as String,
      loanType: json['loan_type'] as String,
      amount: (json['amount'] as num).toDouble(),
      interestRate: (json['interest_rate'] as num).toDouble(),
      termMonths: json['term_months'] as int,
      monthlyPayment: (json['monthly_payment'] as num).toDouble(),
      totalInterest: (json['total_interest'] as num).toDouble(),
      totalPaid: (json['total_paid'] as num).toDouble(),
      remainingBalance: (json['remaining_balance'] as num).toDouble(),
      status: json['status'] as String,
      purpose: json['purpose'] as String?,
      progressPercentage: json['progress_percentage'] != null
          ? (json['progress_percentage'] as num).toDouble()
          : null,
      repayments: json['repayments'] != null
          ? (json['repayments'] as List)
              .map((e) => LoanRepayment.fromJson(
                  e is Map<String, dynamic> ? e : e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{}))
              .toList()
          : null,
      appliedAt: json['applied_at'] as String?,
      maturityDate: json['maturity_date'] as String?,
      currency: currencyCode,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'account_id': accountId,
      'loan_number': loanNumber,
      'loan_type': loanType,
      'amount': amount,
      'interest_rate': interestRate,
      'term_months': termMonths,
      'monthly_payment': monthlyPayment,
      'total_interest': totalInterest,
      'total_paid': totalPaid,
      'remaining_balance': remainingBalance,
      'status': status,
      'purpose': purpose,
      'progress_percentage': progressPercentage,
      'repayments': repayments?.map((e) => e.toJson()).toList(),
      'applied_at': appliedAt,
      'maturity_date': maturityDate,
      'currency': currency,
    };
  }

  double get effectiveProgress =>
      progressPercentage ?? (amount > 0 ? (totalPaid / amount) * 100 : 0);
}
