import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:shimmer/shimmer.dart';
import '../../core/theme.dart';

class ChatBubble extends StatefulWidget {
  final String content;
  final bool isOutbound;
  final String time;
  final String type;
  final String? status;
  final DateTime? readAt;
  final String? mediaUrl;
  final String? replyToContent;
  final String? metadata;
  final bool isNote;
  final bool showTail;
  final Function(String emoji)? onReaction;
  final Function()? onSwipe;
  final Function()? onLongPress;
  final Function(String url)? onMediaTap;
  final Function()? onRetry;

  const ChatBubble({
    super.key,
    required this.content,
    required this.isOutbound,
    required this.time,
    required this.type,
    this.status,
    this.readAt,
    this.mediaUrl,
    this.isNote = false,
    this.showTail = true,
    this.onReaction,
    this.onSwipe,
    this.onLongPress,
    this.replyToContent,
    this.onMediaTap,
    this.metadata,
    this.onRetry,
  });

  @override
  State<ChatBubble> createState() => _ChatBubbleState();
}

class _ChatBubbleState extends State<ChatBubble> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  // _offsetAnimation removed as it was unused
  double _dragExtent = 0;
  bool _thresholdMet = false;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    )..repeat(); // Keep it rotating for sync icons
    // Animation initialization removed
  }

  @override
  void didUpdateWidget(ChatBubble oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.status == 'sending' && !_controller.isAnimating) {
      _controller.repeat();
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onHorizontalDragUpdate(DragUpdateDetails details) {
    if (widget.isOutbound) return; // Usually only inbound are swiped to reply in some UX, but let's allow both if needed.
    // Actually, let's follow WhatsApp: both can be swiped.
    
    setState(() {
      _dragExtent += details.delta.dx;
      if (_dragExtent < 0) _dragExtent = 0; // Only swipe right
      if (_dragExtent > 70) _dragExtent = 70; // Cap it
      
      if (_dragExtent > 50 && !_thresholdMet) {
        _thresholdMet = true;
        HapticFeedback.lightImpact();
      } else if (_dragExtent <= 50) {
        _thresholdMet = false;
      }
    });
  }

  void _onHorizontalDragEnd(DragEndDetails details) {
    if (_dragExtent > 50) {
      widget.onSwipe?.call();
    }
    setState(() {
      _dragExtent = 0;
      _thresholdMet = false;
    });
  }

  bool _isAiMessage() {
    if (widget.metadata == null) return false;
    try {
      final data = jsonDecode(widget.metadata!);
      return data['is_bot'] == true || data['is_ai'] == true;
    } catch (_) {
      return false;
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    final baseColor = widget.isNote 
      ? (isDark ? const Color(0xFF424220) : const Color(0xFFFFF9C4))
      : (widget.isOutbound 
          ? (isDark ? const Color(0xFF005C4B) : const Color(0xFFE7FFDB))
          : (isDark ? const Color(0xFF1F2C34) : Colors.white));

    final textColor = isDark ? Colors.white : Colors.black87;

    return Stack(
      clipBehavior: Clip.none,
      children: [
        // Reply Indicator (Behind)
        if (_dragExtent > 10)
          Positioned(
            left: _dragExtent - 40,
            top: 0,
            bottom: 0,
            child: Center(
              child: AnimatedScale(
                scale: _thresholdMet ? 1.2 : 1.0,
                duration: const Duration(milliseconds: 100),
                child: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.1),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.reply,
                    size: 20,
                    color: _thresholdMet ? AppTheme.primaryColor : Colors.grey,
                  ),
                ),
              ),
            ),
          ),

        GestureDetector(
          onDoubleTap: () {
            if (widget.onReaction != null) widget.onReaction!('❤️');
          },
          onHorizontalDragUpdate: _onHorizontalDragUpdate,
          onHorizontalDragEnd: _onHorizontalDragEnd,
          onLongPress: widget.onLongPress,
          child: Transform.translate(
            offset: Offset(_dragExtent, 0),
            child: Align(
              alignment: widget.isOutbound ? Alignment.centerRight : Alignment.centerLeft,
              child: Column(
                crossAxisAlignment: widget.isOutbound ? CrossAxisAlignment.end : CrossAxisAlignment.start,
                children: [
                  Container(
                    margin: EdgeInsets.only(
                      top: widget.showTail ? 8 : 2, 
                      bottom: 2, 
                      left: 8, 
                      right: 8
                    ),
                    padding: const EdgeInsets.all(5),
                    decoration: BoxDecoration(
                      color: baseColor,
                      borderRadius: BorderRadius.only(
                        topLeft: const Radius.circular(12),
                        topRight: const Radius.circular(12),
                        bottomLeft: Radius.circular(widget.isOutbound ? 12 : (widget.showTail ? 0 : 12)),
                        bottomRight: Radius.circular(widget.isOutbound ? (widget.showTail ? 0 : 12) : 12),
                      ),
                      boxShadow: [
                        BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 1, offset: const Offset(0, 1)),
                      ],
                    ),
                    child: ConstrainedBox(
                      constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.75),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          if (widget.isNote) 
                            Padding(
                              padding: const EdgeInsets.only(left: 5, bottom: 4),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.lock_outline, size: 10, color: Colors.orange),
                                  const SizedBox(width: 4),
                                  const Text('INTERNAL NOTE', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.orange)),
                                ],
                              ),
                            ),
                          
                          if (_isAiMessage())
                            Padding(
                              padding: const EdgeInsets.only(left: 5, bottom: 4),
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF128C7E).withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: const Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(Icons.smart_toy_outlined, size: 10, color: Color(0xFF128C7E)),
                                    SizedBox(width: 4),
                                    Text('AI', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Color(0xFF128C7E))),
                                  ],
                                ),
                              ),
                            ),

                          if (widget.replyToContent != null)
                            Container(
                              margin: const EdgeInsets.only(bottom: 6, left: 2, right: 2),
                              padding: const EdgeInsets.all(6),
                              decoration: BoxDecoration(
                                color: Colors.black.withValues(alpha: 0.05),
                                borderRadius: BorderRadius.circular(4),
                                border: Border(left: BorderSide(color: widget.isOutbound ? const Color(0xFF25D366) : const Color(0xFF128C7E), width: 3)),
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    widget.isOutbound ? 'You' : 'Contact',
                                    style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF128C7E), fontSize: 11),
                                  ),
                                  Text(
                                    widget.replyToContent!,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: TextStyle(color: isDark ? Colors.white70 : Colors.black54, fontSize: 12),
                                  ),
                                ],
                              ),
                            ),
                          
                          // 1. Media Content
                          if (widget.type == 'image' && widget.mediaUrl != null) 
                            GestureDetector(
                              onTap: () => widget.onMediaTap?.call(widget.mediaUrl!),
                              child: Hero(
                                tag: widget.mediaUrl!,
                                child: ClipRRect(
                                  borderRadius: BorderRadius.circular(8),
                                  child: CachedNetworkImage(
                                    imageUrl: widget.mediaUrl!,
                                    fit: BoxFit.cover,
                                    placeholder: (context, url) => Shimmer.fromColors(
                                      baseColor: Colors.grey[300]!,
                                      highlightColor: Colors.grey[100]!,
                                      child: Container(height: 200, width: double.infinity, color: Colors.white),
                                    ),
                                    errorWidget: (context, url, error) => const Icon(Icons.error),
                                  ),
                                ),
                              ),
                            ),
                          
                          // 2. Audio Content
                          if (widget.type == 'audio' || widget.type == 'ptt')
                            AudioPlayerWidget(isOutbound: widget.isOutbound),
                          
                          if (widget.content.isNotEmpty)
                            Padding(
                              padding: const EdgeInsets.fromLTRB(5, 2, 5, 0),
                              child: Text(
                                widget.content,
                                style: TextStyle(
                                  color: widget.isNote 
                                    ? (isDark ? Colors.orange.shade200 : Colors.brown.shade800) 
                                    : textColor,
                                  fontSize: 16,
                                ),
                              ),
                            ),

                          if (widget.metadata != null)
                            _buildMetadataContent(context),
                          
                          Align(
                            alignment: Alignment.centerRight,
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(
                                  widget.time,
                                  style: TextStyle(
                                    color: widget.isNote 
                                      ? (isDark ? Colors.orange.withValues(alpha: 0.5) : Colors.brown.withValues(alpha: 0.5)) 
                                      : (isDark ? Colors.white54 : Colors.black45),
                                    fontSize: 11,
                                  ),
                                ),
                                if (widget.isOutbound) ...[
                                  const SizedBox(width: 4),
                                  _buildStatusIcon(),
                                ],
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  
                  // --- Reaction Pill ---
                  if (widget.metadata != null)
                    _buildReactionPill(),
                ],
              ),
            ),
          ),
        ),
    ],
    );
  }

  Widget _buildStatusIcon() {
    IconData icon;
    Color color = Colors.grey;

    switch (widget.status) {
      case 'sending':
        return _buildRotatingIcon(Icons.sync, Colors.blue);
      case 'queued':
        icon = Icons.access_time;
        color = Colors.grey;
        break;
      case 'read':
        icon = Icons.done_all;
        color = Colors.blue;
        break;
      case 'delivered':
        icon = Icons.done_all;
        break;
      case 'sent':
        icon = Icons.done;
        break;
      case 'failed':
        icon = Icons.error_outline;
        color = Colors.red;
        break;
      default:
        icon = Icons.access_time;
    }

    final iconWidget = Icon(icon, size: 14, color: color);
    
    if (widget.status == 'failed' && widget.onRetry != null) {
      return Tooltip(
        message: 'Tap to retry',
        child: GestureDetector(
          onTap: widget.onRetry,
          child: Padding(
            padding: const EdgeInsets.all(4.0),
            child: iconWidget,
          ),
        ),
      );
    }
    
    return iconWidget;
  }

  Widget _buildRotatingIcon(IconData icon, Color color) {
    return RotationTransition(
      turns: _controller,
      child: Icon(icon, size: 14, color: color),
    );
  }

  Widget _buildReactionPill() {
    try {
      final Map<String, dynamic> data = jsonDecode(widget.metadata!);
      final String? reaction = data['reaction'];
      if (reaction == null || reaction.isEmpty) return const SizedBox.shrink();

      return Container(
        margin: const EdgeInsets.only(top: -10, left: 10, right: 10),
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 4, offset: const Offset(0, 2)),
          ],
        ),
        child: Text(reaction, style: const TextStyle(fontSize: 14)),
      );
    } catch (_) {
      return const SizedBox.shrink();
    }
  }

  Widget _buildMetadataContent(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    try {
      final Map<String, dynamic> data = jsonDecode(widget.metadata!);
      
      // 1. Handle Interactive (Flow) Response
      if (data.containsKey('interactive')) {
        final interactive = data['interactive'];
        if (interactive['type'] == 'nfm_reply' && interactive['nfm_reply'] != null) {
          final reply = interactive['nfm_reply'];
          final responseJson = reply['response_json'];
          Map<String, dynamic> responseData = {};
          if (responseJson != null) {
            try {
              responseData = jsonDecode(responseJson);
            } catch (_) {}
          }

          return Container(
            margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.5),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.green.shade200),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(Icons.assignment_turned_in_outlined, size: 16, color: Colors.green.shade700),
                    const SizedBox(width: 8),
                    Text(
                      reply['body'] ?? 'Flow Submitted',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.green.shade800),
                    ),
                  ],
                ),
                if (responseData.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  ...responseData.entries.take(3).map((e) => Padding(
                    padding: const EdgeInsets.only(bottom: 2),
                    child: Text(
                      '${e.key}: ${e.value}',
                      style: const TextStyle(fontSize: 12, color: Colors.black54),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  )),
                  if (responseData.length > 3)
                    const Text('...', style: TextStyle(fontSize: 12, color: Colors.grey)),
                ],
              ],
            ),
          );
        }
      }

      // 2. Handle Template (Outbound)
      if (data.containsKey('template_name')) {
        return Container(
          margin: const EdgeInsets.only(top: 4, bottom: 4, left: 4),
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
          decoration: BoxDecoration(
            color: Colors.blue.shade50,
            borderRadius: BorderRadius.circular(4),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.verified_outlined, size: 12, color: Colors.blue.shade700),
              const SizedBox(width: 4),
              Text(
                'TEMPLATE: ${data['template_name']}',
                style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.blue.shade700),
              ),
            ],
          ),
        );
      }
      // 3. Handle Transcription
      if (data.containsKey('transcription')) {
        return Container(
          margin: const EdgeInsets.only(top: 8, bottom: 4, left: 4, right: 4),
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: Colors.black.withValues(alpha: 0.03),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(Icons.auto_awesome, size: 12, color: Colors.teal.shade700),
                  const SizedBox(width: 4),
                  Text(
                    'TRANSCRIPTION',
                    style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.teal.shade700, letterSpacing: 0.5),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                data['transcription'],
                style: TextStyle(
                  fontSize: 13, 
                  fontStyle: FontStyle.italic,
                  color: isDark ? Colors.white70 : Colors.black54,
                  height: 1.4,
                ),
              ),
            ],
          ),
        );
      }
    } catch (_) {}
    return const SizedBox.shrink();
  }
}

