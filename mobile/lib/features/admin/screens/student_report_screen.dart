import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../shared/theme/app_theme.dart';
import '../../../shared/widgets/error_banner.dart';
import '../../auth/providers/auth_provider.dart';
import '../../reference/providers/reference_provider.dart';
import '../models/student_report_model.dart';
import '../providers/student_report_provider.dart';
import 'audit_log_screen.dart';

class StudentReportScreen extends ConsumerStatefulWidget {
  const StudentReportScreen({super.key});

  @override
  ConsumerState<StudentReportScreen> createState() => _StudentReportScreenState();
}

class _StudentReportScreenState extends ConsumerState<StudentReportScreen> {
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
      ref.read(studentReportProvider.notifier).loadMore();
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(studentReportProvider);
    final kelasAsync = ref.watch(kelasListProvider);
    final isAdmin = ref.watch(authProvider).user?.isAdmin ?? false;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Laporan Kehadiran'),
        actions: [
          if (isAdmin)
            IconButton(
              icon: const Icon(Icons.fact_check_outlined),
              tooltip: 'Audit Log',
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const AuditLogScreen()),
              ),
            ),
        ],
      ),
      body: Column(
        children: [
          if (state.semesterLabel != null)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text('Semester: ${state.semesterLabel}', style: Theme.of(context).textTheme.bodySmall),
              ),
            ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
            child: Row(
              children: [
                Expanded(
                  child: kelasAsync.when(
                    data: (kelasList) => DropdownButtonFormField<int?>(
                      initialValue: state.kelasId,
                      isExpanded: true,
                      decoration: const InputDecoration(labelText: 'Kelas', isDense: true),
                      items: [
                        const DropdownMenuItem(value: null, child: Text('Semua Kelas')),
                        ...kelasList.map((k) => DropdownMenuItem(value: k.id, child: Text(k.namaKelas))),
                      ],
                      onChanged: (value) => ref.read(studentReportProvider.notifier).setKelas(value),
                    ),
                    loading: () => const SizedBox.shrink(),
                    error: (_, _) => const SizedBox.shrink(),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: DropdownButtonFormField<String?>(
                    initialValue: state.statusFilter,
                    isExpanded: true,
                    decoration: const InputDecoration(labelText: 'Status', isDense: true),
                    items: const [
                      DropdownMenuItem(value: null, child: Text('Semua Status')),
                      DropdownMenuItem(value: 'present', child: Text('Hadir')),
                      DropdownMenuItem(value: 'excused', child: Text('Sakit/Izin')),
                      DropdownMenuItem(value: 'absent', child: Text('Alpa')),
                    ],
                    onChanged: (value) => ref.read(studentReportProvider.notifier).setStatusFilter(value),
                  ),
                ),
              ],
            ),
          ),
          if (state.error != null) ErrorBanner(message: state.error!),
          Expanded(
            child: state.loading
                ? const Center(child: CircularProgressIndicator())
                : state.rows.isEmpty
                    ? const Center(child: Text('Tidak ada data pada filter ini.'))
                    : RefreshIndicator(
                        onRefresh: () => ref.read(studentReportProvider.notifier).refresh(),
                        child: ListView.separated(
                          controller: _scrollController,
                          itemCount: state.rows.length + (state.hasMore ? 1 : 0),
                          separatorBuilder: (_, _) => const Divider(height: 1),
                          itemBuilder: (context, index) {
                            if (index >= state.rows.length) {
                              return const Padding(
                                padding: EdgeInsets.all(16),
                                child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                              );
                            }
                            return _StudentReportTile(row: state.rows[index]);
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }
}

class _StudentReportTile extends StatelessWidget {
  const _StudentReportTile({required this.row});

  final StudentReportRow row;

  Color get _color {
    if (row.persentase >= 80) return AppTheme.statusColor('Hadir');
    if (row.persentase >= 60) return AppTheme.statusColor('Telat');
    return AppTheme.statusColor('Alpa');
  }

  @override
  Widget build(BuildContext context) {
    return ListTile(
      title: Text(row.nama),
      subtitle: Text('Hadir ${row.hadir} • Sakit/Izin ${row.sakitIzin} • Alpa ${row.alpa} • Total ${row.total}'),
      trailing: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Text(
            '${row.persentase.toStringAsFixed(1)}%',
            style: TextStyle(color: _color, fontWeight: FontWeight.bold, fontSize: 16),
          ),
        ],
      ),
    );
  }
}
