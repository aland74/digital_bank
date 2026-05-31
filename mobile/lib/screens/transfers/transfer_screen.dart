import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../blocs/dashboard/dashboard_bloc.dart';
import '../../blocs/dashboard/dashboard_state.dart';
import '../../blocs/transfers/transfers_bloc.dart';
import '../../blocs/transfers/transfers_event.dart';
import '../../blocs/transfers/transfers_state.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../data/models/account.dart';
import '../../data/models/pending_transfer.dart';
import '../../widgets/loading_indicator.dart';
import '../../widgets/empty_state.dart';

class TransferScreen extends StatefulWidget {
  const TransferScreen({super.key});

  @override
  State<TransferScreen> createState() => TransferScreenState();
}

class TransferScreenState extends State<TransferScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  void selectTab(int index) {
    if (index >= 0 && index < _tabController.length) {
      _tabController.animateTo(index);
    }
  }

  // Send Money Form State
  Account? _selectedAccount;
  final _recipientController = TextEditingController();
  final _amountController = TextEditingController();
  final _descController = TextEditingController();
  final _sendFormKey = GlobalKey<FormState>();

  // Convert Currency State
  String _convertFrom = 'USD';
  final _convertAmountController = TextEditingController();
  double _convertedValue = 0.0;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    context.read<TransfersBloc>().add(const LoadTransfers());
  }

  @override
  void dispose() {
    _tabController.dispose();
    _recipientController.dispose();
    _amountController.dispose();
    _descController.dispose();
    _convertAmountController.dispose();
    super.dispose();
  }

  void _onSendMoney() {
    if (_sendFormKey.currentState?.validate() ?? false) {
      if (_selectedAccount == null) return;
      context.read<TransfersBloc>().add(CreateTransferEvent(
            fromAccountId: _selectedAccount!.id,
            toAccountNumber: _recipientController.text.trim(),
            amount: double.parse(_amountController.text.trim()),
            description: _descController.text.trim(),
          ));
    }
  }

  void _onConvert() {
    final amt = double.tryParse(_convertAmountController.text.trim());
    if (amt != null && amt > 0) {
      context.read<TransfersBloc>().add(ConvertCurrencyEvent(
            fromCurrency: _convertFrom,
            amount: amt,
          ));
    }
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<TransfersBloc, TransfersState>(
      listener: (context, state) {
        if (state is TransferActionSuccess) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: AppColors.emerald,
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12)),
            ),
          );
          // Clear inputs
          _recipientController.clear();
          _amountController.clear();
          _descController.clear();
          _convertAmountController.clear();
          setState(() {
            _convertedValue = 0.0;
          });
        } else if (state is TransfersError) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: AppColors.danger,
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12)),
            ),
          );
        }
      },
      child: Scaffold(
        backgroundColor: AppColors.darkBackground,
        appBar: AppBar(
          backgroundColor: AppColors.darkSurface,
          elevation: 0,
          title: const Text(
            'Transfers & Conversions',
            style: TextStyle(
              fontFamily: 'Outfit',
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
          bottom: TabBar(
            controller: _tabController,
            indicatorColor: AppColors.primary,
            labelColor: AppColors.primary,
            unselectedLabelColor: Colors.white54,
            labelStyle: const TextStyle(
              fontFamily: 'Outfit',
              fontWeight: FontWeight.w600,
            ),
            tabs: const [
              Tab(text: 'Send Money'),
              Tab(text: 'Pending'),
              Tab(text: 'Convert'),
            ],
          ),
        ),
        body: BlocBuilder<DashboardBloc, DashboardState>(
          builder: (context, dashboardState) {
            if (dashboardState is DashboardLoading) {
              return const LoadingIndicator(message: 'Loading accounts...');
            }
            if (dashboardState is DashboardLoaded) {
              final accounts = dashboardState.data.accounts;
              if (_selectedAccount == null && accounts.isNotEmpty) {
                _selectedAccount = accounts.first;
              }

              return TabBarView(
                controller: _tabController,
                children: [
                  _buildSendMoneyTab(accounts),
                  _buildPendingTab(),
                  _buildConvertTab(),
                ],
              );
            }
            return const Center(child: Text('Failed to load accounts.'));
          },
        ),
      ),
    );
  }

  // ── Send Money Tab ───────────────────────────────────────────
  Widget _buildSendMoneyTab(List<Account> accounts) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Form(
        key: _sendFormKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const SizedBox(height: 10),
            _buildLabel('Source Account'),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              decoration: BoxDecoration(
                color: AppColors.darkSurface,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: Colors.white.withValues(alpha: 0.1)),
              ),
              child: DropdownButtonHideUnderline(
                child: DropdownButton<Account>(
                  value: _selectedAccount,
                  dropdownColor: AppColors.darkSurface,
                  icon: const Icon(Icons.keyboard_arrow_down, color: Colors.white70),
                  items: accounts.map((account) {
                    return DropdownMenuItem<Account>(
                      value: account,
                      child: Text(
                        '${account.accountName} (${account.currency}) - Bal: ${account.balance}',
                        style: const TextStyle(color: Colors.white, fontFamily: 'Outfit'),
                      ),
                    );
                  }).toList(),
                  onChanged: (Account? val) {
                    setState(() {
                      _selectedAccount = val;
                    });
                  },
                ),
              ),
            ),
            const SizedBox(height: 20),

            _buildLabel('Recipient Account Number'),
            const SizedBox(height: 8),
            TextFormField(
              controller: _recipientController,
              style: const TextStyle(color: Colors.white),
              decoration: _inputDecoration('e.g. NXB0000000001', Icons.account_circle_outlined),
              validator: (v) => v == null || v.isEmpty ? 'Recipient Account Number is required' : null,
            ),
            const SizedBox(height: 20),

            _buildLabel('Amount'),
            const SizedBox(height: 8),
            TextFormField(
              controller: _amountController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              style: const TextStyle(color: Colors.white),
              decoration: _inputDecoration('0.00', Icons.attach_money),
              validator: (v) {
                if (v == null || v.isEmpty) return 'Amount is required';
                final amt = double.tryParse(v);
                if (amt == null || amt <= 0) return 'Invalid amount';
                if (_selectedAccount != null && amt > _selectedAccount!.availableBalance) {
                  return 'Insufficient available balance';
                }
                return null;
              },
            ),
            const SizedBox(height: 20),

            _buildLabel('Description / Reference'),
            const SizedBox(height: 8),
            TextFormField(
              controller: _descController,
              maxLines: 2,
              style: const TextStyle(color: Colors.white),
              decoration: _inputDecoration('Transfer purpose...', Icons.notes),
            ),
            const SizedBox(height: 32),

            BlocBuilder<TransfersBloc, TransfersState>(
              builder: (context, state) {
                final isLoading = state is TransferActionLoading;
                return Container(
                  height: 52,
                  decoration: BoxDecoration(
                    gradient: isLoading ? null : AppColors.primaryGradient,
                    color: isLoading ? Colors.white12 : null,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: ElevatedButton(
                    onPressed: isLoading ? null : _onSendMoney,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.transparent,
                      shadowColor: Colors.transparent,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    child: isLoading
                        ? const CircularProgressIndicator(color: Colors.white)
                        : const Text(
                            'Initiate Transfer',
                            style: TextStyle(
                              fontFamily: 'Outfit',
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                          ),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  // ── Pending Tab ──────────────────────────────────────────────
  Widget _buildPendingTab() {
    return BlocBuilder<TransfersBloc, TransfersState>(
      builder: (context, state) {
        if (state is TransfersLoading) {
          return const LoadingIndicator(message: 'Loading pending transfers...');
        }
        if (state is TransfersLoaded) {
          final incoming = state.incomingTransfers;
          final outgoing = state.outgoingTransfers;

          if (incoming.isEmpty && outgoing.isEmpty) {
            return const EmptyState(
              icon: Icons.hourglass_empty_rounded,
              title: 'No Pending Transfers',
              subtitle: 'All your transfer requests are completed or expired.',
            );
          }

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (incoming.isNotEmpty) ...[
                _buildHeader('Incoming Requests'),
                const SizedBox(height: 8),
                ...incoming.map((t) => _buildIncomingTile(t)),
                const SizedBox(height: 24),
              ],
              if (outgoing.isNotEmpty) ...[
                _buildHeader('Outgoing Requests'),
                const SizedBox(height: 8),
                ...outgoing.map((t) => _buildOutgoingTile(t)),
              ],
            ],
          );
        }
        return const Center(child: Text('Failed to load transfers.'));
      },
    );
  }

  Widget _buildIncomingTile(PendingTransfer transfer) {
    return Card(
      color: AppColors.darkSurface,
      margin: const EdgeInsets.symmetric(vertical: 6),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
        side: BorderSide(color: Colors.white.withValues(alpha: 0.08)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  transfer.senderName ?? 'Sender',
                  style: const TextStyle(
                    fontFamily: 'Outfit',
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                Text(
                  '${transfer.currency} ${transfer.amount}',
                  style: TextStyle(
                    fontFamily: 'Outfit',
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: AppColors.primary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 6),
            if (transfer.description != null && transfer.description!.isNotEmpty)
              Text(
                transfer.description!,
                style: const TextStyle(color: Colors.white70, fontSize: 13),
              ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () {
                      context.read<TransfersBloc>().add(DeclineTransferEvent(transfer.id));
                    },
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: AppColors.danger),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    child: const Text('Decline', style: TextStyle(color: AppColors.danger)),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () {
                      context.read<TransfersBloc>().add(AcceptTransferEvent(transfer.id));
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.emerald,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    child: const Text('Accept', style: TextStyle(color: Colors.white)),
                  ),
                ),
              ],
            )
          ],
        ),
      ),
    );
  }

  Widget _buildOutgoingTile(PendingTransfer transfer) {
    return Card(
      color: AppColors.darkSurface,
      margin: const EdgeInsets.symmetric(vertical: 6),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
        side: BorderSide(color: Colors.white.withValues(alpha: 0.08)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'To: ${transfer.receiverName ?? 'Recipient'}',
                    style: const TextStyle(
                      fontFamily: 'Outfit',
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Ref: ${transfer.referenceNumber}',
                    style: const TextStyle(color: Colors.white38, fontSize: 12),
                  ),
                  if (transfer.description != null && transfer.description!.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      transfer.description!,
                      style: const TextStyle(color: Colors.white70, fontSize: 13),
                    ),
                  ],
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  '${transfer.currency} ${transfer.amount}',
                  style: const TextStyle(
                    fontFamily: 'Outfit',
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 8),
                TextButton(
                  onPressed: () {
                    context.read<TransfersBloc>().add(CancelTransferEvent(transfer.id));
                  },
                  child: const Text('Cancel', style: TextStyle(color: AppColors.danger)),
                )
              ],
            )
          ],
        ),
      ),
    );
  }

  // ── Convert Currency Tab ─────────────────────────────────────
  Widget _buildConvertTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const SizedBox(height: 10),
          _buildLabel('Convert From'),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: ChoiceChip(
                  label: const Center(child: Text('USD')),
                  selected: _convertFrom == 'USD',
                  selectedColor: AppColors.primary,
                  onSelected: (selected) {
                    setState(() {
                      _convertFrom = 'USD';
                      _calculateValue();
                    });
                  },
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: ChoiceChip(
                  label: const Center(child: Text('IQD')),
                  selected: _convertFrom == 'IQD',
                  selectedColor: AppColors.primary,
                  onSelected: (selected) {
                    setState(() {
                      _convertFrom = 'IQD';
                      _calculateValue();
                    });
                  },
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          _buildLabel('Amount to Convert'),
          const SizedBox(height: 8),
          TextFormField(
            controller: _convertAmountController,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            style: const TextStyle(color: Colors.white),
            decoration: _inputDecoration('0.00', Icons.swap_vert),
            onChanged: (_) => _calculateValue(),
          ),
          const SizedBox(height: 24),

          BlocBuilder<TransfersBloc, TransfersState>(
            builder: (context, state) {
              double rate = 1450.0;
              if (state is TransfersLoaded && state.exchangeRate != null) {
                rate = state.exchangeRate!.rate;
              }

              return Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppColors.darkSurface,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
                ),
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Exchange Rate', style: TextStyle(color: Colors.white54)),
                        Text(
                          '1 USD = $rate IQD',
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                    const Divider(color: Colors.white10, height: 24),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('You will receive', style: TextStyle(color: Colors.white54)),
                        Text(
                          _convertFrom == 'USD'
                              ? Formatters.currency(_convertedValue, 'IQD')
                              : Formatters.currency(_convertedValue, 'USD'),
                          style: TextStyle(
                            color: AppColors.primary,
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Outfit',
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              );
            },
          ),
          const SizedBox(height: 32),

          BlocBuilder<TransfersBloc, TransfersState>(
            builder: (context, state) {
              final isLoading = state is TransferActionLoading;
              return Container(
                height: 52,
                decoration: BoxDecoration(
                  gradient: isLoading ? null : AppColors.primaryGradient,
                  color: isLoading ? Colors.white12 : null,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: ElevatedButton(
                  onPressed: isLoading ? null : _onConvert,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.transparent,
                    shadowColor: Colors.transparent,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  child: isLoading
                      ? const CircularProgressIndicator(color: Colors.white)
                      : const Text(
                          'Convert Now',
                          style: TextStyle(
                            fontFamily: 'Outfit',
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  void _calculateValue() {
    final amt = double.tryParse(_convertAmountController.text.trim()) ?? 0.0;
    final state = context.read<TransfersBloc>().state;
    double rate = 1450.0;
    if (state is TransfersLoaded && state.exchangeRate != null) {
      rate = state.exchangeRate!.rate;
    }

    setState(() {
      if (_convertFrom == 'USD') {
        _convertedValue = amt * rate;
      } else {
        _convertedValue = amt / rate;
      }
    });
  }

  // ── UI Helpers ───────────────────────────────────────────────
  Widget _buildLabel(String text) {
    return Text(
      text,
      style: const TextStyle(
        fontFamily: 'Outfit',
        fontSize: 13,
        fontWeight: FontWeight.w500,
        color: Colors.white60,
      ),
    );
  }

  Widget _buildHeader(String text) {
    return Text(
      text,
      style: const TextStyle(
        fontFamily: 'Outfit',
        fontSize: 18,
        fontWeight: FontWeight.bold,
        color: Colors.white,
      ),
    );
  }

  InputDecoration _inputDecoration(String hint, IconData prefixIcon) {
    return InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(color: Colors.white.withValues(alpha: 0.25)),
      prefixIcon: Icon(prefixIcon, color: Colors.white38, size: 20),
      filled: true,
      fillColor: Colors.white.withValues(alpha: 0.07),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.1)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.1)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: AppColors.primary, width: 1.5),
      ),
    );
  }
}
