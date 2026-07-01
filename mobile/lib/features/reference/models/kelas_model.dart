class KelasModel {
  const KelasModel({required this.id, required this.namaKelas});

  final int id;
  final String namaKelas;

  factory KelasModel.fromJson(Map<String, dynamic> json) {
    return KelasModel(id: json['id'] as int, namaKelas: json['nama_kelas'] as String? ?? '-');
  }
}
