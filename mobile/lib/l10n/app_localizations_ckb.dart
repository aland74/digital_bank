// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Central Kurdish (`ckb`).
class AppLocalizationsCkb extends AppLocalizations {
  AppLocalizationsCkb([String locale = 'ckb']) : super(locale);

  @override
  String get appTitle => 'بانکی دابەشکراو';

  @override
  String get appTagline => 'بانکاری دیجیتاڵی پریمیەم';

  @override
  String get loginTitle => 'بەخێربێیتەوە';

  @override
  String get loginSubtitle => 'بچۆ ژوورەوە بۆ هەژمارەکەت';

  @override
  String get loginEmail => 'ئیمەیڵ';

  @override
  String get loginPassword => 'وشەی نهێنی';

  @override
  String get loginButton => 'چوونە ژوورەوە';

  @override
  String get loginForgotPassword => 'وشەی نهێنیت لەبیرکردووە؟';

  @override
  String get loginNoAccount => 'هەژمار نەتەوەیە؟';

  @override
  String get loginRegister => 'دروستکردنی هەژمار';

  @override
  String get loginBiometric => 'چوونە ژوورەوە بایۆمێتریک';

  @override
  String get registerTitle => 'دروستکردنی هەژمار';

  @override
  String get registerSubtitle => 'ئەمڕۆ ببە بەندێکی بانکی دابەشکراو';

  @override
  String get registerName => 'ناوی تەواو';

  @override
  String get registerEmail => 'ئیمەیڵ';

  @override
  String get registerPhone => 'ژمارەی مۆبایل';

  @override
  String get registerBranch => 'لق';

  @override
  String get registerPassword => 'وشەی نهێنی';

  @override
  String get registerConfirmPassword => 'دووپاتکردنەوەی وشەی نهێنی';

  @override
  String get registerButton => 'دروستکردنی هەژمار';

  @override
  String get registerHasAccount => 'پێشتر هەژمارتەوەیە؟';

  @override
  String get registerLogin => 'چوونە ژوورەوە';

  @override
  String get otpTitle => 'کۆدی پشتڕاستکردنەوە';

  @override
  String get otpSubtitle => 'کۆدی ٦ ژمارەیی بنووسە کە ناردراوە بۆ ئیمەیڵەکەت';

  @override
  String get otpButton => 'پشتڕاستکردنەوە';

  @override
  String get otpResend => 'ناردنەوەی کۆد';

  @override
  String otpResendIn(Object seconds) {
    return 'ناردنەوە لە $seconds چرکە';
  }

  @override
  String get dashboardTitle => 'داشبۆرد';

  @override
  String get dashboardTotalBalance => 'کۆی باڵانس';

  @override
  String get dashboardAccounts => 'هەژمارەکانم';

  @override
  String get dashboardRecentTransactions => 'مەوەلەکانی ئەم دواییانە';

  @override
  String get dashboardQuickActions => 'کردارە خێراکان';

  @override
  String get dashboardViewAll => 'پیشاندانی هەموو';

  @override
  String get dashboardNoAccounts => 'هیچ هەژمارێک نییە';

  @override
  String get dashboardNoTransactions => 'هیچ مەوەلەیەکی ئەم دواییانە نییە';

  @override
  String dashboardHello(String name) {
    return 'سڵاو، $name';
  }

  @override
  String get dashboardGoodMorning => 'بەیانیت باش';

  @override
  String get dashboardGoodAfternoon => 'ڕۆژت باش';

  @override
  String get dashboardGoodEvening => 'ئێوارەت باش';

  @override
  String get dashboardWelcomeBack => 'بەخێربێیتەوە';

  @override
  String get dashboardUsdBalance => 'باڵانسی دۆلار';

  @override
  String get dashboardIqdBalance => 'باڵانسی دینار';

  @override
  String get dashboardSendMoney => 'ناردنی پارە';

  @override
  String get dashboardConvert => 'گۆڕین';

  @override
  String get dashboardLoading => 'داشبۆرد لۆد دەکرێت...';

  @override
  String get navHome => 'سەرەتا';

  @override
  String get navCards => 'کارتەکان';

  @override
  String get navTransfer => 'گواستنەوە';

  @override
  String get navLoans => 'قەرزەکان';

  @override
  String get navProfile => 'پڕۆفایل';

  @override
  String get transferTitle => 'گواستنەوە';

  @override
  String get transferNew => 'گواستنەوەی نوێ';

  @override
  String get transferPending => 'گواستنەوە چاوەڕوانەکان';

  @override
  String get transferFrom => 'لە هەژمار';

  @override
  String get transferTo => 'بۆ هەژمار';

  @override
  String get transferAmount => 'بڕ';

  @override
  String get transferDescription => 'وەسف';

  @override
  String get transferSend => 'ناردنی پارە';

