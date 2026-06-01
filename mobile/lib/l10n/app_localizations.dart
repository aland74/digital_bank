import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_ckb.dart';
import 'app_localizations_en.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
    : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations? of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations);
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
        delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('ckb'),
    Locale('en'),
  ];

  /// No description provided for @appTitle.
  ///
  /// In en, this message translates to:
  /// **'Distributed Bank'**
  String get appTitle;

  /// No description provided for @appTagline.
  ///
  /// In en, this message translates to:
  /// **'Premium Digital Banking'**
  String get appTagline;

  /// No description provided for @loginTitle.
  ///
  /// In en, this message translates to:
  /// **'Welcome Back'**
  String get loginTitle;

  /// No description provided for @loginSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Sign in to your account'**
  String get loginSubtitle;

  /// No description provided for @loginEmail.
  ///
  /// In en, this message translates to:
  /// **'Email Address'**
  String get loginEmail;

  /// No description provided for @loginPassword.
  ///
  /// In en, this message translates to:
  /// **'Password'**
  String get loginPassword;

  /// No description provided for @loginButton.
  ///
  /// In en, this message translates to:
  /// **'Sign In'**
  String get loginButton;

  /// No description provided for @loginForgotPassword.
  ///
  /// In en, this message translates to:
  /// **'Forgot Password?'**
  String get loginForgotPassword;

  /// No description provided for @loginNoAccount.
  ///
  /// In en, this message translates to:
  /// **'Don\'t have an account?'**
  String get loginNoAccount;

  /// No description provided for @loginRegister.
  ///
  /// In en, this message translates to:
  /// **'Create Account'**
  String get loginRegister;

  /// No description provided for @loginBiometric.
  ///
  /// In en, this message translates to:
  /// **'Sign in with Biometrics'**
  String get loginBiometric;

  /// No description provided for @registerTitle.
  ///
  /// In en, this message translates to:
  /// **'Create Account'**
  String get registerTitle;

  /// No description provided for @registerSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Join Distributed Bank today'**
  String get registerSubtitle;

  /// No description provided for @registerName.
  ///
  /// In en, this message translates to:
  /// **'Full Name'**
  String get registerName;

  /// No description provided for @registerEmail.
  ///
  /// In en, this message translates to:
  /// **'Email Address'**
  String get registerEmail;

  /// No description provided for @registerPhone.
  ///
  /// In en, this message translates to:
  /// **'Phone Number'**
  String get registerPhone;

  /// No description provided for @registerBranch.
  ///
  /// In en, this message translates to:
  /// **'Branch'**
  String get registerBranch;

  /// No description provided for @registerPassword.
  ///
  /// In en, this message translates to:
  /// **'Password'**
  String get registerPassword;

  /// No description provided for @registerConfirmPassword.
  ///
  /// In en, this message translates to:
  /// **'Confirm Password'**
  String get registerConfirmPassword;

  /// No description provided for @registerButton.
  ///
  /// In en, this message translates to:
  /// **'Create Account'**
  String get registerButton;

  /// No description provided for @registerHasAccount.
  ///
  /// In en, this message translates to:
  /// **'Already have an account?'**
  String get registerHasAccount;

  /// No description provided for @registerLogin.
  ///
  /// In en, this message translates to:
  /// **'Sign In'**
  String get registerLogin;

  /// No description provided for @otpTitle.
  ///
  /// In en, this message translates to:
  /// **'Verification Code'**
  String get otpTitle;

  /// No description provided for @otpSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Enter the 6-digit code sent to your email'**
  String get otpSubtitle;

  /// No description provided for @otpButton.
  ///
  /// In en, this message translates to:
  /// **'Verify'**
  String get otpButton;

  /// No description provided for @otpResend.
  ///
  /// In en, this message translates to:
  /// **'Resend Code'**
  String get otpResend;

  /// No description provided for @otpResendIn.
  ///
  /// In en, this message translates to:
  /// **'Resend in {seconds}s'**
  String otpResendIn(Object seconds);

  /// No description provided for @dashboardTitle.
  ///
  /// In en, this message translates to:
  /// **'Dashboard'**
  String get dashboardTitle;

  /// No description provided for @dashboardTotalBalance.
  ///
  /// In en, this message translates to:
  /// **'Total Balance'**
  String get dashboardTotalBalance;

  /// No description provided for @dashboardAccounts.
  ///
  /// In en, this message translates to:
  /// **'My Accounts'**
  String get dashboardAccounts;

  /// No description provided for @dashboardRecentTransactions.
  ///
  /// In en, this message translates to:
  /// **'Recent Transactions'**
  String get dashboardRecentTransactions;

  /// No description provided for @dashboardQuickActions.
  ///
  /// In en, this message translates to:
  /// **'Quick Actions'**
  String get dashboardQuickActions;

  /// No description provided for @dashboardViewAll.
  ///
  /// In en, this message translates to:
  /// **'View All'**
  String get dashboardViewAll;

  /// No description provided for @dashboardNoAccounts.
  ///
  /// In en, this message translates to:
  /// **'No accounts yet'**
  String get dashboardNoAccounts;

  /// No description provided for @dashboardNoTransactions.
  ///
  /// In en, this message translates to:
  /// **'No recent transactions'**
  String get dashboardNoTransactions;

  /// No description provided for @dashboardHello.
  ///
  /// In en, this message translates to:
  /// **'Hello, {name}'**
  String dashboardHello(String name);

  /// No description provided for @dashboardGoodMorning.
  ///
  /// In en, this message translates to:
  /// **'Good morning'**
  String get dashboardGoodMorning;

  /// No description provided for @dashboardGoodAfternoon.
  ///
  /// In en, this message translates to:
  /// **'Good afternoon'**
  String get dashboardGoodAfternoon;

  /// No description provided for @dashboardGoodEvening.
  ///
  /// In en, this message translates to:
  /// **'Good evening'**
  String get dashboardGoodEvening;

  /// No description provided for @dashboardWelcomeBack.
  ///
  /// In en, this message translates to:
  /// **'Welcome back'**
  String get dashboardWelcomeBack;

  /// No description provided for @dashboardUsdBalance.
  ///
  /// In en, this message translates to:
  /// **'USD Balance'**
  String get dashboardUsdBalance;

  /// No description provided for @dashboardIqdBalance.
  ///
  /// In en, this message translates to:
  /// **'IQD Balance'**
  String get dashboardIqdBalance;

  /// No description provided for @dashboardSendMoney.
  ///
  /// In en, this message translates to:
  /// **'Send Money'**
  String get dashboardSendMoney;

  /// No description provided for @dashboardConvert.
  ///
  /// In en, this message translates to:
  /// **'Convert'**
  String get dashboardConvert;

  /// No description provided for @dashboardLoading.
  ///
  /// In en, this message translates to:
  /// **'Loading dashboard...'**
  String get dashboardLoading;

  /// No description provided for @navHome.
  ///
  /// In en, this message translates to:
  /// **'Home'**
  String get navHome;

  /// No description provided for @navCards.
  ///
  /// In en, this message translates to:
  /// **'Cards'**
  String get navCards;

  /// No description provided for @navTransfer.
  ///
  /// In en, this message translates to:
  /// **'Transfer'**
  String get navTransfer;

  /// No description provided for @navLoans.
  ///
  /// In en, this message translates to:
  /// **'Loans'**
  String get navLoans;

  /// No description provided for @navProfile.
  ///
  /// In en, this message translates to:
  /// **'Profile'**
  String get navProfile;

  /// No description provided for @transferTitle.
  ///
  /// In en, this message translates to:
  /// **'Transfer'**
  String get transferTitle;

  /// No description provided for @transferNew.
  ///
  /// In en, this message translates to:
  /// **'New Transfer'**
  String get transferNew;

  /// No description provided for @transferPending.
  ///
  /// In en, this message translates to:
  /// **'Pending Transfers'**
  String get transferPending;

  /// No description provided for @transferFrom.
  ///
  /// In en, this message translates to:
  /// **'From Account'**
  String get transferFrom;

  /// No description provided for @transferTo.
  ///
  /// In en, this message translates to:
  /// **'To Account'**
  String get transferTo;

  /// No description provided for @transferAmount.
  ///
  /// In en, this message translates to:
  /// **'Amount'**
  String get transferAmount;

  /// No description provided for @transferDescription.
  ///
  /// In en, this message translates to:
  /// **'Description'**
  String get transferDescription;

  /// No description provided for @transferSend.
  ///
  /// In en, this message translates to:
  /// **'Send Money'**
  String get transferSend;

  /// No description provided for @transferConfirm.
  ///
  /// In en, this message translates to:
  /// **'Confirm Transfer'**
  String get transferConfirm;

  /// No description provided for @transferSuccess.
  ///
  /// In en, this message translates to:
  /// **'Transfer Successful'**
  String get transferSuccess;

  /// No description provided for @transferCurrencyExchange.
  ///
  /// In en, this message translates to:
  /// **'Currency Exchange'**
  String get transferCurrencyExchange;

  /// No description provided for @cardsTitle.
  ///
  /// In en, this message translates to:
  /// **'My Cards'**
  String get cardsTitle;

  /// No description provided for @cardsFreeze.
  ///
  /// In en, this message translates to:
  /// **'Freeze Card'**
  String get cardsFreeze;

  /// No description provided for @cardsUnfreeze.
  ///
  /// In en, this message translates to:
  /// **'Unfreeze Card'**
  String get cardsUnfreeze;

  /// No description provided for @cardsReveal.
  ///
  /// In en, this message translates to:
  /// **'Reveal Details'**
  String get cardsReveal;

  /// No description provided for @cardsLimits.
  ///
  /// In en, this message translates to:
  /// **'Spending Limits'**
  String get cardsLimits;

  /// No description provided for @cardsContactless.
  ///
  /// In en, this message translates to:
  /// **'Contactless'**
  String get cardsContactless;

  /// No description provided for @cardsOnline.
  ///
  /// In en, this message translates to:
  /// **'Online Payments'**
  String get cardsOnline;

  /// No description provided for @cardsInternational.
  ///
  /// In en, this message translates to:
  /// **'International'**
  String get cardsInternational;

  /// No description provided for @cardsRequestPin.
  ///
  /// In en, this message translates to:
  /// **'Request PIN Change'**
  String get cardsRequestPin;

  /// No description provided for @loansTitle.
  ///
  /// In en, this message translates to:
  /// **'Loans'**
  String get loansTitle;

  /// No description provided for @loansApply.
  ///
  /// In en, this message translates to:
  /// **'Apply for Loan'**
  String get loansApply;

  /// No description provided for @loansActive.
  ///
  /// In en, this message translates to:
  /// **'Active Loans'**
  String get loansActive;

  /// No description provided for @loansHistory.
  ///
  /// In en, this message translates to:
  /// **'Loan History'**
  String get loansHistory;

  /// No description provided for @loansRepay.
  ///
  /// In en, this message translates to:
  /// **'Make Payment'**
  String get loansRepay;

  /// No description provided for @loansRemaining.
  ///
  /// In en, this message translates to:
  /// **'Remaining Balance'**
  String get loansRemaining;

  /// No description provided for @loansMonthlyPayment.
  ///
  /// In en, this message translates to:
  /// **'Monthly Payment'**
  String get loansMonthlyPayment;

  /// No description provided for @loansProgress.
  ///
  /// In en, this message translates to:
  /// **'Repayment Progress'**
  String get loansProgress;

  /// No description provided for @notificationsTitle.
  ///
  /// In en, this message translates to:
  /// **'Notifications'**
  String get notificationsTitle;

  /// No description provided for @notificationsMarkAllRead.
  ///
  /// In en, this message translates to:
  /// **'Mark All as Read'**
  String get notificationsMarkAllRead;

  /// No description provided for @notificationsEmpty.
  ///
  /// In en, this message translates to:
  /// **'No notifications'**
  String get notificationsEmpty;

  /// No description provided for @notificationsUnread.
  ///
  /// In en, this message translates to:
  /// **'{count} unread'**
  String notificationsUnread(Object count);

  /// No description provided for @profileTitle.
  ///
  /// In en, this message translates to:
  /// **'Profile'**
  String get profileTitle;

  /// No description provided for @profilePersonalInfo.
  ///
  /// In en, this message translates to:
  /// **'Personal Information'**
  String get profilePersonalInfo;

  /// No description provided for @profileSecurity.
  ///
  /// In en, this message translates to:
  /// **'Security'**
  String get profileSecurity;

  /// No description provided for @profileChangePassword.
  ///
  /// In en, this message translates to:
  /// **'Change Password'**
  String get profileChangePassword;

  /// No description provided for @profileTwoFactor.
  ///
  /// In en, this message translates to:
  /// **'Two-Factor Authentication'**
  String get profileTwoFactor;

  /// No description provided for @profileKyc.
  ///
  /// In en, this message translates to:
  /// **'KYC Verification'**
  String get profileKyc;

  /// No description provided for @profileSecurityLogs.
  ///
  /// In en, this message translates to:
  /// **'Security Logs'**
  String get profileSecurityLogs;

  /// No description provided for @profileSupport.
  ///
  /// In en, this message translates to:
  /// **'Help & Support'**
  String get profileSupport;

  /// No description provided for @profileLogout.
  ///
  /// In en, this message translates to:
  /// **'Logout'**
  String get profileLogout;

  /// No description provided for @profileLanguage.
  ///
  /// In en, this message translates to:
  /// **'Language'**
  String get profileLanguage;

  /// No description provided for @profileTheme.
  ///
  /// In en, this message translates to:
  /// **'Theme'**
  String get profileTheme;

  /// No description provided for @settingsTitle.
  ///
  /// In en, this message translates to:
  /// **'Settings'**
  String get settingsTitle;

  /// No description provided for @settingsDarkMode.
  ///
  /// In en, this message translates to:
  /// **'Dark Mode'**
  String get settingsDarkMode;

  /// No description provided for @settingsLanguage.
  ///
  /// In en, this message translates to:
  /// **'Language'**
  String get settingsLanguage;

  /// No description provided for @settingsAbout.
  ///
  /// In en, this message translates to:
  /// **'About'**
  String get settingsAbout;

  /// No description provided for @settingsVersion.
  ///
  /// In en, this message translates to:
  /// **'Version'**
  String get settingsVersion;

  /// No description provided for @generalSave.
  ///
  /// In en, this message translates to:
  /// **'Save'**
  String get generalSave;

  /// No description provided for @generalCancel.
  ///
  /// In en, this message translates to:
  /// **'Cancel'**
  String get generalCancel;

  /// No description provided for @generalConfirm.
  ///
  /// In en, this message translates to:
  /// **'Confirm'**
  String get generalConfirm;

  /// No description provided for @generalDelete.
  ///
  /// In en, this message translates to:
  /// **'Delete'**
  String get generalDelete;

  /// No description provided for @generalEdit.
  ///
  /// In en, this message translates to:
  /// **'Edit'**
  String get generalEdit;

  /// No description provided for @generalRetry.
  ///
  /// In en, this message translates to:
  /// **'Retry'**
  String get generalRetry;

  /// No description provided for @generalLoading.
  ///
  /// In en, this message translates to:
  /// **'Loading...'**
  String get generalLoading;

  /// No description provided for @generalError.
  ///
  /// In en, this message translates to:
  /// **'Something went wrong'**
  String get generalError;

  /// No description provided for @generalSuccess.
  ///
  /// In en, this message translates to:
  /// **'Success'**
  String get generalSuccess;

  /// No description provided for @generalNoData.
  ///
  /// In en, this message translates to:
  /// **'No data available'**
  String get generalNoData;

  /// No description provided for @generalSearch.
  ///
  /// In en, this message translates to:
  /// **'Search'**
  String get generalSearch;

  /// No description provided for @generalFilter.
  ///
  /// In en, this message translates to:
  /// **'Filter'**
  String get generalFilter;

  /// No description provided for @generalCurrency.
  ///
  /// In en, this message translates to:
  /// **'Currency'**
  String get generalCurrency;

  /// No description provided for @generalAmount.
  ///
  /// In en, this message translates to:
  /// **'Amount'**
  String get generalAmount;

  /// No description provided for @generalDate.
  ///
  /// In en, this message translates to:
  /// **'Date'**
  String get generalDate;

  /// No description provided for @generalStatus.
  ///
  /// In en, this message translates to:
  /// **'Status'**
  String get generalStatus;

  /// No description provided for @generalReference.
  ///
  /// In en, this message translates to:
  /// **'Reference'**
  String get generalReference;

  /// No description provided for @beneficiariesTitle.
  ///
  /// In en, this message translates to:
  /// **'Beneficiaries'**
  String get beneficiariesTitle;

  /// No description provided for @beneficiariesAdd.
  ///
  /// In en, this message translates to:
  /// **'Add Beneficiary'**
  String get beneficiariesAdd;

  /// No description provided for @beneficiariesNoData.
  ///
  /// In en, this message translates to:
  /// **'No beneficiaries yet'**
  String get beneficiariesNoData;

  /// No description provided for @supportTitle.
  ///
  /// In en, this message translates to:
  /// **'Help & Support'**
  String get supportTitle;

  /// No description provided for @supportNewTicket.
  ///
  /// In en, this message translates to:
  /// **'New Ticket'**
  String get supportNewTicket;

  /// No description provided for @supportMyTickets.
  ///
  /// In en, this message translates to:
  /// **'My Tickets'**
  String get supportMyTickets;

  /// No description provided for @supportSubject.
  ///
  /// In en, this message translates to:
  /// **'Subject'**
  String get supportSubject;

  /// No description provided for @supportMessage.
  ///
  /// In en, this message translates to:
  /// **'Message'**
  String get supportMessage;

  /// No description provided for @supportCategory.
  ///
  /// In en, this message translates to:
  /// **'Category'**
  String get supportCategory;

  /// No description provided for @supportPriority.
  ///
  /// In en, this message translates to:
  /// **'Priority'**
  String get supportPriority;

  /// No description provided for @currencyUSD.
  ///
  /// In en, this message translates to:
  /// **'US Dollar'**
  String get currencyUSD;

  /// No description provided for @currencyIQD.
  ///
  /// In en, this message translates to:
  /// **'Iraqi Dinar'**
  String get currencyIQD;

  /// No description provided for @currencyExchange.
  ///
  /// In en, this message translates to:
  /// **'Exchange Rate'**
  String get currencyExchange;

  /// No description provided for @statusActive.
  ///
  /// In en, this message translates to:
  /// **'Active'**
  String get statusActive;

  /// No description provided for @statusInactive.
  ///
  /// In en, this message translates to:
  /// **'Inactive'**
  String get statusInactive;

  /// No description provided for @statusPending.
  ///
  /// In en, this message translates to:
  /// **'Pending'**
  String get statusPending;

  /// No description provided for @statusCompleted.
  ///
  /// In en, this message translates to:
  /// **'Completed'**
  String get statusCompleted;

  /// No description provided for @statusFailed.
  ///
  /// In en, this message translates to:
  /// **'Failed'**
  String get statusFailed;

  /// No description provided for @statusCancelled.
  ///
  /// In en, this message translates to:
  /// **'Cancelled'**
  String get statusCancelled;

  /// No description provided for @statusFrozen.
  ///
  /// In en, this message translates to:
  /// **'Frozen'**
  String get statusFrozen;

  /// No description provided for @statusApproved.
  ///
  /// In en, this message translates to:
  /// **'Approved'**
  String get statusApproved;

  /// No description provided for @statusRejected.
  ///
  /// In en, this message translates to:
  /// **'Rejected'**
  String get statusRejected;

  /// No description provided for @registerBranchRequired.
  ///
  /// In en, this message translates to:
  /// **'Please select a branch'**
  String get registerBranchRequired;

  /// No description provided for @registerPasswordMismatch.
  ///
  /// In en, this message translates to:
  /// **'Passwords do not match'**
  String get registerPasswordMismatch;

  /// No description provided for @forgotPasswordTitle.
  ///
  /// In en, this message translates to:
  /// **'Forgot Password?'**
  String get forgotPasswordTitle;

  /// No description provided for @forgotPasswordSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Enter your email address and we\'ll send you a secure code to reset your password.'**
  String get forgotPasswordSubtitle;

  /// No description provided for @forgotPasswordSendCode.
  ///
  /// In en, this message translates to:
  /// **'Send Reset Code'**
  String get forgotPasswordSendCode;

  /// No description provided for @forgotPasswordBackToLogin.
  ///
  /// In en, this message translates to:
  /// **'Back to Sign In'**
  String get forgotPasswordBackToLogin;

  /// No description provided for @resetPasswordTitle.
  ///
  /// In en, this message translates to:
  /// **'Reset Password'**
  String get resetPasswordTitle;

  /// No description provided for @resetPasswordSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Enter the 6-digit code and your new password.'**
  String get resetPasswordSubtitle;

  /// No description provided for @resetPasswordSuccess.
  ///
  /// In en, this message translates to:
  /// **'Password reset successfully! Sign in with your new password.'**
  String get resetPasswordSuccess;

  /// No description provided for @resetPasswordNew.
  ///
  /// In en, this message translates to:
  /// **'New Password'**
  String get resetPasswordNew;

  /// No description provided for @resetPasswordConfirm.
  ///
  /// In en, this message translates to:
  /// **'Confirm New Password'**
  String get resetPasswordConfirm;

  /// No description provided for @resetPasswordButton.
  ///
  /// In en, this message translates to:
  /// **'Reset Password'**
  String get resetPasswordButton;

  /// No description provided for @resetPasswordResend.
  ///
  /// In en, this message translates to:
  /// **'Resend Reset Code'**
  String get resetPasswordResend;

  /// No description provided for @resetPasswordHint.
  ///
  /// In en, this message translates to:
  /// **'Min 8 chars, mixed case, numbers'**
  String get resetPasswordHint;

  /// No description provided for @resetPasswordConfirmHint.
  ///
  /// In en, this message translates to:
  /// **'Confirm your password'**
  String get resetPasswordConfirmHint;

  /// No description provided for @resetCodeLabel.
  ///
  /// In en, this message translates to:
  /// **'Reset Code (OTP)'**
  String get resetCodeLabel;

  /// No description provided for @resetCodeRequired.
  ///
  /// In en, this message translates to:
  /// **'Please enter the reset code'**
  String get resetCodeRequired;

  /// No description provided for @resetCodeLengthError.
  ///
  /// In en, this message translates to:
  /// **'Code must be 6 digits'**
  String get resetCodeLengthError;

  /// No description provided for @resetPasswordRequired.
  ///
  /// In en, this message translates to:
  /// **'Please enter a new password'**
  String get resetPasswordRequired;

  /// No description provided for @resetPasswordMinLength.
  ///
  /// In en, this message translates to:
  /// **'Password must be at least 8 characters'**
  String get resetPasswordMinLength;

  /// No description provided for @resetPasswordUppercase.
  ///
  /// In en, this message translates to:
  /// **'Must include an uppercase letter'**
  String get resetPasswordUppercase;

  /// No description provided for @resetPasswordLowercase.
  ///
  /// In en, this message translates to:
  /// **'Must include a lowercase letter'**
  String get resetPasswordLowercase;

  /// No description provided for @resetPasswordNumber.
  ///
  /// In en, this message translates to:
  /// **'Must include a number'**
  String get resetPasswordNumber;

  /// No description provided for @resetPasswordConfirmRequired.
  ///
  /// In en, this message translates to:
  /// **'Please confirm your password'**
  String get resetPasswordConfirmRequired;

  /// No description provided for @resetPasswordMismatch.
  ///
  /// In en, this message translates to:
  /// **'Passwords do not match'**
  String get resetPasswordMismatch;

  /// No description provided for @sandboxTitle.
  ///
  /// In en, this message translates to:
  /// **'Developer Sandbox'**
  String get sandboxTitle;

  /// No description provided for @sandboxOtpLabel.
  ///
  /// In en, this message translates to:
  /// **'Your OTP: '**
  String get sandboxOtpLabel;

  /// No description provided for @otpTwoFactorTitle.
  ///
  /// In en, this message translates to:
  /// **'Two-Factor Verification'**
  String get otpTwoFactorTitle;

  /// No description provided for @otpSentToEmail.
  ///
  /// In en, this message translates to:
  /// **'Enter the 6-digit code sent to\n{email}'**
  String otpSentToEmail(String email);

  /// No description provided for @otpResentSuccess.
  ///
  /// In en, this message translates to:
  /// **'Verification code resent successfully!'**
  String get otpResentSuccess;

  /// No description provided for @otpSecurityNote.
  ///
  /// In en, this message translates to:
  /// **'Never share your verification code with anyone, including bank staff.'**
  String get otpSecurityNote;

  /// No description provided for @otpResendInSeconds.
  ///
  /// In en, this message translates to:
  /// **'Resend code in {seconds}s'**
  String otpResendInSeconds(String seconds);
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['ckb', 'en'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'ckb':
      return AppLocalizationsCkb();
    case 'en':
      return AppLocalizationsEn();
  }

  throw FlutterError(
    'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
    'an issue with the localizations generation tool. Please file an issue '
    'on GitHub with a reproducible sample app and the gen-l10n configuration '
    'that was used.',
  );
}
