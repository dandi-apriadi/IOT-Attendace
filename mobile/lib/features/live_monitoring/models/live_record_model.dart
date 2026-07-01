class LiveRecordModel {
  const LiveRecordModel({
    required this.id,
    required this.jadwalId,
    required this.kelasId,
    required this.date,
    required this.time,
    required this.name,
    required this.nim,
    required this.courseName,
    required this.courseCode,
    required this.kelasName,
    required this.metodeAbsensi,
    required this.status,
    required this.isPending,
  });

  final int? id;
  final int? jadwalId;
  final int? kelasId;
  final String date;
  final String time;
  final String name;
  final String nim;
  final String courseName;
  final String courseCode;
  final String kelasName;
  final String metodeAbsensi;
  final String status;
  final bool isPending;

  factory LiveRecordModel.fromJson(Map<String, dynamic> json) {
    return LiveRecordModel(
      id: json['id'] as int?,
      jadwalId: json['jadwal_id'] as int?,
      kelasId: json['kelas_id'] as int?,
      date: json['date'] as String? ?? '',
      time: json['time'] as String? ?? '-',
      // The API uses the literal string "N/A" for missing relations rather
      // than null -- decode as-is instead of assuming null-safety here.
      name: json['name'] as String? ?? 'N/A',
      nim: json['nim'] as String? ?? 'N/A',
      courseName: json['course_name'] as String? ?? 'N/A',
      courseCode: json['course_code'] as String? ?? 'N/A',
      kelasName: json['kelas_name'] as String? ?? 'N/A',
      metodeAbsensi: json['metode_absensi'] as String? ?? '-',
      status: json['status'] as String? ?? '-',
      isPending: json['is_pending'] as bool? ?? false,
    );
  }
}
