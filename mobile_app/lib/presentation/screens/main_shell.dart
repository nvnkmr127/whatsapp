import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../logic/blocs/auth_bloc.dart';
import 'inbox_screen.dart';
import 'contact_picker_screen.dart';
import 'template_list_screen.dart';
import 'management_screen.dart';
import 'profile_screen.dart';
import 'chat_screen.dart';
import '../../core/notification_service.dart';
import '../widgets/notification_banner.dart';
import '../../logic/blocs/chat_bloc.dart';
import '../widgets/offline_banner.dart';
import 'dart:async';

class MainShell extends StatefulWidget {
  const MainShell({super.key});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _currentIndex = 0; // Default to Inbox

  final List<Widget> _screens = [];

  StreamSubscription? _navSub;

  @override
  void initState() {
    super.initState();
    _navSub = NotificationService.navigationStream.listen((data) async {
      if (!mounted) return;

      final convId = data['conversation_id'];
      final targetTeamId = data['team_id'];
      
      final authState = context.read<AuthBloc>().state;
      if (authState is Authenticated && targetTeamId != null) {
        context.read<AuthBloc>().add(TenantSelected(targetTeamId));
        await Future.delayed(const Duration(milliseconds: 300));
        if (!mounted) return;
      }

      if (mounted) {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => ChatScreen(
              conversationId: convId,
              contactName: 'Notification Chat',
            ),
          ),
        );
      }
    });

    context.read<ChatBloc>().notificationStream.listen((payload) {
      if (!mounted) return;
      final message = payload['message'];
      if (message == null) return;
      
      final convId = message['conversation_id'];
      final direction = message['direction'];
      
      if (direction == 'inbound' && convId != ChatScreen.activeConversationId) {
        final contactName = payload['contact']?['name'] ?? payload['contact']?['phone'] ?? 'New Message';
        final content = message['content'] ?? 'Sent a message';
        
        NotificationBanner.show(
          context,
          title: contactName,
          body: content,
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => ChatScreen(
                  conversationId: convId,
                  contactName: contactName,
                ),
              ),
            );
          },
        );
      }
    });
  }

  @override
  void dispose() {
    _navSub?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final authState = context.watch<AuthBloc>().state;
    final bool isAdmin = authState is Authenticated && (authState.user?['role'] == 'admin' || authState.user?['role'] == 'owner' || authState.user?['role'] == 'manager');

    if (_screens.isEmpty) {
      _screens.addAll([
        const InboxScreen(),
        const ContactPickerScreen(),
        if (isAdmin) const ManagementScreen(),
        const TemplateListScreen(),
        const ProfileScreen(),
      ]);
    }

    return OfflineBanner(
      child: Scaffold(
        body: IndexedStack(
          index: _currentIndex,
          children: _screens,
        ),
        bottomNavigationBar: BottomNavigationBar(
          currentIndex: _currentIndex,
          onTap: (index) {
            setState(() => _currentIndex = index);
          },
          type: BottomNavigationBarType.fixed,
          selectedItemColor: const Color(0xFF128C7E),
          unselectedItemColor: Colors.grey,
          showUnselectedLabels: true,
          items: [
            const BottomNavigationBarItem(
              icon: Icon(Icons.inbox_outlined),
              activeIcon: Icon(Icons.inbox),
              label: 'Inbox',
            ),
            const BottomNavigationBarItem(
              icon: Icon(Icons.people_outline),
              activeIcon: Icon(Icons.people),
              label: 'Contacts',
            ),
            if (isAdmin)
              const BottomNavigationBarItem(
                icon: Icon(Icons.admin_panel_settings_outlined),
                activeIcon: Icon(Icons.admin_panel_settings),
                label: 'Manage',
              ),
            const BottomNavigationBarItem(
              icon: Icon(Icons.description_outlined),
              activeIcon: Icon(Icons.description),
              label: 'Templates',
            ),
            const BottomNavigationBarItem(
              icon: Icon(Icons.settings_outlined),
              activeIcon: Icon(Icons.settings),
              label: 'Settings',
            ),
          ],
        ),
      ),
    );
  }
}
