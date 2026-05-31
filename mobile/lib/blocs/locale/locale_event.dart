import 'package:equatable/equatable.dart';

abstract class LocaleEvent extends Equatable {
  const LocaleEvent();

  @override
  List<Object?> get props => [];
}

class ChangeLocale extends LocaleEvent {
  final String languageCode;

  const ChangeLocale({required this.languageCode});

  @override
  List<Object?> get props => [languageCode];
}
