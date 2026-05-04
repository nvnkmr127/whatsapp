import 'dart:async';
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

class _LoginScreenState extends State<LoginScreen> {
  final _mobileController = TextEditingController();
  final _otpController = TextEditingController();
  bool _otpSent = false;
  int _resendCooldown = 0;
  Timer? _cooldownTimer;

  @override
  void initState() {
    super.initState();
    _loadSavedConfig();
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
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
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
                Container(
                  decoration: const BoxDecoration(
                    gradient: LinearGradient(
                      colors: [Color(0xFF075E54), Color(0xFF128C7E)],
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                    ),
                  ),
                  child: Center(
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.all(30),
                      child: Card(
                        elevation: 10,
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(20)),
                        child: Padding(
                          padding:
                              const EdgeInsets.symmetric(horizontal: 20, vertical: 40),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.qr_code_2,
                                  size: 60, color: Color(0xFF128C7E)),
                              const SizedBox(height: 10),
                              const Text(
                                'Watxio',
                                style: TextStyle(
                                    fontSize: 24,
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFF075E54)),
                              ),
                              const SizedBox(height: 10),
                              const Text(
                                'Login with your mobile number',
                                style: TextStyle(color: Colors.grey, fontSize: 14),
                              ),
                              const SizedBox(height: 30),

                              // Mobile Number Field
                              _buildTextField(
                                controller: _mobileController,
                                hint: 'Mobile Number',
                                icon: Icons.phone_android,
                                enabled: !isLoading && !_otpSent,
                                keyboardType: TextInputType.phone,
                              ),
                              
                              if (_otpSent) ...[
                                const SizedBox(height: 15),
                                _buildTextField(
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
                                       context.read<AuthBloc>().add(OtpRequested(_mobileController.text));
                                       _startCooldown();
                                     },
                                     child: Text(
                                       _resendCooldown > 0 ? 'Resend in ${_resendCooldown}s' : 'Resend OTP',
                                       style: const TextStyle(fontSize: 12),
                                     ),
                                   ),
                                 ),
                              ],

                              const SizedBox(height: 30),
                              SizedBox(
                                width: double.infinity,
                                height: 55,
                                child: ElevatedButton(
                                  onPressed: isLoading ? null : _handleLogin,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFF25D366),
                                  ),
                                  child: Text(
                                      !_otpSent ? 'Send OTP' : 'Enter Workspace'),
                                ),
                              ),
                              
                              const SizedBox(height: 25),
                              const Row(
                                children: [
                                  Expanded(child: Divider()),
                                  Padding(
                                    padding: EdgeInsets.symmetric(horizontal: 10),
                                    child: Text('OR', style: TextStyle(color: Colors.grey, fontSize: 12)),
                                  ),
                                  Expanded(child: Divider()),
                                ],
                              ),
                              const SizedBox(height: 20),
                              
                              // QR Scan Option
                              SizedBox(
                                width: double.infinity,
                                child: OutlinedButton.icon(
                                  icon: const Icon(Icons.qr_code_scanner, size: 20),
                                  label: const Text('Login with QR Code'),
                                  onPressed: isLoading ? null : () async {
                                    final result = await Navigator.push(
                                      context,
                                      MaterialPageRoute(builder: (context) => const AppConfigScreen()),
                                    );
                                    if (result == true) _loadSavedConfig();
                                  },
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
                
                if (isLoading)
                  Container(
                    color: Colors.black.withValues(alpha: 0.3),
                    child: const Center(
                      child: CircularProgressIndicator(color: Colors.white),
                    ),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String hint,
    required IconData icon,
    bool obscure = false,
    bool enabled = true,
    TextInputType? keyboardType,
  }) {
    return TextField(
      controller: controller,
      obscureText: obscure,
      enabled: enabled,
      keyboardType: keyboardType,
      decoration: InputDecoration(
        labelText: hint,
        hintText: hint,
        prefixIcon: Icon(icon),
        filled: true,
        fillColor: Colors.grey.shade50,
        border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(15),
            borderSide: BorderSide.none),
      ),
    );
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
}
