import 'package:equatable/equatable.dart';
import '../../data/models/account.dart';
import '../../data/models/transaction.dart';

class DashboardData extends Equatable {
  final List<Account> accounts;
  final List<Transaction> recentTransactions;
  final int unreadNotifications;
  final int pendingTransfers;

  const DashboardData({
    required this.accounts,
    required this.recentTransactions,
    this.unreadNotifications = 0,
    this.pendingTransfers = 0,
  });

  double get totalBalanceUSD {
    return accounts
        .where((a) => a.currency == 'USD')
        .fold(0.0, (sum, a) => sum + a.balance);
  }

  double get totalBalanceIQD {
    return accounts
        .where((a) => a.currency == 'IQD')
        .fold(0.0, (sum, a) => sum + a.balance);
  }

  @override
  List<Object?> get props => [accounts, recentTransactions, unreadNotifications, pendingTransfers];
}

abstract class DashboardState extends Equatable {
  const DashboardState();

  @override
  List<Object?> get props => [];
}

class DashboardInitial extends DashboardState {
  const DashboardInitial();
}

class DashboardLoading extends DashboardState {
  const DashboardLoading();
}

class DashboardLoaded extends DashboardState {
  final DashboardData data;

  const DashboardLoaded({required this.data});

  @override
  List<Object?> get props => [data];
}

class DashboardError extends DashboardState {
  final String message;

  const DashboardError({required this.message});

  @override
  List<Object?> get props => [message];
}
