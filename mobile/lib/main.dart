import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;

import 'config/api_config.dart';
import 'services/auth_service.dart';
import 'register_page.dart';
import 'login_page.dart';
import 'home_page.dart';
import 'profile/profile_page.dart';
import 'risk_map/risk_map_page.dart';

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
    const SnackBar(content: Text('Menghubungkan ke server Laravel...')),
  );

  try {
    final response = await http
        .get(Uri.parse(ApiConfig.user))
        .timeout(const Duration(seconds: 10));

    debugPrint('Status API: ${response.statusCode}');

    if (!context.mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Status API: ${response.statusCode}'),
        backgroundColor: response.statusCode == 401
            ? Colors.green
            : Colors.blueGrey,
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
      locale: const Locale('id'),
      supportedLocales: const [Locale('id'), Locale('en')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      theme: ThemeData(
        useMaterial3: true,
        fontFamily: 'Poppins',
        scaffoldBackgroundColor: const Color(0xFFFFEEF1),
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFFD50032)),
      ),
      routes: {
        '/welcome': (_) => const WelcomePage(),
        '/risk-map': (_) => const RiskMapPage(),
        '/profile': (_) => const ProfilePage(),
      },
      home: const SessionGate(),
    );
  }
}

class SessionGate extends StatefulWidget {
  const SessionGate({super.key});

  @override
  State<SessionGate> createState() => _SessionGateState();
}

class _SessionGateState extends State<SessionGate> {
  SessionRestoreStatus? _status;
  bool _hasSavedSession = false;

  @override
  void initState() {
    super.initState();
    _restoreSession();
  }

  Future<void> _restoreSession() async {
    final status = await AuthService.restoreSession();
    final savedUser = await AuthService.getSavedUser();
    final savedToken = await AuthService.getToken();

    if (!mounted) return;

    setState(() {
      _status = status;
      _hasSavedSession = savedUser != null && savedToken?.isNotEmpty == true;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_status == null) {
      return const _SplashArtScreen();
    }

    if (_status == SessionRestoreStatus.authenticated) {
      return const HomePage();
    }

    if (_status == SessionRestoreStatus.networkUnavailable &&
        _hasSavedSession) {
      return HomePage(offlineMode: true, onRetryConnection: _restoreSession);
    }

    if (_status == SessionRestoreStatus.signedOut) {
      return const WelcomePage();
    }

    return Scaffold(
      backgroundColor: WelcomePage.pinkBackground,
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.cloud_off_outlined, size: 48),
                const SizedBox(height: 16),
                const Text(
                  'Sesi belum dapat diperiksa karena server tidak terjangkau.',
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: _restoreSession,
                  child: const Text('Coba Lagi'),
                ),
                TextButton(
                  onPressed: () => setState(() {
                    _status = SessionRestoreStatus.signedOut;
                  }),
                  child: const Text('Ke halaman masuk'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _SplashArtScreen extends StatelessWidget {
  const _SplashArtScreen();

  static const Color _navigationBarColor = Color(0xFF260209);

  @override
  Widget build(BuildContext context) {
    final screenSize = MediaQuery.sizeOf(context);

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        systemNavigationBarColor: _navigationBarColor,
        systemNavigationBarIconBrightness: Brightness.light,
        systemNavigationBarDividerColor: _navigationBarColor,
      ),
      child: Scaffold(
        backgroundColor: _navigationBarColor,
        body: Stack(
          fit: StackFit.expand,
          children: [
            Image.asset(
              'assets/images/SplashArt.png',
              fit: BoxFit.cover,
              alignment: Alignment.topCenter,
            ),
            Positioned(
              top: screenSize.height * 0.15,
              left: 0,
              right: 0,
              child: Center(
                child: SizedBox(
                  width: 366,
                  height: 213,
                  child: Image.asset(
                    'assets/images/logo_sigap_putih_transparan.png',
                    fit: BoxFit.contain,
                  ),
                ),
              ),
            ),
            Positioned(
              top: screenSize.height * 0.44,
              left: 28,
              right: 28,
              child: const Text(
                'Bersama lebih siap\nhadapi bencana',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 24,
                  height: 1.4,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
            Positioned(
              left: 0,
              right: 0,
              bottom: 48,
              child: Center(
                child: SizedBox(
                  width: 220,
                  height: 6,
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: const LinearProgressIndicator(
                      backgroundColor: Color(0x66FFFFFF),
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
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
                          MaterialPageRoute(builder: (_) => const LoginPage()),
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
