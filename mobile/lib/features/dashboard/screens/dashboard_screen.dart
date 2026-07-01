import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../shared/theme/app_theme.dart';
import '../../../shared/widgets/error_banner.dart';
import '../../../shared/widgets/status_badge.dart';
import '../../auth/providers/auth_provider.dart';
import '../models/dashboard_summary_model.dart';
import '../providers/dashboard_provider.dart';
import '../widgets/attendance_trend_section.dart';
import '../widgets/iot_donut_chart.dart';
import '../widgets/percentage_bar_chart.dart';
import '../widgets/weekly_line_chart.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(dashboardProvider);
    final user = ref.watch(authProvider).user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Logout',
            onPressed: () => ref.read(authProvider.notifier).logout(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(dashboardProvider.notifier).refreshNow(),
        child: state.loading && !state.hasData
            ? const Center(child: CircularProgressIndicator())
            : ListView(
                padding: EdgeInsets.zero,
                children: [
                  _HeaderBanner(userName: user?.name, role: user?.role, summary: state.data),
                  if (state.error != null) Padding(padding: const EdgeInsets.all(12), child: ErrorBanner(message: state.error!)),
                  if (state.data != null) _DashboardBody(summary: state.data!, isAdmin: user?.isAdmin ?? false),
                  const SizedBox(height: 24),
                ],
              ),
      ),
    );
  }
}

class _HeaderBanner extends StatelessWidget {
  const _HeaderBanner({required this.userName, required this.role, required this.summary});

  final String? userName;
  final String? role;
  final DashboardSummaryModel? summary;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
      decoration: const BoxDecoration(
        color: AppTheme.navy,
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(28)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Halo, ${userName ?? '-'}',
            style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 4),
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                decoration: BoxDecoration(color: AppTheme.primary, borderRadius: BorderRadius.circular(999)),
                child: Text(
                  (role ?? '-').toUpperCase(),
                  style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 0.5),
                ),
              ),
              if (summary?.semesterAktif != null) ...[
                const SizedBox(width: 8),
                Icon(Icons.school_outlined, size: 14, color: Colors.white.withValues(alpha: 0.75)),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(
                    summary!.semesterAktif!,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 12),
                  ),
                ),
              ],
            ],
          ),
          if (summary != null) ...[
            const SizedBox(height: 20),
            Row(
              children: [
                Expanded(child: _HeaderStat(icon: Icons.how_to_reg, label: 'Hadir Hari Ini', value: '${summary!.hadirHariIni}')),
                Container(width: 1, height: 34, color: Colors.white24),
                Expanded(child: _HeaderStat(icon: Icons.schedule, label: 'Sesi Aktif', value: '${summary!.sesiAktif}')),
                Container(width: 1, height: 34, color: Colors.white24),
                Expanded(
                  child: _HeaderStat(
                    icon: Icons.router,
                    label: 'Device Online',
                    value: '${summary!.deviceOnline}/${summary!.deviceAktif}',
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class _HeaderStat extends StatelessWidget {
  const _HeaderStat({required this.icon, required this.label, required this.value});

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, color: AppTheme.primary, size: 20),
        const SizedBox(height: 6),
        Text(value, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 2),
        Text(label, textAlign: TextAlign.center, style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 11)),
      ],
    );
  }
}

class _DashboardBody extends StatelessWidget {
  const _DashboardBody({required this.summary, required this.isAdmin});

  final DashboardSummaryModel summary;
  final bool isAdmin;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 20, 16, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const AttendanceTrendSection(),
          const SizedBox(height: 16),
          if (isAdmin && summary.weeklyChart != null && !summary.weeklyChart!.isEmpty)
            _SectionCard(
              title: 'Tren Kehadiran Mingguan',
              child: WeeklyLineChart(chart: summary.weeklyChart!),
            ),
          if (isAdmin && summary.iotChart != null) ...[
            const SizedBox(height: 16),
            _SectionCard(
              title: 'Status Perangkat IoT',
              child: IotDonutChart(chart: summary.iotChart!),
            ),
          ],
          if (!isAdmin && summary.dosenClassChart != null && !summary.dosenClassChart!.isEmpty) ...[
            _SectionCard(
              title: 'Partisipasi per Kelas (Bulan Ini)',
              child: PercentageBarChart(chart: summary.dosenClassChart!),
            ),
            const SizedBox(height: 16),
          ],
          if (!isAdmin && summary.dosenCourseChart != null && !summary.dosenCourseChart!.isEmpty) ...[
            _SectionCard(
              title: 'Performa Kehadiran per Mata Kuliah',
              child: PercentageBarChart(chart: summary.dosenCourseChart!),
            ),
          ],
          const SizedBox(height: 16),
          _SectionCard(
            title: 'Absensi Terbaru',
            padded: false,
            child: summary.latestAbsensi.isEmpty
                ? const Padding(padding: EdgeInsets.all(20), child: Text('Belum ada data absensi.'))
                : Column(
                    children: [
                      for (final item in summary.latestAbsensi) _LatestAbsensiTile(item: item),
                    ],
                  ),
          ),
          const SizedBox(height: 16),
          _SectionCard(
            title: 'Perangkat Terpantau',
            padded: false,
            child: summary.recentDevices.isEmpty
                ? const Padding(padding: EdgeInsets.all(20), child: Text('Belum ada device terdaftar.'))
                : Column(
                    children: [
                      for (final device in summary.recentDevices) _RecentDeviceTile(device: device),
                    ],
                  ),
          ),
          Align(
            alignment: Alignment.centerRight,
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: Text(
                'Diperbarui ${DateFormat('HH:mm:ss').format(summary.generatedAt.toLocal())}',
                style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({required this.title, required this.child, this.padded = true});

  final String title;
  final Widget child;
  final bool padded;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.only(top: 16, bottom: 4),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Text(title, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: AppTheme.textPrimary)),
            ),
            const SizedBox(height: 12),
            padded ? Padding(padding: const EdgeInsets.symmetric(horizontal: 16), child: child) : child,
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }
}

class _LatestAbsensiTile extends StatelessWidget {
  const _LatestAbsensiTile({required this.item});

  final DashboardLatestAbsensi item;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      dense: true,
      title: Text(item.mahasiswaNama, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
      subtitle: Text(
        '${item.mahasiswaNim} • ${item.mataKuliahNama} • ${item.kelasName}',
        style: const TextStyle(fontSize: 11.5),
      ),
      trailing: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          StatusBadge(label: item.status),
          if (item.waktuTap != null) ...[
            const SizedBox(height: 4),
            Text(item.waktuTap!, style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
          ],
        ],
      ),
    );
  }
}

class _RecentDeviceTile extends StatelessWidget {
  const _RecentDeviceTile({required this.device});

  final DashboardRecentDevice device;

  @override
  Widget build(BuildContext context) {
    final color = AppTheme.statusColor(device.status);
    return ListTile(
      dense: true,
      leading: CircleAvatar(
        radius: 16,
        backgroundColor: color.withValues(alpha: 0.15),
        child: Icon(Icons.router, color: color, size: 16),
      ),
      title: Text(device.name, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
      subtitle: Text(
        device.lastSeenAt != null
            ? 'Terakhir aktif ${DateFormat('dd/MM HH:mm').format(device.lastSeenAt!.toLocal())}'
            : 'Belum pernah terhubung',
        style: const TextStyle(fontSize: 11.5),
      ),
      trailing: Text(device.statusLabel, style: TextStyle(color: color, fontWeight: FontWeight.w700, fontSize: 12)),
    );
  }
}
