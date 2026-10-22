
import 'package:http/http.dart' as http;
import 'config/api_config.dart';

Future<void> testApiConnection() async {
  try {
    final response = await http
        .get(Uri.parse(ApiConfig.user))
        .timeout(const Duration(seconds: 10));

    print('Status API: ${response.statusCode}');
    print('Respons API: ${response.body}');
  } catch (e) {
    print('Gagal menghubungi API: $e');
  }
}
