import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client_provider.dart';
import '../../../core/polling/polling_notifier.dart';
import '../data/dashboard_repository.dart';
import '../models/dashboard_summary_model.dart';

final dashboardRepositoryProvider = Provider<DashboardRepository>((ref) {
  return DashboardRepository(ref.watch(apiClientProvider));
});

final dashboardProvider =
    StateNotifierProvider.autoDispose<DashboardNotifier, PollingState<DashboardSummaryModel>>((ref) {
  return DashboardNotifier(ref, ref.watch(dashboardRepositoryProvider));
});

class DashboardNotifier extends PollingNotifier<DashboardSummaryModel> {
  DashboardNotifier(super.ref, this._repository) : super(interval: const Duration(seconds: 20));

  final DashboardRepository _repository;

  @override
  Future<DashboardSummaryModel> fetch() => _repository.fetchSummary();
}
