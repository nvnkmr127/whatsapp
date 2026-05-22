import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:timeago/timeago.dart' as timeago;
import '../../logic/blocs/chat_bloc.dart';
import '../widgets/chat_avatar.dart';
import '../widgets/empty_inbox.dart';
import '../widgets/inbox_shimmer.dart';
import 'chat_screen.dart';
import '../widgets/error_view.dart';
import 'profile_screen.dart';
import 'analytics_screen.dart';
import 'contact_picker_screen.dart';
import 'starred_messages_screen.dart';
import 'call_log_screen.dart';
import '../../data/repositories/chat_repository.dart';
import '../../core/socket_service.dart';
import '../../core/fcm_service.dart';
import '../../core/api_service.dart';
import '../../core/realtime_session_controller.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../../logic/blocs/message_bloc.dart';
import '../../logic/blocs/auth_bloc.dart';
import '../../logic/blocs/canned_bloc.dart';
import '../../core/storage_keys.dart';

class InboxScreen extends StatefulWidget {
  const InboxScreen({super.key});

  @override
  State<InboxScreen> createState() => _InboxScreenState();
}

class _InboxScreenState extends State<InboxScreen> {
  final TextEditingController _searchController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  String _searchQuery = '';
  int _currentPage = 1;
  bool _isLoadingMore = false;
  final Set<int> _selectedIds = {};
  final Set<int> _pinnedIds = {};
  late final RealtimeSessionController _realtimeSessionController;

  @override
  void initState() {
    super.initState();
    _realtimeSessionController = RealtimeSessionController(
      socketFactory: ({
        required String token,
        required String whatsappNumberId,
        required String tenantId,
      }) {
        return SocketService(
          context.read<ChatBloc>(),
          context.read<MessageBloc>(),
          token: token,
          whatsappNumberId: whatsappNumberId,
          tenantId: tenantId,
        );
      },
    );
    _initServices();
    context.read<ChatBloc>().add(FetchConversations());
    _scrollController.addListener(_onScroll);
  }

