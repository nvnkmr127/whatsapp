import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';

class NotificationBanner extends StatelessWidget {
  final String title;
  final String body;
  final String? imageUrl;
  final VoidCallback onTap;

  const NotificationBanner({
    super.key,
    required this.title,
    required this.body,
    this.imageUrl,
    required this.onTap,
  });

  static void show(BuildContext context, {
    required String title,
    required String body,
    String? imageUrl,
    required VoidCallback onTap,
  }) {
    final overlay = Overlay.of(context);
    late OverlayEntry entry;

    entry = OverlayEntry(
      builder: (context) => _BannerWidget(
        title: title,
        body: body,
        imageUrl: imageUrl,
        onTap: () {
          entry.remove();
          onTap();
        },
        onDismiss: () => entry.remove(),
      ),
    );

    overlay.insert(entry);
    Future.delayed(const Duration(seconds: 4), () {
      if (entry.mounted) entry.remove();
    });
  }

  @override
  Widget build(BuildContext context) => const SizedBox.shrink();
}

class _BannerWidget extends StatefulWidget {
  final String title;
  final String body;
  final String? imageUrl;
  final VoidCallback onTap;
  final VoidCallback onDismiss;

  const _BannerWidget({
    required this.title,
    required this.body,
    this.imageUrl,
    required this.onTap,
    required this.onDismiss,
  });

  @override
  State<_BannerWidget> createState() => _BannerWidgetState();
}

class _BannerWidgetState extends State<_BannerWidget> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<Offset> _offsetAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 400),
    );
    _offsetAnimation = Tween<Offset>(
      begin: const Offset(0, -1),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _controller, curve: Curves.easeOutBack));
    _controller.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: SlideTransition(
        position: _offsetAnimation,
        child: Padding(
          padding: const EdgeInsets.all(12.0),
          child: Material(
            color: Colors.transparent,
            child: InkWell(
              onTap: widget.onTap,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.1),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                  border: Border.all(color: const Color(0xFFE9EDEF)),
                ),
                child: Row(
                  children: [
                    if (widget.imageUrl != null)
                      CircleAvatar(
                        radius: 20,
                        backgroundImage: CachedNetworkImageProvider(widget.imageUrl!),
                      )
                    else
                      const CircleAvatar(
                        radius: 20,
                        backgroundColor: Color(0xFF128C7E),
                        child: Icon(Icons.person, color: Colors.white),
                      ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            widget.title,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          Text(
                            widget.body,
                            style: const TextStyle(color: Colors.grey, fontSize: 13),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close, size: 18, color: Colors.grey),
                      tooltip: 'Dismiss',
                      onPressed: widget.onDismiss,
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
