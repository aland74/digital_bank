import 'dart:ui';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../core/storage/local_storage.dart';
import 'locale_event.dart';
import 'locale_state.dart';

class LocaleBloc extends Bloc<LocaleEvent, LocaleState> {
  LocaleBloc() : super(const LocaleState()) {
    on<ChangeLocale>(_onChangeLocale);
    _loadSaved();
  }

  void _loadSaved() {
    final saved = LocalStorage().getLocale();
    if (saved != null) {
      add(ChangeLocale(languageCode: saved));
    }
  }

  Future<void> _onChangeLocale(
    ChangeLocale event,
    Emitter<LocaleState> emit,
  ) async {
    emit(LocaleState(locale: Locale(event.languageCode)));
    await LocalStorage().saveLocale(event.languageCode);
  }
}
