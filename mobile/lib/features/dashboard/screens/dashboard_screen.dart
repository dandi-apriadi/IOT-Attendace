import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../shared/widgets/error_banner.dart';
import '../../auth/providers/auth_provider.dart';
import '../models/dashboard_summary_model.dart';
import '../providers/dashboard_provider.dart';

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
                padding: const EdgeInsets.all(16),
                children: [
                  if (user != null)
                    Text(
                      'Halo, ${user.name} (${user.role})',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                  const SizedBox(height: 16),
                  if (state.error != null) ErrorBanner(message: state.error!),
                  if (state.data != null) _SummaryGrid(summary: state.data!),
                ],
              ),
      ),
    );
  }
}

class _SummaryGrid extends StatelessWidget {
  const _SummaryGrid({required this.summary});

  final DashboardSummaryModel summary;

  @override
  Widget build(BuildContext context) {
    final cards = [
      _SummaryCard(
        icon: Icons.how_to_reg,
        label: 'Hadir Hari Ini',
        value: summary.hadirHariIni.toString(),
        color: Colors.green,
      ),
      _SummaryCard(
        icon: Icons.schedule,
        label: 'Sesi Aktif',
        value: summary.sesiAktif.toString(),
        color: Colors.blue,
      ),
      _SummaryCard(
        icon: Icons.router,
        label: 'Device Online',
        value: '${summary.deviceOnline}/${summary.deviceAktif}',
        color: Colors.orange,
      ),
      _SummaryCard(
        icon: Icons.school,
        label: 'Semester Aktif',
        value: summary.semesterAktif ?? '-',
        color: Colors.purple,
      ),
    ];

    return Column(
      children: [
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
          childAspectRatio: 1.1,
          children: cards,
        ),
        const SizedBox(height: 12),
        Text(
          'Diperbarui ${DateFormat('HH:mm:ss').format(summary.generatedAt.toLocal())}',
          style: Theme.of(context).textTheme.bodySmall,
        ),
      ],
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });

  final IconData icon;
  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Icon(icon, color: color, size: 28),
            Text(
              value,
              style: Theme.of(context).textTheme.titleLarge,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            Text(label, style: Theme.of(context).textTheme.bodySmall),
          ],
        ),
      ),
    );
  }
}
