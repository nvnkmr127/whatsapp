import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../logic/blocs/auth_bloc.dart';
import '../../core/api_service.dart';
import 'automation_form_screen.dart';

class AutomationListScreen extends StatefulWidget {
  final ApiService apiService;
  const AutomationListScreen({super.key, required this.apiService});

  @override
  State<AutomationListScreen> createState() => _AutomationListScreenState();
}

class _AutomationListScreenState extends State<AutomationListScreen> {
  List<dynamic> _automations = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchAutomations();
  }

  Future<void> _fetchAutomations() async {
    setState(() => _isLoading = true);
    try {
      final response = await widget.apiService.getAutomations();
      setState(() {
        _automations = response.data;
        _isLoading = false;
      });
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _automations = [];
        });
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  Future<void> _toggleAutomation(int id) async {
    try {
      await widget.apiService.toggleAutomation(id);
      _fetchAutomations();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Failed to toggle: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        if (state is Authenticated) {
          _fetchAutomations();
        }
      },
      child: Scaffold(
      appBar: AppBar(
        title: const Text('Automation Rules', style: TextStyle(fontWeight: FontWeight.bold)),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh), 
            tooltip: 'Refresh Automations',
            onPressed: _fetchAutomations
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _automations.isEmpty
              ? _buildEmptyState()
              : RefreshIndicator(
                  onRefresh: _fetchAutomations,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(12),
                    itemCount: _automations.length,
                    itemBuilder: (context, index) {
                      final item = _automations[index];
                      return _buildAutomationCard(item);
                    },
                  ),
                ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _navigateToForm(null),
        child: const Icon(Icons.add),
      ),
    ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.auto_awesome, size: 80, color: Colors.grey[300]),
          const SizedBox(height: 16),
          Text('No Automations Yet', style: TextStyle(color: Colors.grey[600], fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          const Text('Automate replies, tagging, and more.', style: TextStyle(color: Colors.grey)),
          const SizedBox(height: 16),
          TextButton(onPressed: _fetchAutomations, child: const Text('RETRY')),
        ],
      ),
    );
  }

  Widget _buildAutomationCard(dynamic item) {
    final bool isActive = item['is_active'] ?? false;
    final int runs = item['runs_count'] ?? 0;

    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Colors.grey.withValues(alpha: 0.2)),
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        leading: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: (isActive ? const Color(0xFF128C7E) : Colors.grey).withValues(alpha: 0.1),
            shape: BoxShape.circle,
          ),
          child: Icon(
            _getTriggerIcon(item['trigger_type']),
            color: isActive ? const Color(0xFF128C7E) : Colors.grey,
          ),
        ),
        title: Text(item['name'] ?? 'Untitled Rule', style: const TextStyle(fontWeight: FontWeight.bold)),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 4),
            Text('Trigger: ${item['trigger_type']}', style: TextStyle(fontSize: 12, color: Colors.grey[600])),
            Text('Runs: $runs', style: TextStyle(fontSize: 11, color: Colors.grey[500])),
          ],
        ),
        trailing: Switch(
          value: isActive,
          onChanged: (_) => _toggleAutomation(item['id']),
          activeThumbColor: const Color(0xFF128C7E),
        ),
        onTap: () => _navigateToForm(item),
      ),
    );
  }

  IconData _getTriggerIcon(String? type) {
    switch (type) {
      case 'message_received': return Icons.message;
      case 'contact_tagged': return Icons.local_offer;
      case 'out_of_office': return Icons.schedule;
      default: return Icons.bolt;
    }
  }

  Future<void> _navigateToForm(dynamic item) async {
    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (ctx) => AutomationFormScreen(
          apiService: widget.apiService,
          initialData: item,
        ),
      ),
    );
    if (result == true) _fetchAutomations();
  }
}
