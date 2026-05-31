import 'package:equatable/equatable.dart';

abstract class TransfersEvent extends Equatable {
  const TransfersEvent();

  @override
  List<Object?> get props => [];
}

class LoadTransfers extends TransfersEvent {
  const LoadTransfers();
}

class CreateTransferEvent extends TransfersEvent {
  final int fromAccountId;
  final String toAccountNumber;
  final double amount;
  final String description;

  const CreateTransferEvent({
    required this.fromAccountId,
    required this.toAccountNumber,
    required this.amount,
    required this.description,
  });

  @override
  List<Object?> get props => [fromAccountId, toAccountNumber, amount, description];
}

class AcceptTransferEvent extends TransfersEvent {
  final int transferId;

  const AcceptTransferEvent(this.transferId);

  @override
  List<Object?> get props => [transferId];
}

class DeclineTransferEvent extends TransfersEvent {
  final int transferId;

  const DeclineTransferEvent(this.transferId);

  @override
  List<Object?> get props => [transferId];
}

class CancelTransferEvent extends TransfersEvent {
  final int transferId;

  const CancelTransferEvent(this.transferId);

  @override
  List<Object?> get props => [transferId];
}

class ConvertCurrencyEvent extends TransfersEvent {
  final String fromCurrency;
  final double amount;

  const ConvertCurrencyEvent({
    required this.fromCurrency,
    required this.amount,
  });

  @override
  List<Object?> get props => [fromCurrency, amount];
}
