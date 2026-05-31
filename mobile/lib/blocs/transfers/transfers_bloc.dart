import 'package:flutter_bloc/flutter_bloc.dart';
import '../../data/repositories/transfer_repository.dart';
import '../../data/repositories/account_repository.dart';
import '../../data/models/pending_transfer.dart';
import 'transfers_event.dart';
import 'transfers_state.dart';

class TransfersBloc extends Bloc<TransfersEvent, TransfersState> {
  final TransferRepository _transferRepository;

  TransfersBloc({
    required TransferRepository transferRepository,
  })  : _transferRepository = transferRepository,
        super(const TransfersInitial()) {
    on<LoadTransfers>(_onLoadTransfers);
    on<CreateTransferEvent>(_onCreateTransfer);
    on<AcceptTransferEvent>(_onAcceptTransfer);
    on<DeclineTransferEvent>(_onDeclineTransfer);
    on<CancelTransferEvent>(_onCancelTransfer);
    on<ConvertCurrencyEvent>(_onConvertCurrency);
  }

  Future<void> _onLoadTransfers(
    LoadTransfers event,
    Emitter<TransfersState> emit,
  ) async {
    emit(const TransfersLoading());
    try {
      final pendingResult = await _transferRepository.getPendingTransfers();
      final exchangeResult = await _transferRepository.getExchangeRate();

      if (pendingResult.success && pendingResult.data != null) {
        final data = pendingResult.data!;
        final incomingList = data['incoming'] as List? ?? [];
        final outgoingList = data['outgoing'] as List? ?? [];

        final List<PendingTransfer> incomingTransfers = incomingList
            .map((e) => PendingTransfer.fromJson(
                e is Map<String, dynamic> ? e : e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{}))
            .toList();
        final List<PendingTransfer> outgoingTransfers = outgoingList
            .map((e) => PendingTransfer.fromJson(
                e is Map<String, dynamic> ? e : e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{}))
            .toList();

        emit(TransfersLoaded(
          incomingTransfers: incomingTransfers,
          outgoingTransfers: outgoingTransfers,
          exchangeRate: exchangeResult.success ? exchangeResult.data : null,
        ));
      } else {
        emit(TransfersError(pendingResult.errorMessage));
      }
    } catch (e) {
      emit(TransfersError(e.toString()));
    }
  }

  Future<void> _onCreateTransfer(
    CreateTransferEvent event,
    Emitter<TransfersState> emit,
  ) async {
    emit(const TransferActionLoading());
    final result = await _transferRepository.createTransfer({
      'from_account_id': event.fromAccountId,
      'to_account_number': event.toAccountNumber,
      'amount': event.amount,
      'description': event.description,
    });

    if (result.success) {
      emit(TransferActionSuccess(result.message ?? 'Transfer initiated successfully.'));
      add(const LoadTransfers());
    } else {
      emit(TransfersError(result.errorMessage));
    }
  }

  Future<void> _onAcceptTransfer(
    AcceptTransferEvent event,
    Emitter<TransfersState> emit,
  ) async {
    emit(const TransferActionLoading());
    final result = await _transferRepository.acceptTransfer(event.transferId);
    if (result.success) {
      emit(TransferActionSuccess('Transfer accepted successfully.'));
      add(const LoadTransfers());
    } else {
      emit(TransfersError(result.errorMessage));
    }
  }

  Future<void> _onDeclineTransfer(
    DeclineTransferEvent event,
    Emitter<TransfersState> emit,
  ) async {
    emit(const TransferActionLoading());
    final result = await _transferRepository.declineTransfer(event.transferId);
    if (result.success) {
      emit(TransferActionSuccess('Transfer declined successfully.'));
      add(const LoadTransfers());
    } else {
      emit(TransfersError(result.errorMessage));
    }
  }

  Future<void> _onCancelTransfer(
    CancelTransferEvent event,
    Emitter<TransfersState> emit,
  ) async {
    emit(const TransferActionLoading());
    final result = await _transferRepository.cancelTransfer(event.transferId);
    if (result.success) {
      emit(TransferActionSuccess('Transfer cancelled successfully.'));
      add(const LoadTransfers());
    } else {
      emit(TransfersError(result.errorMessage));
    }
  }

  Future<void> _onConvertCurrency(
    ConvertCurrencyEvent event,
    Emitter<TransfersState> emit,
  ) async {
    emit(const TransferActionLoading());
    final result = await _transferRepository.convertCurrency(
      event.fromCurrency,
      event.amount,
    );
    if (result.success) {
      emit(const TransferActionSuccess('Currency converted successfully.'));
      add(const LoadTransfers());
    } else {
      emit(TransfersError(result.errorMessage));
    }
  }
}
