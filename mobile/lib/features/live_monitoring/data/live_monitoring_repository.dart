import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../models/live_payload_model.dart';

class LiveMonitoringRepository {
  LiveMonitoringRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<LivePayloadModel> fetchLive({String? date, int? jadwalId, int? kelasId}) async {
    final dio = await _apiClient.instance();
    final response = await dio.get(ApiEndpoints.monitoringLive, queryParameters: {
      'date': ?date,
      'jadwal_id': ?jadwalId,
      'kelas_id': ?kelasId,
    });
    return LivePayloadModel.fromJson(response.data as Map<String, dynamic>);
  }
}