  @override
  String get transferConfirm => 'پشتڕاستکردنەوەی گواستنەوە';

  @override
  String get transferSuccess => 'گواستنەوەکە سەرکەوتوو بوو';

  @override
  String get transferCurrencyExchange => 'گۆڕینی دراو';

  @override
  String get cardsTitle => 'کارتەکانم';

  @override
  String get cardsFreeze => 'ستونکردنی کارت';

  @override
  String get cardsUnfreeze => 'لە ستون دەرکردنی کارت';

  @override
  String get cardsReveal => 'پیشاندانی وردەکاری';

  @override
  String get cardsLimits => 'سنوری خەرجکردن';

  @override
  String get cardsContactless => 'بێ پەیوەندی';

  @override
  String get cardsOnline => 'پارەدانی ئۆنلاین';

  @override
  String get cardsInternational => 'نێودەوڵەتی';

  @override
  String get cardsRequestPin => 'داواکردنی گۆڕینی PIN';

  @override
  String get loansTitle => 'قەرزەکان';

  @override
  String get loansApply => 'دامەزراندنی قەرز';

  @override
  String get loansActive => 'قەرزە چالاکەکان';

  @override
  String get loansHistory => 'مێژووی قەرز';

  @override
  String get loansRepay => 'پارەدان';

  @override
  String get loansRemaining => 'باڵانسی ماوە';

  @override
  String get loansMonthlyPayment => 'پارەدانی مانگانە';

  @override
  String get loansProgress => 'پێشکەوتنی قەرز';

  @override
  String get notificationsTitle => 'ئاگادارییەکان';

  @override
  String get notificationsMarkAllRead => 'نیشانکردنی هەموو وەک خوێندراوە';

  @override
  String get notificationsEmpty => 'هیچ ئاگادارییەک نییە';

  @override
  String notificationsUnread(Object count) {
    return '$count نەخوێندراوە';
  }

  @override
  String get profileTitle => 'پڕۆفایل';

  @override
  String get profilePersonalInfo => 'زانیاری کەسی';

  @override
  String get profileSecurity => 'ئاسایش';

  @override
  String get profileChangePassword => 'گۆڕینی وشەی نهێنی';

  @override
  String get profileTwoFactor => 'پشتڕاستکردنەوەی دوو قۆناغی';

  @override
  String get profileKyc => 'پشتڕاستکردنەوەی KYC';

  @override
  String get profileSecurityLogs => 'تۆماری ئاسایش';

  @override
  String get profileSupport => 'یارمەتی و پشتگیری';

  @override
  String get profileLogout => 'چوونە دەرەوە';

  @override
  String get profileLanguage => 'زمان';

  @override
  String get profileTheme => 'ڕووکار';

  @override
  String get settingsTitle => 'ڕێکخستنەکان';

  @override
  String get settingsDarkMode => 'دۆخی تاریک';

  @override
  String get settingsLanguage => 'زمان';

  @override
  String get settingsAbout => 'دەربارە';

  @override
  String get settingsVersion => 'وەشان';

  @override
  String get generalSave => 'پاشەکەوتکردن';

  @override
  String get generalCancel => 'هەڵوەشاندنەوە';

  @override
  String get generalConfirm => 'پشتڕاستکردنەوە';

  @override
  String get generalDelete => 'سڕینەوە';

  @override
  String get generalEdit => 'دەستکاریکردن';

  @override
  String get generalRetry => 'دووبارەکردنەوە';

  @override
  String get generalLoading => 'لۆدکردن...';

  @override
  String get generalError => 'هەڵەیەک ڕوویدا';

  @override
  String get generalSuccess => 'سەرکەوتوو بوو';

  @override
  String get generalNoData => 'هیچ زانیارییەک بەردەست نییە';

  @override
  String get generalSearch => 'گەڕان';

  @override
  String get generalFilter => 'پاڵاوتن';

  @override
  String get generalCurrency => 'دراو';

  @override
  String get generalAmount => 'بڕ';

  @override
  String get generalDate => 'بەروار';

  @override
  String get generalStatus => 'دۆخ';

  @override
  String get generalReference => 'ئاماژە';

  @override
  String get beneficiariesTitle => 'بەخشینکەرەکان';

  @override
  String get beneficiariesAdd => 'زیادکردنی بەخشینکەر';

  @override
  String get beneficiariesNoData => 'هیچ بەخشینکەرێک نییە';

  @override
  String get supportTitle => 'یارمەتی و پشتگیری';

  @override
  String get supportNewTicket => 'تیکێتی نوێ';

  @override
  String get supportMyTickets => 'تیکێتەکانم';

  @override
  String get supportSubject => 'بابەت';

  @override
  String get supportMessage => 'پەیام';

  @override
  String get supportCategory => 'پۆل';

