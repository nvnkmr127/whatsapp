import 'package:flutter/material.dart';
import '../../core/api_service.dart';
import '../widgets/chat_avatar.dart';

class ForwardMessageScreen extends StatefulWidget {
  final dynamic message;
  final ApiService apiService;
  const ForwardMessageScreen({super.key, required this.message, required this.apiService});

  @override
  State<ForwardMessageScreen> createState() => _ForwardMessageScreenState();
}

class _ForwardMessageScreenState extends State<ForwardMessageScreen> {
  List<dynamic> _contacts = [];
  bool _isLoading = true;
  String _searchQuery = '';
  final Set<int> _selectedContactIds = {};

  @override
  void initState() {
    super.initState();
    _fetch();
  }

  Future<void> _fetch() async {
    setState(() => _isLoading = true);
    try {
      final response = await widget.apiService.getContacts(search: _searchQuery);
      setState(() {
        _contacts = response.data['data'] ?? [];
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _forward() async {
    if (_selectedContactIds.isEmpty) return;
    
    setState(() => _isLoading = true);
    try {
      for (final id in _selectedContactIds) {
        // Find existing conversation for this contact
        final contact = _contacts.firstWhere((c) => c['id'] == id);
        final conversationId = contact['conversation_id'];
        if (conversationId != null) {
          await widget.apiService.sendMessage(conversationId, {
            'content': widget.message.content,
            'type': widget.message.type,
            'is_forwarded': true,
          });
        }
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Message forwarded!')));
        Navigator.pop(context);
      }
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Forward to...'),
        backgroundColor: const Color(0xFF128C7E),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: TextField(
              decoration: const InputDecoration(hintText: 'Search contacts...', prefixIcon: Icon(Icons.search)),
              onChanged: (val) {
                _searchQuery = val;
                _fetch();
              },
            ),
          ),
          Expanded(
            child: _isLoading 
              ? const Center(child: CircularProgressIndicator())
              : ListView.builder(
                  itemCount: _contacts.length,
                  itemBuilder: (context, index) {
                    final c = _contacts[index];
                    final isSelected = _selectedContactIds.contains(c['id']);
                    return CheckboxListTile(
                      value: isSelected,
                      title: Text(c['name'] ?? 'Unknown'),
                      subtitle: Text(c['phone_number'] ?? ''),
                      secondary: ChatAvatar(name: c['name']),
                      onChanged: (val) {
                        setState(() {
                          if (val!) {
                            _selectedContactIds.add(c['id']);
                          } else {
                            _selectedContactIds.remove(c['id']);
                          }
                        });
                      },
                    );
                  },
                ),
          ),
        ],
      ),
      floatingActionButton: _selectedContactIds.isNotEmpty
        ? FloatingActionButton(
            onPressed: _forward,
            backgroundColor: const Color(0xFF25D366),
            child: const Icon(Icons.send, color: Colors.white),
          )
        : null,
    );
  }
}
