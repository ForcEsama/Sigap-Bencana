class ApiConfig {
  static const String baseUrl = 'http://192.168.10.210:8000/api';

  static const String register = '$baseUrl/register';
  static const String login = '$baseUrl/login';
  static const String user = '$baseUrl/user';
  static const String logout = '$baseUrl/logout';
  static const String reports = '$baseUrl/reports';

  static String report(int id) => '$reports/$id';
}
