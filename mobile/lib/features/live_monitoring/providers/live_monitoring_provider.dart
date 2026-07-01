import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client_provider.dart';
import '../../../core/polling/polling_notifier.dart';
import '../data/live_monitoring_repository.dart';
import '../models/live_payload_model.dart';

final liveMonitoringRepositoryProvider = Provider<LiveMonitoringRepository>((ref) {
  return LiveMonitoringRepository(ref.watch(apiClientProvider));
});

final liveMonitoringProvider =
    StateNotifierProvider.autoDispose<LiveMonitoringNotifier, PollingState<LivePayloadModel>>((ref) {
  return LiveMonitoringNotifier(ref, ref.watch(liveMonitoringRepositoryProvider));
});

class LiveMonitoringNotifier extends PollingNotifier<LivePayloadModel> {
  LiveMonitoringNotifier(super.ref, this._repository) : super(interval: const Duration(seconds: 6));

  final LiveMonitoringRepository _repository;

  int? _kelasId;
  int? _jadwalId;

  int? get kelasId => _kelasId;
  int? get jadwalId => _jadwalId;

  void setKelas(int? kelasId) {
    _kelasId = kelasId;
    _jadwalId = null;
    refreshNow();
  }

  void setJadwal(int? jadwalId) {
    _jadwalId = jadwalId;
    refreshNow();
  }

  void clearFilters() {
    _kelasId = null;
    _jadwalId = null;
    refreshNow();
  }

  @override
  Future<LivePayloadModel> fetch() {
    return _repository.fetchLive(jadwalId: _jadwalId, kelasId: _kelasId);
  }
}
