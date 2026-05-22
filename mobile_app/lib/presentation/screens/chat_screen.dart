import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';
import 'media_viewer.dart';
import 'forward_message_screen.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../../core/api_service.dart';
import '../../core/socket_service.dart';
import '../../logic/blocs/chat_bloc.dart';
import '../../logic/blocs/canned_bloc.dart';
import '../../logic/blocs/message_bloc.dart';
import '../../data/models/local_message.dart';
import 'package:file_picker/file_picker.dart';
import 'package:mime/mime.dart';
import '../../core/storage_keys.dart';

import '../widgets/chat_bubble.dart';
import 'contact_profile_screen.dart';

class ChatScreen extends StatefulWidget {
  final int conversationId;
  final String contactName;

  static int? activeConversationId;

  const ChatScreen({
    super.key,
    required this.conversationId,
    required this.contactName,
  });

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final TextEditingController _msgController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  final ImagePicker _picker = ImagePicker();
  bool _isNoteMode = false;
  dynamic _replyToMessage;
  SocketService? _socketService;
  Timer? _typingTimer;
  bool _isSearching = false;
  String _chatSearchQuery = '';
  final TextEditingController _chatSearchController = TextEditingController();
  double _uploadProgress = 0;
  bool _isUploading = false;
  String? _uploadingFileName;
  bool _isAiEnabled = true;
  bool _isAiLoading = true;
  DateTime? _windowExpiresAt;    // null = unknown, set from API
  String _windowTimeLeft = '';    // e.g. "23h 12m left"
  Timer? _windowCountdownTimer;

