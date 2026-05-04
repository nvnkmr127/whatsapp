import 'package:flutter/material.dart';

class EmptyInboxWidget extends StatelessWidget {
  const EmptyInboxWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(40),
            decoration: BoxDecoration(
              color: Colors.green[50],
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.chat_bubble_outline, size: 80, color: Color(0xFF128C7E)),
          ),
          const SizedBox(height: 30),
          const Text(
            'Your inbox is empty',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.black87),
          ),
          const SizedBox(height: 10),
          const Text(
            'New WhatsApp messages for your team\nwill appear here.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: Colors.grey),
          ),
        ],
      ),
    );
  }
}
