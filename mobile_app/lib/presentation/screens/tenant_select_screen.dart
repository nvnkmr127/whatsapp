import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../logic/blocs/auth_bloc.dart';

class TenantSelectScreen extends StatelessWidget {
  final List<dynamic> teams;

  const TenantSelectScreen({super.key, required this.teams});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0C1013),
      appBar: AppBar(
        title: const Text('Select Workspace', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFE2E8F0))),
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
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Padding(
                  padding: EdgeInsets.fromLTRB(20, 20, 20, 8),
                  child: Text(
                    'Your Organizations',
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFFE2E8F0),
                      letterSpacing: -0.5,
                    ),
                  ),
                ),
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 20),
                  child: Text(
                    'Please select the workspace you wish to manage.',
                    style: TextStyle(color: Colors.grey, fontSize: 14),
                  ),
                ),
                const SizedBox(height: 24),
                Expanded(
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                    itemCount: teams.length,
                    itemBuilder: (context, index) {
                      final team = teams[index] as dynamic;
                      final id = int.tryParse(team['id'].toString());
                      final name = team['name']?.toString() ?? 'Team';

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
                                  child: CircleAvatar(
                                    backgroundColor: Colors.transparent,
                                    radius: 20,
                                    child: Text(
                                      name.isNotEmpty ? name[0].toUpperCase() : 'T',
                                      style: const TextStyle(
                                        color: Color(0xFF72D8C8),
                                        fontWeight: FontWeight.bold,
                                        fontSize: 16,
                                      ),
                                    ),
                                  ),
                                ),
                                title: Text(
                                  name,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 18,
                                    color: Color(0xFFE2E8F0),
                                  ),
                                ),
                                subtitle: const Padding(
                                  padding: EdgeInsets.only(top: 4),
                                  child: Text(
                                    'Agent Dashboard',
                                    style: TextStyle(color: Colors.grey, fontSize: 13),
                                  ),
                                ),
                                trailing: const Icon(
                                  Icons.arrow_forward_ios,
                                  size: 14,
                                  color: Color(0xFF72D8C8),
                                ),
                                onTap: id == null
                                    ? null
                                    : () {
                                        context.read<AuthBloc>().add(TenantSelected(id));
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
        ],
      ),
    );
  }
}