  @override
  void initState() {
    super.initState();
    ChatScreen.activeConversationId = widget.conversationId;
    context.read<MessageBloc>().add(LoadMessages(widget.conversationId));
    context.read<CannedBloc>().add(FetchCannedMessages());
    _initRealtime();
    _scrollController.addListener(_onScroll);
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
      context.read<MessageBloc>().add(LoadMoreMessages(widget.conversationId));
    }
  }


  @override
  void dispose() {
    if (ChatScreen.activeConversationId == widget.conversationId) {
      ChatScreen.activeConversationId = null;
    }
    _typingTimer?.cancel();
    _windowCountdownTimer?.cancel();
    _socketService?.disconnect();
    _msgController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _initRealtime() async {
    const storage = FlutterSecureStorage();
    final token = await storage.read(key: StorageKeys.authToken);
    final tenantId = await storage.read(key: StorageKeys.tenantId);
    final whatsappNumberId = await storage.read(key: StorageKeys.activeWhatsAppNumberId);
    if (!mounted || token == null || tenantId == null || whatsappNumberId == null) return;

    _socketService = SocketService(
      context.read<ChatBloc>(),
      context.read<MessageBloc>(),
      token: token,
      whatsappNumberId: whatsappNumberId,
      tenantId: tenantId,
    );

    _socketService?.subscribeToConversation(widget.conversationId);

    try {
      await context.read<ApiService>().reportPresence(widget.conversationId);
      if (!mounted) return;
      final convRes = await context.read<ApiService>().getConversation(widget.conversationId);
      if (!mounted) return;
      if (mounted) {
        final data = convRes.data;
        setState(() {
          _isAiEnabled = data['is_ai_enabled'] ?? true;
          _isAiLoading = false;
        });

        // ── 24h Window Expiry ─────────────────────────────────────────
        // Backend returns 'session_expires_at' (ISO8601) when window is open.
        // e.g. "2026-04-30T22:15:00Z"  or null when window is closed.
        final expiresAt = data['session_expires_at'] as String?;
        if (expiresAt != null) {
          final expiry = DateTime.tryParse(expiresAt);
          if (expiry != null && expiry.isAfter(DateTime.now())) {
            _windowExpiresAt = expiry;
            _startWindowCountdown();
          } else {
            _windowExpiresAt = null; // window expired
          }
        } else {
          _windowExpiresAt = null; // no session data → treat as closed
        }
      }
      if (!mounted) return;
      await context.read<ApiService>().markConversationAsRead(widget.conversationId);
      if (!mounted) return;
      context.read<ChatBloc>().add(FetchConversations(isRefresh: true)); 
    } catch (_) {
      if (mounted) setState(() => _isAiLoading = false);
    }
  }

  void _startWindowCountdown() {
    _windowCountdownTimer?.cancel();
    _updateWindowTimeLeft(); // run immediately
    _windowCountdownTimer = Timer.periodic(const Duration(minutes: 1), (_) {
      if (!mounted) return;
      _updateWindowTimeLeft();
    });
  }

  void _updateWindowTimeLeft() {
    if (_windowExpiresAt == null) {
      setState(() => _windowTimeLeft = '');
      return;
    }
    final remaining = _windowExpiresAt!.difference(DateTime.now());
    if (remaining.isNegative) {
      // Window just expired — update state
      setState(() {
        _windowExpiresAt = null;
        _windowTimeLeft = '';
      });
      _windowCountdownTimer?.cancel();
      return;
    }
    final h = remaining.inHours;
    final m = remaining.inMinutes.remainder(60);
    setState(() {
      _windowTimeLeft = h > 0 ? '${h}h ${m}m left' : '${m}m left';
    });
  }

  void _onComposerChanged(String value) {
    final socket = _socketService;
    if (socket == null) return;

    socket.whisperTyping(widget.conversationId, 'You', value.trim().isNotEmpty);
    _typingTimer?.cancel();
    _typingTimer = Timer(const Duration(seconds: 2), () {
      socket.whisperTyping(widget.conversationId, 'You', false);
    });
  }

  Future<void> _handleFileUpload() async {
    final result = await (FilePicker as dynamic).platform.pickFiles(
      type: FileType.any,
      allowMultiple: false,
    );

    if (result == null || result.files.single.path == null) return;
    if (!mounted) return;
    final file = result.files.single;
    final path = file.path!;
    final name = file.name;
    final size = file.size;

    // 1. File Size Check (20MB)
    if (size > 20 * 1024 * 1024) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('File too large (Max 20MB)'), backgroundColor: Colors.red),
      );
      return;
    }

    // 2. Safe MIME Handling
    final mime = lookupMimeType(path) ?? 'application/octet-stream';
    String type = 'document';
    if (mime.startsWith('image/')) {
      type = 'image';
    } else if (mime.startsWith('video/')) {
      type = 'video';
    } else if (mime.startsWith('audio/')) {
      type = 'audio';
    }

    setState(() {
      _isUploading = true;
      _uploadingFileName = name;
      _uploadProgress = 0;
    });

    try {
      final apiService = context.read<ApiService>();
      final response = await apiService.uploadMedia(path, onSendProgress: (sent, total) {
        if (total > 0 && mounted) {
          setState(() => _uploadProgress = sent / total);
        }
      });
      if (!mounted) return;

      if (response.statusCode == 200 && response.data['url'] != null) {
        if (!mounted) return;
        context.read<MessageBloc>().add(
          SendMessageToConversation(
            widget.conversationId,
            name, // Content is file name
            type: type,
            mediaUrl: response.data['url'],
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Upload failed: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isUploading = false;
          _uploadingFileName = null;
          _uploadProgress = 0;
        });
      }
    }
  }

  void _showAttachmentPicker() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        margin: const EdgeInsets.all(20),
        padding: const EdgeInsets.symmetric(vertical: 20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _buildAttachmentIcon(Icons.insert_drive_file, 'Document', Colors.indigo, () {
                   Navigator.pop(ctx);
                   _handleFileUpload();
                }),
                _buildAttachmentIcon(Icons.camera_alt, 'Camera', Colors.pink, () async {
                   Navigator.pop(ctx);
                   final XFile? photo = await _picker.pickImage(source: ImageSource.camera);
                   if (photo != null) _handleFileUploadDirect(photo.path, 'image');
                }),
                _buildAttachmentIcon(Icons.image, 'Gallery', Colors.purple, () {
                   Navigator.pop(ctx);
                   _handleFileUpload();
                }),
              ],
            ),
            const SizedBox(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _buildAttachmentIcon(Icons.headset, 'Audio', Colors.orange, () {
                   Navigator.pop(ctx);
                   _handleFileUpload();
                }),
                _buildAttachmentIcon(Icons.location_on, 'Location', Colors.green, () {
                  Navigator.pop(ctx);
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Location sharing not supported yet')));
                }),
                _buildAttachmentIcon(Icons.person, 'Contact', Colors.blue, () {
                  Navigator.pop(ctx);
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Contact sharing not supported yet')));
                }),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAttachmentIcon(IconData icon, String label, Color color, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        children: [
          CircleAvatar(
            radius: 25,
            backgroundColor: color,
            child: Icon(icon, color: Colors.white),
          ),
          const SizedBox(height: 5),
          Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
        ],
      ),
    );
  }

  Future<void> _handleFileUploadDirect(String path, String type) async {
    final fileName = path.split('/').last;
    context.read<MessageBloc>().add(SendMessageToConversation(
      widget.conversationId, 
      fileName, 
      type: type, 
      localMediaPath: path,
    ));
  }


  void _sendMessage() {
    if (_msgController.text.trim().isEmpty) return;
    
    // Internal Notes use a different payload in the real system
    context.read<MessageBloc>().add(
          SendMessageToConversation(
            widget.conversationId, 
            _msgController.text.trim(),
            type: _isNoteMode ? 'note' : 'text',
            replyToMessageId: _replyToMessage?.remoteId,
          ),
        );
    _msgController.clear();
    setState(() => _replyToMessage = null);
    
    // Smooth scroll to bottom
    Future.delayed(const Duration(milliseconds: 100), () {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          0,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _showTemplatePicker() async {
    final apiService = context.read<ApiService>();
    final messageBloc = context.read<MessageBloc>();
    String query = '';
    
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => StatefulBuilder(
        builder: (context, setLocalState) {
          return Container(
            height: MediaQuery.of(context).size.height * 0.7,
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.only(topLeft: Radius.circular(20), topRight: Radius.circular(20)),
            ),
            child: Column(
              children: [
                Container(margin: const EdgeInsets.only(top: 10), height: 4, width: 40, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(10))),
                const Padding(padding: EdgeInsets.all(20), child: Text('WhatsApp Templates', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold))),
                
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: TextField(
                    decoration: InputDecoration(
                      hintText: 'Search templates...', 
                      prefixIcon: const Icon(Icons.search),
                      filled: true,
                      fillColor: Colors.grey.shade100,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                    ),
                    onChanged: (val) => setLocalState(() => query = val),
                  ),
                ),
                const SizedBox(height: 10),
                
                Expanded(
                  child: FutureBuilder(
                    future: apiService.getTemplates(),
                    builder: (context, snapshot) {
                      if (snapshot.connectionState == ConnectionState.waiting) return const Center(child: CircularProgressIndicator());
                      if (snapshot.hasError) {
                        return Center(
                          child: Padding(
                            padding: const EdgeInsets.all(20),
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.error_outline, color: Colors.red, size: 40),
                                const SizedBox(height: 8),
                                Text('Failed to load templates: ${snapshot.error}', textAlign: TextAlign.center),
                              ],
                            ),
                          ),
                        );
                      }
                      
                      final List allTemplates = snapshot.data?.data ?? [];
                      final List templates = allTemplates.where((t) => 
                        (t['name'] ?? '').toString().toLowerCase().contains(query.toLowerCase())
                      ).toList();

                      if (templates.isEmpty) return const Center(child: Text('No templates found.'));
                      
                      return ListView.builder(
                        itemCount: templates.length,
                        itemBuilder: (context, i) {
                          final t = templates[i];
                          return ListTile(
                            leading: const Icon(Icons.description_outlined, color: Color(0xFF128C7E)),
                            title: Text(t['name'] ?? 'Untitled'),
                            subtitle: Text(t['category'] ?? 'UTILITY', style: const TextStyle(fontSize: 10)),
                            trailing: const Icon(Icons.send, size: 18),
                            onTap: () {
                              messageBloc.add(SendTemplate(widget.conversationId, t['id']));
                              Navigator.pop(context);
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Template sent!')));
                            },
                          );
                        },
                      );
                    },
                  ),
                ),
              ],
            ),
          );
        }
      ),
    );
  }


  void _showAssignDialog() {
    final apiService = context.read<ApiService>();
    
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.only(topLeft: Radius.circular(20), topRight: Radius.circular(20)),
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(margin: const EdgeInsets.only(top: 10), height: 4, width: 40, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(10))),
              Padding(padding: EdgeInsets.all(20), child: Text('Assign Conversation', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold))),
              
              FutureBuilder(
                future: apiService.getTeamMembers(),
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) return const Padding(padding: EdgeInsets.all(40), child: Center(child: CircularProgressIndicator()));
                  final List members = snapshot.data?.data ?? [];
                  
                  return Column(
                    children: [
                      ...members.map((m) => ListTile(
                        leading: CircleAvatar(child: Text(m['initials'] ?? '??')),
                        title: Text(m['name']),
                        onTap: () async {
                            await apiService.assignConversation(widget.conversationId, m['id']);
                            if (context.mounted) {
                              Navigator.pop(context);
                              ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Assigned to ${m['name']}')));
                            }
                        },
                      )),
                      const Divider(),
                      ListTile(
                        leading: const Icon(Icons.check_circle_outline, color: Colors.green),
                        title: const Text('Resolve Conversation'),
                        onTap: () {
                          Navigator.pop(context); // Close sheet
                          showDialog(
                            context: context,
                            builder: (ctx) => AlertDialog(
                              title: const Text('Resolve'),
                              content: const Text('Are you sure you want to resolve this conversation?'),
                              actions: [
                                TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('CANCEL')),
                                TextButton(
                                  onPressed: () async {
                                    await apiService.closeConversation(widget.conversationId);
                                    if (context.mounted) {
                                      Navigator.pop(ctx);
                                      Navigator.pop(context); // Close chat screen
                                      context.read<ChatBloc>().add(FetchConversations(isRefresh: true));
                                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Conversation resolved')));
                                    }
                                  }, 
                                  child: const Text('RESOLVE', style: TextStyle(color: Colors.green))
                                ),
                              ],
                            ),
                          );
                        },
                      ),
                      ListTile(
                        leading: const Icon(Icons.refresh_outlined, color: Colors.blue),
                        title: const Text('Reopen Conversation'),
                        onTap: () async {
                          Navigator.pop(context);
                          await apiService.reopenConversation(widget.conversationId);
                          if (context.mounted) {
                            context.read<ChatBloc>().add(FetchConversations(isRefresh: true));
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Conversation reopened'), backgroundColor: Colors.blue),
                            );
                          }
                        },
                      ),
                      const SizedBox(height: 20),
                    ],
                  );
                }
              ),
            ],
          ),
        ),
      ),
    );
  }




  Future<void> _toggleAi() async {
    final newState = !_isAiEnabled;
    setState(() => _isAiEnabled = newState);
    
    try {
      await context.read<ApiService>().toggleConversationAi(widget.conversationId, newState);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(newState ? 'AI Assistant enabled' : 'AI Assistant paused'),
            duration: const Duration(seconds: 1),
            backgroundColor: newState ? Colors.green : Colors.orange,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isAiEnabled = !newState);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to toggle AI: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  Future<void> _suggestAiReply() async {
    try {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Row(
            children: [
              SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)),
              SizedBox(width: 12),
              Text('Generating AI suggestion...'),
            ],
          ),
          duration: Duration(seconds: 5),
          backgroundColor: Color(0xFF128C7E),
        ),
      );
      final response = await context.read<ApiService>().suggestAiReply(widget.conversationId);
      final suggestion = response.data['suggestion'] as String?;
      if (suggestion != null && mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        setState(() {
          _msgController.text = suggestion;
          _msgController.selection = TextSelection.fromPosition(
            TextPosition(offset: suggestion.length),
          );
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('AI suggestion failed: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {

    return Scaffold(
      backgroundColor: const Color(0xFFE5DDD5), 
      appBar: AppBar(
        titleSpacing: 0,
        backgroundColor: const Color(0xFF075E54),
        title: _isSearching 
          ? TextField(
              controller: _chatSearchController,
              autofocus: true,
              style: const TextStyle(color: Colors.white),
              decoration: const InputDecoration(hintText: 'Search chat...', hintStyle: TextStyle(color: Colors.white54), border: InputBorder.none),
              onChanged: (val) => setState(() => _chatSearchQuery = val),
            )
          : GestureDetector(
          onTap: () async {
            try {
              final apiService = context.read<ApiService>();
              final response = await apiService.getConversation(widget.conversationId);
              final data = response.data;
              final contact = data is Map ? data['contact'] : null;
              final contactId = (contact is Map ? contact['id'] : null) as num?;
              if (!context.mounted) return;
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (ctx) => ContactProfileScreen(
                    id: contactId?.toInt(),
                    contact: contact is Map ? Map<String, dynamic>.from(contact) : null,
                  ),
                ),
              );
            } catch (_) {
              if (!context.mounted) return;
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (ctx) => ContactProfileScreen(
                    id: null,
                    contact: {'name': widget.contactName, 'phone_number': 'WhatsApp Chat'},
                  ),
                ),
              );
            }
          },
          child: Row(
            children: [
              // Contact avatar
              const CircleAvatar(backgroundColor: Colors.white24, child: Icon(Icons.person, color: Colors.white)),
              const SizedBox(width: 10),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Contact name with session window dot indicator
                  Row(
                    children: [
                      Text(
                        widget.contactName,
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                      const SizedBox(width: 6),
                      // Green pulsing dot (web-style) — only shown when window is open
                      if (_windowExpiresAt != null)
                        _PulsingDot(),
                    ],
                  ),
                  // Subtitle: typing / window status / countdown
                  BlocBuilder<ChatBloc, ChatState>(
                    builder: (context, state) {
                      if (state is ConversationsLoaded && state.typingAgents.containsKey(widget.conversationId)) {
                        return const Text(
                          'typing...',
                          style: TextStyle(color: Color(0xFF25D366), fontSize: 12, fontStyle: FontStyle.italic),
                        );
                      }
                      if (_windowExpiresAt != null) {
                        return Text(
                          'Window Open · $_windowTimeLeft',
                          style: const TextStyle(color: Color(0xFF25D366), fontSize: 11, fontWeight: FontWeight.bold),
                        );
                      }
                      return const Text(
                        'Window Closed',
                        style: TextStyle(color: Colors.orangeAccent, fontSize: 11, fontWeight: FontWeight.bold),
                      );
                    },
                  ),
                ],
              ),
            ],
          ),
        ),
        actions: [
            IconButton(
              icon: Icon(_isSearching ? Icons.close : Icons.search, color: Colors.white),
              tooltip: _isSearching ? 'Close Search' : 'Search Chat',
              onPressed: () => setState(() => _isSearching = !_isSearching),
            ),
          if (_isAiLoading)
            const Padding(padding: EdgeInsets.all(12), child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)))
          else
            IconButton(
              icon: Icon(_isAiEnabled ? Icons.smart_toy : Icons.smart_toy_outlined, color: _isAiEnabled ? Colors.greenAccent : Colors.white70),
              tooltip: _isAiEnabled ? 'AI Enabled' : 'AI Disabled',
              onPressed: _toggleAi,
            ),
          IconButton(
            icon: const Icon(Icons.more_vert, color: Colors.white),
            tooltip: 'Chat Options',
            onPressed: _showAssignDialog,
          ),
        ],
      ),
      body: Stack(
        children: [
          Column(
            children: [
              if (_isUploading)
                Container(
                  color: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Row(
                    children: [
                      const Icon(Icons.cloud_upload, color: Color(0xFF128C7E), size: 20),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Uploading $_uploadingFileName...',
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 4),
                            LinearProgressIndicator(
                              value: _uploadProgress,
                              backgroundColor: Colors.grey.shade200,
                              valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF128C7E)),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 10),
                      Text('${(_uploadProgress * 100).toInt()}%', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                    ],
                  ),
                ),
              Expanded(
            child: BlocBuilder<MessageBloc, MessageState>(
              builder: (context, state) {
                if (state is MessageLoading) {
                  return const Center(child: CircularProgressIndicator());
                }
                if (state is MessageLoaded) {
                  final messages = state.messages.where((m) => 
                    (m.content ?? '').toLowerCase().contains(_chatSearchQuery.toLowerCase())
                  ).toList();
                  return ListView.builder(
                    controller: _scrollController,
                    reverse: true,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    itemCount: messages.length + (state.isLoadingMore ? 1 : 0),
                    itemBuilder: (context, index) {
                      if (index == messages.length) {
                        return const Padding(
                          padding: EdgeInsets.symmetric(vertical: 20),
                          child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                        );
                      }
                      final msg = messages[index];

                      final bool isFirstInGroup = index == messages.length - 1 || 
                          messages[index + 1].direction != msg.direction ||
                          messages[index + 1].type == 'note' || msg.type == 'note';

                      final bool showDateHeader = index == messages.length - 1 ||
                          messages[index + 1].createdAt.day != msg.createdAt.day;

                      return Column(
                        key: ValueKey(msg.id),
                        children: [
                          if (showDateHeader)
                            _buildDateHeader(msg.createdAt),
                          ChatBubble(
                            content: msg.content ?? '',
                            isOutbound: msg.direction == 'outbound',
                            time: '${msg.createdAt.hour.toString().padLeft(2, '0')}:${msg.createdAt.minute.toString().padLeft(2, '0')}',
                            type: msg.type,
                            status: msg.status,
                            readAt: msg.readAt,
                            mediaUrl: msg.mediaUrl,
                            replyToContent: msg.replyToContent,
                            metadata: msg.metadata,
                            isNote: msg.type == 'note',
                            showTail: isFirstInGroup,
                            heroTag: 'chat_media_${msg.id}_${msg.mediaUrl}',
                            onReaction: (emoji) {
                              if (msg.remoteId != null) {
                                context.read<ApiService>().reactToMessage(msg.remoteId!, emoji);
                              }
                            },
                            onSwipe: () {
                              setState(() => _replyToMessage = msg);
                            },
                            onLongPress: () {
                                // Close reaction overlay if it was somehow open, 
                                // but here we prioritize the actions bottom sheet.
                                showModalBottomSheet(
                                  context: context,
                                  builder: (ctx) => Column(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      ListTile(
                                        leading: const Icon(Icons.reply_outlined),
                                        title: const Text('Reply'),
                                        onTap: () {
                                          setState(() => _replyToMessage = msg);
                                          Navigator.pop(ctx);
                                        },
                                      ),
                                      ListTile(
                                        leading: const Icon(Icons.star_outline),
                                        title: const Text('Star Message'),
                                        onTap: () {
                                          if (msg.remoteId != null) {
                                             context.read<ApiService>().toggleStar(msg.remoteId!);
                                             ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Message starred!')));
                                          }
                                          Navigator.pop(ctx);
                                        },
                                      ),
                                      ListTile(
                                        leading: const Icon(Icons.forward_outlined),
                                        title: const Text('Forward'),
                                        onTap: () {
                                           Navigator.pop(ctx);
                                           Navigator.push(context, MaterialPageRoute(builder: (ctx) => ForwardMessageScreen(message: msg, apiService: context.read<ApiService>())));
                                        },
                                      ),
                                      const SizedBox(height: 20),
                                    ],
                                  ),
                                );
                            },
                            onMediaTap: (url) => Navigator.push(context, MaterialPageRoute(builder: (context) => MediaViewerPage(imageUrl: url, heroTag: 'chat_media_${msg.id}_${msg.mediaUrl}'))),
                            onRetry: () {
                              if (msg.id != null) {
                                context.read<MessageBloc>().add(RetryMessage(msg.id!));
                              }
                            },
                          ),
                        ],
                      );
                    },
                  );
                }
                return const SizedBox();
              },
            ),
          ),

          // --- Suggestion Bar ---
          BlocBuilder<CannedBloc, CannedState>(
            builder: (context, state) {
              if (state is CannedLoaded && _msgController.text.startsWith('/')) {
                final query = _msgController.text.substring(1).toLowerCase();
                final list = state.messages.where((m) => 
                  m.title.toLowerCase().contains(query) || 
                  m.content.toLowerCase().contains(query)
                ).toList();
                
                if (list.isEmpty) return const SizedBox();

                return Container(
                  height: 50, 
                  color: Theme.of(context).cardColor,
                  child: ListView.builder(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.symmetric(horizontal: 8),
                    itemCount: list.length,
                    itemBuilder: (ctx, i) => Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: ActionChip(
                        avatar: const Icon(Icons.bolt, size: 16, color: Colors.orange),
                        label: Text(list[i].title),
                        onPressed: () {
                          setState(() {
                            _msgController.text = list[i].content;
                            _msgController.selection = TextSelection.fromPosition(
                              TextPosition(offset: _msgController.text.length)
                            );
                          });
                        },
                      ),
                    ),
                  ),
                );
              }
              return const SizedBox();
            },
          ),


          // 2. Message Input / Window Closed Banner
          if (_windowExpiresAt == null && !_isNoteMode)
            // ── WINDOW CLOSED BANNER (matches web) ─────────────────────────
            _WindowClosedBanner(onSendTemplate: _showTemplatePicker)
          else
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
            decoration: BoxDecoration(
              color: Theme.of(context).brightness == Brightness.dark
                  ? Theme.of(context).cardColor
                  : Colors.white.withValues(alpha: 0.9),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.05),
                  blurRadius: 10,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (_replyToMessage != null)
                  Container(
                    margin: const EdgeInsets.only(bottom: 8),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Theme.of(context).brightness == Brightness.dark
                          ? Colors.white.withValues(alpha: 0.05)
                          : const Color(0xFFF0F0F0),
                      borderRadius: BorderRadius.circular(12),
                      border: Border(left: BorderSide(color: Theme.of(context).primaryColor, width: 4)),
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _replyToMessage.direction == 'inbound' ? widget.contactName : 'You',
                                style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF128C7E), fontSize: 12),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                _replyToMessage.content ?? 'Media',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  color: Theme.of(context).brightness == Brightness.dark
                                      ? Colors.white70
                                      : Colors.black87,
                                  fontSize: 13,
                                ),
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close, size: 18, color: Colors.grey),
                          tooltip: 'Dismiss Reply',
                          onPressed: () => setState(() => _replyToMessage = null),
                        ),
                      ],
                    ),
                  ),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Expanded(
                      child: Container(
                        decoration: BoxDecoration(
                          color: Theme.of(context).brightness == Brightness.dark
                              ? (_isNoteMode ? const Color(0xFF4A4526) : Colors.white.withValues(alpha: 0.05))
                              : (_isNoteMode ? const Color(0xFFFFF9C4) : const Color(0xFFF5F6F7)),
                          borderRadius: BorderRadius.circular(24),
                          border: Border.all(
                            color: _isNoteMode
                                ? (Theme.of(context).brightness == Brightness.dark ? Colors.orange.shade800 : Colors.orange.shade300)
                                : Colors.transparent,
                            width: 1,
                          ),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            IconButton(
                              icon: Icon(
                                _isNoteMode ? Icons.note_alt : Icons.insert_emoticon,
                                color: _isNoteMode ? Colors.orange : Colors.grey.shade600,
                              ),
                              tooltip: _isNoteMode ? 'Switch to Message' : 'Switch to Note',
                              onPressed: () => setState(() => _isNoteMode = !_isNoteMode),
                            ),
                            Expanded(
                              child: Padding(
                                padding: const EdgeInsets.symmetric(vertical: 8),
                                child: TextField(
                                  controller: _msgController,
                                  onChanged: _onComposerChanged,
                                  maxLines: 5,
                                  minLines: 1,
                                  style: TextStyle(
                                    fontSize: 15,
                                    color: Theme.of(context).brightness == Brightness.dark
                                        ? Colors.white
                                        : Colors.black87,
                                  ),
                                  decoration: InputDecoration(
                                    hintText: _isNoteMode ? 'Internal Note (Team only)' : 'Type a message',
                                    hintStyle: TextStyle(color: Colors.grey.shade500, fontSize: 15),
                                    border: InputBorder.none,
                                    isDense: true,
                                    contentPadding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
                                  ),
                                ),
                              ),
                            ),
                            IconButton(
                              icon: const Icon(Icons.attach_file, color: Colors.grey),
                              tooltip: 'Attach File',
                              onPressed: _showAttachmentPicker,
                            ),
                            if (!_isNoteMode && _msgController.text.isEmpty) ...[
                              IconButton(
                                icon: const Icon(Icons.camera_alt, color: Colors.grey),
                                tooltip: 'Take Photo',
                                onPressed: () async {
                                  final XFile? photo = await _picker.pickImage(source: ImageSource.camera);
                                  if (photo != null) _handleFileUploadDirect(photo.path, 'image');
                                },
                              ),
                            ],
                            const SizedBox(width: 8),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    if (_isAiEnabled && _msgController.text.isEmpty) ...[
                      GestureDetector(
                        onTap: _suggestAiReply,
                        child: Container(
                          height: 48,
                          width: 48,
                          margin: const EdgeInsets.only(bottom: 0),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFF5C6BC0), Color(0xFF3F51B5)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(
                                color: Theme.of(context).primaryColor.withValues(alpha: 0.1),
                                blurRadius: 8,
                                offset: const Offset(0, 3),
                              ),
                            ],
                          ),
                          child: const Center(child: Text('✨', style: TextStyle(fontSize: 20))),
                        ),
                      ),
                      const SizedBox(width: 8),
                    ],
                    Semantics(
                      label: _msgController.text.isEmpty ? 'Voice Message' : 'Send Message',
                      button: true,
                      child: GestureDetector(
                        onTap: _msgController.text.isEmpty ? null : _sendMessage,
                        child: Container(
                          height: 48,
                          width: 48,
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: _msgController.text.isEmpty 
                                  ? [Colors.grey.shade400, Colors.grey.shade500]
                                  : (_isNoteMode 
                                      ? [Colors.orange, Colors.orangeAccent] 
                                      : [const Color(0xFF128C7E), const Color(0xFF075E54)]),
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(
                                color: (_isNoteMode ? Colors.orange : const Color(0xFF128C7E)).withValues(alpha: 0.1),
                                blurRadius: 8,
                                offset: const Offset(0, 3),
                              ),
                            ],
                          ),
                          child: Icon(
                            _msgController.text.isEmpty ? Icons.mic : Icons.send,
                            color: Colors.white,
                            size: 22,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    ],
  ),
);
  }


  Widget _buildDateHeader(DateTime date) {
    String text;
    final now = DateTime.now();
    if (date.year == now.year && date.month == now.month && date.day == now.day) {
      text = 'TODAY';
    } else if (date.year == now.year && date.month == now.month && date.day == now.day - 1) {
      text = 'YESTERDAY';
    } else {
      text = '${date.day}/${date.month}/${date.year}';
    }

    return Center(
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 12),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.8),
          borderRadius: BorderRadius.circular(8),
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 2, offset: const Offset(0, 1)),
          ],
        ),
        child: Text(
          text,
          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.blueGrey),
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Pulsing green dot widget (identical to the web's session-open indicator)
// ─────────────────────────────────────────────────────────────────────────────
class _PulsingDot extends StatefulWidget {
  @override
  State<_PulsingDot> createState() => _PulsingDotState();
}

class _PulsingDotState extends State<_PulsingDot> with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl;
  late final Animation<double> _anim;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 1200))
      ..repeat(reverse: true);
    _anim = Tween(begin: 0.4, end: 1.0).animate(CurvedAnimation(parent: _ctrl, curve: Curves.easeInOut));
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: _anim,
      child: Container(
        width: 8,
        height: 8,
        decoration: BoxDecoration(
          color: const Color(0xFF25D366),
          shape: BoxShape.circle,
          boxShadow: [
            BoxShadow(color: const Color(0xFF25D366).withValues(alpha: 0.6), blurRadius: 6, spreadRadius: 1),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Window Closed Banner — shown instead of the input composer when the 24h
// messaging window has expired. Matches the web dashboard's amber bottom bar.
// ─────────────────────────────────────────────────────────────────────────────
class _WindowClosedBanner extends StatelessWidget {
  final VoidCallback onSendTemplate;
  const _WindowClosedBanner({required this.onSendTemplate});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF8E1),
        border: Border(
          top: BorderSide(color: Colors.amber.shade300, width: 1.5),
        ),
      ),
      child: Row(
        children: [
          // Clock icon in amber circle
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.amber.shade100,
              shape: BoxShape.circle,
            ),
            child: Icon(Icons.access_time_rounded, color: Colors.amber.shade700, size: 20),
          ),
          const SizedBox(width: 12),
          // Text block
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '24h Window Closed',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w900,
                    color: Colors.amber.shade900,
                    letterSpacing: 0.2,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  'Customer hasn\'t replied recently. Use a template to re-open.',
                  style: TextStyle(
                    fontSize: 11,
                    color: Colors.amber.shade800,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          // CTA button
          GestureDetector(
            onTap: onSendTemplate,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
              decoration: BoxDecoration(
                color: const Color(0xFF128C7E),
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF128C7E).withValues(alpha: 0.1),
                    blurRadius: 8,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: const [
                  Icon(Icons.description_outlined, color: Colors.white, size: 15),
                  SizedBox(width: 5),
                  Text(
                    'Send Template',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 11,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 0.5,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
