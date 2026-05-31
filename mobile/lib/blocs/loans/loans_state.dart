import 'package:equatable/equatable.dart';
import '../../data/models/loan.dart';

abstract class LoansState extends Equatable {
  const LoansState();

  @override
  List<Object?> get props => [];
}

class LoansInitial extends LoansState {
  const LoansInitial();
}

class LoansLoading extends LoansState {
  const LoansLoading();
}

class LoansLoaded extends LoansState {
  final List<Loan> loans;
  final String? message;

  const LoansLoaded({required this.loans, this.message});

  LoansLoaded copyWith({
    List<Loan>? loans,
    String? message,
  }) {
    return LoansLoaded(
      loans: loans ?? this.loans,
      message: message,
    );
  }

  @override
  List<Object?> get props => [loans, message];
}

class LoansError extends LoansState {
  final String message;

  const LoansError(this.message);

  @override
  List<Object?> get props => [message];
}

class LoansActionLoading extends LoansState {
  const LoansActionLoading();
}

class LoansActionSuccess extends LoansState {
  final String message;

  const LoansActionSuccess(this.message);

  @override
  List<Object?> get props => [message];
}
