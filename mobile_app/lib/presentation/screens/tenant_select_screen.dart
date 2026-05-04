import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../logic/blocs/auth_bloc.dart';

class TenantSelectScreen extends StatelessWidget {
  final List<dynamic> teams;

  const TenantSelectScreen({super.key, required this.teams});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        title: const Text('Select Workspace', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFF075E54),
        foregroundColor: Colors.white,
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(
            padding: EdgeInsets.fromLTRB(20, 30, 20, 10),
            child: Text('Your Organizations', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Colors.black87)),
          ),
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 20),
            child: Text('Please select the workspace you wish to manage.', style: TextStyle(color: Colors.grey)),
          ),
          const SizedBox(height: 20),
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: teams.length,
              itemBuilder: (context, index) {
                final team = teams[index] as dynamic;
                final id = int.tryParse(team['id'].toString());
                final name = team['name']?.toString() ?? 'Team';

                return Card(
                  elevation: 2,
                  margin: const EdgeInsets.only(bottom: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                    leading: CircleAvatar(
                      backgroundColor: const Color(0xFF128C7E).withValues(alpha: 0.1),
                      child: Text(name[0], style: const TextStyle(color: Color(0xFF128C7E), fontWeight: FontWeight.bold)),
                    ),
                    title: Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
                    subtitle: const Text('Agent Dashboard'),
                    trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                    onTap: id == null
                        ? null
                        : () {
                            context.read<AuthBloc>().add(TenantSelected(id));
                          },
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

