import 'package:flutter/material.dart';
import '../../core/api_service.dart';

class CannedMessageListScreen extends StatefulWidget {
  final ApiService apiService;
  const CannedMessageListScreen({super.key, required this.apiService});

  @override
  State<CannedMessageListScreen> createState() => _CannedMessageListScreenState();
}

class _CannedMessageListScreenState extends State<CannedMessageListScreen> {
  List<dynamic> _messages = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchMessages();
  }

  Future<void> _fetchMessages() async {
    setState(() => _isLoading = true);
    try {
      final response = await widget.apiService.getCannedMessages();
      setState(() {
        _messages = response.data;
        _isLoading = false;
      });
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Canned Replies', style: TextStyle(fontWeight: FontWeight.bold)),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _fetchMessages),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _messages.isEmpty
              ? _buildEmptyState()
              : ListView.builder(
                  padding: const EdgeInsets.all(12),
                  itemCount: _messages.length,
                  itemBuilder: (context, index) {
                    final item = _messages[index];
                    return Card(
                      elevation: 0,
                      margin: const EdgeInsets.only(bottom: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                        side: BorderSide(color: Colors.grey.withValues(alpha: 0.1)),
                      ),
                      child: ListTile(
                        leading: const CircleAvatar(
                          backgroundColor: Color(0xFF128C7E),
                          child: Icon(Icons.flash_on, color: Colors.white, size: 20),
                        ),
                        title: Text(item['shortcut'] ?? '/reply', style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF128C7E))),
                        subtitle: Text(item['message'] ?? '', maxLines: 2, overflow: TextOverflow.ellipsis),
                        onTap: () => _showDetail(item),
                      ),
                    );
                  },
                ),
      floatingActionButton: FloatingActionButton(
        heroTag: 'canned_message_list_fab',
        backgroundColor: const Color(0xFF128C7E),
        onPressed: () {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Please use the web dashboard to manage canned replies.')),
          );
        },
        child: const Icon(Icons.add, color: Colors.white),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.flash_off, size: 80, color: Colors.grey[300]),
          const SizedBox(height: 16),
          const Text('No Canned Replies', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.grey)),
          const SizedBox(height: 8),
          const Text('Create shortcuts for common responses on the web.', style: TextStyle(color: Colors.grey)),
        ],
      ),
    );
  }

  void _showDetail(dynamic item) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(item['shortcut'] ?? 'Canned Reply'),
        content: Text(item['message'] ?? ''),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('CLOSE')),
        ],
      ),
    );
  }
}
