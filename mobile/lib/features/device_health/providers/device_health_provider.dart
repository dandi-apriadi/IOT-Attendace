import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client_provider.dart';
import '../../../core/polling/polling_notifier.dart';
import '../data/device_repository.dart';
import '../models/device_model.dart';

final deviceRepositoryProvider = Provider<DeviceRepository>((ref) {
  return DeviceRepository(ref.watch(apiClientProvider));
});

final deviceHealthProvider =
    StateNotifierProvider.autoDispose<DeviceHealthNotifier, PollingState<DeviceListModel>>((ref) {
  return DeviceHealthNotifier(ref, ref.watch(deviceRepositoryProvider));
});

class DeviceHealthNotifier extends PollingNotifier<DeviceListModel> {
  DeviceHealthNotifier(super.ref, this._repository) : super(interval: const Duration(seconds: 30));

  final DeviceRepository _repository;

  @override
  Future<DeviceListModel> fetch() => _repository.fetchDevices();

  Future<void> ping(int deviceId) async {
    await _repository.pingDevice(deviceId);
    await refreshNow();
  }
}
