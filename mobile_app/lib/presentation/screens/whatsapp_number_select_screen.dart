import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../logic/blocs/auth_bloc.dart';

class WhatsAppNumberSelectScreen extends StatelessWidget {
  final List<dynamic> numbers;

  const WhatsAppNumberSelectScreen({super.key, required this.numbers});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Select WhatsApp Number'),
        backgroundColor: const Color(0xFF075E54),
        foregroundColor: Colors.white,
      ),
      body: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Choose a WhatsApp number to continue:',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 20),
            Expanded(
              child: ListView.builder(
                itemCount: numbers.length,
                itemBuilder: (context, index) {
                  final number = numbers[index];
                  return Card(
                    elevation: 2,
                    margin: const EdgeInsets.only(bottom: 15),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                      leading: const CircleAvatar(
                        backgroundColor: Color(0xFF128C7E),
                        child: Icon(Icons.chat_bubble, color: Colors.white),
                      ),
                      title: Text(
                        number['verified_name'] ?? 'WhatsApp Number',
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      subtitle: Text(number['display_number'] ?? 'No number'),
                      trailing: const Icon(Icons.chevron_right),
                      onTap: () {
                        context.read<AuthBloc>().add(WhatsAppNumberSelected(number['id'].toString()));
                      },
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
