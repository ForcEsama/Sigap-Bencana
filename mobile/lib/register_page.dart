
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import 'config/api_config.dart';

class RegisterPage extends StatefulWidget {
  const RegisterPage({super.key});

  @override
  State<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends State<RegisterPage> {
  static const Color red = Color(0xFFD50032);
  static const Color pink = Color(0xFFFFEEF1);

  final _formKey = GlobalKey<FormState>();

  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  bool _obscurePassword = true;
  bool _obscureConfirmation = true;
  bool _acceptedTerms = false;
  bool _isLoading = false;

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _register() async {
    FocusScope.of(context).unfocus();

    if (!_formKey.currentState!.validate()) return;

    if (!_acceptedTerms) {
      _showMessage('Silakan setujui syarat dan ketentuan.');
      return;
    }

    setState(() => _isLoading = true);

    try {
      final response = await http
          .post(
            Uri.parse(ApiConfig.register),
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
            body: jsonEncode({
              'name': _nameController.text.trim(),
              'email': _emailController.text.trim(),
              'phone': _phoneController.text.trim(),
              'password': _passwordController.text,
              'password_confirmation':
                  _confirmPasswordController.text,
            }),
          )
          .timeout(const Duration(seconds: 20));

      final dynamic decoded = jsonDecode(response.body);
      final Map<String, dynamic> data =
          decoded is Map<String, dynamic> ? decoded : {};

      debugPrint('Register status: ${response.statusCode}');

      if (!mounted) return;

      if (response.statusCode == 200 || response.statusCode == 201) {
        _showMessage(
          data['message']?.toString() ??
              'Registrasi berhasil! Silakan masuk.',
        );

        await Future.delayed(const Duration(milliseconds: 700));

        if (!mounted) return;

        Navigator.pop(context, true);
      } else {
        final errors = data['errors'];

        if (errors is Map && errors.isNotEmpty) {
          final firstError = errors.values.first;
          final message = firstError is List && firstError.isNotEmpty
              ? firstError.first.toString()
              : firstError.toString();

          _showMessage(message);
        } else {
          _showMessage(
            data['message']?.toString() ??
                'Registrasi gagal. Periksa kembali data kamu.',
          );
        }
      }
    } catch (e) {
      debugPrint('Register error: $e');

      if (mounted) {
        _showMessage(
          'Tidak dapat menghubungi server. Periksa koneksi kamu.',
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: red,
      ),
    );
  }

  Widget _buildInput({
    required TextEditingController controller,
    required String hint,
    required IconData icon,
    TextInputType keyboardType = TextInputType.text,
    bool obscureText = false,
    Widget? suffixIcon,
    String? Function(String?)? validator,
  }) {
    return SizedBox(
      height: 42,
      child: TextFormField(
        controller: controller,
        keyboardType: keyboardType,
        obscureText: obscureText,
        validator: validator,
        style: const TextStyle(fontSize: 11),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: const TextStyle(
            fontSize: 10,
            color: Color(0xFF77717A),
          ),
          prefixIcon: Icon(
            icon,
            size: 16,
            color: const Color(0xFF5E5861),
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
            borderSide: const BorderSide(
              color: Color(0xFFD4C7CA),
            ),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(4),
            borderSide: const BorderSide(
              color: Color(0xFFD4C7CA),
            ),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(4),
            borderSide: const BorderSide(color: red),
          ),
          errorStyle: const TextStyle(fontSize: 9, height: 0.8),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: pink,
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) {
            return SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(17, 8, 17, 12),
              child: ConstrainedBox(
                constraints: BoxConstraints(
                  minHeight: constraints.maxHeight - 20,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Align(
                      alignment: Alignment.centerLeft,
                      child: IconButton(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(
                          Icons.arrow_back,
                          size: 19,
                        ),
                        padding: EdgeInsets.zero,
                        constraints: const BoxConstraints(
                          minWidth: 28,
                          minHeight: 28,
                        ),
                      ),
                    ),

                    const SizedBox(height: 2),

                    const Text(
                      'Daftar Akun',
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w600,
                      ),
                    ),

                    const SizedBox(height: 3),

                    const Text(
                      'Buat akun untuk mengakses semua fitur\n'
                      'SIGAP Bencana.',
                      style: TextStyle(
                        fontSize: 10,
                        height: 1.45,
                      ),
                    ),

                    const SizedBox(height: 13),

                    Form(
                      key: _formKey,
                      child: Column(
                        children: [
                          _buildInput(
                            controller: _nameController,
                            hint: 'Nama Lengkap',
                            icon: Icons.person_outline,
                            validator: (value) {
                              if (value == null ||
                                  value.trim().isEmpty) {
                                return 'Nama lengkap wajib diisi.';
                              }
                              return null;
                            },
                          ),

                          const SizedBox(height: 7),

                          _buildInput(
                            controller: _emailController,
                            hint: 'Email',
                            icon: Icons.mail_outline,
                            keyboardType: TextInputType.emailAddress,
                            validator: (value) {
                              if (value == null || value.trim().isEmpty) {
                                return 'Email wajib diisi.';
                              }
                              if (!RegExp(
                                r'^[^@\s]+@[^@\s]+\.[^@\s]+$',
                              ).hasMatch(value.trim())) {
                                return 'Format email tidak valid.';
                              }
                              return null;
                            },
                          ),

                          const SizedBox(height: 7),

                          _buildInput(
                            controller: _phoneController,
                            hint: 'Nomor Telepon',
                            icon: Icons.phone_outlined,
                            keyboardType: TextInputType.phone,
                          ),

                          const SizedBox(height: 7),

                          _buildInput(
                            controller: _passwordController,
                            hint: 'Kata Sandi',
                            icon: Icons.lock_outline,
                            obscureText: _obscurePassword,
                            suffixIcon: IconButton(
                              visualDensity: VisualDensity.compact,
                              onPressed: () {
                                setState(() {
                                  _obscurePassword =
                                      !_obscurePassword;
                                });
                              },
                              icon: Icon(
                                _obscurePassword
                                    ? Icons.visibility_off_outlined
                                    : Icons.visibility_outlined,
                                size: 16,
                              ),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Kata sandi wajib diisi.';
                              }
                              if (value.length < 8) {
                                return 'Minimal 8 karakter.';
                              }
                              return null;
                            },
                          ),

                          const SizedBox(height: 7),

                          _buildInput(
                            controller: _confirmPasswordController,
                            hint: 'Konfirmasi Kata Sandi',
                            icon: Icons.lock_outline,
                            obscureText: _obscureConfirmation,
                            suffixIcon: IconButton(
                              visualDensity: VisualDensity.compact,
                              onPressed: () {
                                setState(() {
                                  _obscureConfirmation =
                                      !_obscureConfirmation;
                                });
                              },
                              icon: Icon(
                                _obscureConfirmation
                                    ? Icons.visibility_off_outlined
                                    : Icons.visibility_outlined,
                                size: 16,
                              ),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Konfirmasi kata sandi wajib diisi.';
                              }
                              if (value != _passwordController.text) {
                                return 'Kata sandi tidak cocok.';
                              }
                              return null;
                            },
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 20),

                    InkWell(
                      onTap: () {
                        setState(() {
                          _acceptedTerms = !_acceptedTerms;
                        });
                      },
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          SizedBox(
                            width: 17,
                            height: 18,
                            child: Checkbox(
                              value: _acceptedTerms,
                              onChanged: (value) {
                                setState(() {
                                  _acceptedTerms = value ?? false;
                                });
                              },
                              activeColor: red,
                              materialTapTargetSize:
                                  MaterialTapTargetSize.shrinkWrap,
                              visualDensity: VisualDensity.compact,
                            ),
                          ),
                          const SizedBox(width: 5),
                          Expanded(
                            child: RichText(
                              text: const TextSpan(
                                style: TextStyle(
                                  fontSize: 9,
                                  height: 1.45,
                                  color: Colors.black,
                                ),
                                children: [
                                  TextSpan(
                                    text: 'Saya setuju dengan ',
                                  ),
                                  TextSpan(
                                    text: 'Syarat & Ketentuan',
                                    style: TextStyle(
                                      color: red,
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                  TextSpan(text: ' dan '),
                                  TextSpan(
                                    text: 'Kebijakan Privasi.',
                                    style: TextStyle(
                                      color: red,
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 14),

                    SizedBox(
                      height: 38,
                      child: ElevatedButton(
                        onPressed: _isLoading ? null : _register,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: red,
                          foregroundColor: Colors.white,
                          disabledBackgroundColor:
                              const Color(0xFFDD8094),
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(4),
                          ),
                        ),
                        child: _isLoading
                            ? const SizedBox(
                                height: 18,
                                width: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                            : const Text(
                                'Daftar Akun',
                                style: TextStyle(fontSize: 11),
                              ),
                      ),
                    ),

                    const SizedBox(height: 9),

                    Center(
                      child: Wrap(
                        alignment: WrapAlignment.center,
                        children: [
                          const Text(
                            'Sudah punya akun? ',
                            style: TextStyle(fontSize: 9),
                          ),
                          GestureDetector(
                            onTap: () {
                              _showMessage(
                                'Halaman Login akan dibuat berikutnya.',
                              );
                            },
                            child: const Text(
                              'Masuk',
                              style: TextStyle(
                                fontSize: 9,
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
            );
          },
        ),
      ),
    );
  }
}
