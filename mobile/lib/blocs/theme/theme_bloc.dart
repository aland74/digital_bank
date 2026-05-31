import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../core/storage/local_storage.dart';
import 'theme_event.dart';
import 'theme_state.dart';

class ThemeBloc extends Bloc<ThemeEvent, ThemeState> {
  ThemeBloc() : super(const ThemeState()) {
    on<ToggleTheme>(_onToggleTheme);
    on<LoadTheme>(_onLoadTheme);
    _loadSaved();
  }

  void _loadSaved() {
    final saved = LocalStorage().getThemeMode();
    if (saved != null) {
      final mode = saved == 'light' ? ThemeMode.light : ThemeMode.dark;
      add(LoadTheme(mode));
    }
  }

  void _onLoadTheme(LoadTheme event, Emitter<ThemeState> emit) {
    emit(ThemeState(themeMode: event.themeMode));
  }

  Future<void> _onToggleTheme(
    ToggleTheme event,
    Emitter<ThemeState> emit,
  ) async {
    final newMode =
        state.themeMode == ThemeMode.dark ? ThemeMode.light : ThemeMode.dark;
    emit(ThemeState(themeMode: newMode));
    await LocalStorage().saveThemeMode(
      newMode == ThemeMode.dark ? 'dark' : 'light',
    );
  }
}
