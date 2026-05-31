import 'package:flutter/material.dart';
import 'app_colors.dart';

class AppTheme {
  static ThemeData get darkTheme {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      fontFamily: 'Outfit',
      colorScheme: const ColorScheme.dark(
        primary: AppColors.primary,
        secondary: AppColors.secondary,
        surface: AppColors.darkSurface,
        error: AppColors.danger,
        onPrimary: Colors.white,
        onSecondary: Colors.white,
        onSurface: AppColors.darkOnSurface,
        onError: Colors.white,
      ),
      scaffoldBackgroundColor: AppColors.darkBackground,
      cardColor: AppColors.darkCard,
      dividerColor: AppColors.darkBorder,
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.darkBackground,
        foregroundColor: AppColors.darkOnBackground,
        elevation: 0,
        centerTitle: true,
        titleTextStyle: TextStyle(
          fontFamily: 'Outfit',
          fontSize: 18,
          fontWeight: FontWeight.w600,
          color: AppColors.darkOnBackground,
        ),
      ),
      cardTheme: CardThemeData(
        color: AppColors.darkCard,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: AppColors.darkBorder),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          textStyle: const TextStyle(
            fontFamily: 'Outfit',
            fontSize: 16,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.primary,
          side: const BorderSide(color: AppColors.primary),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          textStyle: const TextStyle(
            fontFamily: 'Outfit',
            fontSize: 16,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.primary,
          textStyle: const TextStyle(
            fontFamily: 'Outfit',
            fontSize: 14,
            fontWeight: FontWeight.w500,
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.darkSurfaceVariant,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.darkBorder),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.darkBorder),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.danger),
        ),
        labelStyle: const TextStyle(
          fontFamily: 'Outfit',
          color: AppColors.darkOnSurfaceVariant,
        ),
        hintStyle: const TextStyle(
          fontFamily: 'Outfit',
          color: AppColors.darkOnSurfaceVariant,
        ),
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: AppColors.darkSurface,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.darkOnSurfaceVariant,
        type: BottomNavigationBarType.fixed,
        elevation: 0,
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: AppColors.darkSurface,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: AppColors.darkSurfaceVariant,
        contentTextStyle: const TextStyle(
          fontFamily: 'Outfit',
          color: AppColors.darkOnSurface,
        ),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        behavior: SnackBarBehavior.floating,
      ),
      textTheme: const TextTheme(
        displayLarge: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w700, color: AppColors.darkOnBackground),
        displayMedium: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w700, color: AppColors.darkOnBackground),
        displaySmall: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w700, color: AppColors.darkOnBackground),
        headlineLarge: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w600, color: AppColors.darkOnBackground),
        headlineMedium: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w600, color: AppColors.darkOnBackground),
        headlineSmall: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w600, color: AppColors.darkOnBackground),
        titleLarge: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w600, color: AppColors.darkOnBackground),
        titleMedium: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w500, color: AppColors.darkOnBackground),
        titleSmall: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w500, color: AppColors.darkOnSurface),
        bodyLarge: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w400, color: AppColors.darkOnSurface),
        bodyMedium: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w400, color: AppColors.darkOnSurface),
        bodySmall: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w400, color: AppColors.darkOnSurfaceVariant),
        labelLarge: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w500, color: AppColors.darkOnSurface),
        labelMedium: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w500, color: AppColors.darkOnSurfaceVariant),
        labelSmall: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w500, color: AppColors.darkOnSurfaceVariant),
      ),
    );
  }

  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      fontFamily: 'Outfit',
      colorScheme: const ColorScheme.light(
        primary: AppColors.primary,
        secondary: AppColors.secondary,
        surface: AppColors.lightSurface,
        error: AppColors.danger,
        onPrimary: Colors.white,
        onSecondary: Colors.white,
        onSurface: AppColors.lightOnSurface,
        onError: Colors.white,
      ),
      scaffoldBackgroundColor: AppColors.lightBackground,
      cardColor: AppColors.lightCard,
      dividerColor: AppColors.lightBorder,
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.lightBackground,
        foregroundColor: AppColors.lightOnBackground,
        elevation: 0,
        centerTitle: true,
        titleTextStyle: TextStyle(
          fontFamily: 'Outfit',
          fontSize: 18,
          fontWeight: FontWeight.w600,
          color: AppColors.lightOnBackground,
        ),
      ),
      cardTheme: CardThemeData(
        color: AppColors.lightCard,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: AppColors.lightBorder),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          textStyle: const TextStyle(
            fontFamily: 'Outfit',
            fontSize: 16,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.primary,
          side: const BorderSide(color: AppColors.primary),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          textStyle: const TextStyle(
            fontFamily: 'Outfit',
            fontSize: 16,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.primary,
          textStyle: const TextStyle(
            fontFamily: 'Outfit',
            fontSize: 14,
            fontWeight: FontWeight.w500,
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.lightSurfaceVariant,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.lightBorder),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.lightBorder),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.danger),
        ),
        labelStyle: const TextStyle(
          fontFamily: 'Outfit',
          color: AppColors.lightOnSurfaceVariant,
        ),
        hintStyle: const TextStyle(
          fontFamily: 'Outfit',
          color: AppColors.lightOnSurfaceVariant,
        ),
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: AppColors.lightSurface,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.lightOnSurfaceVariant,
        type: BottomNavigationBarType.fixed,
        elevation: 0,
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: AppColors.lightSurface,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: AppColors.lightSurfaceVariant,
        contentTextStyle: const TextStyle(
          fontFamily: 'Outfit',
          color: AppColors.lightOnSurface,
        ),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        behavior: SnackBarBehavior.floating,
      ),
      textTheme: const TextTheme(
        displayLarge: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w700, color: AppColors.lightOnBackground),
        displayMedium: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w700, color: AppColors.lightOnBackground),
        displaySmall: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w700, color: AppColors.lightOnBackground),
        headlineLarge: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w600, color: AppColors.lightOnBackground),
        headlineMedium: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w600, color: AppColors.lightOnBackground),
        headlineSmall: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w600, color: AppColors.lightOnBackground),
        titleLarge: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w600, color: AppColors.lightOnBackground),
        titleMedium: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w500, color: AppColors.lightOnBackground),
        titleSmall: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w500, color: AppColors.lightOnSurface),
        bodyLarge: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w400, color: AppColors.lightOnSurface),
        bodyMedium: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w400, color: AppColors.lightOnSurface),
        bodySmall: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w400, color: AppColors.lightOnSurfaceVariant),
        labelLarge: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w500, color: AppColors.lightOnSurface),
        labelMedium: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w500, color: AppColors.lightOnSurfaceVariant),
        labelSmall: TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w500, color: AppColors.lightOnSurfaceVariant),
      ),
    );
  }
}
