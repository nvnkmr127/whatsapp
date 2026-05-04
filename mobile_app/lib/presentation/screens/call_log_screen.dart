import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../logic/blocs/auth_bloc.dart';
import '../../core/api_service.dart';
import '../widgets/chat_avatar.dart';
import 'package:intl/intl.dart';
import 'call_screen.dart';

class CallLogScreen extends StatefulWidget {
  final ApiService apiService;
  const CallLogScreen({super.key, required this.apiService});

  @override
  State<CallLogScreen> createState() => _CallLogScreenState();
}

class _CallLogScreenState extends State<CallLogScreen> {
  List<dynamic> _calls = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _fetch();
  }

  Future<void> _fetch() async {
    if (mounted) setState(() => _loading = true);
    try {
      final response = await widget.apiService.getCalls();
      if (mounted) {
        setState(() {
          final data = response.data;
          _calls = data is Map ? (data['data'] ?? []) : (data is List ? data : []);
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _loading = false;
          _calls = [];
        });
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Failed to load call logs: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        if (state is Authenticated) {
          _fetch();
        }
      },
      child: _buildContent(),
    );
  }

  Widget _buildContent() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_calls.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.add_ic_call_outlined, size: 80, color: Colors.grey[300]),
            const SizedBox(height: 20),
            Text('No recent calls', style: TextStyle(color: Colors.grey[600], fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            TextButton(onPressed: _fetch, child: const Text('RETRY')),
          ],
        ),
      );
    }

    return ListView.builder(
      itemCount: _calls.length,
      itemBuilder: (context, index) {
        final call = _calls[index];
        final bool isMissed = call['status'] == 'missed';
        final bool isOutgoing = call['direction'] == 'outgoing';
        final date = DateTime.tryParse((call['created_at'] ?? call['initiated_at'] ?? '').toString()) ?? DateTime.now();

        final contact = call['contact'];
        final contactName = (contact is Map ? contact['name'] : null) ?? call['contact_name'] ?? 'Unknown';

        return ListTile(
          leading: ChatAvatar(name: contactName.toString()),
          title: Text(
            contactName.toString(),
            style: TextStyle(fontWeight: FontWeight.bold, color: isMissed ? Colors.red : null),
          ),
          subtitle: Row(
            children: [
              Icon(
                isOutgoing ? Icons.call_made : Icons.call_received, 
                size: 14, 
                color: isMissed ? Colors.red : Colors.green
              ),
              const SizedBox(width: 4),
              Text(DateFormat('MMM dd, HH:mm').format(date)),
            ],
          ),
          trailing: IconButton(
            icon: const Icon(Icons.call, color: Color(0xFF075E54)),
            tooltip: 'Call Back',
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => CallScreen(name: contactName.toString()),
                ),
              );
            },
          ),
        );
      },
    );
  }
}
