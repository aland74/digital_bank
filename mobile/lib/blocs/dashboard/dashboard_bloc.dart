import 'package:flutter_bloc/flutter_bloc.dart';
import '../../data/repositories/account_repository.dart';
import '../../data/repositories/transaction_repository.dart';
import '../../data/models/account.dart';
import '../../data/models/transaction.dart';
import '../../core/storage/local_storage.dart';
import 'dashboard_event.dart';
import 'dashboard_state.dart';

class DashboardBloc extends Bloc<DashboardEvent, DashboardState> {
  final AccountRepository _accountRepository;
  final TransactionRepository _transactionRepository;

  DashboardBloc({
    required AccountRepository accountRepository,
    required TransactionRepository transactionRepository,
  })  : _accountRepository = accountRepository,
        _transactionRepository = transactionRepository,
        super(const DashboardInitial()) {
    on<LoadDashboard>(_onLoadDashboard);
    on<RefreshDashboard>(_onRefreshDashboard);
  }

  Future<void> _onLoadDashboard(
    LoadDashboard event,
    Emitter<DashboardState> emit,
  ) async {
    emit(const DashboardLoading());

    // Try cache first
    final cached = LocalStorage().getCachedDashboard();
    if (cached != null) {
      try {
        final accounts = (cached['accounts'] as List? ?? [])
            .map((e) => Account.fromJson(
                e is Map<String, dynamic> ? e : e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{}))
            .toList();
        final transactions = (cached['transactions'] as List? ?? [])
            .map((e) => Transaction.fromJson(
                e is Map<String, dynamic> ? e : e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{}))
            .toList();
        emit(DashboardLoaded(
          data: DashboardData(
            accounts: accounts,
            recentTransactions: transactions,
          ),
        ));
      } catch (_) {
        // Cache corrupt — fetch fresh
      }
    }

    await _fetchDashboard(emit);
  }

  Future<void> _onRefreshDashboard(
    RefreshDashboard event,
    Emitter<DashboardState> emit,
  ) async {
    await _fetchDashboard(emit);
  }

  Future<void> _fetchDashboard(Emitter<DashboardState> emit) async {
    try {
      final accountsResult = await _accountRepository.getAccounts();
      final txnResult = await _transactionRepository.getTransactions(page: 1);

      if (accountsResult.success && accountsResult.data != null) {
        final accounts = accountsResult.data!;
        final transactions = <Transaction>[];

        if (txnResult.success && txnResult.data != null) {
          final txnData = txnResult.data!;
          final txnList = txnData['data'] as List? ?? [];
          for (final t in txnList) {
            transactions.add(Transaction.fromJson(
                t is Map<String, dynamic> ? t : t is Map ? Map<String, dynamic>.from(t) : <String, dynamic>{}));
          }
        }

        final data = DashboardData(
          accounts: accounts,
          recentTransactions: transactions,
        );

        // Cache it
        LocalStorage().cacheDashboard({
          'accounts': accounts.map((a) => a.toJson()).toList(),
          'transactions': transactions.map((t) => t.toJson()).toList(),
        });

        emit(DashboardLoaded(data: data));
      } else {
        emit(DashboardError(message: accountsResult.errorMessage));
      }
    } catch (e) {
      emit(DashboardError(message: 'Failed to load dashboard'));
    }
  }
}
