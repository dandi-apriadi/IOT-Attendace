import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../shared/theme/app_theme.dart';
import '../../../shared/widgets/error_banner.dart';
import '../../reference/providers/reference_provider.dart';
import '../providers/attendance_trend_provider.dart';
import 'attendance_trend_chart_widget.dart';

class AttendanceTrendSection extends ConsumerWidget {
  const AttendanceTrendSection({super.key});

  Future<void> _pickDateRange(BuildContext context, WidgetRef ref, AttendanceTrendFilters filters) async {
    final now = DateTime.now();
    final range = await showDateRangePicker(
      context: context,
      firstDate: DateTime(now.year - 2),
      lastDate: now,
      initialDateRange: DateTimeRange(start: filters.startDate, end: filters.endDate),
    );
    if (range == null) return;
    ref.read(attendanceTrendProvider.notifier).setDateRange(range.start, range.end);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(attendanceTrendProvider);
    final kelasAsync = ref.watch(kelasListProvider);
    final mataKuliahAsync = ref.watch(mataKuliahListProvider);
    final dateFmt = DateFormat('dd/MM/yy');

    return Card(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Expanded(
                  child: Text('Tren Absensi', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: AppTheme.textPrimary)),
                ),
                if (state.data != null)
                  Text('Total: ${state.data!.total}', style: const TextStyle(fontSize: 12, color: AppTheme.textMuted)),
              ],
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                OutlinedButton.icon(
                  onPressed: () => _pickDateRange(context, ref, state.filters),
                  icon: const Icon(Icons.date_range, size: 16),
                  label: Text(
                    '${dateFmt.format(state.filters.startDate)} - ${dateFmt.format(state.filters.endDate)}',
                    style: const TextStyle(fontSize: 12),
                  ),
                  style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8)),
                ),
                SizedBox(
                  width: 140,
                  child: kelasAsync.when(
                    data: (kelasList) => DropdownButtonFormField<int?>(
                      initialValue: state.filters.kelasId,
                      isExpanded: true,
                      decoration: const InputDecoration(labelText: 'Kelas', isDense: true, contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 8)),
                      style: const TextStyle(fontSize: 12, color: AppTheme.textPrimary),
                      items: [
                        const DropdownMenuItem(value: null, child: Text('Semua')),
                        ...kelasList.map((k) => DropdownMenuItem(value: k.id, child: Text(k.namaKelas, overflow: TextOverflow.ellipsis))),
                      ],
                      onChanged: (value) => ref.read(attendanceTrendProvider.notifier).setKelas(value),
                    ),
                    loading: () => const SizedBox.shrink(),
                    error: (_, _) => const SizedBox.shrink(),
                  ),
                ),
                SizedBox(
                  width: 160,
                  child: mataKuliahAsync.when(
                    data: (mkList) => DropdownButtonFormField<int?>(
                      initialValue: state.filters.mataKuliahId,
                      isExpanded: true,
                      decoration: const InputDecoration(labelText: 'Mata Kuliah', isDense: true, contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 8)),
                      style: const TextStyle(fontSize: 12, color: AppTheme.textPrimary),
                      items: [
                        const DropdownMenuItem(value: null, child: Text('Semua')),
                        ...mkList.map((mk) => DropdownMenuItem(value: mk.id, child: Text(mk.kodeMk, overflow: TextOverflow.ellipsis))),
                      ],
                      onChanged: (value) => ref.read(attendanceTrendProvider.notifier).setMataKuliah(value),
                    ),
                    loading: () => const SizedBox.shrink(),
                    error: (_, _) => const SizedBox.shrink(),
                  ),
                ),
                SizedBox(
                  width: 140,
                  child: DropdownButtonFormField<String?>(
                    initialValue: state.filters.statusFilter,
                    isExpanded: true,
                    decoration: const InputDecoration(labelText: 'Status', isDense: true, contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 8)),
                    style: const TextStyle(fontSize: 12, color: AppTheme.textPrimary),
                    items: const [
                      DropdownMenuItem(value: null, child: Text('Semua')),
                      DropdownMenuItem(value: 'present', child: Text('Hadir')),
                      DropdownMenuItem(value: 'excused', child: Text('Sakit/Izin')),
                      DropdownMenuItem(value: 'absent', child: Text('Alpa')),
                    ],
                    onChanged: (value) => ref.read(attendanceTrendProvider.notifier).setStatusFilter(value),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            if (state.error != null) ErrorBanner(message: state.error!),
            state.loading && state.data == null
                ? const SizedBox(height: 180, child: Center(child: CircularProgressIndicator()))
                : AttendanceTrendChartWidget(
                    labels: state.data?.labels ?? [],
                    values: state.data?.values ?? [],
                  ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }
}
