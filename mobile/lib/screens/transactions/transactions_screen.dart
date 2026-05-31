import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../data/models/transaction.dart';
import '../../data/repositories/transaction_repository.dart';
import '../../widgets/loading_indicator.dart';
import '../../widgets/empty_state.dart';

class TransactionsScreen extends StatefulWidget {
  const TransactionsScreen({super.key});

  @override
  State<TransactionsScreen> createState() => _TransactionsScreenState();
}

class _TransactionsScreenState extends State<TransactionsScreen> {
  final TransactionRepository _txnRepo = TransactionRepository();
  final ScrollController _scrollController = ScrollController();

  final List<Transaction> _transactions = [];
  bool _isLoading = false;
  bool _hasMore = true;
  int _currentPage = 1;

  @override
  void initState() {
    super.initState();
    _loadMoreTransactions();
    _scrollController.addListener(() {
      if (_scrollController.position.pixels >=
              _scrollController.position.maxScrollExtent - 200 &&
          !_isLoading &&
          _hasMore) {
        _loadMoreTransactions();
      }
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _loadMoreTransactions() async {
    if (_isLoading) return;
    setState(() => _isLoading = true);

    try {
      final response = await _txnRepo.getTransactions(page: _currentPage);
      if (response.success && response.data != null) {
        final data = response.data!;
        final list = data['data'] as List? ?? [];
        final lastPage = data['last_page'] as int? ?? 1;

        final newTxns = list
            .map((e) => Transaction.fromJson(
                e is Map<String, dynamic> ? e : e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{}))
            .toList();

        setState(() {
          _transactions.addAll(newTxns);
          _hasMore = _currentPage < lastPage;
          if (_hasMore) {
            _currentPage++;
          }
        });
      }
    } catch (_) {
      // Error handling
    } finally {
      setState(() => _isLoading = false);
    }
  }

  void _showTransactionDetails(Transaction txn) {
    showModalBottomSheet(
      context: context,
      backgroundColor: AppColors.darkSurface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        side: BorderSide(color: Colors.white10),
      ),
      builder: (context) {
        final isCredit = txn.isCredit;
        return Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Center(
                child: Container(
                  width: 50,
                  height: 5,
                  decoration: BoxDecoration(
                    color: Colors.white24,
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
              ),
              const SizedBox(height: 24),
              Text(
                txn.description ?? txn.type.toUpperCase(),
                style: const TextStyle(
                  color: Colors.white,
                  fontFamily: 'Outfit',
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              Text(
                '${isCredit ? "+" : "-"}${txn.currency} ${txn.amount}',
                style: TextStyle(
                  color: isCredit ? AppColors.emerald : AppColors.danger,
                  fontFamily: 'Outfit',
                  fontSize: 26,
                  fontWeight: FontWeight.bold,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              const Divider(color: Colors.white10),
              const SizedBox(height: 12),
              _buildDetailRow('Transaction Type', txn.type.toUpperCase()),
              _buildDetailRow('Reference Number', txn.referenceNumber),
              _buildDetailRow('Status', txn.status.toUpperCase()),
              _buildDetailRow(
                'Date & Time',
                txn.createdAt != null
                    ? Formatters.dateTime(DateTime.parse(txn.createdAt!))
                    : 'N/A',
              ),
              if (txn.channel != null) _buildDetailRow('Channel', txn.channel!),
              const SizedBox(height: 16),
            ],
          ),
        );
      },
    );
  }

  Widget _buildDetailRow(String label, String val) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.white54, fontSize: 14)),
          Text(
            val,
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w600,
              fontSize: 14,
              fontFamily: 'Outfit',
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.darkBackground,
      appBar: AppBar(
        backgroundColor: AppColors.darkSurface,
        elevation: 0,
        title: const Text(
          'All Transactions',
          style: TextStyle(
            fontFamily: 'Outfit',
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new, color: Colors.white70, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: _transactions.isEmpty && !_isLoading
          ? const EmptyState(
              icon: Icons.receipt_long_outlined,
              title: 'No Transactions',
              subtitle: 'You have not made any transactions yet.',
            )
          : ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.all(16),
              itemCount: _transactions.length + (_hasMore ? 1 : 0),
              itemBuilder: (context, index) {
                if (index == _transactions.length) {
                  return const Padding(
                    padding: EdgeInsets.symmetric(vertical: 24),
                    child: Center(child: CircularProgressIndicator()),
                  );
                }

                final txn = _transactions[index];
                final isCredit = txn.isCredit;

                return ListTile(
                  contentPadding: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
                  leading: Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: (isCredit ? AppColors.emerald : AppColors.danger)
                          .withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(
                      isCredit ? Icons.arrow_downward : Icons.arrow_upward,
                      color: isCredit ? AppColors.emerald : AppColors.danger,
                      size: 22,
                    ),
                  ),
                  title: Text(
                    txn.description ?? txn.type,
                    style: const TextStyle(
                      color: Colors.white,
                      fontFamily: 'Outfit',
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  subtitle: Text(
                    txn.createdAt != null
                        ? Formatters.relativeTime(DateTime.parse(txn.createdAt!))
                        : '',
                    style: const TextStyle(color: Colors.white38, fontSize: 13),
                  ),
                  trailing: Text(
                    '${isCredit ? "+" : "-"}${txn.currency} ${txn.amount}',
                    style: TextStyle(
                      color: isCredit ? AppColors.emerald : AppColors.danger,
                      fontFamily: 'Outfit',
                      fontSize: 15,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  onTap: () => _showTransactionDetails(txn),
                );
              },
            ),
    );
  }
}
