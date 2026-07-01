class LiveSessionModel {
  const LiveSessionModel({
    required this.id,
    required this.kelasId,
    required this.courseName,
    required this.courseCode,
    required this.className,
    required this.lecturerName,
    required this.startTime,
    required this.endTime,
    required this.attendanceCount,
    required this.phase,
    required this.phaseLabel,
  });

  final int id;
  final int kelasId;
  final String courseName;
  final String courseCode;
  final String className;
  final String lecturerName;
  final String startTime;
  final String endTime;
  final int attendanceCount;
  final String phase;
  final String phaseLabel;

  factory LiveSessionModel.fromJson(Map<String, dynamic> json) {
    return LiveSessionModel(
      id: json['id'] as int,
      kelasId: json['kelas_id'] as int? ?? 0,
      courseName: json['course_name'] as String? ?? 'N/A',
      courseCode: json['course_code'] as String? ?? 'N/A',
      className: json['class_name'] as String? ?? 'N/A',
      lecturerName: json['lecturer_name'] as String? ?? '-',
      startTime: json['start_time'] as String? ?? '',
      endTime: json['end_time'] as String? ?? '',
      attendanceCount: json['attendance_count'] as int? ?? 0,
      phase: json['phase'] as String? ?? 'upcoming',
      phaseLabel: json['phase_label'] as String? ?? '',
    );
  }
}
