import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../dashboard/models/attendance_trend_model.dart';
import '../models/audit_log_model.dart';
import '../models/student_report_model.dart';

class AdminRepository {
  AdminRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<AuditLogPage> fetchAuditLog({required int page}) async {
    final dio = await _apiClient.instance();
    final response = await dio.get(ApiEndpoints.auditLog, queryParameters: {'page': page, 'per_page': 30});
    return AuditLogPage.fromJson(response.data as Map<String, dynamic>);
  }

  Future<StudentReportPage> fetchStudentReport({
    required int page,
    int? semesterId,
    int? kelasId,
    int? mataKuliahId,
    String? statusFilter,
  }) async {
    final dio = await _apiClient.instance();
    final response = await dio.get(ApiEndpoints.studentReport, queryParameters: {
      'page': page,
      'per_page': 25,
      'semester_id': ?semesterId,
      'kelas_id': ?kelasId,
      'mata_kuliah_id': ?mataKuliahId,
      'status_filter': ?statusFilter,
    });
    return StudentReportPage.fromJson(response.data as Map<String, dynamic>);
  }

  Future<AttendanceTrendModel> fetchAttendanceTrend({
    String? startDate,
    String? endDate,
    int? kelasId,
    int? mataKuliahId,
    String? statusFilter,
  }) async {
    final dio = await _apiClient.instance();
    final response = await dio.get(ApiEndpoints.attendanceTrend, queryParameters: {
      'start_date': ?startDate,
      'end_date': ?endDate,
      'kelas_id': ?kelasId,
      'mata_kuliah_id': ?mataKuliahId,
      'status_filter': ?statusFilter,
    });
    return AttendanceTrendModel.fromJson(response.data as Map<String, dynamic>);
  }
}
