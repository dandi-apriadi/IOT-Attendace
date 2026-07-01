import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../shared/widgets/error_banner.dart';
import '../../../shared/widgets/status_badge.dart';
import '../../reference/providers/reference_provider.dart';
import '../models/attendance_record_model.dart';
import '../providers/attendance_history_provider.dart';

class AttendanceHistoryScreen extends ConsumerStatefulWidget {
  const AttendanceHistoryScreen({super.key});

  @override
  ConsumerState<AttendanceHistoryScreen> createState() => _AttendanceHistoryScreenState();
}

class _AttendanceHistoryScreenState extends ConsumerState<AttendanceHistoryScreen> {
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
      ref.read(attendanceHistoryProvider.notifier).loadMore();
    }
  }

  Future<void> _pickDateRange() async {
    final now = DateTime.now();
    final range = await showDateRangePicker(
      context: context,
      firstDate: DateTime(now.year - 2),
      lastDate: now,
      initialDateRange: DateTimeRange(start: now.subtract(const Duration(days: 7)), end: now),
    );
    if (range == null) return;
    final fmt = DateFormat('yyyy-MM-dd');
    ref.read(attendanceHistoryProvider.notifier).setDateRange(fmt.format(range.start), fmt.format(range.end));
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(attendanceHistoryProvider);
    final kelasAsync = ref.watch(kelasListProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Riwayat Absensi'),
        actions: [
          IconButton(
            icon: const Icon(Icons.date_range),
            tooltip: 'Pilih rentang tanggal',
            onPressed: _pickDateRange,
          ),
        ],
      ),
      body: Column(
        children: [
          kelasAsync.when(
            data: (kelasList) => Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              child: DropdownButtonFormField<int?>(
                initialValue: state.kelasId,
                isExpanded: true,
                decoration: const InputDecoration(labelText: 'Kelas', isDense: true),
                items: [
                  const DropdownMenuItem(value: null, child: Text('Semua Kelas')),
                  ...kelasList.map((k) => DropdownMenuItem(value: k.id, child: Text(k.namaKelas))),
                ],
                onChanged: (value) => ref.read(attendanceHistoryProvider.notifier).setKelas(value),
              ),
            ),
            loading: () => const SizedBox.shrink(),
            error: (_, _) => const SizedBox.shrink(),
          ),
          if (state.error != null) ErrorBanner(message: state.error!),
          Expanded(
            child: state.loading
                ? const Center(child: CircularProgressIndicator())
                : state.records.isEmpty
                    ? const Center(child: Text('Tidak ada data pada rentang ini.'))
                    : ListView.separated(
                        controller: _scrollController,
                        itemCount: state.records.length + (state.hasMore ? 1 : 0),
                        separatorBuilder: (_, _) => const Divider(height: 1),
                        itemBuilder: (context, index) {
                          if (index >= state.records.length) {
                            return const Padding(
                              padding: EdgeInsets.all(16),
                              child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                            );
                          }
                          return _HistoryTile(record: state.records[index]);
                        },
                      ),
          ),
        ],
      ),
    );
  }
}

class _HistoryTile extends StatelessWidget {
  const _HistoryTile({required this.record});

  final AttendanceRecordModel record;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      title: Text(record.mahasiswaNama),
      subtitle: Text(
        '${record.mahasiswaNim} • ${record.mataKuliahKode} • ${record.kelasName}\n'
        '${record.tanggal} ${record.waktuTap ?? ''}',
      ),
      isThreeLine: true,
      trailing: StatusBadge(label: record.status),
    );
  }
}
