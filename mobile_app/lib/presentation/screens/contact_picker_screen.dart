import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../logic/blocs/canned_bloc.dart';
import 'package:timeago/timeago.dart' as timeago;
import '../../logic/blocs/auth_bloc.dart';
import '../../core/api_service.dart';
import '../widgets/chat_avatar.dart';
import 'chat_screen.dart';

class ContactPickerScreen extends StatefulWidget {
  const ContactPickerScreen({super.key});

  @override
  State<ContactPickerScreen> createState() => _ContactPickerScreenState();
}

class _ContactPickerScreenState extends State<ContactPickerScreen> {
  final List<dynamic> _contacts = [];
  bool _isLoading = false;
  bool _isLoadingMore = false;
  bool _hasNextPage = true;
  int _currentPage = 1;
  String _searchQuery = '';
  String _sortBy = 'last_activity'; // 'name' or 'last_activity'
  
  final TextEditingController _searchController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _fetchContacts(isRefresh: true);
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _scrollController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
      if (!_isLoadingMore && _hasNextPage) {
        _fetchContacts(isRefresh: false);
      }
    }
  }

  Future<void> _fetchContacts({bool isRefresh = false}) async {
    if (isRefresh) {
      setState(() {
        _isLoading = true;
        _currentPage = 1;
        _contacts.clear();
        _hasNextPage = true;
      });
    } else {
      setState(() => _isLoadingMore = true);
    }

    try {
      final response = await context.read<ApiService>().getContacts(
        page: _currentPage,
        search: _searchQuery,
        sortBy: _sortBy,
      );
      if (!mounted) return;

      final data = response.data;
      final List newItems = data['data'] ?? [];
      
      setState(() {
        _contacts.addAll(newItems);
        _hasNextPage = data['next_page_url'] != null;
        _isLoading = false;
        _isLoadingMore = false;
        _currentPage++;
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Failed to load contacts: $e')));
        setState(() {
          _isLoading = false;
          _isLoadingMore = false;
        });
      }
    }
  }

  void _onSearchChanged(String query) {
    if (_debounce?.isActive ?? false) _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 500), () {
      setState(() => _searchQuery = query);
      _fetchContacts(isRefresh: true);
    });
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        if (state is Authenticated) {
          _fetchContacts(isRefresh: true);
        }
      },
      child: Scaffold(
        backgroundColor: Colors.white,
        appBar: AppBar(
        backgroundColor: const Color(0xFF128C7E),
        elevation: 0,
        title: const Text('Select Contact', style: TextStyle(color: Colors.white)),
        actions: [
          IconButton(
            icon: const Icon(Icons.sort, color: Colors.white),
            tooltip: 'Sort Contacts',
            onPressed: _showSortOptions,
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(60),
          child: Container(
            padding: const EdgeInsets.all(10),
            child: TextField(
              controller: _searchController,
              onChanged: _onSearchChanged,
              decoration: InputDecoration(
                hintText: 'Search name or number...',
                prefixIcon: const Icon(Icons.search, color: Colors.grey),
                filled: true,
                fillColor: Colors.white,
                contentPadding: const EdgeInsets.symmetric(vertical: 0),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(30),
                  borderSide: BorderSide.none,
                ),
              ),
            ),
          ),
        ),
      ),
      body: _isLoading 
        ? const Center(child: CircularProgressIndicator())
        : RefreshIndicator(
            onRefresh: () => _fetchContacts(isRefresh: true),
            child: _contacts.isEmpty && !_isLoading
              ? _buildNoResultsView()
              : ListView.builder(
                controller: _scrollController,
                itemCount: _contacts.length + (_isLoadingMore ? 1 : 0),
                itemBuilder: (context, index) {
                  if (index == _contacts.length) {
                    return const Padding(
                      padding: EdgeInsets.all(20),
                      child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                    );
                  }
                  
                  final contact = _contacts[index];
                  final tags = contact['tags'] as List? ?? [];
                  final updatedAt = DateTime.tryParse(contact['updated_at'] ?? '');

                  return ListTile(
                    key: ValueKey(contact['id']),
                    leading: ChatAvatar(name: contact['name']),
                    title: Row(
                      children: [
                        Expanded(
                          child: Text(
                            contact['name'] ?? 'Unknown', 
                            style: const TextStyle(fontWeight: FontWeight.bold)
                          ),
                        ),
                        if (updatedAt != null)
                          Text(
                            timeago.format(updatedAt, locale: 'en_short'),
                            style: const TextStyle(fontSize: 10, color: Colors.grey),
                          ),
                      ],
                    ),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(contact['phone_number'] ?? ''),
                        if (tags.isNotEmpty)
                          Padding(
                            padding: const EdgeInsets.only(top: 4),
                            child: Wrap(
                              spacing: 4,
                              children: tags.take(3).map((tag) => Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: _parseColor(tag['color']).withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  tag['name'], 
                                  style: TextStyle(fontSize: 10, color: _parseColor(tag['color'])),
                                ),
                              )).toList(),
                            ),
                          ),
                      ],
                    ),
                    onTap: () => _openChat(contact),
                  );
                },
              ),
            ),
          ),
    );
  }

  void _showSortOptions() {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const ListTile(title: Text('Sort By', style: TextStyle(fontWeight: FontWeight.bold))),
          ListTile(
            leading: const Icon(Icons.access_time),
            title: const Text('Recent Activity'),
            trailing: _sortBy == 'last_activity' ? const Icon(Icons.check, color: Color(0xFF128C7E)) : null,
            onTap: () {
              setState(() => _sortBy = 'last_activity');
              Navigator.pop(ctx);
              _fetchContacts(isRefresh: true);
            },
          ),
          ListTile(
            leading: const Icon(Icons.sort_by_alpha),
            title: const Text('Name (A-Z)'),
            trailing: _sortBy == 'name' ? const Icon(Icons.check, color: Color(0xFF128C7E)) : null,
            onTap: () {
              setState(() => _sortBy = 'name');
              Navigator.pop(ctx);
              _fetchContacts(isRefresh: true);
            },
          ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  Future<void> _openChat(dynamic contact) async {
    final id = (contact['id'] as num?)?.toInt();
    if (id == null) return;

    try {
      final response = await context.read<ApiService>().getContact(id);
      if (!mounted) return;
      final data = response.data;
      final contactData = data['contact'] as Map<String, dynamic>?;
      final activeConversation = contactData?['active_conversation'] ?? contactData?['activeConversation'];
      
      if (activeConversation != null && activeConversation['id'] != null) {
        if (!mounted) return;
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => ChatScreen(
              conversationId: activeConversation['id'],
              contactName: contact['name'] ?? 'Unknown',
            ),
          ),
        );
      } else {
         if (!mounted) return;
         ScaffoldMessenger.of(context).showSnackBar(
           const SnackBar(content: Text('No active conversation found for this contact.'))
         );
      }
    } catch (e) {
       if (mounted) {
         ScaffoldMessenger.of(context).showSnackBar(
           SnackBar(content: Text('Failed to open chat: $e'))
         );
       }
    }
  }

  Widget _buildNoResultsView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.person_search_outlined, size: 80, color: Colors.grey[300]),
          const SizedBox(height: 16),
          Text(
            _searchQuery.isEmpty ? 'No Contacts Yet' : 'No matches for "$_searchQuery"',
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.grey),
          ),
          const SizedBox(height: 8),
          const Text('Try adjusting your search or sync contacts.', style: TextStyle(color: Colors.grey)),
        ],
      ),
    );
  }

  Color _parseColor(String? hex) {
    if (hex == null || hex.isEmpty) return Colors.grey;
    try {
      return Color(int.parse(hex.replaceFirst('#', '0xFF')));
    } catch (_) {
      return Colors.grey;
    }
  }
}

