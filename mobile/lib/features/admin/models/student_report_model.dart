class StudentReportRow {
  const StudentReportRow({
    required this.mahasiswaId,
    required this.nama,
    required this.total,
    required this.hadir,
    required this.sakitIzin,
    required this.alpa,
    required this.persentase,
  });

  final int mahasiswaId;
  final String nama;
  final int total;
  final int hadir;
  final int sakitIzin;
  final int alpa;
  final double persentase;

  factory StudentReportRow.fromJson(Map<String, dynamic> json) {
    return StudentReportRow(
      mahasiswaId: json['mahasiswa_id'] as int,
      nama: json['nama'] as String? ?? '-',
      total: json['total'] as int? ?? 0,
      hadir: json['hadir'] as int? ?? 0,
      sakitIzin: json['sakit_izin'] as int? ?? 0,
      alpa: json['alpa'] as int? ?? 0,
      persentase: (json['persentase'] as num?)?.toDouble() ?? 0,
    );
  }
}

class StudentReportPage {
  const StudentReportPage({
    required this.rows,
    required this.currentPage,
    required this.lastPage,
    required this.semesterLabel,
  });

  final List<StudentReportRow> rows;
  final int currentPage;
  final int lastPage;
  final String? semesterLabel;

  bool get hasMore => currentPage < lastPage;

  factory StudentReportPage.fromJson(Map<String, dynamic> json) {
    final meta = json['meta'] as Map<String, dynamic>? ?? {};
    final semester = meta['semester'] as Map<String, dynamic>?;
    return StudentReportPage(
      rows: (json['data'] as List<dynamic>? ?? [])
          .map((e) => StudentReportRow.fromJson(e as Map<String, dynamic>))
          .toList(),
      currentPage: meta['current_page'] as int? ?? 1,
      lastPage: meta['last_page'] as int? ?? 1,
      semesterLabel: semester != null
          ? '${semester['nama_semester'] ?? ''} ${semester['tahun_ajaran'] ?? ''}'.trim()
          : null,
    );
  }
}
