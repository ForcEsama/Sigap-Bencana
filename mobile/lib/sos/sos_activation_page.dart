import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'sos_disaster_type_page.dart';

class SosActivationPage extends StatefulWidget {
  const SosActivationPage({super.key});

  @override
  State<SosActivationPage> createState() => _SosActivationPageState();
}

class _SosActivationPageState extends State<SosActivationPage>
    with TickerProviderStateMixin {
  late final AnimationController _holdController;
  late final AnimationController _pulseController;

  bool _isOpeningNextStep = false;
  bool _disablePulseAnimation = false;

  @override
  void initState() {
    super.initState();

    _holdController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 3),
    )..addStatusListener(_onHoldStatusChanged);

    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 5200),
    )..repeat();
  }

  @override
  void dispose() {
    _holdController
      ..removeStatusListener(_onHoldStatusChanged)
      ..dispose();
    _pulseController.dispose();

    super.dispose();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _disablePulseAnimation = MediaQuery.of(context).disableAnimations;
    if (_disablePulseAnimation) {
      _pulseController.stop();
    } else if (!_isOpeningNextStep && !_pulseController.isAnimating) {
      _pulseController.repeat();
    }
  }

  void _onHoldStatusChanged(AnimationStatus status) {
    if (status != AnimationStatus.completed || _isOpeningNextStep) {
      return;
    }

    _isOpeningNextStep = true;
    _pulseController.stop();
    HapticFeedback.mediumImpact();

    Navigator.of(context)
        .push<void>(
          MaterialPageRoute<void>(builder: (_) => const SosDisasterTypePage()),
        )
        .then((_) {
          if (!mounted) return;

          _isOpeningNextStep = false;
          _holdController.reset();
          if (!_disablePulseAnimation) _pulseController.repeat();
        });
  }

  void _startHold() {
    if (_isOpeningNextStep) return;

    _holdController.forward(from: 0);
  }

  void _cancelHold() {
    if (_holdController.isAnimating) {
      _holdController.reverse();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF252525),
      body: Stack(
        fit: StackFit.expand,
        children: [
          // BACKGROUND IMAGE
          Positioned.fill(
            child: Image.asset(
              'assets/images/map_dummy1.png',
              fit: BoxFit.cover,
              errorBuilder: (context, error, stackTrace) {
                return const DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [Color(0xFF5D5960), Color(0xFF342D35)],
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                    ),
                  ),
                );
              },
            ),
          ),

          // DARK OVERLAY
          const Positioned.fill(child: ColoredBox(color: Color(0xB8000000))),

          // MAIN CONTENT
          SafeArea(
            child: Column(
              children: [
                const Spacer(flex: 2),

                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 24),
                  child: Text(
                    'Aktifkan Mode Darurat',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 25,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),

                const SizedBox(height: 10),

                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 24),
                  child: Text(
                    'Tekan dan tahan selama\n3 detik untuk melanjutkan',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 19,
                      height: 1.35,
                    ),
                  ),
                ),

                const Spacer(),

                // SOS HOLD BUTTON
                GestureDetector(
                  onTapDown: (_) => _startHold(),
                  onTapUp: (_) => _cancelHold(),
                  onTapCancel: _cancelHold,
                  child: AnimatedBuilder(
                    animation: _pulseController,
                    builder: (context, child) {
                      final phase = _pulseController.value * math.pi * 2;
                      final outerPulse = _pulseWave(phase);
                      final middlePulse = _pulseWave(phase + 0.8);
                      final innerPulse = _pulseWave(phase + 1.6);

                      return SizedBox(
                        width: 232,
                        height: 232,
                        child: Stack(
                          alignment: Alignment.center,
                          children: [
                            Transform.scale(
                              scale: 0.975 + outerPulse * 0.025,
                              child: _ring(
                                232,
                                Colors.white.withValues(
                                  alpha: 0.12 + outerPulse * 0.08,
                                ),
                              ),
                            ),

                            Transform.scale(
                              scale: 0.98 + middlePulse * 0.02,
                              child: _ring(
                                190,
                                const Color(
                                  0xFFFF8999,
                                ).withValues(alpha: 0.38 + middlePulse * 0.17),
                              ),
                            ),

                            Transform.scale(
                              scale: 0.985 + innerPulse * 0.015,
                              child: _ring(150, const Color(0xFFFF465A)),
                            ),

                            SizedBox(
                              width: 108,
                              height: 108,
                              child: CircularProgressIndicator(
                                value: _holdController.value,
                                strokeWidth: 5,
                                backgroundColor: Colors.white24,
                                valueColor: const AlwaysStoppedAnimation<Color>(
                                  Colors.white,
                                ),
                              ),
                            ),

                            Container(
                              width: 98,
                              height: 98,
                              decoration: const BoxDecoration(
                                color: Color(0xFFFF0520),
                                shape: BoxShape.circle,
                              ),
                              alignment: Alignment.center,
                              child: const Text(
                                'SOS',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 25,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                ),

                const Spacer(flex: 2),

                // CANCEL BUTTON
                Padding(
                  padding: const EdgeInsets.only(bottom: 18),
                  child: IconButton.filled(
                    onPressed: () => Navigator.of(context).pop(),
                    style: IconButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: Colors.black,
                      fixedSize: const Size(56, 56),
                    ),
                    icon: const Icon(Icons.close, size: 28),
                    tooltip: 'Batal',
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _ring(double size, Color color) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(color: color, shape: BoxShape.circle),
    );
  }

  double _pulseWave(double phase) => (math.sin(phase) + 1) / 2;
}
