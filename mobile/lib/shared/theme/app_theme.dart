import 'package:flutter/material.dart';

/// Mirrors the web dashboard's palette (public/css/premium-design.css)
/// so the mobile app feels like the same product, not a generic Material app.
class AppTheme {
  AppTheme._();

  /// "--kinetic-yellow" in the web CSS -- despite the name it's the brand teal.
  static const primary = Color(0xFF129B78);
  static const primaryContainer = Color(0xFFE6F6EC);

  /// "--primary-blue" -- the web's sidebar/header navy.
  static const navy = Color(0xFF10213D);
  static const navyContainer = Color(0xFF132846);

  static const bgMain = Color(0xFFF7F9FB);
  static const bgSurface = Color(0xFFFFFFFF);
  static const textPrimary = Color(0xFF191C1E);
  static const textMuted = Color(0xFF43474F);

  static ThemeData light() {
    final colorScheme = ColorScheme.fromSeed(
      seedColor: primary,
      primary: primary,
      secondary: navy,
      surface: bgSurface,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: bgMain,
      appBarTheme: const AppBarTheme(
        centerTitle: false,
        elevation: 0,
        backgroundColor: navy,
        foregroundColor: Colors.white,
        titleTextStyle: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w600),
        iconTheme: IconThemeData(color: Colors.white),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: bgSurface,
        indicatorColor: primaryContainer,
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return TextStyle(
            fontSize: 12,
            fontWeight: selected ? FontWeight.w600 : FontWeight.w400,
            color: selected ? primary : textMuted,
          );
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return IconThemeData(color: selected ? primary : textMuted);
        }),
      ),
      cardTheme: CardThemeData(
        color: bgSurface,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: navy.withValues(alpha: 0.06)),
        ),
        margin: EdgeInsets.zero,
      ),
      inputDecorationTheme: InputDecorationTheme(
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: primary, width: 1.5),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: primary,
          foregroundColor: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
          padding: const EdgeInsets.symmetric(vertical: 14),
        ),
      ),
      textTheme: const TextTheme().apply(bodyColor: textPrimary, displayColor: textPrimary),
    );
  }

  /// Background/text pair matching the web's pill-shaped status badges.
  static const statusPairs = {
    'Hadir': (bg: Color(0xFFE6F6EC), fg: Color(0xFF1DB173)),
    'Telat': (bg: Color(0xFFFFF4E6), fg: Color(0xFF705D00)),
    'Alpa': (bg: Color(0xFFFDECEC), fg: Color(0xFFBA1A1A)),
    'Sakit': (bg: Color(0xFFE7F3FE), fg: Color(0xFF0B6BB3)),
    'Izin': (bg: Color(0xFFF1EBFE), fg: Color(0xFF6D28D9)),
    'Pending': (bg: Color(0xFFEFF1F3), fg: Color(0xFF5B6168)),
  };

  static const statusColors = {
    'Hadir': Color(0xFF1DB173),
    'Telat': Color(0xFF705D00),
    'Sakit': Color(0xFF0B6BB3),
    'Izin': Color(0xFF6D28D9),
    'Alpa': Color(0xFFBA1A1A),
    'Pending': Color(0xFF5B6168),
    'online': Color(0xFF1DB173),
    'offline': Color(0xFFBA1A1A),
    'stale': Color(0xFFF59E0B),
    'disabled': Color(0xFF9CA3AF),
    'unknown': Color(0xFF5B6168),
  };

  static Color statusColor(String status) => statusColors[status] ?? const Color(0xFF6B7280);

  static ({Color bg, Color fg}) statusPair(String status) =>
      statusPairs[status] ?? (bg: const Color(0xFFEFF1F3), fg: statusColor(status));
}
