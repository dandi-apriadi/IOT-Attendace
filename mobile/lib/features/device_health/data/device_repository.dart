import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../models/device_model.dart';

class DeviceRepository {
  DeviceRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<DeviceListModel> fetchDevices() async {
    final dio = await _apiClient.instance();
    final response = await dio.get(ApiEndpoints.devices);
    return DeviceListModel.fromJson(response.data as Map<String, dynamic>);
  }

  Future<void> pingDevice(int deviceId) async {
    final dio = await _apiClient.instance();
    await dio.post(ApiEndpoints.devicePing(deviceId));
  }
}
