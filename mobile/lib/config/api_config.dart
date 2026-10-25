class ApiConfig {
  static const String baseUrl = 'http://192.168.18.31:8000/api';

  static const String register = '$baseUrl/register';
  static const String login = '$baseUrl/login';
  static const String user = '$baseUrl/user';
  static const String updateUser = '$baseUrl/user';
  static const String logout = '$baseUrl/logout';
  static const String reports = '$baseUrl/reports';
  static const String news = '$baseUrl/v1/news';
  static const String warnings = '$baseUrl/v1/warnings';
  static const String notifications = '$baseUrl/v1/me/notifications';
  static const String sos = '$baseUrl/v1/sos';
  static const String mySos = '$baseUrl/v1/me/sos';

  static String report(int id) => '$reports/$id';

  static String newsArticle(String slug) => '$news/$slug';

  static String warning(int id) => '$warnings/$id';

  static String sosRequest(int id) => '$mySos/$id';
}
