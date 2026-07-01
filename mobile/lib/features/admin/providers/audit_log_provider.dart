import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/admin_repository.dart';
import '../models/audit_log_model.dart';
import 'admin_provider.dart';

class AuditLogState {
  const AuditLogState({
    this.logs = const [],
    this.currentPage = 0,
    this.lastPage = 1,
    this.totalEvents = 0,
    this.authEvents = 0,
    this.errorEvents = 0,
    this.loading = true,
    this.loadingMore = false,
    this.error,
  });

  final List<AuditLogModel> logs;
  final int currentPage;
  final int lastPage;
  final int totalEvents;
  final int authEvents;
  final int errorEvents;
  final bool loading;
  final bool loadingMore;
  final String? error;

  bool get hasMore => currentPage < lastPage;

  AuditLogState copyWith({
    List<AuditLogModel>? logs,
    int? currentPage,
    int? lastPage,
    int? totalEvents,
    int? authEvents,
    int? errorEvents,
    bool? loading,
    bool? loadingMore,
    String? error,
    bool clearError = false,
  }) {
    return AuditLogState(
      logs: logs ?? this.logs,
      currentPage: currentPage ?? this.currentPage,
      lastPage: lastPage ?? this.lastPage,
      totalEvents: totalEvents ?? this.totalEvents,
      authEvents: authEvents ?? this.authEvents,
      errorEvents: errorEvents ?? this.errorEvents,
      loading: loading ?? this.loading,
      loadingMore: loadingMore ?? this.loadingMore,
      error: clearError ? null : (error ?? this.error),
    );
  }
}

final auditLogProvider = StateNotifierProvider.autoDispose<AuditLogNotifier, AuditLogState>((ref) {
  return AuditLogNotifier(ref.watch(adminRepositoryProvider));
});

class AuditLogNotifier extends StateNotifier<AuditLogState> {
  AuditLogNotifier(this._repository) : super(const AuditLogState()) {
    _loadFirstPage();
  }

  final AdminRepository _repository;

  Future<void> _loadFirstPage() async {
    state = state.copyWith(loading: true, clearError: true);
    try {
      final page = await _repository.fetchAuditLog(page: 1);
      state = state.copyWith(
        logs: page.logs,
        currentPage: page.currentPage,
        lastPage: page.lastPage,
        totalEvents: page.totalEvents,
        authEvents: page.authEvents,
        errorEvents: page.errorEvents,
        loading: false,
      );
    } catch (_) {
      state = state.copyWith(loading: false, error: 'Gagal memuat audit log.');
    }
  }

  Future<void> loadMore() async {
    if (!state.hasMore || state.loadingMore) return;
    state = state.copyWith(loadingMore: true);
    try {
      final page = await _repository.fetchAuditLog(page: state.currentPage + 1);
      state = state.copyWith(
        logs: [...state.logs, ...page.logs],
        currentPage: page.currentPage,
        lastPage: page.lastPage,
        loadingMore: false,
      );
    } catch (_) {
      state = state.copyWith(loadingMore: false, error: 'Gagal memuat data tambahan.');
    }
  }

  Future<void> refresh() => _loadFirstPage();
}
