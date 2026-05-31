import 'package:equatable/equatable.dart';

abstract class LoansEvent extends Equatable {
  const LoansEvent();

  @override
  List<Object?> get props => [];
}

class LoadLoans extends LoansEvent {
  const LoadLoans();
}

class ApplyLoanEvent extends LoansEvent {
  final Map<String, dynamic> loanData;

  const ApplyLoanEvent(this.loanData);

  @override
  List<Object?> get props => [loanData];
}

class PayLoanEvent extends LoansEvent {
  final int loanId;
  final double amount;

  const PayLoanEvent({required this.loanId, required this.amount});

  @override
  List<Object?> get props => [loanId, amount];
}
