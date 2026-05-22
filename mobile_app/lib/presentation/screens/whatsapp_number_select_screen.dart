import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../logic/blocs/auth_bloc.dart';

class WhatsAppNumberSelectScreen extends StatelessWidget {
  final List<dynamic> numbers;

  const WhatsAppNumberSelectScreen({super.key, required this.numbers});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0C1013),
      appBar: AppBar(
        title: const Text('Select WhatsApp Number', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFE2E8F0))),
        backgroundColor: const Color(0xFF0C1013),
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: Stack(
        children: [
          // Top-right emerald glow
          Positioned(
            top: -120,
            right: -120,
            child: Container(
              width: 320,
              height: 320,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    const Color(0xFF128C7E).withOpacity(0.15),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
          ),
          // Bottom-left emerald glow
          Positioned(
            bottom: -120,
            left: -120,
            child: Container(
              width: 320,
              height: 320,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    const Color(0xFF25D366).withOpacity(0.10),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
          ),
          // Main content
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 20),
                  const Text(
                    'Choose a WhatsApp number to continue:',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFFE2E8F0),
                      letterSpacing: -0.5,
                    ),
                  ),
                  const SizedBox(height: 24),
                  Expanded(
                    child: ListView.builder(
                      itemCount: numbers.length,
                      itemBuilder: (context, index) {
                        final number = numbers[index];
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 16),
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(20),
                            child: BackdropFilter(
                              filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
                              child: Container(
                                decoration: BoxDecoration(
                                  color: const Color(0xFF1C2023).withOpacity(0.45),
                                  borderRadius: BorderRadius.circular(20),
                                  border: Border.all(
                                    color: Colors.white.withOpacity(0.08),
                                    width: 1.2,
                                  ),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withOpacity(0.2),
                                      blurRadius: 15,
                                      offset: const Offset(0, 8),
                                    ),
                                  ],
                                ),
                                child: ListTile(
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                                  leading: Container(
                                    padding: const EdgeInsets.all(2),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFF128C7E).withOpacity(0.15),
                                      shape: BoxShape.circle,
                                      border: Border.all(
                                        color: const Color(0xFF72D8C8).withOpacity(0.2),
                                        width: 1,
                                      ),
                                    ),
                                    child: const CircleAvatar(
                                      backgroundColor: Colors.transparent,
                                      radius: 20,
                                      child: Icon(
                                        Icons.chat_bubble_outline,
                                        color: Color(0xFF72D8C8),
                                        size: 20,
                                      ),
                                    ),
                                  ),
                                  title: Text(
                                    number['verified_name'] ?? 'WhatsApp Number',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 18,
                                      color: Color(0xFFE2E8F0),
                                    ),
                                  ),
                                  subtitle: Padding(
                                    padding: const EdgeInsets.only(top: 4),
                                    child: Text(
                                      number['display_number'] ?? 'No number',
                                      style: const TextStyle(color: Colors.grey, fontSize: 13),
                                    ),
                                  ),
                                  trailing: const Icon(
                                    Icons.chevron_right,
                                    color: Color(0xFF72D8C8),
                                  ),
                                  onTap: () {
                                    context.read<AuthBloc>().add(WhatsAppNumberSelected(number['id'].toString()));
                                  },
                                ),
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
