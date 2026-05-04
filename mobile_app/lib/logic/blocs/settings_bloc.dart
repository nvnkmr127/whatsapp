import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

// --- Events ---
abstract class SettingsEvent {}
class LoadSettings extends SettingsEvent {}
class ToggleTheme extends SettingsEvent {}
class ChangeLanguage extends SettingsEvent {
  final String languageCode;
  ChangeLanguage(this.languageCode);
}

// --- State ---
class SettingsState {
  final ThemeMode themeMode;
  final String languageCode;

  SettingsState({this.themeMode = ThemeMode.system, this.languageCode = 'en'});

  SettingsState copyWith({ThemeMode? themeMode, String? languageCode}) {
    return SettingsState(
      themeMode: themeMode ?? this.themeMode,
      languageCode: languageCode ?? this.languageCode,
    );
  }
}

// --- Bloc ---
class SettingsBloc extends Bloc<SettingsEvent, SettingsState> {
  final _storage = const FlutterSecureStorage();

  SettingsBloc() : super(SettingsState()) {
    on<LoadSettings>(_onLoad);
    on<ToggleTheme>(_onToggleTheme);
    on<ChangeLanguage>(_onChangeLanguage);
  }

  Future<void> _onLoad(LoadSettings event, Emitter<SettingsState> emit) async {
    final theme = await _storage.read(key: 'theme_mode') ?? 'system';
    final lang = await _storage.read(key: 'language_code') ?? 'en';

    emit(state.copyWith(
      themeMode: _parseTheme(theme),
      languageCode: lang,
    ));
  }

  Future<void> _onToggleTheme(ToggleTheme event, Emitter<SettingsState> emit) async {
    final newMode = state.themeMode == ThemeMode.light ? ThemeMode.dark : ThemeMode.light;
    await _storage.write(key: 'theme_mode', value: newMode.name);
    emit(state.copyWith(themeMode: newMode));
  }

  Future<void> _onChangeLanguage(ChangeLanguage event, Emitter<SettingsState> emit) async {
    await _storage.write(key: 'language_code', value: event.languageCode);
    emit(state.copyWith(languageCode: event.languageCode));
  }

  ThemeMode _parseTheme(String val) {
    if (val == 'light') return ThemeMode.light;
    if (val == 'dark') return ThemeMode.dark;
    return ThemeMode.system;
  }
}
