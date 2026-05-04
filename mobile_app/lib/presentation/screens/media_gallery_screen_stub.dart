import 'package:flutter/material.dart';

class MediaGalleryScreen extends StatelessWidget {
  final dynamic isar;
  final int conversationId;
  const MediaGalleryScreen({super.key, this.isar, required this.conversationId});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Media Gallery')),
      body: const Center(child: Text('Media gallery is only available on mobile.')),
    );
  }
}
