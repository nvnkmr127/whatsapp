import 'package:flutter/material.dart';

class StarredMessagesScreen extends StatelessWidget {
  final dynamic isar;
  const StarredMessagesScreen({super.key, this.isar});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Starred Messages')),
      body: const Center(child: Text('Starred messages are only available on mobile.')),
    );
  }
}
