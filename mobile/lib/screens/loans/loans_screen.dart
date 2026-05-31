import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../blocs/loans/loans_bloc.dart';
import '../../blocs/loans/loans_event.dart';
import '../../blocs/loans/loans_state.dart';
import '../../blocs/cards/cards_bloc.dart';
import '../../blocs/cards/cards_event.dart';
import '../../blocs/cards/cards_state.dart';
import '../../core/theme/app_colors.dart';
import '../../data/models/loan.dart';
import '../../data/models/card.dart';
import '../../widgets/loading_indicator.dart';
import '../../widgets/empty_state.dart';
import '../../core/utils/formatters.dart';

class LoansTabScreen extends StatefulWidget {
  const LoansTabScreen({super.key});

  @override
  State<LoansTabScreen> createState() => _LoansTabScreenState();
}

class _LoansTabScreenState extends State<LoansTabScreen> {
  @override
  void initState() {
    super.initState();
    context.read<LoansBloc>().add(const LoadLoans());
    context.read<CardsBloc>().add(const LoadCards());
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<LoansBloc, LoansState>(
      listener: (context, state) {
        if (state is LoansActionSuccess) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: AppColors.emerald,
            ),
          );
        } else if (state is LoansError) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: AppColors.danger,
            ),
          );
        }
      },
      child: Scaffold(
        appBar: AppBar(
          title: const Text(
            'Loans & Credits',
            style: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.bold),
          ),
          elevation: 0,
          actions: [
            IconButton(
              icon: const Icon(Icons.add_business_outlined),
              tooltip: 'Apply for Loan',
              onPressed: () => _showApplyLoanDialog(context),
            ),
          ],
        ),
        body: BlocBuilder<LoansBloc, LoansState>(
          builder: (context, state) {
            if (state is LoansLoading) {
              return const LoadingIndicator(message: 'Loading loans...');
            }

            List<Loan> loans = [];
            if (state is LoansLoaded) {
              loans = state.loans;
            }

            if (loans.isEmpty) {
              return Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const EmptyState(
                    icon: Icons.account_balance_outlined,
                    title: 'No Loans Active',
                    subtitle: 'Need capital? Apply for personal, business, or auto loans with low interest rates.',
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: () => _showApplyLoanDialog(context),
                    icon: const Icon(Icons.add),
                    label: const Text('Apply for Loan Now'),
                  ),
                ],
              );
            }

            return RefreshIndicator(
              onRefresh: () async {
                context.read<LoansBloc>().add(const LoadLoans());
                context.read<CardsBloc>().add(const LoadCards());
              },
              child: ListView.builder(
                padding: const EdgeInsets.all(16.0),
                itemCount: loans.length,
                itemBuilder: (context, index) {
                  return _buildLoanCard(loans[index]);
                },
              ),
            );
          },
        ),
      ),
    );
  }

  Widget _buildLoanCard(Loan loan) {
    final bool isApproved = loan.status == 'approved';
    final bool isPending = loan.status == 'pending';
    final bool isPaid = loan.status == 'completed' || loan.status == 'paid';
    final bool isRejected = loan.status == 'rejected';

    Color statusColor = AppColors.amber;
    if (isApproved) statusColor = AppColors.emerald;
    if (isPaid) statusColor = Colors.blue;
    if (isRejected) statusColor = AppColors.danger;

    final double totalPaid = loan.amount + loan.totalInterest - loan.remainingBalance;
    final double totalToPay = loan.amount + loan.totalInterest;
    final double payProgress = totalToPay > 0 ? (totalPaid / totalToPay).clamp(0.0, 1.0) : 0.0;

    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Theme.of(context).dividerColor.withValues(alpha: 0.1)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Row 1: Category & Status Badge
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: statusColor.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Icon(
                        _getCategoryIcon(loan.loanType),
                        color: statusColor,
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          loan.loanType.toUpperCase(),
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Outfit',
                          ),
                        ),
                        Text(
                          'ID: #${loan.id}',
                          style: TextStyle(fontSize: 10, color: Colors.grey.shade500),
                        ),
                      ],
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    loan.status.toUpperCase(),
                    style: TextStyle(
                      color: statusColor,
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Row 2: Loan principal, interest, remaining balance
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('PRINCIPAL', style: TextStyle(color: Colors.grey.shade500, fontSize: 9, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    Text(
                      Formatters.currency(loan.amount, loan.currency),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, fontFamily: 'Outfit'),
                    ),
                  ],
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Text('INTEREST RATE', style: TextStyle(color: Colors.grey.shade500, fontSize: 9, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    Text(
                      '${loan.interestRate}%',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.primary, fontFamily: 'Outfit'),
                    ),
                  ],
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text('MONTHLY DUE', style: TextStyle(color: Colors.grey.shade500, fontSize: 9, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    Text(
                      Formatters.currency(loan.monthlyPayment, loan.currency),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.danger, fontFamily: 'Outfit'),
                    ),
                  ],
                ),
              ],
            ),

            if (isApproved || isPaid) ...[
              const SizedBox(height: 16),
              // Repayment progress
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('REPAYMENT PROGRESS', style: TextStyle(color: Colors.grey.shade500, fontSize: 9, fontWeight: FontWeight.bold)),
                  Text(
                    '${(payProgress * 100).toStringAsFixed(0)}% Repaid',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 10, color: AppColors.emerald),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              ClipRRect(
                borderRadius: BorderRadius.circular(4),
                child: LinearProgressIndicator(
                  value: payProgress,
                  backgroundColor: Colors.grey.withValues(alpha: 0.15),
                  color: AppColors.emerald,
                  minHeight: 8,
                ),
              ),
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Remaining: ${Formatters.currency(loan.remainingBalance, loan.currency)}',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold),
                  ),
                  Text(
                    'Paid: ${Formatters.currency(totalPaid, loan.currency)}',
                    style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                  ),
                ],
              ),
            ],

            if (isApproved) ...[
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.emerald.withValues(alpha: 0.1),
                    foregroundColor: AppColors.emerald,
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: () => _showRepayDialog(loan),
                  icon: const Icon(Icons.payment, size: 16),
                  label: const Text('Make Repayment'),
                ),
              ),
            ],
            
            if (loan.purpose != null && loan.purpose!.isNotEmpty) ...[
              const SizedBox(height: 12),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.grey.withValues(alpha: 0.05),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  'Purpose: ${loan.purpose}',
                  style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontStyle: FontStyle.italic),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  IconData _getCategoryIcon(String category) {
    switch (category.toLowerCase()) {
      case 'personal':
        return Icons.person_outline;
      case 'business':
        return Icons.business_outlined;
      case 'housing':
      case 'home':
      case 'mortgage':
        return Icons.home_outlined;
      case 'auto':
      case 'car':
        return Icons.directions_car_outlined;
      default:
        return Icons.account_balance_wallet_outlined;
    }
  }

  void _showRepayDialog(Loan loan) {
    final amountController = TextEditingController(text: loan.monthlyPayment.toString());

    showDialog(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Text('Loan Repayment', style: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.bold)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Remaining Balance: ${Formatters.currency(loan.remainingBalance, loan.currency)}',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: amountController,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: InputDecoration(
                  labelText: 'Repayment Amount (${loan.currency})',
                  border: const OutlineInputBorder(),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () {
                final double? amt = double.tryParse(amountController.text.trim());
                if (amt != null && amt > 0) {
                  context.read<LoansBloc>().add(PayLoanEvent(
                        loanId: loan.id,
                        amount: amt,
                      ));
                  Navigator.pop(dialogContext);
                }
              },
              child: const Text('Submit Payment'),
            ),
          ],
        );
      },
    );
  }

  void _showApplyLoanDialog(BuildContext context) {
    final cardsState = context.read<CardsBloc>().state;

    List<BankCard> cards = [];
    if (cardsState is CardsLoaded) {
      cards = cardsState.cards;
    }

    if (cards.isEmpty) {
      showDialog(
        context: context,
        builder: (c) => AlertDialog(
          title: const Text('Card Required', style: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.bold)),
          content: const Text(
            'To apply for a loan, you must have an active virtual debit or credit card linked to your account.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(c),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.pop(c);
                // Switch or notify user to go to Cards tab
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Please navigate to the "Cards" tab to order a card first.'),
                  ),
                );
              },
              child: const Text('Get Card'),
            ),
          ],
        ),
      );
      return;
    }

    String selectedCardId = cards.first.id.toString();
    String selectedCategory = 'personal';
    final amountController = TextEditingController();
    final termController = TextEditingController();
    final purposeController = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (modalContext) {
        return StatefulBuilder(
          builder: (context, setState) {
            // Live calculator estimation
            double amount = double.tryParse(amountController.text.trim()) ?? 0.0;
            int term = int.tryParse(termController.text.trim()) ?? 0;
            
            // Interest rate based on category
            double interestRate = 8.50;
            if (selectedCategory == 'home') interestRate = 4.25;
            if (selectedCategory == 'auto') interestRate = 5.75;
            if (selectedCategory == 'business') interestRate = 7.00;
            if (selectedCategory == 'education') interestRate = 3.50;

            double estimatedTotal = amount + (amount * (interestRate / 100));
            double estimatedMonthly = term > 0 ? estimatedTotal / term : 0.0;
            
            final selectedCard = cards.firstWhere((c) => c.id.toString() == selectedCardId);
            final String currencyCode = selectedCard.accountNumber?.contains('IQD') ?? false ? 'IQD' : 'USD';

            return Padding(
              padding: EdgeInsets.only(
                left: 20,
                right: 20,
                top: 24,
                bottom: MediaQuery.of(modalContext).viewInsets.bottom + 24,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Apply for New Loan',
                    style: TextStyle(
                      fontFamily: 'Outfit',
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Select Card
                  const Text('Select Linked Card', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                  const SizedBox(height: 6),
                  DropdownButtonFormField<String>(
                    value: selectedCardId,
                    decoration: InputDecoration(
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    ),
                    items: cards.map((card) {
                      return DropdownMenuItem(
                        value: card.id.toString(),
                        child: Text(
                          '${card.cardBrand.toUpperCase()} ${card.cardType} (•••• ${card.last4}) — $currencyCode',
                          style: const TextStyle(fontSize: 13),
                        ),
                      );
                    }).toList(),
                    onChanged: (val) {
                      if (val != null) {
                        setState(() => selectedCardId = val);
                      }
                    },
                  ),
                  const SizedBox(height: 14),

                  // Select Category
                  const Text('Loan Category', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                  const SizedBox(height: 6),
                  DropdownButtonFormField<String>(
                    value: selectedCategory,
                    decoration: InputDecoration(
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    ),
                    items: const [
                      DropdownMenuItem(value: 'personal', child: Text('Personal Loan (8.5%)')),
                      DropdownMenuItem(value: 'business', child: Text('Business Loan (7.0%)')),
                      DropdownMenuItem(value: 'home', child: Text('Housing / Mortgage (4.25%)')),
                      DropdownMenuItem(value: 'auto', child: Text('Auto Loan (5.75%)')),
                      DropdownMenuItem(value: 'education', child: Text('Education Loan (3.5%)')),
                    ],
                    onChanged: (val) {
                      if (val != null) setState(() => selectedCategory = val);
                    },
                  ),
                  const SizedBox(height: 14),

                  // Amount & Term row
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Amount ($currencyCode)', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                            const SizedBox(height: 6),
                            TextField(
                              controller: amountController,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: InputDecoration(
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                                hintText: '0.00',
                              ),
                              onChanged: (_) => setState(() {}),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Term (Months)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                            const SizedBox(height: 6),
                            TextField(
                              controller: termController,
                              keyboardType: TextInputType.number,
                              decoration: InputDecoration(
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                                hintText: 'e.g. 12, 24',
                              ),
                              onChanged: (_) => setState(() {}),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),

                  // Live Estimate Box
                  if (amount > 0 && term > 0)
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: AppColors.primary.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.primary.withValues(alpha: 0.2)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'ESTIMATED MONTHLY INSTALLMENT',
                            style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: AppColors.primary),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            Formatters.currency(estimatedMonthly, currencyCode),
                            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.primary, fontFamily: 'Outfit'),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Estimated total payback: ${Formatters.currency(estimatedTotal, currencyCode)} (Interest rate $interestRate%)',
                            style: TextStyle(fontSize: 10, color: Colors.grey.shade600),
                          ),
                        ],
                      ),
                    ),
                  const SizedBox(height: 14),

                  // Purpose input
                  const Text('Purpose / Description', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: purposeController,
                    maxLines: 2,
                    decoration: InputDecoration(
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      hintText: 'e.g. Business expansion, purchasing furniture',
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Submit button
                  SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        backgroundColor: AppColors.primary,
                      ),
                      onPressed: () {
                        final amt = double.tryParse(amountController.text.trim()) ?? 0.0;
                        final termVal = int.tryParse(termController.text.trim()) ?? 0;
                        final purpose = purposeController.text.trim();

                        if (amt > 0 && termVal > 0 && purpose.isNotEmpty) {
                          context.read<LoansBloc>().add(ApplyLoanEvent({
                            'card_id': int.parse(selectedCardId),
                            'loan_type': selectedCategory,
                            'amount': amt,
                            'term_months': termVal,
                            'purpose': purpose,
                          }));
                          Navigator.pop(modalContext);
                        } else {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text('Please fill all fields with valid data.'),
                              backgroundColor: AppColors.danger,
                            ),
                          );
                        }
                      },
                      child: const Text(
                        'Submit Loan Application',
                        style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }
}
