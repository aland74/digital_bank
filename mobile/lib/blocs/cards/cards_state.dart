import 'package:equatable/equatable.dart';
import '../../data/models/card.dart';

abstract class CardsState extends Equatable {
  const CardsState();

  @override
  List<Object?> get props => [];
}

class CardsInitial extends CardsState {
  const CardsInitial();
}

class CardsLoading extends CardsState {
  const CardsLoading();
}

class CardsLoaded extends CardsState {
  final List<BankCard> cards;
  final String? message;
  final Map<String, dynamic>? revealedCredentials;

  const CardsLoaded({
    required this.cards,
    this.message,
    this.revealedCredentials,
  });

  CardsLoaded copyWith({
    List<BankCard>? cards,
    String? message,
    Map<String, dynamic>? revealedCredentials,
  }) {
    return CardsLoaded(
      cards: cards ?? this.cards,
      message: message,
      revealedCredentials: revealedCredentials ?? this.revealedCredentials,
    );
  }

  @override
  List<Object?> get props => [cards, message, revealedCredentials];
}

class CardsError extends CardsState {
  final String message;

  const CardsError(this.message);

  @override
  List<Object?> get props => [message];
}

class CardsActionLoading extends CardsState {
  const CardsActionLoading();
}

class CardsActionSuccess extends CardsState {
  final String message;

  const CardsActionSuccess(this.message);

  @override
  List<Object?> get props => [message];
}

class CardRevealedState extends CardsState {
  final int cardId;
  final Map<String, dynamic> credentials;

  const CardRevealedState({required this.cardId, required this.credentials});

  @override
  List<Object?> get props => [cardId, credentials];
}
