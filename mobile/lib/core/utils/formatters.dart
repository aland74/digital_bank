import 'package:intl/intl.dart';

class Formatters {
  /// Format a double as USD: $1,234.56
  static String currencyUSD(double amount) {
    final formatter = NumberFormat.currency(
      locale: 'en_US',
      symbol: '\$',
      decimalDigits: 2,
    );
    return formatter.format(amount);
  }

  /// Format a double as IQD: ١٬٢٣٤ د.ع
  static String currencyIQD(double amount) {
    final formatter = NumberFormat.currency(
      locale: 'ar_IQ',
      symbol: 'د.ع',
      decimalDigits: 0,
    );
    return formatter.format(amount);
  }

  /// Format currency by code. Defaults to USD.
  static String currency(double amount, String currencyCode) {
    switch (currencyCode.toUpperCase()) {
      case 'IQD':
        return currencyIQD(amount);
      case 'USD':
      default:
        return currencyUSD(amount);
    }
  }

  /// Format date as "May 22, 2026"
  static String date(DateTime date) {
    return DateFormat.yMMMMd('en_US').format(date);
  }

  /// Format date + time as "May 22, 2026 3:45 PM"
  static String dateTime(DateTime date) {
    return DateFormat.yMMMMd('en_US').add_jm().format(date);
  }

  /// Format as short date: "22 May 2026"
  static String dateShort(DateTime date) {
    return DateFormat.d('en_US').add_MMM().add_y().format(date);
  }

  /// Relative time: "2 hours ago", "Yesterday", etc.
  static String relativeTime(DateTime date) {
    final now = DateTime.now();
    final diff = now.difference(date);

    if (diff.inSeconds < 60) return 'Just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    if (diff.inDays == 1) return 'Yesterday';
    if (diff.inDays < 7) return '${diff.inDays}d ago';
    if (diff.inDays < 30) return '${(diff.inDays / 7).floor()}w ago';
    return dateShort(date);
  }

  /// Mask card number: **** **** **** 1234
  static String maskCardNumber(String cardNumber) {
    final cleaned = cardNumber.replaceAll(RegExp(r'\s+'), '');
    if (cleaned.length < 4) return cardNumber;
    final last4 = cleaned.substring(cleaned.length - 4);
    return '\u2022\u2022\u2022\u2022 \u2022\u2022\u2022\u2022 \u2022\u2022\u2022\u2022 $last4';
  }

  /// Format phone number: +964 750 123 4567
  static String phone(String phone) {
    final cleaned = phone.replaceAll(RegExp(r'[^\d+]'), '');
    if (cleaned.startsWith('+964') && cleaned.length == 13) {
      return '+964 ${cleaned.substring(4, 7)} ${cleaned.substring(7, 10)} ${cleaned.substring(10)}';
    }
    return phone;
  }

  /// Compact number: 1.2K, 3.5M
  static String compact(double number) {
    if (number >= 1000000) {
      return '${(number / 1000000).toStringAsFixed(1)}M';
    }
    if (number >= 1000) {
      return '${(number / 1000).toStringAsFixed(1)}K';
    }
    return number.toStringAsFixed(0);
  }
}