  Future<void> _initServices() async {
    final apiService = context.read<ApiService>();
    
    // 1. FCM Notifications
    FCMService(apiService).initialize();

    // 2. Real-time WebSockets
    const storage = FlutterSecureStorage();
    final token = await storage.read(key: StorageKeys.authToken);
    final tenantId = await storage.read(key: StorageKeys.tenantId);
    final whatsappNumberId = await storage.read(key: StorageKeys.activeWhatsAppNumberId);

    if (token != null && tenantId != null && whatsappNumberId != null && mounted) {
      _realtimeSessionController.connect(
        token: token,
        whatsappNumberId: whatsappNumberId,
        tenantId: tenantId,
        teamId: int.parse(tenantId),
      );
    }

  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200 && !_isLoadingMore) {
      final state = context.read<ChatBloc>().state;
      if (state is ConversationsLoaded && state.hasNextPage) {
        setState(() {
          _isLoadingMore = true;
          _currentPage++;
        });
        context.read<ChatBloc>().add(FetchConversations(page: _currentPage));
      }
    }
  }

  @override
  void dispose() {
    _realtimeSessionController.disconnect();
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Color _parseColor(String? hex) {
    if (hex == null || hex.isEmpty) return Colors.grey;
    try {
      return Color(int.parse(hex.replaceFirst('#', '0xFF')));
    } catch (_) {
      return Colors.grey;
    }
  }


  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
      appBar: AppBar(
        title: _selectedIds.isNotEmpty 
          ? Text('${_selectedIds.length} selected')
          : (_searchQuery.isEmpty 
            ? BlocBuilder<AuthBloc, AuthState>(
                builder: (context, state) {
                  if (state is Authenticated && state.whatsappNumbers.length > 1) {
                    return DropdownButtonHideUnderline(
                      child: DropdownButton<String>(
                        value: state.activeNumberId,
                        dropdownColor: const Color(0xFF128C7E),
                        icon: const Icon(Icons.keyboard_arrow_down, color: Colors.white70),
                        style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                        onChanged: (val) {
                          if (val != null) {
                            context.read<AuthBloc>().add(WhatsAppNumberSelected(val));
                          }
                        },
                        items: state.whatsappNumbers.map<DropdownMenuItem<String>>((number) {
                          return DropdownMenuItem<String>(
                            value: number['id'].toString(),
                            child: Text(number['display_number'] ?? 'WhatsApp'),
                          );
                        }).toList(),
                      ),
                    );
                  }
                  return const Text('Watxio', style: TextStyle(fontWeight: FontWeight.bold));
                },
              )
            : TextField(

                controller: _searchController,
                autofocus: true,
                style: const TextStyle(color: Colors.white),
                decoration: const InputDecoration(
                  labelText: 'Search',
                  hintText: 'Search chats...',
                  hintStyle: TextStyle(color: Colors.white54),
                  labelStyle: TextStyle(color: Colors.white70),
                  border: InputBorder.none,
                ),
                onChanged: (val) {
                  setState(() => _searchQuery = val);
                  // Debounce search
                  Future.delayed(const Duration(milliseconds: 500), () {
                    if (context.mounted && _searchQuery == val) {
                      context.read<ChatBloc>().add(FetchConversations(isRefresh: true, query: val.trim()));
                    }
                  });
                },
              )),
        actions: _selectedIds.isNotEmpty 
          ? [
              IconButton(
                icon: const Icon(Icons.push_pin_outlined),
                tooltip: 'Pin/Unpin',
                onPressed: () {
                   setState(() {
                     for (final id in _selectedIds) {
                       if (_pinnedIds.contains(id)) {
                         _pinnedIds.remove(id);
                       } else {
                         _pinnedIds.add(id);
                       }
                     }
                     _selectedIds.clear();
                   });
                },
              ),
              IconButton(
                icon: const Icon(Icons.check_circle_outline),
                tooltip: 'Bulk Resolve',
                onPressed: () async {
                  final ids = _selectedIds.toList();
                  setState(() => _selectedIds.clear());

                  int ok = 0;
                  int fail = 0;
                  for (final id in ids) {
                    try {
                      await context.read<ApiService>().closeConversation(id);
                      ok++;
                    } catch (_) {
                      fail++;
                    }
                  }

                  if (!context.mounted) return;
                  context.read<ChatBloc>().add(FetchConversations(isRefresh: true));
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text('Resolved $ok of ${ids.length} chats' + (fail > 0 ? ' ($fail failed)' : ''))),
                  );
                },
              ),
              IconButton(
                icon: const Icon(Icons.close),
                tooltip: 'Clear Selection',
                onPressed: () => setState(() => _selectedIds.clear()),
              ),
            ]
          : [
          IconButton(
            icon: Icon(_searchQuery.isEmpty ? Icons.search : Icons.close), 
            tooltip: _searchQuery.isEmpty ? 'Search' : 'Close Search',
            onPressed: () {
              setState(() {
                if (_searchQuery.isNotEmpty) {
                  _searchQuery = '';
                  _searchController.clear();
                  context.read<ChatBloc>().add(FetchConversations(isRefresh: true));
                } else {
                  _searchQuery = ' '; // Trigger search mode
                }
              });
            },
          ),
          IconButton(
            icon: const Icon(Icons.account_circle_outlined), 
            tooltip: 'Profile Settings',
            onPressed: () {
              Navigator.push(context, MaterialPageRoute(builder: (context) => const ProfileScreen()));
            },
          ),
        ],
        bottom: (_selectedIds.isEmpty && _searchQuery.isEmpty) ? const TabBar(
          indicatorColor: Colors.white,
          tabs: [
            Tab(text: 'CHATS'),
            Tab(text: 'CALLS'),
          ],
        ) : null,
      ),
      drawer: BlocBuilder<AuthBloc, AuthState>(
        builder: (context, state) {
          if (state is! Authenticated) return const SizedBox();
          return Drawer(
            child: ListView(
              children: [
                DrawerHeader(
                  decoration: const BoxDecoration(color: Color(0xFF128C7E)),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const CircleAvatar(backgroundColor: Colors.white, child: Icon(Icons.person, color: Color(0xFF128C7E))),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(state.user?['name'] ?? 'User', style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
                              ],
                            ),
                          ),
                          if (state.activeMemberId != null)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(color: Colors.orange, borderRadius: BorderRadius.circular(4)),
                              child: const Text('VIEWING AS', style: TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold)),
                            ),
                        ],
                      ),
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Text('MENU', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.teal.shade900, fontSize: 12, letterSpacing: 1.2)),
                ),
                ListTile(
                  leading: const Icon(Icons.dashboard_outlined),
                  title: const Text('Dashboard Analytics'),
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (ctx) => AnalyticsScreen(apiService: context.read<ApiService>())));
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.star_outline),
                  title: const Text('Starred Messages'),
                  onTap: () {
                    Navigator.pop(context);
                    final isar = context.read<ChatRepository>().isar;
                    if (isar != null) {
                      Navigator.push(context, MaterialPageRoute(builder: (ctx) => StarredMessagesScreen(isar: isar)));
                    } else {
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Offline storage not available.')));
                    }
                  },
                ),
                const Divider(),
                if (state.user?['role'] == 'admin' || state.user?['role'] == 'owner') ...[
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    child: Text('VIEW AS MEMBER', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.teal.shade900, fontSize: 12, letterSpacing: 1.2)),
                  ),
                  ListTile(
                    leading: const Icon(Icons.person_outline, size: 20),
                    title: const Text('Myself (Original)', style: TextStyle(fontSize: 14)),
                    selected: state.activeMemberId == null,
                    onTap: () {
                      context.read<AuthBloc>().add(MemberSelected('self'));
                      Navigator.pop(context);
                    },
                  ),
                    ...state.members.where((m) => m['id'] != state.user?['id']).map((member) => ListTile(
                      leading: const Icon(Icons.person, size: 20),
                      title: Text(member['name'], style: const TextStyle(fontSize: 14)),
                      subtitle: Text(member['role'].toString().toUpperCase(), style: const TextStyle(fontSize: 10)),
                      selected: state.activeMemberId == member['id'].toString(),
                      onTap: () {
                        context.read<AuthBloc>().add(MemberSelected(member['id'].toString()));
                        Navigator.pop(context);
                      },
                    )),
                  const Divider(),
                ],

                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Text('SWITCH WORKSPACE', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.teal.shade900, fontSize: 12, letterSpacing: 1.2)),
                ),
                ...state.teams.map((team) {
                  final isSelected = team['id'].toString() == state.tenantId;
                  return ListTile(
                    leading: Icon(Icons.business, color: isSelected ? const Color(0xFF128C7E) : null),
                    title: Text(team['name'], style: TextStyle(fontWeight: isSelected ? FontWeight.bold : null)),
                    trailing: isSelected ? const Icon(Icons.check, color: Color(0xFF128C7E), size: 16) : null,
                    selected: isSelected,
                    onTap: () {
                      context.read<AuthBloc>().add(TenantSelected(team['id']));
                      Navigator.pop(context); // Close drawer
                    },
                  );
                }),
                const Divider(),
                ListTile(
                  leading: const Icon(Icons.logout, color: Colors.red),
                  title: const Text('Logout', style: TextStyle(color: Colors.red)),
                  onTap: () {
                    showDialog(
                      context: context,
                      builder: (ctx) => AlertDialog(
                        title: const Text('Logout'),
                        content: const Text('Are you sure you want to logout?'),
                        actions: [
                          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('CANCEL')),
                          TextButton(
                            onPressed: () async {
                              await context.read<ApiService>().logout();
                              if (context.mounted) {
                                Navigator.pop(ctx);
                                context.read<AuthBloc>().add(LogoutRequested());
                              }
                            }, 
                            child: const Text('LOGOUT', style: TextStyle(color: Colors.red))
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ],
            ),
          );
        },
      ),
      floatingActionButton: FloatingActionButton(
        heroTag: 'inbox_fab',
        onPressed: () {
          Navigator.push(context, MaterialPageRoute(builder: (c) => const ContactPickerScreen()));
        },
        child: const Icon(Icons.chat_bubble_outline),
      ),
      body: BlocListener<AuthBloc, AuthState>(
        listener: (context, state) {
          if (state is Authenticated) {
            _initServices();
            context.read<ChatBloc>().add(FetchConversations(isRefresh: true));
            context.read<CannedBloc>().add(FetchCannedMessages());
          }
        },
        child: TabBarView(
          children: [
            BlocBuilder<ChatBloc, ChatState>(
              builder: (context, state) {
          if (state is ChatLoading) {
            return const InboxShimmer();
          }

          if (state is ConversationsLoaded) {
            _isLoadingMore = false;

            final conversations = state.conversations.where((c) {
              final name = (c['name'] ?? '').toString().toLowerCase();
              return name.contains(_searchQuery.trim().toLowerCase());
            }).toList();

            // Sort pinned to top
            conversations.sort((a, b) {
              final aPinned = _pinnedIds.contains(a['id']);
              final bPinned = _pinnedIds.contains(b['id']);
              if (aPinned && !bPinned) return -1;
              if (!aPinned && bPinned) return 1;
              return 0;
            });

            if (conversations.isEmpty) {
              return const EmptyInboxWidget();
            }

            return RefreshIndicator(
              onRefresh: () async {
                context.read<ChatBloc>().add(FetchConversations(isRefresh: true, filter: state.currentFilter));
              },
              child: Column(
                children: [
                  // --- Filter Bar ---
                  Container(
                    height: 50,
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                    child: ListView(
                      scrollDirection: Axis.horizontal,
                      children: [
                        _FilterChip(label: 'All', value: 'all', current: state.currentFilter),
                        const SizedBox(width: 8),
                        _FilterChip(label: 'Unread', value: 'unread', current: state.currentFilter),
                        const SizedBox(width: 8),
                        _FilterChip(label: 'Assigned to me', value: 'assigned', current: state.currentFilter),
                      ],
                    ),
                  ),
                  const Divider(height: 1),
                  Expanded(
                    child: ListView.separated(
                controller: _scrollController,
                itemCount: conversations.length + (state.hasNextPage ? 1 : 0),
                separatorBuilder: (context, index) => const Divider(height: 1, indent: 80),
                itemBuilder: (context, index) {
                  if (index == conversations.length) {
                    return Padding(
                      padding: EdgeInsets.all(16.0),
                      child: Container(
                        alignment: Alignment.center,
                        child: const CircularProgressIndicator(),
                      ),
                    );
                  }
                  final conv = conversations[index];
                  final lastMsg = conv['last_message'];
                  final bool isSelected = _selectedIds.contains(conv['id']);

                  return InkWell(
                    key: ValueKey(conv['id']),
                    onTap: () {
                       if (_selectedIds.isNotEmpty) {
                         setState(() {
                           if (_selectedIds.contains(conv['id'])) {
                             _selectedIds.remove(conv['id']);
                           } else {
                             _selectedIds.add(conv['id']);
                           }
                         });
                       } else {
                         Navigator.push(
                           context,
                           MaterialPageRoute(
                             builder: (context) => ChatScreen(
                               conversationId: conv['id'],
                               contactName: conv['name'],
                             ),
                           ),
                         );
                       }
                    },
                    onLongPress: () {
                      setState(() {
                        _selectedIds.add(conv['id']);
                      });
                    },
                    child: Container(
                      color: isSelected ? Colors.teal.withValues(alpha: 0.05) : Colors.transparent,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          isSelected 
                            ? const CircleAvatar(
                                backgroundColor: Color(0xFF25D366),
                                radius: 24,
                                child: Icon(Icons.check, color: Colors.white),
                              )
                            : ChatAvatar(name: conv['name'], imageUrl: null),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Row(
                                  children: [
                                    if (_pinnedIds.contains(conv['id']))
                                      const Padding(
                                        padding: EdgeInsets.only(right: 4),
                                        child: Icon(Icons.push_pin, size: 12, color: Colors.grey),
                                      ),
                                    Expanded(
                                      child: Text(
                                        conv['name'] ?? 'Unknown',
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                      ),
                                    ),
                                    if (lastMsg != null)
                                      Text(
                                        conv['last_interaction'] != null 
                                        ? timeago.format(DateTime.fromMillisecondsSinceEpoch(conv['last_interaction'] * 1000), locale: 'en_short')
                                        : '',
                                        style: TextStyle(
                                          fontSize: 11, 
                                          color: (conv['unread_count'] ?? 0) > 0 ? const Color(0xFF25D366) : Colors.grey
                                        ),
                                      ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                (state).typingAgents.containsKey(conv['id'])
                                  ? Text(
                                      '${(state).typingAgents[conv['id']]} is typing...',
                                      style: const TextStyle(color: Color(0xFF25D366), fontStyle: FontStyle.italic, fontSize: 13),
                                    )
                                  : Row(
                                      children: [
                                        if (lastMsg != null && lastMsg['is_outbound'])
                                          const Padding(
                                            padding: EdgeInsets.only(right: 4),
                                            child: Icon(Icons.done_all, size: 14, color: Colors.blue),
                                          ),
                                        Expanded(
                                          child: Text(
                                            lastMsg?['content'] ?? 'No messages yet',
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            style: const TextStyle(color: Colors.blueGrey, fontSize: 13),
                                          ),
                                        ),
                                        if ((conv['unread_count'] ?? 0) > 0)
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                            decoration: BoxDecoration(color: const Color(0xFF25D366), borderRadius: BorderRadius.circular(10)),
                                            child: Text(
                                              conv['unread_count'].toString(),
                                              style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                                            ),
                                          ),
                                      ],
                                    ),
                                const SizedBox(height: 6),
                                Row(
                                  children: [
                                    if (conv['assignee'] != null)
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                        margin: const EdgeInsets.only(right: 8),
                                        decoration: BoxDecoration(color: Colors.blue.shade50, borderRadius: BorderRadius.circular(4)),
                                        child: Row(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            const Icon(Icons.person, size: 10, color: Colors.blue),
                                            const SizedBox(width: 2),
                                            Text(conv['assignee']['name'], style: TextStyle(fontSize: 9, color: Colors.blue.shade700, fontWeight: FontWeight.bold)),
                                          ],
                                        ),
                                      ),
                                    if (conv['sla_status'] != null && conv['sla_status'] != 'on_track')
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                        margin: const EdgeInsets.only(right: 8),
                                        decoration: BoxDecoration(
                                          color: conv['sla_status'] == 'breached' ? Colors.red.shade50 : Colors.orange.shade50,
                                          borderRadius: BorderRadius.circular(4),
                                          border: Border.all(color: conv['sla_status'] == 'breached' ? Colors.red.shade200 : Colors.orange.shade200),
                                        ),
                                        child: Row(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            Icon(Icons.alarm, size: 10, color: conv['sla_status'] == 'breached' ? Colors.red : Colors.orange.shade700),
                                            const SizedBox(width: 2),
                                            Text(
                                              conv['sla_status'] == 'breached' ? 'SLA BREACH' : 'SLA SOON',
                                              style: TextStyle(
                                                fontSize: 9, 
                                                color: conv['sla_status'] == 'breached' ? Colors.red : Colors.orange.shade800, 
                                                fontWeight: FontWeight.bold,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    if (conv['tags'] != null)
                                      Expanded(
                                        child: Wrap(
                                          spacing: 4,
                                          runSpacing: 4,
                                          children: (conv['tags'] as List).take(2).map((tag) => Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                                            decoration: BoxDecoration(
                                              color: _parseColor(tag['color']).withValues(alpha: 0.1),
                                              borderRadius: BorderRadius.circular(2),
                                            ),
                                            child: Text(
                                              tag['name'].toString().toUpperCase(),
                                              style: TextStyle(fontSize: 8, color: _parseColor(tag['color']), fontWeight: FontWeight.bold),
                                            ),
                                          )).toList(),
                                        ),
                                      ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                      },
                    ),
                  ),
                ],
              ),
            );
          }
          if (state is ChatError) {
            return ErrorView(
              message: state.message,
              onRetry: () => context.read<ChatBloc>().add(FetchConversations(isRefresh: true)),
            );
          }

          return const EmptyInboxWidget();
        },
            ),
            CallLogScreen(apiService: context.read<ApiService>()),
          ],
        ),
      ),
    ),
  );
}
}

class _FilterChip extends StatelessWidget {
  final String label;
  final String value;
  final String current;

  const _FilterChip({required this.label, required this.value, required this.current});

  @override
  Widget build(BuildContext context) {
    final bool isSelected = value == current;
    return ChoiceChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (selected) {
        if (selected) {
          context.read<ChatBloc>().add(FetchConversations(isRefresh: true, filter: value));
        }
      },
      selectedColor: Theme.of(context).colorScheme.primary,
      labelStyle: TextStyle(
        color: isSelected
            ? Colors.white
            : (Theme.of(context).brightness == Brightness.dark ? Colors.white70 : Colors.black87),
        fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
      ),
    );
  }
}
