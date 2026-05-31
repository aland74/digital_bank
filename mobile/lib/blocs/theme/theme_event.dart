import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';

abstract class ThemeEvent extends Equatable {
  const ThemeEvent();

  @override
  List<Object?> get props => [];
}

class ToggleTheme extends ThemeEvent {
  const ToggleTheme();
}

class LoadTheme extends ThemeEvent {
  final ThemeMode themeMode;
  const LoadTheme(this.themeMode);

  @override
  List<Object?> get props => [themeMode];
}
