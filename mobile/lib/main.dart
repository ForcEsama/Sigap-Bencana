import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;

import 'config/api_config.dart';
import 'register_page.dart';
import 'login_page.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();

  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Color(0xFFFFEEF1),
      statusBarIconBrightness: Brightness.dark,
      systemNavigationBarColor: Color(0xFFFFEEF1),
      systemNavigationBarIconBrightness: Brightness.dark,
      systemNavigationBarDividerColor: Color(0xFFFFEEF1),
    ),
  );

  runApp(const SigapBencanaApp());
}

Future<void> testApiConnection(BuildContext context) async {
  ScaffoldMessenger.of(context).showSnackBar(
    const SnackBar(
      content: Text('Menghubungkan ke server Laravel...'),
    ),
  );

  try {
    final response = await http
        .get(Uri.parse(ApiConfig.user))
        .timeout(const Duration(seconds: 10));

    debugPrint('Status API: ${response.statusCode}');
    debugPrint('Respons API: ${response.body}');

    if (!context.mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Status API: ${response.statusCode}'),
        backgroundColor:
            response.statusCode == 401 ? Colors.green : Colors.blueGrey,
      ),
    );
  } catch (e) {
    debugPrint('Gagal menghubungi API: $e');

    if (!context.mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Koneksi gagal: $e'),
        backgroundColor: Colors.red,
        duration: const Duration(seconds: 5),
      ),
    );
  }
}

class SigapBencanaApp extends StatelessWidget {
  const SigapBencanaApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'SIGAP BENCANA',
      theme: ThemeData(
        useMaterial3: true,
        fontFamily: 'Poppins',
        scaffoldBackgroundColor: const Color(0xFFFFEEF1),
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFFD50032),
        ),
      ),
      home: const WelcomePage(),
    );
  }
}

class WelcomePage extends StatelessWidget {
  const WelcomePage({super.key});

  static const Color pinkBackground = Color(0xFFFFEEF1);
  static const Color red = Color(0xFFD50032);

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.sizeOf(context);

    return Scaffold(
      backgroundColor: pinkBackground,
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: Stack(
                fit: StackFit.expand,
                children: [
                  Positioned.fill(
                    child: ClipPath(
                      clipper: HeroCurveClipper(),
                      child: Image.asset(
                        'assets/images/registration-page-background.png',
                        fit: BoxFit.cover,
                        alignment: const Alignment(0, -1),
                      ),
                    ),
                  ),
                  Align(
                    alignment: Alignment.topCenter,
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(24, 24, 24, 8),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Image.asset(
                            'assets/images/SigapBencanaLogo1.png',
                            width: 275,
                            height: 183,
                            fit: BoxFit.contain,
                          ),
                          const SizedBox(height: 12),
                          const Text(
                            'Informasi, Peringatan\n'
                            'dan Bantuan Bencana\n'
                            'untuk Indonesia yang lebih aman.',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 18,
                              height: 1.4,
                              fontWeight: FontWeight.w500,
                              color: Colors.black,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Tombol Masuk, Daftar Akun, dan Tes API
            Padding(
              padding: EdgeInsets.fromLTRB(
                24,
                18,
                24,
                size.height < 700 ? 20 : 28,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  SizedBox(
                    height: 48,
                    child: ElevatedButton(
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const LoginPage(),
                          ),
                        );
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: red,
                        foregroundColor: Colors.white,
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(5),
                        ),
                      ),
                      child: const Text(
                        'Masuk',
                        style: TextStyle(fontSize: 15),
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),
                  SizedBox(
                    height: 48,
                    child: OutlinedButton(
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const RegisterPage(),
                          ),
                        );
                      },
                      style: OutlinedButton.styleFrom(
                        foregroundColor: red,
                        backgroundColor: Colors.white,
                        side: const BorderSide(color: red),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(5),
                        ),
                      ),
                      child: const Text(
                        'Daftar Akun',
                        style: TextStyle(fontSize: 15),
                      ),
                    ),
                  ),
                  const SizedBox(height: 6),
                  TextButton.icon(
                    onPressed: () => testApiConnection(context),
                    icon: const Icon(Icons.wifi, size: 18),
                    label: const Text('Tes Koneksi API'),
                    style: TextButton.styleFrom(
                      foregroundColor: Colors.blueGrey,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class HeroCurveClipper extends CustomClipper<Path> {
  @override
  Path getClip(Size size) {
    final path = Path()
      ..moveTo(0, size.height * 0.05)
      ..quadraticBezierTo(
        size.width * 0.25,
        -size.height * 0.02,
        size.width * 0.48,
        size.height * 0.07,
      )
      ..quadraticBezierTo(
        size.width * 0.76,
        size.height * 0.17,
        size.width,
        size.height * 0.02,
      )
      ..lineTo(size.width, size.height)
      ..lineTo(0, size.height)
      ..close();

    return path;
  }

  @override
  bool shouldReclip(covariant CustomClipper<Path> oldClipper) {
    return false;
  }
}
