import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../blocs/auth/auth_bloc.dart';
import '../../blocs/auth/auth_event.dart';
import '../../blocs/auth/auth_state.dart';
import '../../blocs/dashboard/dashboard_bloc.dart';
import '../../blocs/dashboard/dashboard_event.dart';
import '../../blocs/dashboard/dashboard_state.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../widgets/currency_text.dart';
import '../../widgets/loading_indicator.dart';
import '../../widgets/empty_state.dart';
import '../transfers/transfer_screen.dart';
import '../profile/profile_screen.dart';
import '../cards/card_screen.dart';
import '../loans/loans_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  int _currentIndex = 0;
  final GlobalKey<TransferScreenState> _transferScreenKey = GlobalKey<TransferScreenState>();

  @override
  void initState() {
    super.initState();
    context.read<DashboardBloc>().add(const LoadDashboard());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: IndexedStack(
          index: _currentIndex,
          children: [
            _HomeTab(onTabChange: (index, subTab) {
              setState(() => _currentIndex = index);
              if (index == 2 && subTab != null) {
                WidgetsBinding.instance.addPostFrameCallback((_) {
                  _transferScreenKey.currentState?.selectTab(subTab);
                });
              }
            }),
            const CardsTabScreen(),
            TransferScreen(key: _transferScreenKey),
            const LoansTabScreen(),
            const ProfileScreen(),
          ],
        ),
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) => setState(() => _currentIndex = index),
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.home_outlined),
            activeIcon: Icon(Icons.home),
            label: 'Home',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.credit_card_outlined),
            activeIcon: Icon(Icons.credit_card),
            label: 'Cards',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.swap_horiz_outlined),
            activeIcon: Icon(Icons.swap_horiz),
            label: 'Transfer',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.account_balance_outlined),
            activeIcon: Icon(Icons.account_balance),
            label: 'Loans',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person_outlined),
            activeIcon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
    );
  }
}

// ── Home Tab ──────────────────────────────────────────────────

class _HomeTab extends StatelessWidget {
  final void Function(int index, int? subTab) onTabChange;
  const _HomeTab({required this.onTabChange});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<DashboardBloc, DashboardState>(
      builder: (context, state) {
        if (state is DashboardLoading) {
          return const LoadingIndicator(message: 'Loading dashboard...');
        }
        if (state is DashboardError) {
          return Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(state.message),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () => context
                      .read<DashboardBloc>()
                      .add(const RefreshDashboard()),
                  child: const Text('Retry'),
                ),
              ],
            ),
          );
        }
        if (state is DashboardLoaded) {
          final data = state.data;
          return RefreshIndicator(
            onRefresh: () async {
              context.read<DashboardBloc>().add(const RefreshDashboard());
            },
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Header
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    BlocBuilder<AuthBloc, AuthState>(
                      builder: (context, authState) {
                        final name = authState is AuthAuthenticated
                          ? authState.user.name.split(' ').first
                          : 'User';
                        final hour = DateTime.now().hour;
                        String greeting = 'Welcome back';
                        if (hour < 12) {
                          greeting = 'Good morning';
                        } else if (hour < 17) {
                          greeting = 'Good afternoon';
                        } else {
                          greeting = 'Good evening';
                        }
                        return Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Hello, $name',
                              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                    fontFamily: 'Outfit',
                                    fontWeight: FontWeight.bold,
                                  ),
                            ),
                            Text(
                              greeting,
                              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                    color: Colors.white54,
                                  ),
                            ),
                          ],
                        );
                      },
                    ),
                    IconButton(
                      onPressed: () => Navigator.pushNamed(context, '/notifications'),
                      icon: const Icon(Icons.notifications_outlined, color: Colors.white),
                    ),
                  ],
                ),
                const SizedBox(height: 24),

                // Balance cards
                _BalanceCard(
                  label: 'USD Balance',
                  amount: data.totalBalanceUSD,
                  currency: 'USD',
                  gradient: AppColors.primaryGradient,
                ),
                const SizedBox(height: 12),
                _BalanceCard(
                  label: 'IQD Balance',
                  amount: data.totalBalanceIQD,
                  currency: 'IQD',
                  gradient: AppColors.secondaryGradient,
                ),
                const SizedBox(height: 24),

                // Quick actions
                Text(
                  'Quick Actions',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontFamily: 'Outfit',
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    _QuickAction(
                      icon: Icons.send_rounded,
                      label: 'Send Money',
                      onTap: () => onTabChange(2, 0),
                    ),
                    _QuickAction(
                      icon: Icons.credit_card_rounded,
                      label: 'Cards',
                      onTap: () => onTabChange(1, null),
                    ),
                    _QuickAction(
                      icon: Icons.account_balance_rounded,
                      label: 'Loans',
                      onTap: () => onTabChange(3, null),
                    ),
                    _QuickAction(
                      icon: Icons.currency_exchange_rounded,
                      label: 'Convert',
                      onTap: () => onTabChange(2, 2),
                    ),
                  ],
                ),
                const SizedBox(height: 24),

                // Recent transactions
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Recent Transactions',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontFamily: 'Outfit',
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    TextButton(
                      onPressed: () => Navigator.pushNamed(context, '/transactions'),
                      child: const Text('View All'),
                    ),
                  ],
                ),
                if (data.recentTransactions.isEmpty)
                  const EmptyState(
                    icon: Icons.receipt_long_outlined,
                    title: 'No recent transactions',
                  )
                else
                  ...data.recentTransactions.take(5).map(
                        (txn) => _TransactionTile(transaction: txn),
                      ),
              ],
            ),
          );
        }
        return const SizedBox.shrink();
      },
    );
  }
}

