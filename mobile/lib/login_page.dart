
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import 'config/api_config.dart';
import 'services/auth_service.dart';

import 'home_page.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  static const Color red = Color(0xFFD50032);
  static const Color pink = Color(0xFFFFEEF1);

  final _formKey = GlobalKey<FormState>();
  final _identityController = TextEditingController();
  final _passwordController = TextEditingController();

  bool _obscurePassword = true;
  bool _rememberMe = false;
  bool _isLoading = false;

  @override
  void dispose() {
    _identityController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: red,
      ),
    );
  }

  Future<void> _login() async {
    FocusScope.of(context).unfocus();

    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      final response = await http
          .post(
            Uri.parse(ApiConfig.login),
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
            body: jsonEncode({
              'email': _identityController.text.trim(),
              'password': _passwordController.text,
            }),
          )
          .timeout(const Duration(seconds: 20));

      debugPrint('Login status: ${response.statusCode}');

      dynamic decoded;

      try {
        decoded = jsonDecode(response.body);
      } catch (_) {
        decoded = null;
      }

      final data = decoded is Map<String, dynamic>
          ? decoded
          : <String, dynamic>{};

      if (!mounted) return;

      if (response.statusCode == 200) {
        final token = data['token'];

        if (token == null || token.toString().isEmpty) {
          _showMessage(
            'Login berhasil, tetapi token tidak ditemukan dari server.',
          );
          return;
        }
        final userData = data['user'];

        if (userData is! Map<String, dynamic>) {
          _showMessage('Data pengguna tidak ditemukan dari server.');
          return;
        }

        await AuthService.saveSession(
          token: token.toString(),
          user: userData,
        );
        // Token dan data pengguna sudah disimpan secara aman.

        _showMessage('Login berhasil! Selamat datang.');

        debugPrint('Token autentikasi berhasil diterima.');

        // Sementara kembali ke halaman sebelumnya.
        // Berikutnya kita ganti dengan homepage khusus pengguna login.
        await Future.delayed(const Duration(milliseconds: 700));

        if (!mounted) return;

        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(
            builder: (_) => const HomePage(),
          ),
          (route) => false,
        );
      } else {
        final errors = data['errors'];

        if (errors is Map && errors.isNotEmpty) {
          final firstError = errors.values.first;
          final message =
              firstError is List && firstError.isNotEmpty
                  ? firstError.first.toString()
                  : firstError.toString();

          _showMessage(message);
        } else {
          _showMessage(
            data['message']?.toString() ??
                'Login gagal. Periksa email dan kata sandi.',
          );
        }
      }
    } catch (e, stackTrace) {
      debugPrint('Login error: $e');
      debugPrint('Stack trace: $stackTrace');

      if (mounted) {
        _showMessage('Error: $e');
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Widget _input({
    required TextEditingController controller,
    required String hint,
    required IconData icon,
    bool obscureText = false,
    Widget? suffixIcon,
    TextInputType keyboardType = TextInputType.text,
    String? Function(String?)? validator,
  }) {
    return SizedBox(
      height: 41,
      child: TextFormField(
        controller: controller,
        keyboardType: keyboardType,
        obscureText: obscureText,
        validator: validator,
        style: const TextStyle(fontSize: 12),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: const TextStyle(
            fontSize: 11,
            color: Color(0xFF716A75),
          ),
          prefixIcon: Icon(
            icon,
            size: 18,
            color: const Color(0xFF504B55),
          ),
          suffixIcon: suffixIcon,
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 10,
            vertical: 8,
          ),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(4),
            borderSide: const BorderSide(color: Color(0xFFBDB7BA)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(4),
            borderSide: const BorderSide(color: Color(0xFFBDB7BA)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(4),
            borderSide: const BorderSide(color: red),
          ),
          errorStyle: const TextStyle(fontSize: 9),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: pink,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Align(
                  alignment: Alignment.centerLeft,
                  child: IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.arrow_back, size: 20),
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(
                      minWidth: 28,
                      minHeight: 28,
                    ),
                  ),
                ),

                const SizedBox(height: 8),

                const Text(
                  'Masuk',
                  style: TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.w600,
                  ),
                ),

                const SizedBox(height: 4),

                const Text(
                  'Masuk ke akun SIGAP Bencana\nAnda.',
                  style: TextStyle(fontSize: 11, height: 1.45),
                ),

                const SizedBox(height: 16),

                _input(
                  controller: _identityController,
                  hint: 'Email atau Nomor Telepon',
                  icon: Icons.person_outline,
                  keyboardType: TextInputType.emailAddress,
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return 'Email wajib diisi.';
                    }
                    return null;
                  },
                ),

                const SizedBox(height: 7),

                _input(
                  controller: _passwordController,
                  hint: 'Kata Sandi',
                  icon: Icons.lock_outline,
                  obscureText: _obscurePassword,
                  suffixIcon: IconButton(
                    visualDensity: VisualDensity.compact,
                    onPressed: () {
                      setState(() {
                        _obscurePassword = !_obscurePassword;
                      });
                    },
                    icon: Icon(
                      _obscurePassword
                          ? Icons.visibility_off_outlined
                          : Icons.visibility_outlined,
                      size: 18,
                    ),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Kata sandi wajib diisi.';
                    }
                    return null;
                  },
                ),

                const SizedBox(height: 5),

                Row(
                  children: [
                    SizedBox(
                      width: 24,
                      height: 30,
                      child: Checkbox(
                        value: _rememberMe,
                        onChanged: (value) {
                          setState(() {
                            _rememberMe = value ?? false;
                          });
                        },
                        activeColor: red,
                        side: const BorderSide(color: red),
                        materialTapTargetSize:
                            MaterialTapTargetSize.shrinkWrap,
                      ),
                    ),
                    const SizedBox(width: 4),
                    const Text(
                      'Ingat saya',
                      style: TextStyle(fontSize: 10),
                    ),
                    const Spacer(),
                    TextButton(
                      onPressed: () {
                        _showMessage(
                          'Fitur lupa kata sandi akan dibuat berikutnya.',
                        );
                      },
                      style: TextButton.styleFrom(
                        foregroundColor: red,
                        padding: EdgeInsets.zero,
                        minimumSize: Size.zero,
                        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                      child: const Text(
                        'Lupa kata sandi?',
                        style: TextStyle(fontSize: 10),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 12),

                SizedBox(
                  height: 40,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : _login,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: red,
                      foregroundColor: Colors.white,
                      disabledBackgroundColor: const Color(0xFFDD8094),
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                    child: _isLoading
                        ? const SizedBox(
                            height: 19,
                            width: 19,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Text(
                            'Masuk',
                            style: TextStyle(fontSize: 12),
                          ),
                  ),
                ),

                const SizedBox(height: 15),

                const Row(
                  children: [
                    Expanded(
                      child: Divider(color: Color(0xFFBDB7BA)),
                    ),
                    Padding(
                      padding: EdgeInsets.symmetric(horizontal: 7),
                      child: Text(
                        'atau masuk dengan',
                        style: TextStyle(
                          fontSize: 10,
                          color: Color(0xFF625C65),
                        ),
                      ),
                    ),
                    Expanded(
                      child: Divider(color: Color(0xFFBDB7BA)),
                    ),
                  ],
                ),

                const SizedBox(height: 15),

                SizedBox(
                  height: 40,
                  child: OutlinedButton(
                    onPressed: () {
                      _showMessage(
                        'Login Google akan diintegrasikan berikutnya.',
                      );
                    },
                    style: OutlinedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: Colors.black,
                      side: const BorderSide(color: Color(0xFFBDB7BA)),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(
                          Icons.g_mobiledata,
                          color: Color(0xFF4285F4),
                          size: 29,
                        ),
                        const SizedBox(width: 7),
                        const Text(
                          'Masuk dengan Google',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                const SizedBox(height: 16),

                Center(
                  child: Wrap(
                    alignment: WrapAlignment.center,
                    children: [
                      const Text(
                        'Belum punya akun? ',
                        style: TextStyle(fontSize: 10),
                      ),
                      GestureDetector(
                        onTap: () => Navigator.pop(context),
                        child: const Text(
                          'Daftar',
                          style: TextStyle(
                            fontSize: 10,
                            color: red,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
