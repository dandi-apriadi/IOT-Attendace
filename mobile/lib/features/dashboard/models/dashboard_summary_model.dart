class ChartData {
  const ChartData({required this.labels, required this.values});

  final List<String> labels;
  final List<double> values;

  bool get isEmpty => labels.isEmpty || (labels.length == 1 && labels.first == 'Belum ada data');

  factory ChartData.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const ChartData(labels: [], values: []);
    final labels = (json['labels'] as List<dynamic>? ?? []).map((e) => e.toString()).toList();
    final values = (json['data'] as List<dynamic>? ?? []).map((e) => (e as num).toDouble()).toList();
    return ChartData(labels: labels, values: values);
  }
}

class DashboardLatestAbsensi {
  const DashboardLatestAbsensi({
    required this.mahasiswaNama,
    required this.mahasiswaNim,
    required this.mataKuliahNama,
    required this.kelasName,
    required this.waktuTap,
    required this.tanggal,
    required this.status,
  });

  final String mahasiswaNama;
  final String mahasiswaNim;
  final String mataKuliahNama;
  final String kelasName;
  final String? waktuTap;
  final String tanggal;
  final String status;

  factory DashboardLatestAbsensi.fromJson(Map<String, dynamic> json) {
    return DashboardLatestAbsensi(
      mahasiswaNama: json['mahasiswa_nama'] as String? ?? 'N/A',
      mahasiswaNim: json['mahasiswa_nim'] as String? ?? 'N/A',
      mataKuliahNama: json['mata_kuliah_nama'] as String? ?? 'N/A',
      kelasName: json['kelas_name'] as String? ?? 'N/A',
      waktuTap: json['waktu_tap'] as String?,
      tanggal: json['tanggal'] as String? ?? '',
      status: json['status'] as String? ?? '-',
    );
  }
}

class DashboardRecentDevice {
  const DashboardRecentDevice({
    required this.deviceId,
    required this.name,
    required this.isActive,
    required this.lastSeenAt,
    required this.status,
    required this.statusLabel,
  });

  final String deviceId;
  final String name;
  final bool isActive;
  final DateTime? lastSeenAt;
  final String status;
  final String statusLabel;

  factory DashboardRecentDevice.fromJson(Map<String, dynamic> json) {
    return DashboardRecentDevice(
      deviceId: json['device_id'] as String? ?? '-',
      name: json['name'] as String? ?? '-',
      isActive: json['is_active'] as bool? ?? false,
      lastSeenAt: json['last_seen_at'] != null ? DateTime.tryParse(json['last_seen_at'] as String) : null,
      status: json['status'] as String? ?? 'unknown',
      statusLabel: json['status_label'] as String? ?? 'Unknown',
    );
  }
}

class DashboardSummaryModel {
  const DashboardSummaryModel({
    required this.hadirHariIni,
    required this.sesiAktif,
    required this.deviceAktif,
    required this.deviceOnline,
    required this.semesterAktif,
    required this.generatedAt,
    required this.latestAbsensi,
    required this.recentDevices,
    this.weeklyChart,
    this.iotChart,
    this.dosenClassChart,
    this.dosenCourseChart,
    this.dosenScheduleCount,
  });

  final int hadirHariIni;
  final int sesiAktif;
  final int deviceAktif;
  final int deviceOnline;
  final String? semesterAktif;
  final DateTime generatedAt;
  final List<DashboardLatestAbsensi> latestAbsensi;
  final List<DashboardRecentDevice> recentDevices;

  /// Admin only.
  final ChartData? weeklyChart;
  final ChartData? iotChart;

  /// Dosen only.
  final ChartData? dosenClassChart;
  final ChartData? dosenCourseChart;
  final int? dosenScheduleCount;

  factory DashboardSummaryModel.fromJson(Map<String, dynamic> json) {
    return DashboardSummaryModel(
      hadirHariIni: json['hadir_hari_ini'] as int? ?? 0,
      sesiAktif: json['sesi_aktif'] as int? ?? 0,
      deviceAktif: json['device_aktif'] as int? ?? 0,
      deviceOnline: json['device_online'] as int? ?? 0,
      semesterAktif: json['semester_aktif'] as String?,
      generatedAt: DateTime.tryParse(json['generated_at'] as String? ?? '') ?? DateTime.now(),
      latestAbsensi: (json['latest_absensi'] as List<dynamic>? ?? [])
          .map((e) => DashboardLatestAbsensi.fromJson(e as Map<String, dynamic>))
          .toList(),
      recentDevices: (json['recent_devices'] as List<dynamic>? ?? [])
          .map((e) => DashboardRecentDevice.fromJson(e as Map<String, dynamic>))
          .toList(),
      weeklyChart: json['weekly_chart'] != null ? ChartData.fromJson(json['weekly_chart'] as Map<String, dynamic>) : null,
      iotChart: json['iot_chart'] != null ? ChartData.fromJson(json['iot_chart'] as Map<String, dynamic>) : null,
      dosenClassChart:
          json['dosen_class_chart'] != null ? ChartData.fromJson(json['dosen_class_chart'] as Map<String, dynamic>) : null,
      dosenCourseChart: json['dosen_course_chart'] != null
          ? ChartData.fromJson(json['dosen_course_chart'] as Map<String, dynamic>)
          : null,
      dosenScheduleCount: json['dosen_schedule_count'] as int?,
    );
  }
}
