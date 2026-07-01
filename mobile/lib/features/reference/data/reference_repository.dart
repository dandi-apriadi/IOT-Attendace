import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../models/kelas_model.dart';

class ReferenceRepository {
  ReferenceRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<List<KelasModel>> fetchKelas() async {
    final dio = await _apiClient.instance();
    final response = await dio.get(ApiEndpoints.kelas);
    final data = response.data['data'] as List<dynamic>? ?? [];
    return data.map((e) => KelasModel.fromJson(e as Map<String, dynamic>)).toList();
  }
}
