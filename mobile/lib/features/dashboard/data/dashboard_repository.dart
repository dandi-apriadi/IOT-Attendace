import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../models/dashboard_summary_model.dart';

class DashboardRepository {
  DashboardRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<DashboardSummaryModel> fetchSummary() async {
    final dio = await _apiClient.instance();
    final response = await dio.get(ApiEndpoints.dashboardSummary);
    return DashboardSummaryModel.fromJson(response.data as Map<String, dynamic>);
  }
}
