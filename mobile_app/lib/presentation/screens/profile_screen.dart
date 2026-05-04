import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../logic/blocs/auth_bloc.dart';
import '../../logic/blocs/settings_bloc.dart';
import '../../core/api_service.dart';
import '../widgets/chat_avatar.dart';
import '../screens/analytics_screen.dart';
import '../screens/broadcasting_screen.dart';
import '../screens/automation_list_screen.dart';
import '../screens/activity_log_screen.dart';
import '../screens/ai_settings_screen.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Agent Profile', style: TextStyle(fontWeight: FontWeight.bold)),
      ),
      body: BlocBuilder<AuthBloc, AuthState>(
        builder: (context, state) {
          if (state is Authenticated) {
            final user = state.user;
            final name = user?['name'] ?? 'Active Agent';
            final phone = user?['phone'] ?? '';

            return ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // 1. Profile Header
                Container(
                  alignment: Alignment.center,
                  child: Column(
                    children: [
                      state.businessProfile?['profile_picture_url'] != null && state.businessProfile!['profile_picture_url'].isNotEmpty
                          ? CircleAvatar(
                              radius: 50,
                              backgroundImage: NetworkImage(state.businessProfile!['profile_picture_url']),
                            )
                          : ChatAvatar(size: 100, name: name),
                      const SizedBox(height: 15),
                      Text(
                        name,
                        style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                      ),
                      if (phone.isNotEmpty)
                        Text(
                          phone,
                          style: const TextStyle(color: Colors.grey),
                        ),
                      if (state.businessProfile?['description'] != null)
                        Padding(
                          padding: const EdgeInsets.only(top: 8.0),
                          child: Text(
                            state.businessProfile!['description'],
                            style: const TextStyle(color: Colors.grey, fontSize: 13),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      if (state.businessProfile?['about'] != null)
                        Padding(
                          padding: const EdgeInsets.only(top: 4.0),
                          child: Text(
                            state.businessProfile!['about'],
                            style: const TextStyle(color: Colors.grey, fontSize: 12, fontStyle: FontStyle.italic),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      const SizedBox(height: 10),
                      TextButton.icon(
                        icon: const Icon(Icons.edit, size: 16, color: Color(0xFF128C7E)),
                        label: const Text('Edit Profile', style: TextStyle(color: Color(0xFF128C7E))),
                        onPressed: () => _showEditProfileDialog(context, name),
                      ),
                    ],
                  ),
                ),
                // 2. Business Details (From WhatsApp API)
                if (state.businessProfile != null) ...[
                  _buildSectionHeader('Business Profile'),
                  Card(
                    elevation: 0, color: Theme.of(context).cardColor,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    child: Column(
                      children: [
                        if (state.businessProfile?['email'] != null && state.businessProfile!['email'].isNotEmpty)
                          _buildInfoTile(Icons.email_outlined, 'Email', state.businessProfile!['email']),
                        if (state.businessProfile?['email'] != null && state.businessProfile!['email'].isNotEmpty)
                          const Divider(height: 1, indent: 50),
                        
                        if (state.businessProfile?['address'] != null && state.businessProfile!['address'].isNotEmpty)
                          _buildInfoTile(Icons.location_on_outlined, 'Address', state.businessProfile!['address']),
                        if (state.businessProfile?['address'] != null && state.businessProfile!['address'].isNotEmpty)
                          const Divider(height: 1, indent: 50),
                          
                        if (state.businessProfile?['vertical'] != null && state.businessProfile!['vertical'].isNotEmpty)
                          _buildInfoTile(Icons.category_outlined, 'Industry', state.businessProfile!['vertical']),
                        if (state.businessProfile?['vertical'] != null && state.businessProfile!['vertical'].isNotEmpty)
                          const Divider(height: 1, indent: 50),
                          
                        if (state.businessProfile?['websites'] != null && (state.businessProfile!['websites'] as List).isNotEmpty)
                          _buildInfoTile(Icons.language_outlined, 'Website', (state.businessProfile!['websites'] as List).first.toString()),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),
                ],
                
                // 3. Business Management
                _buildSectionHeader('Business Management'),
                Card(
                  elevation: 0, color: Theme.of(context).cardColor,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: Column(
                    children: [
                      ListTile(
                        leading: const Icon(Icons.phone_android, color: Color(0xFF128C7E)),
                        title: const Text('WhatsApp Numbers', style: TextStyle(fontWeight: FontWeight.w500)),
                        subtitle: Text(state.activeNumberId != null ? 'Active: ${state.whatsappNumbers.firstWhere((n) => n['id'].toString() == state.activeNumberId, orElse: () => {'verified_name': 'Default'})['verified_name'] ?? state.whatsappNumbers.firstWhere((n) => n['id'].toString() == state.activeNumberId, orElse: () => {'name': 'Default'})['name']}' : 'Select Number'),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () => _showNumberSelector(context, state),
                      ),
                      const Divider(height: 1, indent: 50),
                      ListTile(
                        leading: const Icon(Icons.business, color: Color(0xFF128C7E)),
                        title: const Text('Team Settings', style: TextStyle(fontWeight: FontWeight.w500)),
                        subtitle: Text('Current: ${state.teams.firstWhere((t) => t['id'].toString() == context.read<ApiService>().currentTenantId, orElse: () => {'name': 'Default'})['name']}'),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () => _showTeamSwitcher(context, state),
                      ),
                      const Divider(height: 1, indent: 50),
                      ListTile(
                        leading: const Icon(Icons.auto_awesome, color: Color(0xFF128C7E)),
                        title: const Text('Automation Rules', style: TextStyle(fontWeight: FontWeight.w500)),
                        subtitle: const Text('Auto-replies, assignments & tags'),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () {
                          Navigator.push(context, MaterialPageRoute(builder: (ctx) => AutomationListScreen(apiService: context.read<ApiService>())));
                        },
                      ),
                      const Divider(height: 1, indent: 50),
                      ListTile(
                        leading: const Icon(Icons.history, color: Color(0xFF128C7E)),
                        title: const Text('Activity Logs', style: TextStyle(fontWeight: FontWeight.w500)),
                        subtitle: const Text('Audit trail of all team actions'),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () {
                          Navigator.push(context, MaterialPageRoute(builder: (ctx) => ActivityLogScreen(apiService: context.read<ApiService>())));
                        },
                      ),
                      const Divider(height: 1, indent: 50),
                      ListTile(
                        leading: const Icon(Icons.smart_toy_outlined, color: Color(0xFF128C7E)),
                        title: const Text('AI Assistant', style: TextStyle(fontWeight: FontWeight.w500)),
                        subtitle: const Text('Configure AI auto-reply settings'),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () {
                          Navigator.push(context, MaterialPageRoute(builder: (ctx) => const AiSettingsScreen()));
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),

                // 3. Analytics & Tools
                _buildSectionHeader('Analytics & Tools'),
                Card(
                  elevation: 0, color: Theme.of(context).cardColor,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: Column(
                    children: [
                      ListTile(
                        leading: const Icon(Icons.bar_chart, color: Color(0xFF128C7E)),
                        title: const Text('Insight Analytics', style: TextStyle(fontWeight: FontWeight.w500)),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () {
                          Navigator.push(context, MaterialPageRoute(builder: (ctx) => AnalyticsScreen(apiService: context.read<ApiService>())));
                        },
                      ),
                      const Divider(height: 1, indent: 50),
                      ListTile(
                        leading: const Icon(Icons.campaign, color: Color(0xFF128C7E)),
                        title: const Text('Broadcasting', style: TextStyle(fontWeight: FontWeight.w500)),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () {
                          Navigator.push(context, MaterialPageRoute(builder: (ctx) => BroadcastingScreen(apiService: context.read<ApiService>())));
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),

                // 4. Preferences
                _buildSectionHeader('Preferences'),
                Card(
                  elevation: 0, color: Theme.of(context).cardColor,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: BlocBuilder<SettingsBloc, SettingsState>(
                    builder: (context, settings) {
                      return Column(
                        children: [
                          ListTile(
                            leading: const Icon(Icons.dark_mode_outlined),
                            title: const Text('Dark Mode', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w500)),
                            trailing: Switch(
                              value: settings.themeMode == ThemeMode.dark,
                              onChanged: (_) => context.read<SettingsBloc>().add(ToggleTheme()),
                              activeThumbColor: const Color(0xFF128C7E),
                            ),
                          ),
                          const Divider(height: 1, indent: 50),
                          ListTile(
                            leading: const Icon(Icons.notifications_outlined),
                            title: const Text('Push Notifications', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w500)),
                            trailing: const Text('ACTIVE', style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 12)),
                          ),
                          const Divider(height: 1, indent: 50),
                          ListTile(
                            leading: const Icon(Icons.security_outlined),
                            title: const Text('Privacy & Security', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w500)),
                            trailing: const Icon(Icons.chevron_right),
                            onTap: () {
                               showAboutDialog(
                                 context: context,
                                 applicationName: 'Watxio',
                                 applicationVersion: '1.0.0',
                                 applicationLegalese: 'Enterprise Grade Messaging Security Enabled.',
                               );
                            },
                          ),
                        ],
                      );
                    }
                  ),
                ),
                const SizedBox(height: 40),

                // 5. Logout Button
                ElevatedButton.icon(
                  onPressed: () {
                    showDialog(
                      context: context,
                      builder: (ctx) => AlertDialog(
                        title: const Text('Logout'),
                        content: const Text('Are you sure you want to logout?'),
                        actions: [
                          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('CANCEL')),
                          TextButton(
                            onPressed: () {
                              context.read<AuthBloc>().add(LogoutRequested());
                              Navigator.pop(ctx);
                              Navigator.popUntil(context, (route) => route.isFirst);
                            }, 
                            child: const Text('LOGOUT', style: TextStyle(color: Colors.red))
                          ),
                        ],
                      ),
                    );
                  },
                  icon: const Icon(Icons.logout),
                  label: const Text('Logout Session'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.red.withValues(alpha: 0.1),
                    foregroundColor: Colors.red,
                    padding: const EdgeInsets.symmetric(vertical: 15),
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
                const SizedBox(height: 20),
                const Center(child: Text('Version 1.0.0 (Isar Cached)', style: TextStyle(color: Colors.grey, fontSize: 10))),
                const SizedBox(height: 40),
              ],
            );
          }
          return const Center(child: Text('Please log in again.'));
        },
      ),
    );
  }

  void _showNumberSelector(BuildContext context, Authenticated state) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 20),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('Select WhatsApp Number', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 10),
              ...state.whatsappNumbers.map((n) => ListTile(
                leading: Icon(Icons.phone_android, color: n['id'].toString() == state.activeNumberId ? const Color(0xFF128C7E) : Colors.grey),
                title: Text(n['verified_name'] ?? n['name'] ?? 'Unknown'),
                subtitle: Text(n['display_number'] ?? n['phone'] ?? ''),
                trailing: n['id'].toString() == state.activeNumberId ? const Icon(Icons.check_circle, color: Color(0xFF128C7E)) : null,
                onTap: () {
                  context.read<AuthBloc>().add(WhatsAppNumberSelected(n['id'].toString()));
                  Navigator.pop(ctx);
                },
              )),
            ],
          ),
        ),
      ),
    );
  }

  void _showTeamSwitcher(BuildContext context, Authenticated state) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 20),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('Switch Team Workspace', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 10),
              ...state.teams.map((t) => ListTile(
                leading: const Icon(Icons.business),
                title: Text(t['name'] ?? 'Unknown'),
                trailing: t['id'].toString() == context.read<ApiService>().currentTenantId ? const Icon(Icons.check_circle, color: Color(0xFF128C7E)) : null,
                onTap: () {
                  context.read<AuthBloc>().add(TenantSelected(t['id']));
                  Navigator.pop(ctx);
                },
              )),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 8, bottom: 8, top: 16),
      child: Text(
        title.toUpperCase(),
        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey, letterSpacing: 1.2),
      ),
    );
  }

  Widget _buildInfoTile(IconData icon, String title, String value, {Color? color}) {
    return ListTile(
      leading: Icon(icon, color: color ?? Colors.black87),
      title: Text(title, style: const TextStyle(fontSize: 13, color: Colors.grey)),
      subtitle: Text(value, style: TextStyle(color: color ?? Colors.black87, fontWeight: FontWeight.w600, fontSize: 15)),
    );
  }

  void _showEditProfileDialog(BuildContext context, String currentName) {
    final controller = TextEditingController(text: currentName);
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Edit Profile'),
        content: TextField(
          controller: controller,
          decoration: const InputDecoration(labelText: 'Display Name'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('CANCEL')),
          TextButton(
            onPressed: () async {
              try {
                await context.read<ApiService>().updateProfile({'name': controller.text});
                if (ctx.mounted) {
                  Navigator.pop(ctx);
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profile updated!')));
                  context.read<AuthBloc>().add(AppStarted()); // Refresh user data
                }
              } catch (e) {
                if (ctx.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
                }
              }
            },
            child: const Text('SAVE'),
          ),
        ],
      ),
    );
  }
}
