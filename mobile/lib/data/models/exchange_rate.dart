class ExchangeRate {
  final double rate;
  final double inverseRate;
  final String fromCurrency;
  final String toCurrency;
  final String? lastUpdated;

  const ExchangeRate({
    required this.rate,
    required this.inverseRate,
    required this.fromCurrency,
    required this.toCurrency,
    this.lastUpdated,
  });

  factory ExchangeRate.fromJson(Map<String, dynamic> json) {
    return ExchangeRate(
      rate: (json['rate'] as num).toDouble(),
      inverseRate: (json['inverse_rate'] as num).toDouble(),
      fromCurrency: json['from_currency'] as String,
      toCurrency: json['to_currency'] as String,
      lastUpdated: json['last_updated'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'rate': rate,
      'inverse_rate': inverseRate,
      'from_currency': fromCurrency,
      'to_currency': toCurrency,
      'last_updated': lastUpdated,
    };
  }

  /// Convert an amount from source to target currency.
  double convert(double amount) => amount * rate;
}
