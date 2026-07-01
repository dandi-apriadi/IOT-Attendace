import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../shared/widgets/error_banner.dart';
import '../../../shared/widgets/status_badge.dart';
import '../models/live_payload_model.dart';
import '../models/live_record_model.dart';
import '../providers/live_monitoring_provider.dart';

class LiveMonitoringScreen extends ConsumerWidget {
  const LiveMonitoringScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(liveMonitoringProvider);
    final notifier = ref.read(liveMonitoringProvider.notifier);

    return Scaffold(
      appBar: AppBar(title: const Text('Live Monitoring')),
      body: Column(
        children: [
          if (state.data != null) _FilterBar(payload: state.data!, notifier: notifier),
          if (state.error != null) ErrorBanner(message: state.error!),
          if (state.data != null) _SummaryStrip(payload: state.data!),
          Expanded(
            child: state.loading && !state.hasData
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: notifier.refreshNow,
                    child: _RecordList(records: state.data?.records ?? []),
                  ),
          ),
        ],
      ),
    );
  }
}

class _FilterBar extends StatelessWidget {
  const _FilterBar({required this.payload, required this.notifier});

  final LivePayloadModel payload;
  final LiveMonitoringNotifier notifier;

  @override
  Widget build(BuildContext context) {
    final jadwalOptions = payload.selectedKelasId != null
        ? payload.jadwalList.where((j) => j.kelasId == payload.selectedKelasId).toList()
        : payload.jadwalList;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      child: Row(
        children: [
          Expanded(
            child: DropdownButtonFormField<int?>(
              initialValue: payload.selectedKelasId,
              isExpanded: true,
              decoration: const InputDecoration(labelText: 'Kelas', isDense: true),
              items: [
                const DropdownMenuItem(value: null, child: Text('Semua Kelas')),
                ...payload.kelases.map((k) => DropdownMenuItem(value: k.id, child: Text(k.name))),
              ],
              onChanged: notifier.setKelas,
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: DropdownButtonFormField<int?>(
              initialValue: payload.selectedJadwalId,
              isExpanded: true,
              decoration: const InputDecoration(labelText: 'Jadwal', isDense: true),
              items: [
                const DropdownMenuItem(value: null, child: Text('Semua Jadwal')),
                ...jadwalOptions.map((j) => DropdownMenuItem(value: j.id, child: Text(j.name, overflow: TextOverflow.ellipsis))),
              ],
              onChanged: notifier.setJadwal,
            ),
          ),
        ],
      ),
    );
  }
}

class _SummaryStrip extends StatelessWidget {
  const _SummaryStrip({required this.payload});

  final LivePayloadModel payload;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      child: Row(
        children: [
          Text('Hari ini: ${payload.todayTotal}', style: Theme.of(context).textTheme.bodySmall),
          const SizedBox(width: 12),
          Text('Jam ini: ${payload.thisHourTotal}', style: Theme.of(context).textTheme.bodySmall),
          const Spacer(),
          Text('Update ${payload.lastUpdatedAt}', style: Theme.of(context).textTheme.bodySmall),
        ],
      ),
    );
  }
}

class _RecordList extends StatelessWidget {
  const _RecordList({required this.records});

  final List<LiveRecordModel> records;

  @override
  Widget build(BuildContext context) {
    if (records.isEmpty) {
      return ListView(
        children: const [
          Padding(
            padding: EdgeInsets.all(32),
            child: Center(child: Text('Belum ada data absensi.')),
          ),
        ],
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.symmetric(vertical: 4),
      itemCount: records.length,
      separatorBuilder: (_, _) => const Divider(height: 1),
      itemBuilder: (context, index) {
        final record = records[index];
        return ListTile(
          title: Text(record.name),
          subtitle: Text('${record.nim} • ${record.courseCode} • ${record.kelasName}'),
          trailing: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              StatusBadge(label: record.status),
              const SizedBox(height: 4),
              Text(record.time, style: Theme.of(context).textTheme.bodySmall),
            ],
          ),
        );
      },
    );
  }
}
