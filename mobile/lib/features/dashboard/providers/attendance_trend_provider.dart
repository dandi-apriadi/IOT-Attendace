import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../admin/data/admin_repository.dart';
import '../../admin/providers/admin_provider.dart';
import '../models/attendance_trend_model.dart';

final _dateFmt = DateFormat('yyyy-MM-dd');

class AttendanceTrendFilters {
  AttendanceTrendFilters({DateTime? startDate, DateTime? endDate, this.kelasId, this.mataKuliahId, this.statusFilter})
      : endDate = endDate ?? DateTime.now(),
        startDate = startDate ?? DateTime.now().subtract(const Duration(days: 6));

  final DateTime startDate;
  final DateTime endDate;
  final int? kelasId;
  final int? mataKuliahId;
  final String? statusFilter;

  String get startDateStr => _dateFmt.format(startDate);
  String get endDateStr => _dateFmt.format(endDate);

  AttendanceTrendFilters copyWith({
    DateTime? startDate,
    DateTime? endDate,
    int? kelasId,
    bool clearKelas = false,
    int? mataKuliahId,
    bool clearMataKuliah = false,
    String? statusFilter,
    bool clearStatus = false,
  }) {
    return AttendanceTrendFilters(
      startDate: startDate ?? this.startDate,
      endDate: endDate ?? this.endDate,
      kelasId: clearKelas ? null : (kelasId ?? this.kelasId),
      mataKuliahId: clearMataKuliah ? null : (mataKuliahId ?? this.mataKuliahId),
      statusFilter: clearStatus ? null : (statusFilter ?? this.statusFilter),
    );
  }
}

class AttendanceTrendState {
  const AttendanceTrendState({required this.filters, this.data, this.loading = true, this.error});

  final AttendanceTrendFilters filters;
  final AttendanceTrendModel? data;
  final bool loading;
  final String? error;

  AttendanceTrendState copyWith({
    AttendanceTrendFilters? filters,
    AttendanceTrendModel? data,
    bool? loading,
    String? error,
    bool clearError = false,
  }) {
    return AttendanceTrendState(
      filters: filters ?? this.filters,
      data: data ?? this.data,
      loading: loading ?? this.loading,
      error: clearError ? null : (error ?? this.error),
    );
  }
}

final attendanceTrendProvider =
    StateNotifierProvider.autoDispose<AttendanceTrendNotifier, AttendanceTrendState>((ref) {
  return AttendanceTrendNotifier(ref.watch(adminRepositoryProvider));
});

class AttendanceTrendNotifier extends StateNotifier<AttendanceTrendState> {
  AttendanceTrendNotifier(this._repository) : super(AttendanceTrendState(filters: AttendanceTrendFilters())) {
    _load();
  }

  final AdminRepository _repository;

  Future<void> _load() async {
    state = state.copyWith(loading: true, clearError: true);
    try {
      final data = await _repository.fetchAttendanceTrend(
        startDate: state.filters.startDateStr,
        endDate: state.filters.endDateStr,
        kelasId: state.filters.kelasId,
        mataKuliahId: state.filters.mataKuliahId,
        statusFilter: state.filters.statusFilter,
      );
      state = state.copyWith(data: data, loading: false);
    } catch (_) {
      state = state.copyWith(loading: false, error: 'Gagal memuat tren absensi.');
    }
  }

  void setDateRange(DateTime start, DateTime end) {
    state = state.copyWith(filters: state.filters.copyWith(startDate: start, endDate: end));
    _load();
  }

  void setKelas(int? kelasId) {
    state = state.copyWith(filters: state.filters.copyWith(kelasId: kelasId, clearKelas: kelasId == null));
    _load();
  }

  void setMataKuliah(int? mataKuliahId) {
    state = state.copyWith(
      filters: state.filters.copyWith(mataKuliahId: mataKuliahId, clearMataKuliah: mataKuliahId == null),
    );
    _load();
  }

  void setStatusFilter(String? statusFilter) {
    state = state.copyWith(
      filters: state.filters.copyWith(statusFilter: statusFilter, clearStatus: statusFilter == null),
    );
    _load();
  }

  Future<void> refresh() => _load();
}
