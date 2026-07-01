import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../shared/theme/app_theme.dart';
import '../../../shared/widgets/error_banner.dart';
import '../models/audit_log_model.dart';
import '../providers/audit_log_provider.dart';

class AuditLogScreen extends ConsumerStatefulWidget {
  const AuditLogScreen({super.key});

  @override
  ConsumerState<AuditLogScreen> createState() => _AuditLogScreenState();
}

class _AuditLogScreenState extends ConsumerState<AuditLogScreen> {
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
      ref.read(auditLogProvider.notifier).loadMore();
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(auditLogProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Audit Log')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Row(
              children: [
                _StatChip(label: 'Total', value: state.totalEvents, color: AppTheme.navy),
                const SizedBox(width: 16),
                _StatChip(label: 'Login', value: state.authEvents, color: AppTheme.statusColor('Hadir')),
                const SizedBox(width: 16),
                _StatChip(label: 'Gagal', value: state.errorEvents, color: AppTheme.statusColor('Alpa')),
              ],
            ),
          ),
          if (state.error != null) ErrorBanner(message: state.error!),
          Expanded(
            child: state.loading
                ? const Center(child: CircularProgressIndicator())
                : state.logs.isEmpty
                    ? const Center(child: Text('Belum ada aktivitas tercatat.'))
                    : RefreshIndicator(
                        onRefresh: () => ref.read(auditLogProvider.notifier).refresh(),
                        child: ListView.separated(
                          controller: _scrollController,
                          itemCount: state.logs.length + (state.hasMore ? 1 : 0),
                          separatorBuilder: (_, _) => const Divider(height: 1),
                          itemBuilder: (context, index) {
                            if (index >= state.logs.length) {
                              return const Padding(
                                padding: EdgeInsets.all(16),
                                child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                              );
                            }
                            return _AuditLogTile(log: state.logs[index]);
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }
}

class _StatChip extends StatelessWidget {
  const _StatChip({required this.label, required this.value, required this.color});

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

class _AuditLogTile extends StatelessWidget {
  const _AuditLogTile({required this.log});

  final AuditLogModel log;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(
        log.isError ? Icons.error_outline : Icons.check_circle_outline,
        color: log.isError ? AppTheme.statusColor('Alpa') : AppTheme.statusColor('Hadir'),
      ),
      title: Text(log.description, style: const TextStyle(fontSize: 14)),
      subtitle: Text(
        '${log.userName ?? 'System'} • ${log.ipAddress ?? '-'}'
        '${log.createdAt != null ? ' • ${DateFormat('dd/MM HH:mm:ss').format(log.createdAt!.toLocal())}' : ''}',
        style: Theme.of(context).textTheme.bodySmall,
      ),
    );
  }
}
