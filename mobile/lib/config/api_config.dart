class ApiConfig {
  static const String baseUrl = 'http://192.168.37.183:8000/api';

  static const String register = '$baseUrl/register';
  static const String login = '$baseUrl/login';
  static const String user = '$baseUrl/user';
  static const String updateUser = '$baseUrl/user';
  static const String logout = '$baseUrl/logout';
  static const String reports = '$baseUrl/reports';
  static const String news = '$baseUrl/v1/mobile/news';
  static const String warnings = '$baseUrl/v1/mobile/warnings';
  static const String notifications = '$baseUrl/v1/me/notifications';
  static const String sos = '$baseUrl/v1/sos';
  static const String mySos = '$baseUrl/v1/me/sos';
  static const String fieldTasks = '$baseUrl/v1/field/tasks';
  static const String adminQueue = '$baseUrl/v1/operations/queue';
  static const String evacuationPosts = '$baseUrl/v1/evacuation-posts';
  static const String emergencyContacts = '$baseUrl/v1/emergency-contacts';

  static String report(int id) => '$reports/$id';

  static String newsArticle(String source, String item) =>
      '$news/$source/$item';

  static String warning(int id) => '$warnings/$id';

  static String sosRequest(int id) => '$mySos/$id';

  static String reportHandling(int id) => '$reports/$id/handling';

  static String reportVerification(int id) => '$reports/$id/verification';

  static String reportAssignment(int id) =>
      '$baseUrl/v1/operations/reports/$id/assignment';

  static String sosOperationStatus(int id) =>
      '$baseUrl/v1/operations/sos/$id/status';
}
