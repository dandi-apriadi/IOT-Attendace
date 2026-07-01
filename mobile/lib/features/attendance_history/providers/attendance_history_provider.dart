import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client_provider.dart';
import '../data/attendance_history_repository.dart';
import '../models/attendance_record_model.dart';

final attendanceHistoryRepositoryProvider = Provider<AttendanceHistoryRepository>((ref) {
  return AttendanceHistoryRepository(ref.watch(apiClientProvider));
});

class AttendanceHistoryState {
  const AttendanceHistoryState({
    this.records = const [],
    this.currentPage = 0,
    this.lastPage = 1,
    this.loading = true,
    this.loadingMore = false,
    this.error,
    this.kelasId,
    this.startDate,
    this.endDate,
  });

  final List<AttendanceRecordModel> records;
  final int currentPage;
  final int lastPage;
  final bool loading;
  final bool loadingMore;
  final String? error;
  final int? kelasId;
  final String? startDate;
  final String? endDate;

  bool get hasMore => currentPage < lastPage;

  AttendanceHistoryState copyWith({
    List<AttendanceRecordModel>? records,
    int? currentPage,
    int? lastPage,
    bool? loading,
    bool? loadingMore,
    String? error,
    bool clearError = false,
    int? kelasId,
    bool clearKelas = false,
    String? startDate,
    String? endDate,
  }) {
    return AttendanceHistoryState(
      records: records ?? this.records,
      currentPage: currentPage ?? this.currentPage,
      lastPage: lastPage ?? this.lastPage,
      loading: loading ?? this.loading,
      loadingMore: loadingMore ?? this.loadingMore,
      error: clearError ? null : (error ?? this.error),
      kelasId: clearKelas ? null : (kelasId ?? this.kelasId),
      startDate: startDate ?? this.startDate,
      endDate: endDate ?? this.endDate,
    );
  }
}

final attendanceHistoryProvider =
    StateNotifierProvider.autoDispose<AttendanceHistoryNotifier, AttendanceHistoryState>((ref) {
  return AttendanceHistoryNotifier(ref.watch(attendanceHistoryRepositoryProvider));
});

class AttendanceHistoryNotifier extends StateNotifier<AttendanceHistoryState> {
  AttendanceHistoryNotifier(this._repository) : super(const AttendanceHistoryState()) {
    loadFirstPage();
  }

  final AttendanceHistoryRepository _repository;

  Future<void> loadFirstPage() async {
    state = state.copyWith(loading: true, clearError: true);
    try {
      final page = await _repository.fetchPage(
        page: 1,
        kelasId: state.kelasId,
        startDate: state.startDate,
        endDate: state.endDate,
      );
      state = state.copyWith(
        records: page.records,
        currentPage: page.currentPage,
        lastPage: page.lastPage,
        loading: false,
      );
    } catch (_) {
      state = state.copyWith(loading: false, error: 'Gagal memuat riwayat absensi.');
    }
  }

  Future<void> loadMore() async {
    if (!state.hasMore || state.loadingMore) return;
    state = state.copyWith(loadingMore: true);
    try {
      final page = await _repository.fetchPage(
        page: state.currentPage + 1,
        kelasId: state.kelasId,
        startDate: state.startDate,
        endDate: state.endDate,
      );
      state = state.copyWith(
        records: [...state.records, ...page.records],
        currentPage: page.currentPage,
        lastPage: page.lastPage,
        loadingMore: false,
      );
    } catch (_) {
      state = state.copyWith(loadingMore: false, error: 'Gagal memuat data tambahan.');
    }
  }

  void setKelas(int? kelasId) {
    state = kelasId == null
        ? state.copyWith(clearKelas: true)
        : state.copyWith(kelasId: kelasId);
    loadFirstPage();
  }

  void setDateRange(String? start, String? end) {
    state = AttendanceHistoryState(kelasId: state.kelasId, startDate: start, endDate: end);
    loadFirstPage();
  }
}
