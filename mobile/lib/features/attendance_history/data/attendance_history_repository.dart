import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../models/attendance_record_model.dart';

class AttendanceHistoryRepository {
  AttendanceHistoryRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<AttendanceHistoryPage> fetchPage({
    required int page,
    int? kelasId,
    String? startDate,
    String? endDate,
  }) async {
    final dio = await _apiClient.instance();
    final response = await dio.get(ApiEndpoints.attendanceHistory, queryParameters: {
      'page': page,
      'per_page': 20,
      'kelas_id': ?kelasId,
      'start_date': ?startDate,
      'end_date': ?endDate,
    });
    return AttendanceHistoryPage.fromJson(response.data as Map<String, dynamic>);
  }
}
