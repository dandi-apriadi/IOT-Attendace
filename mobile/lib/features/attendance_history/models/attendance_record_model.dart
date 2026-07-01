class AttendanceRecordModel {
  const AttendanceRecordModel({
    required this.id,
    required this.tanggal,
    required this.waktuTap,
    required this.metodeAbsensi,
    required this.status,
    required this.mahasiswaId,
    required this.mahasiswaNama,
    required this.mahasiswaNim,
    required this.kelasName,
    required this.mataKuliahKode,
    required this.mataKuliahNama,
  });

  final int id;
  final String tanggal;
  final String? waktuTap;
  final String metodeAbsensi;
  final String status;
  final int mahasiswaId;
  final String mahasiswaNama;
  final String mahasiswaNim;
  final String kelasName;
  final String mataKuliahKode;
  final String mataKuliahNama;

  factory AttendanceRecordModel.fromJson(Map<String, dynamic> json) {
    return AttendanceRecordModel(
      id: json['id'] as int,
      tanggal: json['tanggal'] as String? ?? '',
      waktuTap: json['waktu_tap'] as String?,
      metodeAbsensi: json['metode_absensi'] as String? ?? '-',
      status: json['status'] as String? ?? '-',
      mahasiswaId: json['mahasiswa_id'] as int? ?? 0,
      mahasiswaNama: json['mahasiswa_nama'] as String? ?? 'N/A',
      mahasiswaNim: json['mahasiswa_nim'] as String? ?? 'N/A',
      kelasName: json['kelas_name'] as String? ?? 'N/A',
      mataKuliahKode: json['mata_kuliah_kode'] as String? ?? 'N/A',
      mataKuliahNama: json['mata_kuliah_nama'] as String? ?? 'N/A',
    );
  }
}

class AttendanceHistoryPage {
  const AttendanceHistoryPage({
    required this.records,
    required this.currentPage,
    required this.lastPage,
  });

  final List<AttendanceRecordModel> records;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;

  factory AttendanceHistoryPage.fromJson(Map<String, dynamic> json) {
    final meta = json['meta'] as Map<String, dynamic>? ?? {};
    return AttendanceHistoryPage(
      records: (json['data'] as List<dynamic>? ?? [])
          .map((e) => AttendanceRecordModel.fromJson(e as Map<String, dynamic>))
          .toList(),
      currentPage: meta['current_page'] as int? ?? 1,
      lastPage: meta['last_page'] as int? ?? 1,
    );
  }
}
