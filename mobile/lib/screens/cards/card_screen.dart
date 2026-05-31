import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../blocs/cards/cards_bloc.dart';
import '../../blocs/cards/cards_event.dart';
import '../../blocs/cards/cards_state.dart';
import '../../blocs/dashboard/dashboard_bloc.dart';
import '../../blocs/dashboard/dashboard_state.dart';
import '../../blocs/dashboard/dashboard_event.dart';
import '../../blocs/auth/auth_bloc.dart';
import '../../blocs/auth/auth_state.dart';
import '../../core/theme/app_colors.dart';
import '../../data/models/card.dart';
import '../../data/models/account.dart';
import '../../widgets/loading_indicator.dart';
import '../../widgets/empty_state.dart';
import '../../core/utils/formatters.dart';
import '../../data/repositories/card_repository.dart';

class CardsTabScreen extends StatefulWidget {
  const CardsTabScreen({super.key});

  @override
  State<CardsTabScreen> createState() => _CardsTabScreenState();
}

class _CardsTabScreenState extends State<CardsTabScreen> {
  final PageController _pageController = PageController();
  int _activePageIndex = 0;

  @override
  void initState() {
    super.initState();
    context.read<CardsBloc>().add(const LoadCards());
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<CardsBloc, CardsState>(
      listener: (context, state) {
        if (state is CardsActionSuccess) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: AppColors.emerald,
            ),
          );
        } else if (state is CardsError) {
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
            'My Cards',
            style: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.bold),
          ),
          elevation: 0,
          actions: [
            IconButton(
              icon: const Icon(Icons.add_circle_outline),
              tooltip: 'New Card',
              onPressed: () => _showCreateCardDialog(context),
            ),
          ],
        ),
        body: BlocBuilder<CardsBloc, CardsState>(
          builder: (context, state) {
            if (state is CardsLoading) {
              return const LoadingIndicator(message: 'Loading cards...');
            }

            List<BankCard> cards = [];
            if (state is CardsLoaded) {
              cards = state.cards;
            } else if (state is CardRevealedState) {
              // Get the loaded cards from a previous state or trigger load
              // Actually we can load them or store them.
              // To handle reveal cleanly, we can show a dialog or overlay with revealed info.
              // Let's reload cards after we finish revealing or handle it gracefully.
            }

            if (cards.isEmpty) {
              return Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const EmptyState(
                    icon: Icons.credit_card_outlined,
                    title: 'No Cards Yet',
                    subtitle: 'Order a virtual or physical card — admin will review and approve your request.',
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: () => _showCreateCardDialog(context),
                    icon: const Icon(Icons.add),
                    label: const Text('Order New Card'),
                  ),
                ],
              );
            }

            final selectedCard = cards[_activePageIndex < cards.length ? _activePageIndex : 0];

            return SingleChildScrollView(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Cards Carousel
                  SizedBox(
                    height: 220,
                    child: PageView.builder(
                      controller: _pageController,
                      itemCount: cards.length,
                      onPageChanged: (index) {
                        setState(() {
                          _activePageIndex = index;
                        });
                      },
                      itemBuilder: (context, index) {
                        return _buildVisualCard(cards[index]);
                      },
                    ),
                  ),

                  // Carousel Indicators
                  if (cards.length > 1)
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: List.generate(
                        cards.length,
                        (index) => Container(
                          width: 8,
                          height: 8,
                          margin: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: _activePageIndex == index
                                ? AppColors.primary
                                : Colors.grey.withValues(alpha: 0.4),
                          ),
                        ),
                      ),
                    ),

                  const SizedBox(height: 24),

                  // Card Info & Controls
                  Text(
                    'Card Settings & Security',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontFamily: 'Outfit',
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 16),

                  _buildQuickActions(selectedCard),

                  const SizedBox(height: 24),

                  _buildSecuritySwitches(selectedCard),

                  const SizedBox(height: 24),

                  _buildLimitsSection(selectedCard),
                  const SizedBox(height: 32),
                ],
              ),
            );
          },
        ),
      ),
    );
  }

  Widget _buildVisualCard(BankCard card) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bool frozen = card.isFrozen;
    final bool pending = card.status.toLowerCase() == 'pending_approval';
    final bool rejected = card.status.toLowerCase() == 'rejected';
    
    // Choose beautiful gradient based on card brand or type
    Gradient gradient = card.cardType.toLowerCase() == 'credit'
        ? AppColors.secondaryGradient
        : AppColors.primaryGradient;

    if (frozen) {
      gradient = LinearGradient(
        colors: [Colors.grey.shade600, Colors.grey.shade800],
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
      );
    } else if (pending) {
      gradient = LinearGradient(
        colors: [Colors.amber.shade700, Colors.orange.shade800],
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
      );
    } else if (rejected) {
      gradient = LinearGradient(
        colors: [Colors.red.shade700, Colors.red.shade900],
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
      );
    }

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: gradient,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.15),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    card.cardType.toUpperCase(),
                    style: const TextStyle(
                      color: Colors.white70,
                      fontWeight: FontWeight.bold,
                      fontSize: 12,
                      letterSpacing: 1.5,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    card.cardBrand.toUpperCase(),
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w900,
                      fontSize: 18,
                      fontFamily: 'Outfit',
                    ),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Row(
                  children: [
                    Icon(
                      frozen ? Icons.lock : pending ? Icons.hourglass_top : rejected ? Icons.cancel : Icons.check_circle,
                      color: Colors.white,
                      size: 14,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      frozen ? 'FROZEN' : pending ? 'PENDING' : rejected ? 'REJECTED' : 'ACTIVE',
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 10,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '•••• •••• •••• ${card.last4}',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 22,
                  fontWeight: FontWeight.w500,
                  letterSpacing: 2,
                ),
              ),
              const SizedBox(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'CARDHOLDER',
                        style: TextStyle(color: Colors.white60, fontSize: 8),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        card.cardholderName.toUpperCase(),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'EXPIRES',
                        style: TextStyle(color: Colors.white60, fontSize: 8),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        card.expiry,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildQuickActions(BankCard card) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Theme.of(context).dividerColor.withValues(alpha: 0.1)),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 16.0),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: [
            _QuickActionBtn(
              icon: card.isFrozen ? Icons.lock_open : Icons.lock_outline,
              label: card.isFrozen ? 'Unfreeze' : 'Freeze',
              color: card.isFrozen ? AppColors.emerald : AppColors.amber,
              onTap: () {
                if (card.isFrozen) {
                  context.read<CardsBloc>().add(UnfreezeCardEvent(card.id));
                } else {
                  context.read<CardsBloc>().add(FreezeCardEvent(card.id));
                }
              },
            ),
            _QuickActionBtn(
              icon: Icons.visibility_outlined,
              label: 'Reveal Info',
              color: AppColors.primary,
              onTap: () => _showRevealDetailsDialog(card),
            ),
            _QuickActionBtn(
              icon: Icons.vpn_key_outlined,
              label: 'Change PIN',
              color: AppColors.secondary,
              onTap: () => _showPinChangeDialog(card),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSecuritySwitches(BankCard card) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Theme.of(context).dividerColor.withValues(alpha: 0.1)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Security Controls',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
                fontFamily: 'Outfit',
              ),
            ),
            const SizedBox(height: 12),
            _buildSwitchTile(
              icon: Icons.contactless_outlined,
              title: 'Contactless Payments',
              subtitle: 'Allow waves and NFC payments',
              value: card.isContactless,
              onChanged: (_) => context.read<CardsBloc>().add(ToggleContactlessEvent(card.id)),
            ),
            const Divider(),
            _buildSwitchTile(
              icon: Icons.shopping_cart_outlined,
              title: 'Online Transactions',
              subtitle: 'Enable e-commerce & internet purchasing',
              value: card.isOnlineEnabled,
              onChanged: (_) => context.read<CardsBloc>().add(ToggleOnlineEvent(card.id)),
            ),
            const Divider(),
            _buildSwitchTile(
              icon: Icons.public,
              title: 'International Spending',
              subtitle: 'Allow foreign currency & overseas ATMs',
              value: card.isInternationalEnabled,
              onChanged: (_) => context.read<CardsBloc>().add(ToggleInternationalEvent(card.id)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSwitchTile({
    required IconData icon,
    required String title,
    required String subtitle,
    required bool value,
    required ValueChanged<bool> onChanged,
  }) {
    return SwitchListTile(
      secondary: Icon(icon, color: AppColors.primary),
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
      subtitle: Text(subtitle, style: const TextStyle(fontSize: 11)),
      value: value,
      onChanged: onChanged,
      contentPadding: EdgeInsets.zero,
    );
  }

  Widget _buildLimitsSection(BankCard card) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Theme.of(context).dividerColor.withValues(alpha: 0.1)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Transaction Limits',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                    fontFamily: 'Outfit',
                  ),
                ),
                TextButton.icon(
                  onPressed: () => _showUpdateLimitsDialog(card),
                  icon: const Icon(Icons.edit, size: 16),
                  label: const Text('Update'),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _buildLimitBar(
                    label: 'DAILY LIMIT',
                    amount: card.dailyLimit,
                    currency: card.accountNumber?.contains('IQD') ?? false ? 'IQD' : 'USD',
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildLimitBar(
                    label: 'MONTHLY LIMIT',
                    amount: card.monthlyLimit,
                    currency: card.accountNumber?.contains('IQD') ?? false ? 'IQD' : 'USD',
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLimitBar({
    required String label,
    required double amount,
    required String currency,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            color: Colors.grey.shade500,
            fontSize: 10,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          Formatters.currency(amount, currency),
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            fontFamily: 'Outfit',
          ),
        ),
      ],
    );
  }

  void _showCreateCardDialog(BuildContext context) {
    final authState = context.read<AuthBloc>().state;
    String userName = '';
    bool isKycVerified = false;
    if (authState is AuthAuthenticated) {
      userName = authState.user.name;
      isKycVerified = authState.user.isKycVerified;
    }

    String selectedCardBrand = 'visa';
    String selectedCardType = 'debit';
    String selectedAccountType = 'checking';
    String selectedCurrency = 'USD';
    final nameController = TextEditingController(text: userName);
    final formKey = GlobalKey<FormState>();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.darkSurface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        side: BorderSide(color: Colors.white10),
      ),
      builder: (modalContext) {
        return StatefulBuilder(
          builder: (context, setState) {
            return SingleChildScrollView(
              child: Padding(
                padding: EdgeInsets.only(
                  left: 20,
                  right: 20,
                  top: 24,
                  bottom: MediaQuery.of(modalContext).viewInsets.bottom + 24,
                ),
                child: Form(
                  key: formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Order New Card',
                        style: TextStyle(
                          fontFamily: 'Outfit',
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 20),

                      // Cardholder Name
                      const Text('Cardholder Name', style: TextStyle(fontWeight: FontWeight.w600, color: Colors.white70)),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: nameController,
                        style: const TextStyle(color: Colors.white),
                        decoration: InputDecoration(
                          hintText: 'Enter cardholder name',
                          hintStyle: const TextStyle(color: Colors.white24),
                          filled: true,
                          fillColor: Colors.white.withValues(alpha: 0.05),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        validator: (v) => v == null || v.isEmpty ? 'Cardholder name is required' : null,
                      ),
                      const SizedBox(height: 16),

                      // Account Type
                      const Text('Account Type', style: TextStyle(fontWeight: FontWeight.w600, color: Colors.white70)),
                      const SizedBox(height: 8),
                      DropdownButtonFormField<String>(
                        value: selectedAccountType,
                        dropdownColor: AppColors.darkSurface,
                        style: const TextStyle(color: Colors.white),
                        decoration: InputDecoration(
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          filled: true,
                          fillColor: Colors.white.withValues(alpha: 0.05),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        ),
                        items: const [
                          DropdownMenuItem(value: 'checking', child: Text('Checking Account')),
                          DropdownMenuItem(value: 'savings', child: Text('Savings Account')),
                          DropdownMenuItem(value: 'business', child: Text('Business Account')),
                          DropdownMenuItem(value: 'fixed_deposit', child: Text('Fixed Deposit')),
                        ],
                        onChanged: (val) {
                          if (val != null) setState(() => selectedAccountType = val);
                        },
                      ),
                      const SizedBox(height: 16),

                      // Currency
                      const Text('Currency', style: TextStyle(fontWeight: FontWeight.w600, color: Colors.white70)),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(
                            child: _buildSelectCard(
                              title: 'USD (\$)',
                              selected: selectedCurrency == 'USD',
                              onTap: () => setState(() => selectedCurrency = 'USD'),
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: _buildSelectCard(
                              title: 'IQD (د.ع)',
                              selected: selectedCurrency == 'IQD',
                              onTap: () => setState(() => selectedCurrency = 'IQD'),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),

                      // Select Brand
                      const Text('Card Brand', style: TextStyle(fontWeight: FontWeight.w600, color: Colors.white70)),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(
                            child: _buildSelectCard(
                              title: 'Visa',
                              selected: selectedCardBrand == 'visa',
                              onTap: () => setState(() => selectedCardBrand = 'visa'),
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: _buildSelectCard(
                              title: 'Mastercard',
                              selected: selectedCardBrand == 'mastercard',
                              onTap: () => setState(() => selectedCardBrand = 'mastercard'),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),

                      // Select Card Type
                      const Text('Card Type', style: TextStyle(fontWeight: FontWeight.w600, color: Colors.white70)),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(
                            child: _buildSelectCard(
                              title: 'Debit',
                              selected: selectedCardType == 'debit',
                              onTap: () => setState(() => selectedCardType = 'debit'),
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: _buildSelectCard(
                              title: 'Credit',
                              selected: selectedCardType == 'credit',
                              onTap: () => setState(() => selectedCardType = 'credit'),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 24),

                      // Issue card button
                      SizedBox(
                        width: double.infinity,
                        height: 50,
                        child: ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            backgroundColor: AppColors.primary,
                          ),
                          onPressed: () {
                            if (!isKycVerified) {
                              Navigator.pop(modalContext); // Close bottom sheet
                              _showKycRequiredDialog(context);
                              return;
                            }
                            if (formKey.currentState?.validate() ?? false) {
                              context.read<CardsBloc>().add(CreateCardEvent({
                                'card_type': selectedCardType,
                                'card_brand': selectedCardBrand,
                                'cardholder_name': nameController.text.trim(),
                                'account_type': selectedAccountType,
                                'currency': selectedCurrency,
                              }));
                              // Trigger reload dashboard to get the new account
                              Future.delayed(const Duration(seconds: 1), () {
                                if (context.mounted) {
                                  context.read<DashboardBloc>().add(const LoadDashboard());
                                }
                              });
                              Navigator.pop(modalContext);
                            }
                          },
                          child: const Text(
                            'Confirm Card Order',
                            style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  void _showKycRequiredDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          backgroundColor: AppColors.darkSurface,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
            side: const BorderSide(color: Colors.white10),
          ),
          title: const Row(
            children: [
              Icon(Icons.warning_amber_rounded, color: AppColors.amber, size: 28),
              SizedBox(width: 8),
              Text(
                'KYC Verification Required',
                style: TextStyle(
                  fontFamily: 'Outfit',
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                  fontSize: 18,
                ),
              ),
            ],
          ),
          content: const Text(
            'Your identity must be verified before you can order a bank card. Please upload your Passport and National ID to activate full account features.',
            style: TextStyle(color: Colors.white70, fontSize: 14),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text('Cancel', style: TextStyle(color: Colors.white38)),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              onPressed: () async {
                Navigator.pop(dialogContext);
                await Navigator.pushNamed(context, '/kyc-upload');
                if (context.mounted) {
                  context.read<AuthBloc>().add(const CheckAuthStatus());
                }
              },
              child: const Text(
                'Verify Now',
                style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildSelectCard({
    required String title,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 60,
        decoration: BoxDecoration(
          border: Border.all(
            color: selected ? AppColors.primary : Colors.grey.withValues(alpha: 0.3),
            width: selected ? 2 : 1,
          ),
          borderRadius: BorderRadius.circular(12),
          color: selected ? AppColors.primary.withValues(alpha: 0.1) : Colors.transparent,
        ),
        alignment: Alignment.center,
        child: Text(
          title,
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: selected ? AppColors.primary : Colors.grey.shade600,
          ),
        ),
      ),
    );
  }

  void _showUpdateLimitsDialog(BankCard card) {
    final dailyController = TextEditingController(text: card.dailyLimit.toString());
    final monthlyController = TextEditingController(text: card.monthlyLimit.toString());

    showDialog(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Text('Update Limits', style: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.bold)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: dailyController,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(
                  labelText: 'Daily Limit',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: monthlyController,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(
                  labelText: 'Monthly Limit',
                  border: OutlineInputBorder(),
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
                final double? daily = double.tryParse(dailyController.text.trim());
                final double? monthly = double.tryParse(monthlyController.text.trim());
                if (daily != null && monthly != null) {
                  context.read<CardsBloc>().add(UpdateLimitsEvent(
                        cardId: card.id,
                        dailyLimit: daily,
                        monthlyLimit: monthly,
                      ));
                  Navigator.pop(dialogContext);
                }
              },
              child: const Text('Save'),
            ),
          ],
        );
      },
    );
  }

  void _showRevealDetailsDialog(BankCard card) {
    final pinController = TextEditingController();
    bool obscure = true;

    showDialog(
      context: context,
      builder: (dialogContext) {
        return StatefulBuilder(
          builder: (context, setState) {
            return AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              title: const Text('Verify PIN / Password', style: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.bold)),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Please enter your card PIN to view full details securely.', style: TextStyle(fontSize: 12)),
                  const SizedBox(height: 16),
                  TextField(
                    controller: pinController,
                    obscureText: obscure,
                    keyboardType: TextInputType.number,
                    maxLength: 4,
                    decoration: InputDecoration(
                      labelText: 'Card PIN',
                      border: const OutlineInputBorder(),
                      suffixIcon: IconButton(
                        icon: Icon(obscure ? Icons.visibility_off : Icons.visibility),
                        onPressed: () => setState(() => obscure = !obscure),
                      ),
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
                  onPressed: () async {
                    final String pin = pinController.text.trim();
                    if (pin.length == 4) {
                      Navigator.pop(dialogContext);
                      _executeReveal(card.id, pin);
                    }
                  },
                  child: const Text('Verify'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  void _executeReveal(int cardId, String pin) async {
    // Show a loading dialog
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (c) => const Center(child: CircularProgressIndicator()),
    );

    try {
      final cardRepo = CardRepository();
      final result = await cardRepo.revealCard(cardId, pin);
      Navigator.pop(context); // Pop loading

      if (result.success && result.data != null) {
        final data = result.data!;
        _showFullDetailsDialog(data);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result.errorMessage),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    } catch (e) {
      Navigator.pop(context); // Pop loading
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString()),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  void _showFullDetailsDialog(Map<String, dynamic> data) {
    showDialog(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Row(
            children: [
              Icon(Icons.security, color: AppColors.emerald),
              SizedBox(width: 8),
              Text('Card Credentials', style: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.bold)),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildDetailRow('CARD NUMBER', data['card_number'] ?? 'N/A', isSecret: true),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _buildDetailRow('EXPIRY', data['expiry'] ?? 'N/A'),
                  ),
                  Expanded(
                    child: _buildDetailRow('CVV', data['cvv'] ?? 'N/A'),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              _buildDetailRow('CURRENT PIN', data['pin'] ?? 'N/A'),
              const SizedBox(height: 16),
              const Text(
                'This information is highly confidential. Do not share it with anyone.',
                style: TextStyle(color: Colors.red, fontSize: 10, fontWeight: FontWeight.bold),
              ),
            ],
          ),
          actions: [
            ElevatedButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text('Close'),
            ),
          ],
        );
      },
    );
  }

  Widget _buildDetailRow(String label, String value, {bool isSecret = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            color: Colors.grey.shade500,
            fontSize: 9,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 4),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          decoration: BoxDecoration(
            color: Colors.grey.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                value,
                style: TextStyle(
                  fontFamily: 'Courier',
                  fontSize: isSecret ? 16 : 14,
                  fontWeight: FontWeight.bold,
                ),
              ),
              IconButton(
                icon: const Icon(Icons.copy, size: 16),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                onPressed: () {
                  // Copy to clipboard
                  // Clipboard.setData(ClipboardData(text: value));
                },
              ),
            ],
          ),
        ),
      ],
    );
  }

  void _showPinChangeDialog(BankCard card) {
    final reasonController = TextEditingController();
    final descController = TextEditingController();

    showDialog(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Text('Request PIN Change', style: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.bold)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: reasonController,
                decoration: const InputDecoration(
                  labelText: 'Reason for Change',
                  border: OutlineInputBorder(),
                  hintText: 'e.g. Pin forgotten, security compromised',
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: descController,
                maxLines: 2,
                decoration: const InputDecoration(
                  labelText: 'Detailed Explanation',
                  border: OutlineInputBorder(),
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
                final reason = reasonController.text.trim();
                final desc = descController.text.trim();
                if (reason.isNotEmpty) {
                  context.read<CardsBloc>().add(RequestPinChangeEvent(
                        cardId: card.id,
                        reason: reason,
                        description: desc,
                      ));
                  Navigator.pop(dialogContext);
                }
              },
              child: const Text('Submit Request'),
            ),
          ],
        );
      },
    );
  }
}

class _QuickActionBtn extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  const _QuickActionBtn({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(height: 6),
          Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}
