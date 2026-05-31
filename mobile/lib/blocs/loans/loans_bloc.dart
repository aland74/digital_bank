import 'package:flutter_bloc/flutter_bloc.dart';
import '../../data/repositories/loan_repository.dart';
import 'loans_event.dart';
import 'loans_state.dart';

class LoansBloc extends Bloc<LoansEvent, LoansState> {
  final LoanRepository _loanRepository;

  LoansBloc({
    required LoanRepository loanRepository,
  })  : _loanRepository = loanRepository,
        super(const LoansInitial()) {
    on<LoadLoans>(_onLoadLoans);
    on<ApplyLoanEvent>(_onApplyLoan);
    on<PayLoanEvent>(_onPayLoan);
  }

  Future<void> _onLoadLoans(
    LoadLoans event,
    Emitter<LoansState> emit,
  ) async {
    emit(const LoansLoading());
    try {
      final result = await _loanRepository.getLoans();
      if (result.success && result.data != null) {
        emit(LoansLoaded(loans: result.data!));
      } else {
        emit(LoansError(result.errorMessage));
      }
    } catch (e) {
      emit(LoansError(e.toString()));
    }
  }

  Future<void> _onApplyLoan(
    ApplyLoanEvent event,
    Emitter<LoansState> emit,
  ) async {
    emit(const LoansActionLoading());
    try {
      final result = await _loanRepository.applyLoan(event.loanData);
      if (result.success) {
        emit(const LoansActionSuccess('Loan application submitted successfully.'));
        add(const LoadLoans());
      } else {
        emit(LoansError(result.errorMessage));
      }
    } catch (e) {
      emit(LoansError(e.toString()));
    }
  }

  Future<void> _onPayLoan(
    PayLoanEvent event,
    Emitter<LoansState> emit,
  ) async {
    emit(const LoansActionLoading());
    try {
      final result = await _loanRepository.payLoan(event.loanId, event.amount);
      if (result.success) {
        emit(const LoansActionSuccess('Repayment made successfully.'));
        add(const LoadLoans());
      } else {
        emit(LoansError(result.errorMessage));
      }
    } catch (e) {
      emit(LoansError(e.toString()));
    }
  }
}
