import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../shared/theme/app_theme.dart';
import '../../../shared/widgets/error_banner.dart';
import '../models/device_model.dart';
import '../providers/device_health_provider.dart';

class DeviceHealthScreen extends ConsumerWidget {
  const DeviceHealthScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(deviceHealthProvider);
    final notifier = ref.read(deviceHealthProvider.notifier);

    return Scaffold(
      appBar: AppBar(title: const Text('Status Device IoT')),
      body: Column(
        children: [
          if (state.error != null) ErrorBanner(message: state.error!),
          if (state.data != null) _MetaStrip(meta: state.data!.meta),
          Expanded(
            child: state.loading && !state.hasData
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: notifier.refreshNow,
                    child: _DeviceList(
                      devices: state.data?.devices ?? [],
                      onPing: (id) => notifier.ping(id),
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}

class _MetaStrip extends StatelessWidget {
  const _MetaStrip({required this.meta});

  final Map<String, int> meta;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Wrap(
        spacing: 16,
        children: [
          _MetaChip(label: 'Total', value: meta['total'] ?? 0, color: AppTheme.navy),
          _MetaChip(label: 'Online', value: meta['online'] ?? 0, color: AppTheme.statusColor('online')),
          _MetaChip(label: 'Offline', value: meta['offline'] ?? 0, color: AppTheme.statusColor('offline')),
          _MetaChip(label: 'Disabled', value: meta['disabled'] ?? 0, color: AppTheme.statusColor('disabled')),
        ],
      ),
    );
  }
}

class _MetaChip extends StatelessWidget {
  const _MetaChip({required this.label, required this.value, required this.color});

  final String label;
  final int value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(width: 8, height: 8, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
        const SizedBox(width: 6),
        Text('$label: $value', style: Theme.of(context).textTheme.bodySmall),
      ],
    );
  }
}

class _DeviceList extends StatelessWidget {
  const _DeviceList({required this.devices, required this.onPing});

  final List<DeviceModel> devices;
  final void Function(int deviceId) onPing;

  @override
  Widget build(BuildContext context) {
    if (devices.isEmpty) {
      return ListView(
        children: const [
          Padding(
            padding: EdgeInsets.all(32),
            child: Center(child: Text('Belum ada device terdaftar.')),
          ),
        ],
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(8),
      itemCount: devices.length,
      separatorBuilder: (_, _) => const SizedBox(height: 4),
      itemBuilder: (context, index) {
        final device = devices[index];
        final color = AppTheme.statusColor(device.status);
        return Card(
          child: ListTile(
            leading: CircleAvatar(
              backgroundColor: color.withValues(alpha: 0.15),
              child: Icon(Icons.router, color: color),
            ),
            title: Text(device.name),
            subtitle: Text(
              '${device.deviceId} • ${device.ipAddress ?? '-'}\n'
              '${device.lastSeenAt != null ? 'Terakhir aktif ${DateFormat('dd/MM HH:mm').format(device.lastSeenAt!.toLocal())}' : 'Belum pernah terhubung'}',
            ),
            isThreeLine: true,
            trailing: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(device.statusLabel, style: TextStyle(color: color, fontWeight: FontWeight.w600)),
                IconButton(
                  icon: const Icon(Icons.wifi_tethering, size: 20),
                  tooltip: 'Cek koneksi',
                  visualDensity: VisualDensity.compact,
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                  onPressed: () => onPing(device.id),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
