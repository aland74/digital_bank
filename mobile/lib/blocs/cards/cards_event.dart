import 'package:equatable/equatable.dart';

abstract class CardsEvent extends Equatable {
  const CardsEvent();

  @override
  List<Object?> get props => [];
}

class LoadCards extends CardsEvent {
  const LoadCards();
}

class CreateCardEvent extends CardsEvent {
  final Map<String, dynamic> cardData;

  const CreateCardEvent(this.cardData);

  @override
  List<Object?> get props => [cardData];
}

class FreezeCardEvent extends CardsEvent {
  final int cardId;

  const FreezeCardEvent(this.cardId);

  @override
  List<Object?> get props => [cardId];
}

class UnfreezeCardEvent extends CardsEvent {
  final int cardId;

  const UnfreezeCardEvent(this.cardId);

  @override
  List<Object?> get props => [cardId];
}

class RevealCardEvent extends CardsEvent {
  final int cardId;
  final String pin;

  const RevealCardEvent({required this.cardId, required this.pin});

  @override
  List<Object?> get props => [cardId, pin];
}

class UpdateLimitsEvent extends CardsEvent {
  final int cardId;
  final double dailyLimit;
  final double monthlyLimit;

  const UpdateLimitsEvent({
    required this.cardId,
    required this.dailyLimit,
    required this.monthlyLimit,
  });

  @override
  List<Object?> get props => [cardId, dailyLimit, monthlyLimit];
}

class ToggleContactlessEvent extends CardsEvent {
  final int cardId;

  const ToggleContactlessEvent(this.cardId);

  @override
  List<Object?> get props => [cardId];
}

class ToggleOnlineEvent extends CardsEvent {
  final int cardId;

  const ToggleOnlineEvent(this.cardId);

  @override
  List<Object?> get props => [cardId];
}

class ToggleInternationalEvent extends CardsEvent {
  final int cardId;

  const ToggleInternationalEvent(this.cardId);

  @override
  List<Object?> get props => [cardId];
}

class RequestPinChangeEvent extends CardsEvent {
  final int cardId;
  final String reason;
  final String description;

  const RequestPinChangeEvent({
    required this.cardId,
    required this.reason,
    required this.description,
  });

  @override
  List<Object?> get props => [cardId, reason, description];
}
