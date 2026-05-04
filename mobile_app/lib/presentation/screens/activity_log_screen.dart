import 'dart:async';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../core/api_service.dart';

class ActivityLogScreen extends StatefulWidget {
  final ApiService apiService;
  const ActivityLogScreen({super.key, required this.apiService});

  @override
  State<ActivityLogScreen> createState() => _ActivityLogScreenState();
}

class _ActivityLogScreenState extends State<ActivityLogScreen> {
  final ScrollController _scrollController = ScrollController();
  List<dynamic> _activities = [];
  bool _isLoading = false;
  int _currentPage = 1;
  bool _hasNextPage = true;
  String _searchQuery = '';
  String? _selectedType;
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _fetchActivities();
    _scrollController.addListener(() {
      if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent * 0.8 && !_isLoading && _hasNextPage) {
        _fetchActivities(page: _currentPage + 1);
      }
    });
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchActivities({int page = 1, bool isRefresh = false}) async {
    if (_isLoading) return;
    setState(() => _isLoading = true);

    try {
      final response = await widget.apiService.getActivities(
        page: page,
        search: _searchQuery,
        type: _selectedType,
      );
      final newData = response.data['data'] ?? [];
      setState(() {
        if (page == 1 || isRefresh) {
          _activities = newData;
        } else {
          _activities.addAll(newData);
        }
        _currentPage = page;
        _hasNextPage = response.data['next_page_url'] != null;
        _isLoading = false;
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Failed to load logs: $e')));
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Activity Logs', style: TextStyle(fontWeight: FontWeight.bold)),
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            tooltip: 'Filter Logs',
            onPressed: _showFilterDialog,
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(60),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: TextField(
              onChanged: (v) {
                _debounce?.cancel();
                _debounce = Timer(const Duration(milliseconds: 500), () {
                  _searchQuery = v;
                  _fetchActivities(page: 1, isRefresh: true);
                });
              },
              decoration: InputDecoration(
                hintText: 'Search actions or team members...',
                prefixIcon: const Icon(Icons.search),
                filled: true,
                fillColor: Theme.of(context).cardColor,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                contentPadding: EdgeInsets.zero,
              ),
            ),
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () => _fetchActivities(page: 1, isRefresh: true),
        child: _activities.isEmpty && !_isLoading
            ? _buildEmptyState()
            : ListView.separated(
                controller: _scrollController,
                padding: const EdgeInsets.all(12),
                itemCount: _activities.length + (_hasNextPage ? 1 : 0),
                separatorBuilder: (ctx, i) => const SizedBox(height: 8),
                itemBuilder: (ctx, index) {
                  if (index == _activities.length) {
                    return const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator()));
                  }
                  return _buildActivityCard(_activities[index]);
                },
              ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.history, size: 80, color: Colors.grey[300]),
          const SizedBox(height: 16),
          const Text('No Activities Found', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.grey)),
          const SizedBox(height: 12),
          TextButton.icon(
            icon: const Icon(Icons.refresh),
            label: const Text('Retry'),
            onPressed: () => _fetchActivities(page: 1, isRefresh: true),
          ),
        ],
      ),
    );
  }

  Widget _buildActivityCard(dynamic item) {
    final date = DateTime.parse(item['created_at']);
    final userName = item['user']?['name'] ?? 'System';
    final desc = item['description'] ?? 'Generic action';
    
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: Colors.grey.withValues(alpha: 0.1)),
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.all(12),
        leading: CircleAvatar(
          backgroundColor: _getIconColor(desc).withValues(alpha: 0.1),
          child: Icon(_getIcon(desc), color: _getIconColor(desc), size: 20),
        ),
        title: Text(desc, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 4),
            Row(
              children: [
                const Icon(Icons.person, size: 12, color: Colors.grey),
                const SizedBox(width: 4),
                Text(userName, style: const TextStyle(fontSize: 12, color: Colors.grey)),
              ],
            ),
            const SizedBox(height: 2),
            Row(
              children: [
                const Icon(Icons.access_time, size: 12, color: Colors.grey),
                const SizedBox(width: 4),
                Text(DateFormat('MMM dd, hh:mm a').format(date), style: const TextStyle(fontSize: 11, color: Colors.grey)),
              ],
            ),
          ],
        ),
        onTap: () => _showActivityDetails(item),
      ),
    );
  }

  IconData _getIcon(String desc) {
    desc = desc.toLowerCase();
    if (desc.contains('message')) return Icons.chat_bubble_outline;
    if (desc.contains('tag')) return Icons.local_offer_outlined;
    if (desc.contains('ai')) return Icons.smart_toy_outlined;
    if (desc.contains('automation')) return Icons.bolt;
    if (desc.contains('login')) return Icons.login;
    return Icons.info_outline;
  }

  Color _getIconColor(String desc) {
    desc = desc.toLowerCase();
    if (desc.contains('message')) return const Color(0xFF128C7E);
    if (desc.contains('ai')) return Colors.teal;
    if (desc.contains('tag')) return Colors.orange;
    if (desc.contains('automation')) return Colors.purple;
    return Colors.blue;
  }

  void _showFilterDialog() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Padding(
            padding: EdgeInsets.all(16),
            child: Text('Filter by Action', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
          ),
          ListTile(title: const Text('All Actions'), leading: const Icon(Icons.clear_all), onTap: () => _applyType(null)),
          ListTile(title: const Text('Messages'), leading: const Icon(Icons.chat), onTap: () => _applyType('message')),
          ListTile(title: const Text('AI Replies'), leading: const Icon(Icons.smart_toy), onTap: () => _applyType('ai')),
          ListTile(title: const Text('Tags'), leading: const Icon(Icons.local_offer), onTap: () => _applyType('tag')),
          ListTile(title: const Text('Automations'), leading: const Icon(Icons.bolt), onTap: () => _applyType('automation')),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  void _applyType(String? type) {
    Navigator.pop(context);
    setState(() {
      _selectedType = type;
    });
    _fetchActivities(page: 1, isRefresh: true);
  }

  void _showActivityDetails(dynamic item) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Activity Detail'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _detailRow('Action', item['description']),
            _detailRow('Actor', item['user']?['name'] ?? 'System'),
            _detailRow('Time', DateFormat('yyyy-MM-dd HH:mm:ss').format(DateTime.parse(item['created_at']))),
            if (item['properties'] != null) ...[
              const Divider(),
              const Text('Properties:', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Container(
                constraints: const BoxConstraints(maxHeight: 150),
                child: SingleChildScrollView(
                  child: Text(item['properties'].toString(), style: const TextStyle(fontSize: 12, fontFamily: 'monospace')),
                ),
              ),
            ],
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('CLOSE')),
        ],
      ),
    );
  }

  Widget _detailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey, fontSize: 12)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }
}