class _BalanceCard extends StatelessWidget {
  final String label;
  final double amount;
  final String currency;
  final Gradient gradient;

  const _BalanceCard({
    required this.label,
    required this.amount,
    required this.currency,
    required this.gradient,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: gradient,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontFamily: 'Outfit',
              fontSize: 14,
              color: Colors.white70,
            ),
          ),
          const SizedBox(height: 8),
          CurrencyText(
            amount: amount,
            currency: currency,
            style: const TextStyle(
              fontFamily: 'Outfit',
              fontSize: 28,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
        ],
      ),
    );
  }
}

class _QuickAction extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _QuickAction({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Column(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: AppColors.primary.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: AppColors.primary),
            ),
            const SizedBox(height: 8),
            Text(
              label,
              style: Theme.of(context).textTheme.bodySmall,
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

class _TransactionTile extends StatelessWidget {
  final dynamic transaction;

  const _TransactionTile({required this.transaction});

  @override
  Widget build(BuildContext context) {
    final isCredit = transaction.isCredit;
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Container(
        width: 40,
        height: 40,
        decoration: BoxDecoration(
          color: (isCredit ? AppColors.emerald : AppColors.danger)
              .withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Icon(
          isCredit ? Icons.arrow_downward : Icons.arrow_upward,
          color: isCredit ? AppColors.emerald : AppColors.danger,
          size: 20,
        ),
      ),
      title: Text(
        transaction.description ?? transaction.type,
        style: Theme.of(context).textTheme.bodyLarge,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
      subtitle: Text(
        transaction.createdAt != null
            ? Formatters.relativeTime(DateTime.parse(transaction.createdAt!))
            : '',
        style: Theme.of(context).textTheme.bodySmall,
      ),
      trailing: CurrencyText(
        amount: transaction.amount,
        currency: transaction.currency,
        showSign: true,
        style: TextStyle(
          fontFamily: 'Outfit',
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: isCredit ? AppColors.emerald : AppColors.danger,
        ),
      ),
    );
  }
}

// Real visual screens CardsTabScreen and LoansTabScreen are loaded inside the main IndexedStack

