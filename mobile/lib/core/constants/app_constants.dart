class AppConstants {
  // ── Loan types and interest rates ─────────────────────────────
  static const Map<String, double> loanTypes = {
    'personal': 8.5,
    'auto': 6.5,
    'mortgage': 5.0,
    'business': 9.0,
    'education': 4.5,
  };

  static const List<String> loanTypeLabels = [
    'Personal',
    'Auto',
    'Mortgage',
    'Business',
    'Education',
  ];

  // ── Card types ────────────────────────────────────────────────
  static const List<String> cardTypes = ['debit', 'credit', 'prepaid'];

  static const List<String> cardBrands = ['visa', 'mastercard'];

  // ── Transaction types ─────────────────────────────────────────
  static const String txnDeposit = 'deposit';
  static const String txnWithdrawal = 'withdrawal';
  static const String txnTransfer = 'transfer';
  static const String txnPayment = 'payment';
  static const String txnFee = 'fee';
  static const String txnInterest = 'interest';
  static const String txnRefund = 'refund';
  static const String txnCurrencyExchange = 'currency_exchange';

  // ── Notification types ────────────────────────────────────────
  static const String notifTransaction = 'transaction';
  static const String notifSecurity = 'security';
  static const String notifPromotion = 'promotion';
  static const String notifSystem = 'system';
  static const String notifTransfer = 'transfer';
  static const String notifCard = 'card';
  static const String notifLoan = 'loan';

  // ── Support ticket categories ─────────────────────────────────
  static const List<String> ticketCategories = [
    'general',
    'account',
    'card',
    'transfer',
    'loan',
    'security',
    'other',
  ];

  static const List<String> ticketPriorities = ['low', 'medium', 'high', 'urgent'];

  // ── Branches ──────────────────────────────────────────────────
  static const List<String> branches = [
    'Sulaimaniyah',
    'Duhok',
    'Hawler',
  ];

  /// Maps display names → database enum values
  static const Map<String, String> branchCodeMap = {
    'Sulaimaniyah': 'sulaimaniyah',
    'Duhok': 'duhok',
    'Hawler': 'erbil',
  };

  // ── Pagination ────────────────────────────────────────────────
  static const int defaultPageSize = 20;

  // ── Currencies ────────────────────────────────────────────────
  static const String usd = 'USD';
  static const String iqd = 'IQD';

  static const List<String> currencies = [usd, iqd];

  // ── KYC document types ────────────────────────────────────────
  static const List<String> kycDocumentTypes = [
    'national_id',
    'passport',
    'residence_card',
  ];
}
