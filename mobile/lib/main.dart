
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

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
      backgroundColor: const Color(0xFFFFEEF1),
      body: SafeArea(
        child: Column(
          children: [
            // Area utama: background berada di belakang konten
            Expanded(
              child: Stack(
                fit: StackFit.expand,
                children: [
                  // Background ilustrasi penyelamat
                  Positioned.fill(
                    child: ClipPath(
                      clipper: HeroCurveClipper(),
                      child: Image.asset(
                        'assets/images/registration-page-background.png',
                        fit: BoxFit.cover,
                        alignment: Alignment(0, -1),
                      ),
                    ),
                  ),

                  // Logo dan deskripsi di depan background
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

            // Tombol Masuk dan Daftar Akun
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
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text(
                              'Halaman Masuk akan dibuat berikutnya.',
                            ),
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
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text(
                              'Halaman Daftar Akun akan dibuat berikutnya.',
                            ),
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
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// Membuat batas atas ilustrasi sedikit bergelombang.
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
