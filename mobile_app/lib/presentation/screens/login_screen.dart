import 'dart:async';
import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../logic/blocs/auth_bloc.dart';
import '../../core/config_service.dart';
import '../../core/api_service.dart';
import 'app_config_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> with SingleTickerProviderStateMixin {
  final _mobileController = TextEditingController();
  final _otpController = TextEditingController();
  bool _otpSent = false;
  int _resendCooldown = 0;
  Timer? _cooldownTimer;

  late AnimationController _bgAnimationController;
  late Animation<double> _glowAnimation;

  @override
  void initState() {
    super.initState();
    _loadSavedConfig();

    _bgAnimationController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 8),
    )..repeat(reverse: true);

    _glowAnimation = Tween<double>(begin: 0.8, end: 1.2).animate(
      CurvedAnimation(
        parent: _bgAnimationController,
        curve: Curves.easeInOut,
      ),
    );
  }

  Future<void> _loadSavedConfig() async {
    await context.read<ApiService>().reloadConfig();
    final token = await ConfigService.getSetupToken();

    if (token != null && token.isNotEmpty) {
      await ConfigService.clearSetupToken();
      if (mounted) {
        context.read<AuthBloc>().add(TokenLoginRequested(token));
      }
    }
  }

  void _startCooldown() {
    setState(() => _resendCooldown = 30);
    _cooldownTimer?.cancel();
    _cooldownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_resendCooldown == 0) {
        timer.cancel();
      } else {
        setState(() => _resendCooldown--);
      }
    });
  }

  @override
  void dispose() {
    _cooldownTimer?.cancel();
    _mobileController.dispose();
    _otpController.dispose();
    _bgAnimationController.dispose();
    super.dispose();
  }

  void _handleLogin() {
    if (!_otpSent) {
      if (_mobileController.text.trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please enter your mobile number')));
        return;
      }
      context.read<AuthBloc>().add(OtpRequested(_mobileController.text.trim()));
      _startCooldown();
    } else {
      if (_otpController.text.trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please enter the OTP')));
        return;
      }
      if (_otpController.text.trim().length < 4) {
         ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('OTP must be at least 4 digits')));
         return;
      }
      context.read<AuthBloc>().add(OtpLoginRequested(_mobileController.text.trim(), _otpController.text.trim()));
    }
  }

  Widget _buildBrandingLogo() {
    return AnimatedBuilder(
      animation: _bgAnimationController,
      builder: (context, child) {
        final scale = 1.0 + (_glowAnimation.value - 1.0) * 0.05;
        return Transform.scale(
          scale: scale,
          child: Stack(
            alignment: Alignment.center,
            children: [
              // Outer glow ring
              Container(
                width: 90,
                height: 90,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: const Color(0xFF72D8C8).withOpacity(0.1 * _glowAnimation.value),
                    width: 1,
                  ),
                ),
              ),
              // Inner glow ring
              Container(
                width: 76,
                height: 76,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: const Color(0xFF72D8C8).withOpacity(0.2 * (2.0 - _glowAnimation.value)),
                    width: 1.5,
                  ),
                ),
              ),
              // Central logo circle
              Container(
                width: 60,
                height: 60,
                decoration: BoxDecoration(
                  color: const Color(0xFF128C7E).withOpacity(0.15),
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF72D8C8).withOpacity(0.15 * _glowAnimation.value),
                      blurRadius: 15,
                      spreadRadius: 2,
                    ),
                  ],
                ),
                child: const Icon(
                  Icons.chat_bubble_outline,
                  size: 28,
                  color: Color(0xFF72D8C8),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildMobileFormSection(bool isLoading) {
    return Column(
      key: const ValueKey('mobile_form'),
      mainAxisSize: MainAxisSize.min,
      children: [
        PremiumTextField(
          controller: _mobileController,
          hint: 'Mobile Number',
          icon: Icons.phone_android,
          enabled: !isLoading,
          keyboardType: TextInputType.phone,
        ),
        const SizedBox(height: 24),
        _buildPrimaryCTA(
          isLoading: isLoading,
          text: 'Send OTP',
          onPressed: _handleLogin,
        ),
      ],
    );
  }

  Widget _buildOtpFormSection(bool isLoading) {
    return Column(
      key: const ValueKey('otp_form'),
      mainAxisSize: MainAxisSize.min,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              _mobileController.text,
              style: const TextStyle(color: Colors.grey, fontSize: 14, fontWeight: FontWeight.w500),
            ),
            TextButton(
              onPressed: isLoading ? null : () {
                setState(() {
                  _otpSent = false;
                  _otpController.clear();
                });
              },
              child: Text(
                'Change',
                style: TextStyle(
                  color: isLoading ? Colors.grey : const Color(0xFF72D8C8),
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        PremiumTextField(
          controller: _otpController,
          hint: '6-Digit OTP',
          icon: Icons.security,
          enabled: !isLoading,
          keyboardType: TextInputType.number,
        ),
        Align(
          alignment: Alignment.centerRight,
          child: TextButton(
            onPressed: (isLoading || _resendCooldown > 0) ? null : () {
              context.read<AuthBloc>().add(OtpRequested(_mobileController.text.trim()));
              _startCooldown();
            },
            child: Text(
              _resendCooldown > 0 ? 'Resend in ${_resendCooldown}s' : 'Resend OTP',
              style: TextStyle(
                fontSize: 12,
                color: (isLoading || _resendCooldown > 0) ? Colors.grey : const Color(0xFF72D8C8),
              ),
            ),
          ),
        ),
        const SizedBox(height: 16),
        _buildPrimaryCTA(
          isLoading: isLoading,
          text: 'Enter Workspace',
          onPressed: _handleLogin,
        ),
      ],
    );
  }

  Widget _buildPrimaryCTA({
    required bool isLoading,
    required String text,
    required VoidCallback onPressed,
  }) {
    return Container(
      width: double.infinity,
      height: 54,
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF0F766E), Color(0xFF10B981)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF10B981).withOpacity(0.25),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ElevatedButton(
        onPressed: isLoading ? null : onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.transparent,
          shadowColor: Colors.transparent,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
        ),
        child: isLoading
            ? const SizedBox(
                width: 24,
                height: 24,
                child: CircularProgressIndicator(
                  color: Colors.white,
                  strokeWidth: 2.5,
                ),
              )
            : Text(
                text,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                  letterSpacing: 0.5,
                ),
              ),
      ),
    );
  }

  Widget _buildQrButton(bool isLoading) {
    return Container(
      width: double.infinity,
      height: 52,
      decoration: BoxDecoration(
        color: const Color(0xFF1C2023).withOpacity(0.3),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: const Color(0xFF72D8C8).withOpacity(0.3),
          width: 1.2,
        ),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: isLoading ? null : () async {
          final result = await Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => const AppConfigScreen()),
          );
          if (result == true) _loadSavedConfig();
        },
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.qr_code_scanner,
              size: 20,
              color: const Color(0xFF72D8C8).withOpacity(0.9),
            ),
            const SizedBox(width: 10),
            const Text(
              'Login with QR Code',
              style: TextStyle(
                color: Color(0xFF72D8C8),
                fontWeight: FontWeight.w600,
                fontSize: 15,
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0C1013),
      body: BlocListener<AuthBloc, AuthState>(
        listener: (context, state) {
          if (state is AuthError) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(state.message)),
            );
          }
          if (state is OtpSent) {
            setState(() {
              _otpSent = true;
            });
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('OTP sent to your mobile')),
            );
          }
        },
        child: BlocBuilder<AuthBloc, AuthState>(
          builder: (context, state) {
            final isLoading = state is AuthLoading;

            return Stack(
              children: [
                // Base background gradient
                Container(
                  decoration: const BoxDecoration(
                    color: Color(0xFF0C1013),
                  ),
                ),
                // Animated background radial glows
                AnimatedBuilder(
                  animation: _bgAnimationController,
                  builder: (context, child) {
                    final glowScale = _glowAnimation.value;
                    return Stack(
                      children: [
                        Positioned(
                          top: -120 * glowScale,
                          right: -120 * glowScale,
                          child: Container(
                            width: 380 * glowScale,
                            height: 380 * glowScale,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              gradient: RadialGradient(
                                colors: [
                                  const Color(0xFF128C7E).withOpacity(0.22 * (2.0 - glowScale)),
                                  Colors.transparent,
                                ],
                              ),
                            ),
                          ),
                        ),
                        Positioned(
                          bottom: -120 * glowScale,
                          left: -120 * glowScale,
                          child: Container(
                            width: 380 * glowScale,
                            height: 380 * glowScale,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              gradient: RadialGradient(
                                colors: [
                                  const Color(0xFF25D366).withOpacity(0.18 * (2.0 - glowScale)),
                                  Colors.transparent,
                                ],
                              ),
                            ),
                          ),
                        ),
                        Positioned(
                          top: MediaQuery.of(context).size.height * 0.4,
                          left: MediaQuery.of(context).size.width * 0.2,
                          child: Container(
                            width: 450 * glowScale,
                            height: 450 * glowScale,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              gradient: RadialGradient(
                                colors: [
                                  const Color(0xFF0F766E).withOpacity(0.08 * (1.5 - glowScale)),
                                  Colors.transparent,
                                ],
                              ),
                            ),
                          ),
                        ),
                      ],
                    );
                  },
                ),
                
                // Centered Glassmorphic login box
                Center(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.all(24),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(24),
                      child: BackdropFilter(
                        filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
                        child: Container(
                          decoration: BoxDecoration(
                            color: const Color(0xFF1C2023).withOpacity(0.45),
                            borderRadius: BorderRadius.circular(24),
                            border: Border.all(
                              color: Colors.white.withOpacity(0.08),
                              width: 1.2,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.35),
                                blurRadius: 30,
                                offset: const Offset(0, 15),
                              ),
                            ],
                          ),
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 40),
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                _buildBrandingLogo(),
                                const SizedBox(height: 20),
                                // Brand name and Active status dot
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    const Text(
                                      'Watxio Pro',
                                      style: TextStyle(
                                        fontSize: 28,
                                        fontWeight: FontWeight.bold,
                                        color: Color(0xFFE2E8F0),
                                        letterSpacing: -0.5,
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Container(
                                      width: 8,
                                      height: 8,
                                      decoration: BoxDecoration(
                                        color: const Color(0xFF10B981),
                                        shape: BoxShape.circle,
                                        boxShadow: [
                                          BoxShadow(
                                            color: const Color(0xFF10B981).withOpacity(0.6),
                                            blurRadius: 6,
                                            spreadRadius: 2,
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                AnimatedSwitcher(
                                  duration: const Duration(milliseconds: 300),
                                  child: Text(
                                    _otpSent 
                                        ? 'Enter the 6-digit verification code' 
                                        : 'Enter your mobile number to get started',
                                    key: ValueKey(_otpSent),
                                    style: const TextStyle(
                                      color: Colors.grey,
                                      fontSize: 14,
                                    ),
                                    textAlign: TextAlign.center,
                                  ),
                                ),
                                const SizedBox(height: 32),

                                // Phase-switching Input card (mobile / otp)
                                AnimatedSwitcher(
                                  duration: const Duration(milliseconds: 350),
                                  switchInCurve: Curves.easeInOut,
                                  switchOutCurve: Curves.easeInOut,
                                  transitionBuilder: (child, animation) {
                                    return FadeTransition(
                                      opacity: animation,
                                      child: SizeTransition(
                                        sizeFactor: animation,
                                        axisAlignment: -1.0,
                                        child: child,
                                      ),
                                    );
                                  },
                                  child: _otpSent 
                                      ? _buildOtpFormSection(isLoading) 
                                      : _buildMobileFormSection(isLoading),
                                ),
                                
                                const SizedBox(height: 24),
                                Row(
                                  children: [
                                    Expanded(
                                      child: Divider(
                                        color: Colors.white.withOpacity(0.1),
                                        thickness: 1,
                                      ),
                                    ),
                                    Padding(
                                      padding: const EdgeInsets.symmetric(horizontal: 16),
                                      child: Text(
                                        'OR',
                                        style: TextStyle(
                                          color: Colors.grey.shade500,
                                          fontSize: 12,
                                          fontWeight: FontWeight.w600,
                                          letterSpacing: 1.0,
                                        ),
                                      ),
                                    ),
                                    Expanded(
                                      child: Divider(
                                        color: Colors.white.withOpacity(0.1),
                                        thickness: 1,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 24),
                                
                                // QR Scan Option
                                _buildQrButton(isLoading),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
                
                if (isLoading)
                  Container(
                    color: Colors.black.withOpacity(0.5),
                    child: const Center(
                      child: CircularProgressIndicator(color: Color(0xFF72D8C8)),
                    ),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class PremiumTextField extends StatefulWidget {
  final TextEditingController controller;
  final String hint;
  final IconData icon;
  final bool enabled;
  final TextInputType? keyboardType;

  const PremiumTextField({
    super.key,
    required this.controller,
    required this.hint,
    required this.icon,
    this.enabled = true,
    this.keyboardType,
  });

  @override
  State<PremiumTextField> createState() => _PremiumTextFieldState();
}

class _PremiumTextFieldState extends State<PremiumTextField> with SingleTickerProviderStateMixin {
  late FocusNode _focusNode;
  late AnimationController _focusController;
  late Animation<double> _focusAnimation;

  @override
  void initState() {
    super.initState();
    _focusNode = FocusNode();
    _focusController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 250),
    );
    _focusAnimation = CurvedAnimation(
      parent: _focusController,
      curve: Curves.easeInOut,
    );

    _focusNode.addListener(_handleFocusChange);
  }

  void _handleFocusChange() {
    if (_focusNode.hasFocus) {
      _focusController.forward();
    } else {
      _focusController.reverse();
    }
  }

  @override
  void dispose() {
    _focusNode.removeListener(_handleFocusChange);
    _focusNode.dispose();
    _focusController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _focusAnimation,
      builder: (context, child) {
        final borderGlowOpacity = _focusAnimation.value * 0.25;

        return Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF72D8C8).withOpacity(borderGlowOpacity),
                blurRadius: 10 * _focusAnimation.value,
                spreadRadius: 1 * _focusAnimation.value,
              ),
            ],
          ),
          child: TextField(
            controller: widget.controller,
            focusNode: _focusNode,
            enabled: widget.enabled,
            keyboardType: widget.keyboardType,
            style: const TextStyle(color: Colors.white, fontSize: 15),
            decoration: InputDecoration(
              labelText: widget.hint,
              labelStyle: TextStyle(
                color: Color.lerp(Colors.grey.shade400, const Color(0xFF72D8C8), _focusAnimation.value),
                fontSize: 14,
              ),
              hintText: widget.hint,
              hintStyle: TextStyle(color: Colors.grey.shade600, fontSize: 14),
              prefixIcon: Icon(
                widget.icon,
                color: Color.lerp(
                  const Color(0xFF72D8C8).withOpacity(0.6),
                  const Color(0xFF72D8C8),
                  _focusAnimation.value,
                ),
              ),
              filled: true,
              fillColor: Color.lerp(
                Colors.black.withOpacity(0.35),
                const Color(0xFF1C2023).withOpacity(0.6),
                _focusAnimation.value,
              ),
              contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: BorderSide(
                  color: Colors.white.withOpacity(0.08),
                  width: 1.2,
                ),
              ),
              disabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: BorderSide(
                  color: Colors.white.withOpacity(0.03),
                  width: 1.2,
                ),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: const BorderSide(
                  color: Color(0xFF72D8C8),
                  width: 1.5,
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}
