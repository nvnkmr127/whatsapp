import 'package:flutter/material.dart';
import 'package:isar/isar.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../data/models/message.dart';
import 'media_viewer.dart';

class MediaGalleryScreen extends StatefulWidget {
  final Isar isar;
  final int conversationId;
  const MediaGalleryScreen({super.key, required this.isar, required this.conversationId});

  @override
  State<MediaGalleryScreen> createState() => _MediaGalleryScreenState();
}

class _MediaGalleryScreenState extends State<MediaGalleryScreen> {
  late Future<List<LocalMessage>> _mediaFuture;

  @override
  void initState() {
    super.initState();
    _mediaFuture = widget.isar.collection<LocalMessage>().filter()
        .conversationIdEqualTo(widget.conversationId)
        .and()
        .typeEqualTo('image')
        .findAll();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Media Gallery'), backgroundColor: const Color(0xFF075E54)),
      body: FutureBuilder<List<LocalMessage>>(
        future: _mediaFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
             return const Center(child: CircularProgressIndicator());
          }

          if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.photo_library_outlined, size: 80, color: Colors.grey[300]),
                  const SizedBox(height: 16),
                  const Text('No media found in this chat', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold)),
                ],
              ),
            );
          }

          final media = snapshot.data!;
          return GridView.builder(
            padding: const EdgeInsets.all(10),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 3,
              crossAxisSpacing: 10,
              mainAxisSpacing: 10,
            ),
            itemCount: media.length,
            itemBuilder: (context, i) {
              final msg = media[i];
              return InkWell(
                onTap: () {
                  if (msg.mediaUrl != null) {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (ctx) => MediaViewerPage(imageUrl: msg.mediaUrl!))
                    );
                  }
                },
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: CachedNetworkImage(
                    imageUrl: msg.mediaUrl ?? '',
                    placeholder: (context, url) => Container(
                    color: Colors.grey[200],
                    child: const Center(
                      child: Icon(Icons.image, color: Colors.grey, size: 40),
                    ),
                  ),
                    errorWidget: (context, url, error) => const Icon(Icons.broken_image),
                    fit: BoxFit.cover,
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
