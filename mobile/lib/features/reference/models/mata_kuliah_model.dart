class MataKuliahModel {
  const MataKuliahModel({required this.id, required this.kodeMk, required this.namaMk});

  final int id;
  final String kodeMk;
  final String namaMk;

  factory MataKuliahModel.fromJson(Map<String, dynamic> json) {
    return MataKuliahModel(
      id: json['id'] as int,
      kodeMk: json['kode_mk'] as String? ?? '-',
      namaMk: json['nama_mk'] as String? ?? '-',
    );
  }
}
