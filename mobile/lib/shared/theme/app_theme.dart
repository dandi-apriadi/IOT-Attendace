import 'package:flutter/material.dart';

class AppTheme {
  AppTheme._();

  static const primary = Color(0xFF2563EB);

  static ThemeData light() {
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(seedColor: primary),
      appBarTheme: const AppBarTheme(centerTitle: false, elevation: 0),
      inputDecorationTheme: const InputDecorationTheme(border: OutlineInputBorder()),
    );
  }

  static const statusColors = {
    'Hadir': Color(0xFF16A34A),
    'Telat': Color(0xFFF59E0B),
    'Sakit': Color(0xFF0EA5E9),
    'Izin': Color(0xFF8B5CF6),
    'Alpa': Color(0xFFDC2626),
    'Pending': Color(0xFF9CA3AF),
  };

  static Color statusColor(String status) => statusColors[status] ?? const Color(0xFF6B7280);
}