class AudioPlayerWidget extends StatefulWidget {
  final bool isOutbound;
  const AudioPlayerWidget({super.key, required this.isOutbound});

  @override
  State<AudioPlayerWidget> createState() => _AudioPlayerWidgetState();
}

class _AudioPlayerWidgetState extends State<AudioPlayerWidget> {
  bool isPlaying = false;
  double progress = 0.0;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        IconButton(
          icon: Icon(isPlaying ? Icons.pause_rounded : Icons.play_arrow_rounded, size: 30, color: Colors.grey),
          tooltip: isPlaying ? 'Pause' : 'Play',
          onPressed: () {
            setState(() => isPlaying = !isPlaying);
            if (isPlaying) {
              Future.doWhile(() async {
                await Future.delayed(const Duration(milliseconds: 100));
                if (!mounted || !isPlaying) return false;
                setState(() {
                  progress += 0.01;
                  if (progress >= 1.0) {
                    progress = 0.0;
                    isPlaying = false;
                  }
                });
                return isPlaying;
              });
            }
          },
        ),
        const SizedBox(width: 4),
        SizedBox(
          width: 120,
          height: 4,
          child: LinearProgressIndicator(
            value: progress,
            backgroundColor: Colors.grey.shade200,
            valueColor: AlwaysStoppedAnimation<Color>(widget.isOutbound ? Colors.white70 : const Color(0xFF128C7E)),
          ),
        ),
        const SizedBox(width: 8),
        const Text('0:12', style: TextStyle(fontSize: 10, color: Colors.grey)),
      ],
    );
  }
}
