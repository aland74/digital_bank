import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:firebase_core/firebase_core.dart';
import 'app.dart';
import 'core/storage/local_storage.dart';
import 'core/storage/secure_storage.dart';
import 'core/services/notification_service.dart';
import 'core/utils/deep_link_handler.dart';
import 'data/repositories/auth_repository.dart';
import 'data/repositories/account_repository.dart';
import 'data/repositories/transaction_repository.dart';
import 'data/repositories/transfer_repository.dart';
import 'data/repositories/card_repository.dart';
import 'data/repositories/loan_repository.dart';
import 'data/repositories/notification_repository.dart';
import 'data/repositories/profile_repository.dart';
import 'blocs/auth/auth_bloc.dart';
import 'blocs/dashboard/dashboard_bloc.dart';
import 'blocs/theme/theme_bloc.dart';
import 'blocs/locale/locale_bloc.dart';
import 'blocs/transfers/transfers_bloc.dart';
import 'blocs/cards/cards_bloc.dart';
import 'blocs/loans/loans_bloc.dart';
import 'blocs/notifications/notifications_bloc.dart';
import 'blocs/profile/profile_bloc.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Firebase
  try {
    await Firebase.initializeApp();
  } catch (e) {
    debugPrint('Firebase initialization failed: $e');
  }

  // Initialize Notification Service
  await NotificationService().init();

  // Initialize local storage
  await LocalStorage().init();

  // Initialize Deep Link Handler
  await DeepLinkHandler().init();

  runApp(const DistributedBankApp());
}

class DistributedBankApp extends StatelessWidget {
  const DistributedBankApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiBlocProvider(
      providers: [
        BlocProvider<ThemeBloc>(
          create: (_) => ThemeBloc(),
        ),
        BlocProvider<LocaleBloc>(
          create: (_) => LocaleBloc(),
        ),
        BlocProvider<AuthBloc>(
          create: (_) => AuthBloc(
            authRepository: AuthRepository(),
            secureStorage: SecureStorage(),
          ),
        ),
        BlocProvider<DashboardBloc>(
          create: (_) => DashboardBloc(
            accountRepository: AccountRepository(),
            transactionRepository: TransactionRepository(),
          ),
        ),
        BlocProvider<TransfersBloc>(
          create: (_) => TransfersBloc(
            transferRepository: TransferRepository(),
          ),
        ),
        BlocProvider<CardsBloc>(
          create: (_) => CardsBloc(
            cardRepository: CardRepository(),
          ),
        ),
        BlocProvider<LoansBloc>(
          create: (_) => LoansBloc(
            loanRepository: LoanRepository(),
          ),
        ),
        BlocProvider<NotificationsBloc>(
          create: (_) => NotificationsBloc(
            NotificationRepository(),
          ),
        ),
        BlocProvider<ProfileBloc>(
          create: (_) => ProfileBloc(ProfileRepository()),
        ),
      ],
      child: const App(),
    );
  }
}
