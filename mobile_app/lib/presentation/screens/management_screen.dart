import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../logic/blocs/auth_bloc.dart';
import '../../core/api_service.dart';
import 'bot_list_screen.dart';
import 'template_list_screen.dart';
import 'automation_list_screen.dart';
import 'activity_log_screen.dart';
import 'canned_message_list_screen.dart';
import 'analytics_screen.dart';
import 'broadcasting_screen.dart';

class ManagementScreen extends StatelessWidget {
  const ManagementScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final authState = context.watch<AuthBloc>().state;
    final bool isAdmin = authState is Authenticated && 
        (authState.user?['role'] == 'admin' || authState.user?['role'] == 'owner' || authState.user?['role'] == 'manager');

    if (!isAdmin) {
      return _buildAccessDenied();
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF0F2F5),
      appBar: AppBar(
        title: const Text('Admin Console', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFF128C7E),
        foregroundColor: Colors.white,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildSectionHeader('Automations & AI'),
          _buildToolTile(
            context,
            icon: Icons.smart_toy_outlined,
            title: 'Message Bots',
            subtitle: 'Manage keyword responders & presets',
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const BotListScreen())),
          ),
          _buildToolTile(
            context,
            icon: Icons.auto_awesome_outlined,
            title: 'Automation Rules',
            subtitle: 'Auto-replies, tagging & assignments',
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => AutomationListScreen(apiService: context.read<ApiService>()))),
          ),
          _buildToolTile(
            context,
            icon: Icons.flash_on_outlined,
            title: 'Canned Replies',
            subtitle: 'Quick shortcuts for agent replies',
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => CannedMessageListScreen(apiService: context.read<ApiService>()))),
          ),
          
          const SizedBox(height: 16),
          _buildSectionHeader('Marketing & Content'),
          _buildToolTile(
            context,
            icon: Icons.description_outlined,
            title: 'WhatsApp Templates',
            subtitle: 'Draft and test Meta business templates',
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const TemplateListScreen())),
          ),
          _buildToolTile(
            context,
            icon: Icons.campaign_outlined,
            title: 'Broadcasting',
            subtitle: 'Manage bulk message campaigns',
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => BroadcastingScreen(apiService: context.read<ApiService>()))),
          ),
          
          const SizedBox(height: 16),
          _buildSectionHeader('Analytics & Logs'),
          _buildToolTile(
            context,
            icon: Icons.bar_chart_outlined,
            title: 'Insight Analytics',
            subtitle: 'View performance and team metrics',
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => AnalyticsScreen(apiService: context.read<ApiService>()))),
          ),
          _buildToolTile(
            context,
            icon: Icons.history_outlined,
            title: 'Activity Logs',
            subtitle: 'Audit trail of all administrative actions',
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ActivityLogScreen(apiService: context.read<ApiService>()))),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 4, bottom: 8),
      child: Text(
        title.toUpperCase(),
        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey, letterSpacing: 1.2),
      ),
    );
  }

  Widget _buildToolTile(BuildContext context, {required IconData icon, required String title, required String subtitle, required VoidCallback onTap}) {
    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Colors.grey.withValues(alpha: 0.1)),
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        leading: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: const Color(0xFF128C7E).withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: const Color(0xFF128C7E)),
        ),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        subtitle: Text(subtitle, style: TextStyle(color: Colors.grey[600], fontSize: 13)),
        trailing: const Icon(Icons.chevron_right, color: Colors.grey),
        onTap: onTap,
      ),
    );
  }

  Widget _buildAccessDenied() {
    return Scaffold(
      appBar: AppBar(title: const Text('Admin Console')),
      body: const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.lock_outline, size: 64, color: Colors.grey),
            SizedBox(height: 16),
            Text('Admin Access Required', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            SizedBox(height: 8),
            Text('You do not have permission to access these tools.', style: TextStyle(color: Colors.grey)),
          ],
        ),
      ),
    );
  }
}
