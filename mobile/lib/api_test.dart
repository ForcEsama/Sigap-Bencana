import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

import 'config/api_config.dart';

Future<void> testApiConnection() async {
  try {
    final response = await http
        .get(Uri.parse(ApiConfig.user))
        .timeout(const Duration(seconds: 10));

    debugPrint('Status API: ${response.statusCode}');
  } catch (e) {
    debugPrint('Gagal menghubungi API: $e');
  }
}
