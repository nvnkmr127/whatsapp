import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';

class ChatAvatar extends StatelessWidget {
  final String? name;
  final String? imageUrl;
  final double? radius;
  final double size;

  const ChatAvatar({
    super.key,
    this.name,
    this.imageUrl,
    this.size = 50,
    this.radius,
  });

  @override
  Widget build(BuildContext context) {
    final effectiveSize = radius != null ? radius! * 2 : size;
    
    if (imageUrl != null) {
      return CachedNetworkImage(
        imageUrl: imageUrl!,
        imageBuilder: (context, imageProvider) => Container(
          width: effectiveSize,
          height: effectiveSize,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            image: DecorationImage(
              image: imageProvider,
              fit: BoxFit.cover,
            ),
          ),
        ),
        placeholder: (context, url) => _buildPlaceholder(effectiveSize),
        errorWidget: (context, url, error) => _buildPlaceholder(effectiveSize),
      );
    }

    return _buildPlaceholder(effectiveSize);
  }

  Widget _buildPlaceholder(double effectiveSize) {
    if (name != null && name!.isNotEmpty) {
      final initial = name!.substring(0, 1).toUpperCase();
      return Container(
        width: effectiveSize,
        height: effectiveSize,
        decoration: const BoxDecoration(
          color: Colors.blueGrey,
          shape: BoxShape.circle,
        ),
        alignment: Alignment.center,
        child: Text(
          initial,
          style: TextStyle(color: Colors.white, fontSize: effectiveSize * 0.4, fontWeight: FontWeight.bold),
        ),
      );
    }
    return Container(
      width: effectiveSize,
      height: effectiveSize,
      decoration: BoxDecoration(
        color: Colors.grey[300],
        shape: BoxShape.circle,
      ),
      child: Icon(Icons.person, color: Colors.grey[600], size: effectiveSize * 0.6),
    );
  }
}
