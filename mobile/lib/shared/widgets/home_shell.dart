import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../features/admin/screens/student_report_screen.dart';
import '../../features/attendance_history/screens/attendance_history_screen.dart';
import '../../features/dashboard/screens/dashboard_screen.dart';
import '../../features/device_health/screens/device_health_screen.dart';
import '../../features/live_monitoring/screens/live_monitoring_screen.dart';

final homeTabIndexProvider = StateProvider<int>((ref) => 0);

/// Renders only the active tab (no IndexedStack) so that switching tabs
/// unmounts the previous screen -- its autoDispose polling provider is then
/// disposed and its Timer cancelled, instead of polling silently in the
/// background while hidden.
class HomeShell extends ConsumerWidget {
  const HomeShell({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final index = ref.watch(homeTabIndexProvider);
    // "Laporan" (student attendance report, admin/dosen) matches the web's
    // Reports menu -- Audit Log (admin-only) is reachable from inside it
    // rather than taking its own bottom-nav slot.
    final screens = [
      const DashboardScreen(),
      const LiveMonitoringScreen(),
      const DeviceHealthScreen(),
      const AttendanceHistoryScreen(),
      const StudentReportScreen(),
    ];
    final safeIndex = index.clamp(0, screens.length - 1);

    return Scaffold(
      body: screens[safeIndex],
      bottomNavigationBar: NavigationBar(
        selectedIndex: safeIndex,
        onDestinationSelected: (i) => ref.read(homeTabIndexProvider.notifier).state = i,
        destinations: const [
          NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'Dashboard'),
          NavigationDestination(icon: Icon(Icons.sensors_outlined), selectedIcon: Icon(Icons.sensors), label: 'Live'),
          NavigationDestination(icon: Icon(Icons.router_outlined), selectedIcon: Icon(Icons.router), label: 'Device'),
          NavigationDestination(icon: Icon(Icons.history), label: 'Riwayat'),
          NavigationDestination(icon: Icon(Icons.summarize_outlined), selectedIcon: Icon(Icons.summarize), label: 'Laporan'),
        ],
      ),
    );
  }
}
