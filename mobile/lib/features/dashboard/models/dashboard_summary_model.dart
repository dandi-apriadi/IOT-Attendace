class DashboardSummaryModel {
  const DashboardSummaryModel({
    required this.hadirHariIni,
    required this.sesiAktif,
    required this.deviceAktif,
    required this.deviceOnline,
    required this.semesterAktif,
    required this.generatedAt,
  });

  final int hadirHariIni;
  final int sesiAktif;
  final int deviceAktif;
  final int deviceOnline;
  final String? semesterAktif;
  final DateTime generatedAt;

  factory DashboardSummaryModel.fromJson(Map<String, dynamic> json) {
    return DashboardSummaryModel(
      hadirHariIni: json['hadir_hari_ini'] as int? ?? 0,
      sesiAktif: json['sesi_aktif'] as int? ?? 0,
      deviceAktif: json['device_aktif'] as int? ?? 0,
      deviceOnline: json['device_online'] as int? ?? 0,
      semesterAktif: json['semester_aktif'] as String?,
      generatedAt: DateTime.tryParse(json['generated_at'] as String? ?? '') ?? DateTime.now(),
    );
  }
}
