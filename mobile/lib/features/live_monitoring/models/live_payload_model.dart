import 'live_record_model.dart';
import 'live_session_model.dart';

class SimpleOption {
  const SimpleOption({required this.id, required this.name, this.kelasId});
  final int id;
  final String name;
  final int? kelasId;

  factory SimpleOption.fromJson(Map<String, dynamic> json, {String nameKey = 'name'}) {
    return SimpleOption(
      id: json['id'] as int,
      name: json[nameKey] as String? ?? json['nama_kelas'] as String? ?? '-',
      kelasId: json['kelas_id'] as int?,
    );
  }
}

class LivePayloadModel {
  const LivePayloadModel({
    required this.selectedDate,
    required this.selectedJadwalId,
    required this.selectedKelasId,
    required this.sessions,
    required this.sessionSummary,
    required this.todayTotal,
    required this.thisHourTotal,
    required this.lastUpdatedAt,
    required this.records,
    required this.kelases,
    required this.jadwalList,
  });

  final String selectedDate;
  final int? selectedJadwalId;
  final int? selectedKelasId;
  final List<LiveSessionModel> sessions;
  final Map<String, int> sessionSummary;
  final int todayTotal;
  final int thisHourTotal;
  final String lastUpdatedAt;
  final List<LiveRecordModel> records;
  final List<SimpleOption> kelases;
  final List<SimpleOption> jadwalList;

  factory LivePayloadModel.fromJson(Map<String, dynamic> json) {
    return LivePayloadModel(
      selectedDate: json['selected_date'] as String? ?? '',
      selectedJadwalId: json['selected_jadwal_id'] as int?,
      selectedKelasId: json['selected_kelas_id'] as int?,
      sessions: (json['sessions'] as List<dynamic>? ?? [])
          .map((e) => LiveSessionModel.fromJson(e as Map<String, dynamic>))
          .toList(),
      sessionSummary: Map<String, int>.from(json['session_summary'] as Map? ?? {}),
      todayTotal: json['today_total'] as int? ?? 0,
      thisHourTotal: json['this_hour_total'] as int? ?? 0,
      lastUpdatedAt: json['last_updated_at'] as String? ?? '',
      records: (json['records'] as List<dynamic>? ?? [])
          .map((e) => LiveRecordModel.fromJson(e as Map<String, dynamic>))
          .toList(),
      kelases: (json['kelases'] as List<dynamic>? ?? [])
          .map((e) => SimpleOption.fromJson(e as Map<String, dynamic>))
          .toList(),
      jadwalList: (json['jadwal_list'] as List<dynamic>? ?? [])
          .map((e) => SimpleOption.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}
