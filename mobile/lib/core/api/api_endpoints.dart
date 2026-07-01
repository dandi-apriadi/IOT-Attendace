class ApiEndpoints {
  ApiEndpoints._();

  static const login = '/login';
  static const logout = '/logout';
  static const me = '/me';

  static const monitoringLive = '/monitoring/live';
  static const devices = '/devices';
  static String devicePing(int deviceId) => '/devices/$deviceId/ping';
  static const attendanceHistory = '/attendance/history';
  static const dashboardSummary = '/dashboard/summary';

  static const kelas = '/kelas';
  static const mataKuliah = '/mata-kuliah';
  static const jadwal = '/jadwal';

  static const auditLog = '/audit-log';
  static const studentReport = '/reports/student-summary';
  static const attendanceTrend = '/reports/attendance-trend';
}
