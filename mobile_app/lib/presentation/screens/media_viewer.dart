import 'dart:async';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

class MediaViewerPage extends StatefulWidget {
  final String imageUrl;

  const MediaViewerPage({super.key, required this.imageUrl});

  @override
  State<MediaViewerPage> createState() => _MediaViewerPageState();
}

class _MediaViewerPageState extends State<MediaViewerPage> {
  bool _busy = false;

  Future<File> _downloadToAppFiles() async {
    final dir = await getApplicationDocumentsDirectory();
    final fileName = 'media_${DateTime.now().millisecondsSinceEpoch}.jpg';
    final path = '${dir.path}/$fileName';
    await Dio().download(widget.imageUrl, path);
    return File(path);
  }

  Future<void> _onDownload() async {
    if (kIsWeb) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Download is not supported on web.')));
      return;
    }
    setState(() => _busy = true);
    try {
      final file = await _downloadToAppFiles();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Saved to Files: ${file.path}')));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Download failed: $e')));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _onShare() async {
    if (kIsWeb) {
      await Share.share(widget.imageUrl);
      return;
    }
    setState(() => _busy = true);
    try {
      final file = await _downloadToAppFiles();
      if (!mounted) return;
      await Share.shareXFiles([XFile(file.path)], text: 'Media');
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Share failed: $e')));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        iconTheme: const IconThemeData(color: Colors.white),
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.download_rounded),
            tooltip: 'Download',
            onPressed: _busy ? null : _onDownload,
          ),
          IconButton(
            icon: const Icon(Icons.share_rounded), 
            tooltip: 'Share',
            onPressed: _busy ? null : _onShare,
          ),
        ],
      ),
      body: Center(
        child: InteractiveViewer(
          minScale: 0.5,
          maxScale: 4.0,
          child: Hero(
            tag: widget.imageUrl,
            child: Image.network(
              widget.imageUrl,
              fit: BoxFit.contain,
              loadingBuilder: (context, child, loadingProgress) {
                if (loadingProgress == null) return child;
                return Center(
                  child: CircularProgressIndicator(
                    value: loadingProgress.expectedTotalBytes != null
                        ? loadingProgress.cumulativeBytesLoaded / loadingProgress.expectedTotalBytes!
                        : null,
                  ),
                );
              },
              errorBuilder: (context, error, stackTrace) {
                return Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.broken_image_outlined, size: 60, color: Colors.grey),
                    const SizedBox(height: 16),
                    const Text('Failed to load image', style: TextStyle(color: Colors.white70)),
                    const SizedBox(height: 8),
                    TextButton(
                      onPressed: () => setState(() {}),
                      child: const Text('RETRY', style: TextStyle(color: Colors.white)),
                    ),
                  ],
                );
              },
            ),
          ),
        ),
      ),
    );
  }
}
