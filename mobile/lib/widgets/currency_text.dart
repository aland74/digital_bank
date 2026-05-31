import 'package:flutter/material.dart';
import '../core/utils/formatters.dart';

/// Formatted currency display with symbol.
class CurrencyText extends StatelessWidget {
  final double amount;
  final String currency;
  final TextStyle? style;
  final bool showSign;
  final bool compact;

  const CurrencyText({
    super.key,
    required this.amount,
    required this.currency,
    this.style,
    this.showSign = false,
    this.compact = false,
  });

  @override
  Widget build(BuildContext context) {
    final defaultStyle = Theme.of(context).textTheme.bodyLarge;
    final effectiveStyle = style ?? defaultStyle;

    String formatted;
    if (compact) {
      formatted = Formatters.compact(amount);
      switch (currency.toUpperCase()) {
        case 'IQD':
          formatted = '$formatted د.ع';
          break;
        case 'USD':
        default:
          formatted = '\$$formatted';
      }
    } else {
      formatted = Formatters.currency(amount, currency);
    }

    if (showSign && amount > 0) {
      formatted = '+$formatted';
    }

    return Text(formatted, style: effectiveStyle);
  }
}
