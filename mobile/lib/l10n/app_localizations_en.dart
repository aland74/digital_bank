// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appTitle => 'Distributed Bank';

  @override
  String get appTagline => 'Premium Digital Banking';

  @override
  String get loginTitle => 'Welcome Back';

  @override
  String get loginSubtitle => 'Sign in to your account';

  @override
  String get loginEmail => 'Email Address';

  @override
  String get loginPassword => 'Password';

  @override
  String get loginButton => 'Sign In';

  @override
  String get loginForgotPassword => 'Forgot Password?';

  @override
  String get loginNoAccount => 'Don\'t have an account?';

  @override
  String get loginRegister => 'Create Account';

  @override
  String get loginBiometric => 'Sign in with Biometrics';

  @override
  String get registerTitle => 'Create Account';

  @override
  String get registerSubtitle => 'Join Distributed Bank today';

  @override
  String get registerName => 'Full Name';

  @override
  String get registerEmail => 'Email Address';

  @override
  String get registerPhone => 'Phone Number';

  @override
  String get registerBranch => 'Branch';

  @override
  String get registerPassword => 'Password';

  @override
  String get registerConfirmPassword => 'Confirm Password';

  @override
  String get registerButton => 'Create Account';

  @override
  String get registerHasAccount => 'Already have an account?';

  @override
  String get registerLogin => 'Sign In';

  @override
  String get otpTitle => 'Verification Code';

  @override
  String get otpSubtitle => 'Enter the 6-digit code sent to your email';

  @override
  String get otpButton => 'Verify';

  @override
  String get otpResend => 'Resend Code';

  @override
  String otpResendIn(Object seconds) {
    return 'Resend in ${seconds}s';
  }

  @override
  String get dashboardTitle => 'Dashboard';

  @override
  String get dashboardTotalBalance => 'Total Balance';

  @override
  String get dashboardAccounts => 'My Accounts';

  @override
  String get dashboardRecentTransactions => 'Recent Transactions';

  @override
  String get dashboardQuickActions => 'Quick Actions';

  @override
  String get dashboardViewAll => 'View All';

  @override
  String get dashboardNoAccounts => 'No accounts yet';

  @override
  String get dashboardNoTransactions => 'No recent transactions';

  @override
  String get navHome => 'Home';

  @override
  String get navCards => 'Cards';

  @override
  String get navTransfer => 'Transfer';

  @override
  String get navLoans => 'Loans';

  @override
  String get navProfile => 'Profile';

  @override
  String get transferTitle => 'Transfer';

  @override
  String get transferNew => 'New Transfer';

  @override
  String get transferPending => 'Pending Transfers';

  @override
  String get transferFrom => 'From Account';

  @override
  String get transferTo => 'To Account';

  @override
  String get transferAmount => 'Amount';

  @override
  String get transferDescription => 'Description';

  @override
  String get transferSend => 'Send Money';

  @override
  String get transferConfirm => 'Confirm Transfer';

  @override
  String get transferSuccess => 'Transfer Successful';

  @override
  String get transferCurrencyExchange => 'Currency Exchange';

  @override
  String get cardsTitle => 'My Cards';

  @override
  String get cardsFreeze => 'Freeze Card';

  @override
  String get cardsUnfreeze => 'Unfreeze Card';

  @override
  String get cardsReveal => 'Reveal Details';

  @override
  String get cardsLimits => 'Spending Limits';

  @override
  String get cardsContactless => 'Contactless';

  @override
  String get cardsOnline => 'Online Payments';

  @override
  String get cardsInternational => 'International';

  @override
  String get cardsRequestPin => 'Request PIN Change';

  @override
  String get loansTitle => 'Loans';

  @override
  String get loansApply => 'Apply for Loan';

  @override
  String get loansActive => 'Active Loans';

  @override
  String get loansHistory => 'Loan History';

  @override
  String get loansRepay => 'Make Payment';

  @override
  String get loansRemaining => 'Remaining Balance';

  @override
  String get loansMonthlyPayment => 'Monthly Payment';

  @override
  String get loansProgress => 'Repayment Progress';

  @override
  String get notificationsTitle => 'Notifications';

  @override
  String get notificationsMarkAllRead => 'Mark All as Read';

  @override
  String get notificationsEmpty => 'No notifications';

  @override
  String notificationsUnread(Object count) {
    return '$count unread';
  }

  @override
  String get profileTitle => 'Profile';

  @override
  String get profilePersonalInfo => 'Personal Information';

  @override
  String get profileSecurity => 'Security';

  @override
  String get profileChangePassword => 'Change Password';

  @override
  String get profileTwoFactor => 'Two-Factor Authentication';

  @override
  String get profileKyc => 'KYC Verification';

  @override
  String get profileSecurityLogs => 'Security Logs';

  @override
  String get profileSupport => 'Help & Support';

  @override
  String get profileLogout => 'Logout';

  @override
  String get profileLanguage => 'Language';

  @override
  String get profileTheme => 'Theme';

  @override
  String get settingsTitle => 'Settings';

  @override
  String get settingsDarkMode => 'Dark Mode';

  @override
  String get settingsLanguage => 'Language';

  @override
  String get settingsAbout => 'About';

  @override
  String get settingsVersion => 'Version';

  @override
  String get generalSave => 'Save';

  @override
  String get generalCancel => 'Cancel';

  @override
  String get generalConfirm => 'Confirm';

  @override
  String get generalDelete => 'Delete';

  @override
  String get generalEdit => 'Edit';

  @override
  String get generalRetry => 'Retry';

  @override
  String get generalLoading => 'Loading...';

  @override
  String get generalError => 'Something went wrong';

  @override
  String get generalSuccess => 'Success';

  @override
  String get generalNoData => 'No data available';

  @override
  String get generalSearch => 'Search';

  @override
  String get generalFilter => 'Filter';

  @override
  String get generalCurrency => 'Currency';

  @override
  String get generalAmount => 'Amount';

  @override
  String get generalDate => 'Date';

  @override
  String get generalStatus => 'Status';

  @override
  String get generalReference => 'Reference';

  @override
  String get beneficiariesTitle => 'Beneficiaries';

  @override
  String get beneficiariesAdd => 'Add Beneficiary';

  @override
  String get beneficiariesNoData => 'No beneficiaries yet';

  @override
  String get supportTitle => 'Help & Support';

  @override
  String get supportNewTicket => 'New Ticket';

  @override
  String get supportMyTickets => 'My Tickets';

  @override
  String get supportSubject => 'Subject';

  @override
  String get supportMessage => 'Message';

  @override
  String get supportCategory => 'Category';

  @override
  String get supportPriority => 'Priority';

  @override
  String get currencyUSD => 'US Dollar';

  @override
  String get currencyIQD => 'Iraqi Dinar';

  @override
  String get currencyExchange => 'Exchange Rate';

  @override
  String get statusActive => 'Active';

  @override
  String get statusInactive => 'Inactive';

  @override
  String get statusPending => 'Pending';

  @override
  String get statusCompleted => 'Completed';

  @override
  String get statusFailed => 'Failed';

  @override
  String get statusCancelled => 'Cancelled';

  @override
  String get statusFrozen => 'Frozen';

  @override
  String get statusApproved => 'Approved';

  @override
  String get statusRejected => 'Rejected';
}
