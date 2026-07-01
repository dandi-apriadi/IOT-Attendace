class AuditLogModel {
  const AuditLogModel({
    required this.id,
    required this.action,
    required this.description,
    required this.userName,
    required this.ipAddress,
    required this.createdAt,
  });

  final int id;
  final String action;
  final String description;
  final String? userName;
  final String? ipAddress;
  final DateTime? createdAt;

  bool get isError => action.contains('failed');

  factory AuditLogModel.fromJson(Map<String, dynamic> json) {
    return AuditLogModel(
      id: json['id'] as int,
      action: json['action'] as String? ?? '-',
      description: json['description'] as String? ?? '',
      userName: json['user_name'] as String?,
      ipAddress: json['ip_address'] as String?,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'] as String) : null,
    );
  }
}

class AuditLogPage {
  const AuditLogPage({
    required this.logs,
    required this.currentPage,
    required this.lastPage,
    required this.totalEvents,
    required this.authEvents,
    required this.errorEvents,
  });

  final List<AuditLogModel> logs;
  final int currentPage;
  final int lastPage;
  final int totalEvents;
  final int authEvents;
  final int errorEvents;

  bool get hasMore => currentPage < lastPage;

  factory AuditLogPage.fromJson(Map<String, dynamic> json) {
    final meta = json['meta'] as Map<String, dynamic>? ?? {};
    final summary = json['summary'] as Map<String, dynamic>? ?? {};
    return AuditLogPage(
      logs: (json['data'] as List<dynamic>? ?? [])
          .map((e) => AuditLogModel.fromJson(e as Map<String, dynamic>))
          .toList(),
      currentPage: meta['current_page'] as int? ?? 1,
      lastPage: meta['last_page'] as int? ?? 1,
      totalEvents: summary['total_events'] as int? ?? 0,
      authEvents: summary['auth_events'] as int? ?? 0,
      errorEvents: summary['error_events'] as int? ?? 0,
    );
  }
}
