import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/admin_repository.dart';
import '../models/student_report_model.dart';
import 'admin_provider.dart';

class StudentReportState {
  const StudentReportState({
    this.rows = const [],
    this.currentPage = 0,
    this.lastPage = 1,
    this.semesterLabel,
    this.loading = true,
    this.loadingMore = false,
    this.error,
    this.kelasId,
    this.mataKuliahId,
    this.statusFilter,
  });

  final List<StudentReportRow> rows;
  final int currentPage;
  final int lastPage;
  final String? semesterLabel;
  final bool loading;
  final bool loadingMore;
  final String? error;
  final int? kelasId;
  final int? mataKuliahId;
  final String? statusFilter;

  bool get hasMore => currentPage < lastPage;

  StudentReportState copyWith({
    List<StudentReportRow>? rows,
    int? currentPage,
    int? lastPage,
    String? semesterLabel,
    bool? loading,
    bool? loadingMore,
    String? error,
    bool clearError = false,
  }) {
    return StudentReportState(
      rows: rows ?? this.rows,
      currentPage: currentPage ?? this.currentPage,
      lastPage: lastPage ?? this.lastPage,
      semesterLabel: semesterLabel ?? this.semesterLabel,
      loading: loading ?? this.loading,
      loadingMore: loadingMore ?? this.loadingMore,
      error: clearError ? null : (error ?? this.error),
      kelasId: kelasId,
      mataKuliahId: mataKuliahId,
      statusFilter: statusFilter,
    );
  }
}

final studentReportProvider =
    StateNotifierProvider.autoDispose<StudentReportNotifier, StudentReportState>((ref) {
  return StudentReportNotifier(ref.watch(adminRepositoryProvider));
});

class StudentReportNotifier extends StateNotifier<StudentReportState> {
  StudentReportNotifier(this._repository) : super(const StudentReportState()) {
    _loadFirstPage();
  }

  final AdminRepository _repository;

  Future<void> _loadFirstPage() async {
    state = state.copyWith(loading: true, clearError: true);
    try {
      final page = await _repository.fetchStudentReport(
        page: 1,
        kelasId: state.kelasId,
        mataKuliahId: state.mataKuliahId,
        statusFilter: state.statusFilter,
      );
      state = state.copyWith(
        rows: page.rows,
        currentPage: page.currentPage,
        lastPage: page.lastPage,
        semesterLabel: page.semesterLabel,
        loading: false,
      );
    } catch (_) {
      state = state.copyWith(loading: false, error: 'Gagal memuat laporan kehadiran.');
    }
  }

  Future<void> loadMore() async {
    if (!state.hasMore || state.loadingMore) return;
    state = state.copyWith(loadingMore: true);
    try {
      final page = await _repository.fetchStudentReport(
        page: state.currentPage + 1,
        kelasId: state.kelasId,
        mataKuliahId: state.mataKuliahId,
        statusFilter: state.statusFilter,
      );
      state = state.copyWith(
        rows: [...state.rows, ...page.rows],
        currentPage: page.currentPage,
        lastPage: page.lastPage,
        loadingMore: false,
      );
    } catch (_) {
      state = state.copyWith(loadingMore: false, error: 'Gagal memuat data tambahan.');
    }
  }

  void setKelas(int? kelasId) {
    state = StudentReportState(kelasId: kelasId, mataKuliahId: state.mataKuliahId, statusFilter: state.statusFilter);
    _loadFirstPage();
  }

  void setStatusFilter(String? statusFilter) {
    state = StudentReportState(kelasId: state.kelasId, mataKuliahId: state.mataKuliahId, statusFilter: statusFilter);
    _loadFirstPage();
  }

  Future<void> refresh() => _loadFirstPage();
}
