import 'package:equatable/equatable.dart';
import '../../data/models/pending_transfer.dart';
import '../../data/models/exchange_rate.dart';

abstract class TransfersState extends Equatable {
  const TransfersState();

  @override
  List<Object?> get props => [];
}

class TransfersInitial extends TransfersState {
  const TransfersInitial();
}

class TransfersLoading extends TransfersState {
  const TransfersLoading();
}

class TransfersLoaded extends TransfersState {
  final List<PendingTransfer> incomingTransfers;
  final List<PendingTransfer> outgoingTransfers;
  final ExchangeRate? exchangeRate;
  final String? message; // For success alerts

  const TransfersLoaded({
    required this.incomingTransfers,
    required this.outgoingTransfers,
    this.exchangeRate,
    this.message,
  });

  TransfersLoaded copyWith({
    List<PendingTransfer>? incomingTransfers,
    List<PendingTransfer>? outgoingTransfers,
    ExchangeRate? exchangeRate,
    String? message,
  }) {
    return TransfersLoaded(
      incomingTransfers: incomingTransfers ?? this.incomingTransfers,
      outgoingTransfers: outgoingTransfers ?? this.outgoingTransfers,
      exchangeRate: exchangeRate ?? this.exchangeRate,
      message: message,
    );
  }

  @override
  List<Object?> get props => [incomingTransfers, outgoingTransfers, exchangeRate, message];
}

class TransfersError extends TransfersState {
  final String message;

  const TransfersError(this.message);

  @override
  List<Object?> get props => [message];
}

class TransferActionLoading extends TransfersState {
  const TransferActionLoading();
}

class TransferActionSuccess extends TransfersState {
  final String message;

  const TransferActionSuccess(this.message);

  @override
  List<Object?> get props => [message];
}