  @override
  String get supportPriority => 'پێشەنگی';

  @override
  String get currencyUSD => 'دۆلاری ئەمریکی';

  @override
  String get currencyIQD => 'دیناری عێراقی';

  @override
  String get currencyExchange => 'نرخی گۆڕانکاری';

  @override
  String get statusActive => 'چالاک';

  @override
  String get statusInactive => 'ناچالاک';

  @override
  String get statusPending => 'چاوەڕوان';

  @override
  String get statusCompleted => 'تەواوبوو';

  @override
  String get statusFailed => 'شکست';

  @override
  String get statusCancelled => 'هەڵوەشێنرایەوە';

  @override
  String get statusFrozen => 'ستونکراو';

  @override
  String get statusApproved => 'پەسەندکراو';

  @override
  String get statusRejected => 'ڕەتکراوە';

  @override
  String get registerBranchRequired => 'تکایە لقێک هەڵبژێرە';

  @override
  String get registerPasswordMismatch => 'وشە نهێنییەکان وەک یەک نین';

  @override
  String get forgotPasswordTitle => 'وشەی نهێنیت لەبیرکردووە؟';

  @override
  String get forgotPasswordSubtitle =>
      'ناونیشانی ئیمەیڵەکەت بنووسە و ئێمە کۆدێکی پارێزراوت بۆ دەنێرین بۆ گۆڕینی وشەی نهێنی.';

  @override
  String get forgotPasswordSendCode => 'ناردنی کۆدی گۆڕین';

  @override
  String get forgotPasswordBackToLogin => 'گەڕانەوە بۆ چوونە ژوورەوە';

  @override
  String get resetPasswordTitle => 'وشەی نهێنی بگۆڕە';

  @override
  String get resetPasswordSubtitle =>
      'کۆدە ٦ ژمارەییەکە و وشەی نهێنی نوێ بنووسە.';

  @override
  String get resetPasswordSuccess =>
      'وشەی نهێنی بە سەرکەوتوویی گۆڕدرا! بە وشەی نهێنی نوێوە بچۆ ژوورەوە.';

  @override
  String get resetPasswordNew => 'وشەی نهێنی نوێ';

  @override
  String get resetPasswordConfirm => 'دووپاتکردنەوەی وشەی نهێنی نوێ';

  @override
  String get resetPasswordButton => 'گۆڕینی وشەی نهێنی';

  @override
  String get resetPasswordResend => 'ناردنەوەی کۆدی گۆڕین';

  @override
  String get resetPasswordHint => 'لانی کەم ٨ پیت، پیتی گەورە و بچووک و ژمارە';

  @override
  String get resetPasswordConfirmHint => 'وشەی نهێنیت دووپات بکەرەوە';

  @override
  String get resetCodeLabel => 'کۆدی گۆڕین (OTP)';

  @override
  String get resetCodeRequired => 'تکایە کۆدی گۆڕین بنووسە';

  @override
  String get resetCodeLengthError => 'کۆدەکە دەبێت ٦ ژمارە بێت';

  @override
  String get resetPasswordRequired => 'تکایە وشەی نهێنی نوێ بنووسە';

  @override
  String get resetPasswordMinLength => 'پێویستە وشەی نهێنی لانی کەم ٨ پیت بێت';

  @override
  String get resetPasswordUppercase => 'پێویستە پیتی گەورە تێدابێت';

  @override
  String get resetPasswordLowercase => 'پێویستە پیتی بچووک تێدابێت';

  @override
  String get resetPasswordNumber => 'پێویستە ژمارە تێدابێت';

  @override
  String get resetPasswordConfirmRequired =>
      'تکایە وشەی نهێنیەکەت دووپات بکەرەوە';

  @override
  String get resetPasswordMismatch => 'وشە نهێنییەکان وەک یەک نین';

  @override
  String get sandboxTitle => 'ژینگەی تاقیکاری گەشەپێدەر';

  @override
  String get sandboxOtpLabel => 'کۆدی OTP تۆ: ';

  @override
  String get otpTwoFactorTitle => 'پشتڕاستکردنەوەی دوو قۆناغی';

  @override
  String otpSentToEmail(String email) {
    return 'کۆدە ٦ ژمارەییەکە بنووسە کە نێردراوە بۆ\n$email';
  }

  @override
  String get otpResentSuccess =>
      'کۆدی پشتڕاستکردنەوە بە سەرکەوتوویی نێردرایەوە!';

  @override
  String get otpSecurityNote =>
      'هەرگیز کۆدی پشتڕاستکردنەوەکەت بە کەس مەدە، بە کارمەندانی بانکیشەوە.';

  @override
  String otpResendInSeconds(String seconds) {
    return 'ناردنەوەی کۆد لە $seconds چرکە';
  }
}
