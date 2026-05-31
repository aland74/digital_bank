import 'package:flutter_bloc/flutter_bloc.dart';
import '../../data/repositories/card_repository.dart';
import 'cards_event.dart';
import 'cards_state.dart';

class CardsBloc extends Bloc<CardsEvent, CardsState> {
  final CardRepository _cardRepository;

  CardsBloc({
    required CardRepository cardRepository,
  })  : _cardRepository = cardRepository,
        super(const CardsInitial()) {
    on<LoadCards>(_onLoadCards);
    on<CreateCardEvent>(_onCreateCard);
    on<FreezeCardEvent>(_onFreezeCard);
    on<UnfreezeCardEvent>(_onUnfreezeCard);
    on<RevealCardEvent>(_onRevealCard);
    on<UpdateLimitsEvent>(_onUpdateLimits);
    on<ToggleContactlessEvent>(_onToggleContactless);
    on<ToggleOnlineEvent>(_onToggleOnline);
    on<ToggleInternationalEvent>(_onToggleInternational);
    on<RequestPinChangeEvent>(_onRequestPinChange);
  }

  Future<void> _onLoadCards(
    LoadCards event,
    Emitter<CardsState> emit,
  ) async {
    emit(const CardsLoading());
    try {
      final result = await _cardRepository.getCards();
      if (result.success && result.data != null) {
        emit(CardsLoaded(cards: result.data!));
      } else {
        emit(CardsError(result.errorMessage));
      }
    } catch (e) {
      emit(CardsError(e.toString()));
    }
  }

  Future<void> _onCreateCard(
    CreateCardEvent event,
    Emitter<CardsState> emit,
  ) async {
    emit(const CardsActionLoading());
    try {
      final result = await _cardRepository.createCard(event.cardData);
      if (result.success) {
        emit(CardsActionSuccess(result.message ?? 'Card order submitted! Admin will review your request.'));
        add(const LoadCards());
      } else {
        emit(CardsError(result.errorMessage));
      }
    } catch (e) {
      emit(CardsError(e.toString()));
    }
  }

  Future<void> _onFreezeCard(
    FreezeCardEvent event,
    Emitter<CardsState> emit,
  ) async {
    emit(const CardsActionLoading());
    try {
      final result = await _cardRepository.freezeCard(event.cardId);
      if (result.success) {
        emit(const CardsActionSuccess('Card frozen successfully.'));
        add(const LoadCards());
      } else {
        emit(CardsError(result.errorMessage));
      }
    } catch (e) {
      emit(CardsError(e.toString()));
    }
  }

  Future<void> _onUnfreezeCard(
    UnfreezeCardEvent event,
    Emitter<CardsState> emit,
  ) async {
    emit(const CardsActionLoading());
    try {
      final result = await _cardRepository.unfreezeCard(event.cardId);
      if (result.success) {
        emit(const CardsActionSuccess('Card unfrozen successfully.'));
        add(const LoadCards());
      } else {
        emit(CardsError(result.errorMessage));
      }
    } catch (e) {
      emit(CardsError(e.toString()));
    }
  }

  Future<void> _onRevealCard(
    RevealCardEvent event,
    Emitter<CardsState> emit,
  ) async {
    emit(const CardsActionLoading());
    try {
      final result = await _cardRepository.revealCard(event.cardId, event.pin);
      if (result.success && result.data != null) {
        emit(CardRevealedState(cardId: event.cardId, credentials: result.data!));
      } else {
        emit(CardsError(result.errorMessage));
      }
    } catch (e) {
      emit(CardsError(e.toString()));
    }
  }

  Future<void> _onUpdateLimits(
    UpdateLimitsEvent event,
    Emitter<CardsState> emit,
  ) async {
    emit(const CardsActionLoading());
    try {
      final result = await _cardRepository.updateLimits(
        event.cardId,
        event.dailyLimit,
        event.monthlyLimit,
      );
      if (result.success) {
        emit(const CardsActionSuccess('Limits updated successfully.'));
        add(const LoadCards());
      } else {
        emit(CardsError(result.errorMessage));
      }
    } catch (e) {
      emit(CardsError(e.toString()));
    }
  }

  Future<void> _onToggleContactless(
    ToggleContactlessEvent event,
    Emitter<CardsState> emit,
  ) async {
    emit(const CardsActionLoading());
    try {
      final result = await _cardRepository.toggleContactless(event.cardId);
      if (result.success) {
        emit(const CardsActionSuccess('Contactless toggled successfully.'));
        add(const LoadCards());
      } else {
        emit(CardsError(result.errorMessage));
      }
    } catch (e) {
      emit(CardsError(e.toString()));
    }
  }

  Future<void> _onToggleOnline(
    ToggleOnlineEvent event,
    Emitter<CardsState> emit,
  ) async {
    emit(const CardsActionLoading());
    try {
      final result = await _cardRepository.toggleOnline(event.cardId);
      if (result.success) {
        emit(const CardsActionSuccess('Online transactions toggled successfully.'));
        add(const LoadCards());
      } else {
        emit(CardsError(result.errorMessage));
      }
    } catch (e) {
      emit(CardsError(e.toString()));
    }
  }

  Future<void> _onToggleInternational(
    ToggleInternationalEvent event,
    Emitter<CardsState> emit,
  ) async {
    emit(const CardsActionLoading());
    try {
      final result = await _cardRepository.toggleInternational(event.cardId);
      if (result.success) {
        emit(const CardsActionSuccess('International transactions toggled successfully.'));
        add(const LoadCards());
      } else {
        emit(CardsError(result.errorMessage));
      }
    } catch (e) {
      emit(CardsError(e.toString()));
    }
  }

  Future<void> _onRequestPinChange(
    RequestPinChangeEvent event,
    Emitter<CardsState> emit,
  ) async {
    emit(const CardsActionLoading());
    try {
      final result = await _cardRepository.requestPinChange(
        event.cardId,
        event.reason,
        event.description,
      );
      if (result.success) {
        emit(const CardsActionSuccess('PIN change request submitted successfully.'));
        add(const LoadCards());
      } else {
        emit(CardsError(result.errorMessage));
      }
    } catch (e) {
      emit(CardsError(e.toString()));
    }
  }
}
