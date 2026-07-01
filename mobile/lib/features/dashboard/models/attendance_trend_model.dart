class AttendanceTrendModel {
  const AttendanceTrendModel({
    required this.labels,
    required this.values,
    required this.total,
    required this.startDate,
    required this.endDate,
  });

  final List<String> labels;
  final List<double> values;
  final int total;
  final String startDate;
  final String endDate;

  factory AttendanceTrendModel.fromJson(Map<String, dynamic> json) {
    return AttendanceTrendModel(
      labels: (json['labels'] as List<dynamic>? ?? []).map((e) => e.toString()).toList(),
      values: (json['data'] as List<dynamic>? ?? []).map((e) => (e as num).toDouble()).toList(),
      total: json['total'] as int? ?? 0,
      startDate: json['start_date'] as String? ?? '',
      endDate: json['end_date'] as String? ?? '',
    );
  }
}
