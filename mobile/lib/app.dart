import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'blocs/auth/auth_bloc.dart';
import 'blocs/auth/auth_state.dart';
import 'blocs/theme/theme_bloc.dart';
import 'blocs/theme/theme_state.dart';
import 'blocs/locale/locale_bloc.dart';
import 'blocs/locale/locale_state.dart';
import 'core/theme/app_theme.dart';
import 'screens/splash/splash_screen.dart';
import 'screens/auth/login_screen.dart';
import 'screens/auth/register_screen.dart';
import 'screens/auth/otp_screen.dart';
import 'screens/home/dashboard_screen.dart';
import 'screens/transactions/transactions_screen.dart';
import 'screens/notifications/notifications_screen.dart';
import 'screens/profile/kyc_upload_screen.dart';
import 'screens/auth/forgot_password_screen.dart';
import 'screens/auth/reset_password_screen.dart';
import 'l10n/app_localizations.dart';

class App extends StatelessWidget {
  static final navigatorKey = GlobalKey<NavigatorState>();
  const App({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<ThemeBloc, ThemeState>(
      builder: (context, themeState) {
        return BlocBuilder<LocaleBloc, LocaleState>(
          builder: (context, localeState) {
            return MaterialApp(
              navigatorKey: navigatorKey,
              title: 'Distributed Bank',
              debugShowCheckedModeBanner: false,
              theme: AppTheme.lightTheme,
              darkTheme: AppTheme.darkTheme,
              themeMode: themeState.themeMode,
              locale: localeState.locale,
              supportedLocales: AppLocalizations.supportedLocales,
              localizationsDelegates: AppLocalizations.localizationsDelegates,
              initialRoute: '/',
              routes: {
                '/': (_) => const SplashScreen(),
                '/login': (_) => const LoginScreen(),
                '/register': (_) => const RegisterScreen(),
                '/dashboard': (_) => const DashboardScreen(),
                '/transactions': (_) => const TransactionsScreen(),
                '/notifications': (_) => const NotificationsScreen(),
                '/kyc-upload': (_) => const KycUploadScreen(),
                '/forgot-password': (_) => const ForgotPasswordScreen(),
              },
              onGenerateRoute: (settings) {
                switch (settings.name) {
                  case '/otp':
                    final email = settings.arguments as String;
                    return MaterialPageRoute(
                      builder: (_) => OtpScreen(email: email),
                      settings: settings,
                    );
                  case '/reset-password':
                    final args = settings.arguments as Map<String, dynamic>;
                    final email = args['email'] as String;
                    final otp = args['otp'] as String?;
                    return MaterialPageRoute(
                      builder: (_) => ResetPasswordScreen(
                        email: email,
                        sandboxOtp: otp,
                      ),
                      settings: settings,
                    );
                  default:
                    return null;
                }
              },
              builder: (context, child) {
                // Global auth listener — redirect to login on logout
                return BlocListener<AuthBloc, AuthState>(
                  listener: (context, state) {
                    if (state is AuthUnauthenticated) {
                      navigatorKey.currentState?.pushNamedAndRemoveUntil(
                        '/login',
                        (route) => false,
                      );
                    }
                  },
                  child: child ?? const SizedBox.shrink(),
                );
              },
            );
          },
        );
      },
    );
  }
}
