import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../logic/blocs/auth_bloc.dart';
import '../../core/api_service.dart';
import 'bot_edit_screen.dart';

class BotListScreen extends StatefulWidget {
  const BotListScreen({super.key});

  @override
  State<BotListScreen> createState() => _BotListScreenState();
}

class _BotListScreenState extends State<BotListScreen> {
  final List<dynamic> _bots = [];
  bool _isLoading = false;
  int _currentPage = 1;
  bool _hasNextPage = true;
  String _searchQuery = '';
  Timer? _debounce;
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchBots(isRefresh: true);
  }

  Future<void> _fetchBots({bool isRefresh = false}) async {
    if (isRefresh) {
      setState(() {
        _isLoading = true;
        _currentPage = 1;
        _bots.clear();
        _hasNextPage = true;
      });
    }

    try {
      final response = await context.read<ApiService>().getBots(
        page: _currentPage,
        search: _searchQuery,
      );
      
      final List newBots = response.data['data'] ?? [];
      setState(() {
        _bots.addAll(newBots);
        _hasNextPage = response.data['next_page_url'] != null;
        _isLoading = false;
        _currentPage++;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error fetching bots: $e')),
        );
      }
    }
  }

  Future<void> _toggleBotStatus(int id, bool currentStatus) async {
    try {
      await context.read<ApiService>().toggleBot(id);
      setState(() {
        final index = _bots.indexWhere((b) => b['id'] == id);
        if (index != -1) {
          _bots[index]['is_bot_active'] = !currentStatus;
        }
      });
    } catch (e) {
       if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    }
  }

  Future<void> _duplicateBot(int id) async {
    try {
      final response = await context.read<ApiService>().duplicateBot(id);
      setState(() {
        _bots.insert(0, response.data);
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Bot duplicated successfully')),
        );
      }
    } catch (e) {
       if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error duplicating: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        if (state is Authenticated) {
          _fetchBots(isRefresh: true);
        }
      },
      child: Scaffold(
        backgroundColor: const Color(0xFFF0F2F5),
        appBar: AppBar(
          title: const Text('Message Bots', style: TextStyle(fontWeight: FontWeight.bold)),
          backgroundColor: const Color(0xFF128C7E),
          foregroundColor: Colors.white,
          actions: [
            IconButton(icon: const Icon(Icons.refresh), onPressed: () => _fetchBots(isRefresh: true)),
          ],
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(60),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: TextField(
                controller: _searchController,
                onChanged: (v) {
                  _debounce?.cancel();
                  _debounce = Timer(const Duration(milliseconds: 500), () {
                    setState(() => _searchQuery = v);
                    _fetchBots(isRefresh: true);
                  });
                },
                decoration: InputDecoration(
                  hintText: 'Search bots...',
                  prefixIcon: const Icon(Icons.search),
                  filled: true,
                  fillColor: Colors.white,
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                  contentPadding: EdgeInsets.zero,
                ),
              ),
            ),
          ),
        ),
        body: _isLoading && _bots.isEmpty
            ? const Center(child: CircularProgressIndicator())
            : RefreshIndicator(
                onRefresh: () => _fetchBots(isRefresh: true),
                child: _bots.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.smart_toy_outlined, size: 64, color: Colors.grey[300]),
                            const SizedBox(height: 16),
                            Text('No bots found', style: TextStyle(color: Colors.grey[600], fontSize: 18, fontWeight: FontWeight.bold)),
                            const SizedBox(height: 8),
                            Text('Tap + to create your first bot', style: TextStyle(color: Colors.grey[400])),
                          ],
                        ),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.all(12),
                        itemCount: _bots.length + (_hasNextPage ? 1 : 0),
                        itemBuilder: (context, index) {
                          if (index == _bots.length) {
                            _fetchBots();
                            return const Center(child: Padding(
                              padding: EdgeInsets.all(8.0),
                              child: CircularProgressIndicator(),
                            ));
                          }
                          
                          final bot = _bots[index];
                          final bool isActive = bot['is_bot_active'] ?? false;
  
                          return Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            elevation: 0.5,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            child: Padding(
                              padding: const EdgeInsets.all(12.0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              bot['name'] ?? 'Unnamed Bot',
                                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                            ),
                                            if (bot['sending_count'] != null)
                                              Text(
                                                'Sent: ${bot['sending_count']} times',
                                                style: const TextStyle(fontSize: 11, color: Colors.grey),
                                              ),
                                          ],
                                        ),
                                      ),
                                      Switch(
                                        value: isActive,
                                        onChanged: (val) => _toggleBotStatus(bot['id'], isActive),
                                        activeThumbColor: const Color(0xFF128C7E),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 4),
                                  if (bot['trigger'] != null)
                                    Wrap(
                                      spacing: 4,
                                      children: (bot['trigger'] as List).map((t) => Chip(
                                        label: Text(t.toString(), style: const TextStyle(fontSize: 10)),
                                        padding: EdgeInsets.zero,
                                        visualDensity: VisualDensity.compact,
                                      )).toList(),
                                    ),
                                  const SizedBox(height: 8),
                                  Text(
                                    bot['reply_text'] ?? '',
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(color: Colors.black54),
                                  ),
                                  const Divider(),
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.end,
                                    children: [
                                      TextButton.icon(
                                        onPressed: () => _duplicateBot(bot['id']),
                                        icon: const Icon(Icons.copy, size: 18),
                                        label: const Text('Duplicate'),
                                      ),
                                      TextButton.icon(
                                        onPressed: () => _showDeleteBotConfirm(bot['id']),
                                        icon: const Icon(Icons.delete_outline, size: 18, color: Colors.red),
                                        label: const Text('Delete', style: TextStyle(color: Colors.red)),
                                      ),
                                      TextButton.icon(
                                        onPressed: () async {
                                          final result = await Navigator.push(
                                            context,
                                            MaterialPageRoute(
                                              builder: (context) => BotEditScreen(bot: bot),
                                            ),
                                          );
                                          if (result == true) {
                                            _fetchBots(isRefresh: true);
                                          }
                                        },
                                        icon: const Icon(Icons.edit, size: 18),
                                        label: const Text('Edit'),
                                      ),
                                    ],
                                  )
                                ],
                              ),
                            ),
                          );
                        },
                      ),
              ),
        floatingActionButton: FloatingActionButton(
          backgroundColor: const Color(0xFF128C7E),
          child: const Icon(Icons.add, color: Colors.white),
          onPressed: () => _showCreateOptions(),
        ),
      ),
    );
  }

  void _showCreateOptions() {
    showModalBottomSheet(
      context: context,
      builder: (context) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ListTile(
            leading: const Icon(Icons.create, color: Color(0xFF128C7E)),
            title: const Text('Create Custom Bot'),
            onTap: () async {
              Navigator.pop(context);
              final result = await Navigator.push(
                context,
                MaterialPageRoute(builder: (context) => const BotEditScreen()),
              );
              if (result == true && mounted) _fetchBots(isRefresh: true);
            },
          ),
          const Divider(),
          const Padding(
            padding: EdgeInsets.all(12.0),
            child: Text('OR USE A PRESET', style: TextStyle(fontSize: 12, color: Colors.grey, fontWeight: FontWeight.bold)),
          ),
          _presetTile('Welcome Bot', ['hi', 'hello', 'start'], 'Welcome! How can we help you today?'),
          _presetTile('FAQ Bot', ['faq', 'questions', 'help'], 'You can check our pricing at example.com/pricing or contact support.'),
          _presetTile('Lead Gen Bot', ['interested', 'buy', 'price'], 'Great! Please provide your email address to get a quote.'),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  Widget _presetTile(String name, List<String> triggers, String reply) {
    return ListTile(
      leading: const Icon(Icons.auto_awesome, color: Colors.orange),
      title: Text(name),
      subtitle: Text('Triggers: ${triggers.join(", ")}'),
      onTap: () async {
        final messenger = ScaffoldMessenger.of(context);
        Navigator.pop(context);
        try {
          final payload = {
            'name': name,
            'trigger': triggers,
            'reply_text': reply,
            'is_bot_active': false,
          };
          debugPrint('[DEBUG] [API] Creating bot with payload: $payload');
          await context.read<ApiService>().createBot(payload);
          _fetchBots(isRefresh: true);
          if (mounted) {
            messenger.showSnackBar(
              SnackBar(content: Text('Created $name from preset')),
            );
          }
        } catch (e) {
          debugPrint('[DEBUG] [API] Error creating bot: $e');
          if (mounted) {
            messenger.showSnackBar(
              SnackBar(content: Text('Error: $e')),
            );
          }
        }
      },
    );
  }

  void _showDeleteBotConfirm(int id) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Bot?'),
        content: const Text('This will permanently delete this bot. This action cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              _deleteBot(id);
            },
            child: const Text('Delete', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  Future<void> _deleteBot(int id) async {
    final messenger = ScaffoldMessenger.of(context);
    try {
      debugPrint('[DEBUG] [API] Deleting bot: $id');
      await context.read<ApiService>().deleteBot(id);
      if (mounted) {
        setState(() {
          _bots.removeWhere((b) => b['id'] == id);
        });
        messenger.showSnackBar(
          const SnackBar(content: Text('Bot deleted')),
        );
      }
    } catch (e) {
      debugPrint('[DEBUG] [API] Error deleting bot: $e');
      if (mounted) {
        messenger.showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    }
  }
}
