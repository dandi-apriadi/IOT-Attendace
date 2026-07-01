class DeviceModel {
  const DeviceModel({
    required this.id,
    required this.deviceId,
    required this.name,
    required this.type,
    required this.ipAddress,
    required this.isActive,
    required this.lastSeenAt,
    required this.status,
    required this.statusLabel,
  });

  final int id;
  final String deviceId;
  final String name;
  final String? type;
  final String? ipAddress;
  final bool isActive;
  final DateTime? lastSeenAt;
  final String status;
  final String statusLabel;

  factory DeviceModel.fromJson(Map<String, dynamic> json) {
    return DeviceModel(
      id: json['id'] as int,
      deviceId: json['device_id'] as String? ?? '-',
      name: json['name'] as String? ?? '-',
      type: json['type'] as String?,
      ipAddress: json['ip_address'] as String?,
      isActive: json['is_active'] as bool? ?? false,
      lastSeenAt: json['last_seen_at'] != null ? DateTime.tryParse(json['last_seen_at'] as String) : null,
      status: json['status'] as String? ?? 'unknown',
      statusLabel: json['status_label'] as String? ?? 'Unknown',
    );
  }
}

class DeviceListModel {
  const DeviceListModel({required this.devices, required this.meta});

  final List<DeviceModel> devices;
  final Map<String, int> meta;

  factory DeviceListModel.fromJson(Map<String, dynamic> json) {
    return DeviceListModel(
      devices: (json['data'] as List<dynamic>? ?? [])
          .map((e) => DeviceModel.fromJson(e as Map<String, dynamic>))
          .toList(),
      meta: Map<String, int>.from(json['meta'] as Map? ?? {}),
    );
  }
}
