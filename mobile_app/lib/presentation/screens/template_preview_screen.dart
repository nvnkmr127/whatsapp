import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../core/api_service.dart';
import 'template_draft_screen.dart';
import '../../logic/blocs/auth_bloc.dart';

class TemplatePreviewScreen extends StatefulWidget {
  final int templateId;
  final String templateName;

  const TemplatePreviewScreen({
    super.key,
    required this.templateId,
    required this.templateName,
  });

  @override
  State<TemplatePreviewScreen> createState() => _TemplatePreviewScreenState();
}

class _TemplatePreviewScreenState extends State<TemplatePreviewScreen> {
  Map<String, dynamic>? _template;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchDetails();
  }

  Future<void> _fetchDetails() async {
    try {
      final response = await context.read<ApiService>().getTemplate(widget.templateId);
      setState(() {
        _template = response.data;
        _isLoading = false;
      });
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to load template: $e'))
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text(widget.templateName),
        backgroundColor: Theme.of(context).appBarTheme.backgroundColor ?? const Color(0xFF128C7E),
        foregroundColor: Theme.of(context).appBarTheme.foregroundColor ?? Colors.white,
        actions: [
          if (_template?['status'] == 'DRAFT') ...[
            IconButton(
              icon: const Icon(Icons.edit),
              onPressed: () async {
                final result = await Navigator.push(
                  context,
                  MaterialPageRoute(builder: (context) => TemplateDraftScreen(template: _template)),
                );
                if (result == true) _fetchDetails();
              },
            ),
            IconButton(
              icon: const Icon(Icons.delete_outline),
              onPressed: _showDeleteConfirm,
            ),
          ],
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _template == null 
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.error_outline, size: 60, color: Colors.grey),
                      const SizedBox(height: 16),
                      const Text('Could not load template details'),
                      TextButton(onPressed: _fetchDetails, child: const Text('RETRY')),
                    ],
                  ),
                )
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _buildPreviewCard(),
                      const SizedBox(height: 24),
                      _buildActions(),
                    ],
                  ),
                ),
    );
  }

  Widget _buildPreviewCard() {
    final components = (_template?['components'] as List?) ?? [];
    
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ...components.map((c) => _buildComponent(c)),
          ],
        ),
      ),
    );
  }

  Widget _buildComponent(Map<String, dynamic> component) {
    final type = component['type']?.toString().toUpperCase();
    final text = component['text']?.toString() ?? '';
    final format = component['format']?.toString().toUpperCase();

    switch (type) {
      case 'HEADER':
        if (format == 'TEXT') {
          return Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Text(text, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
          );
        } else if (['IMAGE', 'VIDEO', 'DOCUMENT'].contains(format)) {
          return Container(
            height: 150,
            width: double.infinity,
            margin: const EdgeInsets.only(bottom: 12),
            decoration: BoxDecoration(
              color: Theme.of(context).brightness == Brightness.dark ? Colors.grey[850] : Colors.grey[200],
              borderRadius: BorderRadius.circular(8),
            ),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(_getMediaIcon(format), size: 40, color: Colors.grey),
                const SizedBox(height: 8),
                Text('Media Header: $format', style: const TextStyle(color: Colors.grey)),
              ],
            ),
          );
        }
        return const SizedBox.shrink();

      case 'BODY':
        return Padding(
          padding: const EdgeInsets.symmetric(vertical: 8),
          child: Text(text, style: const TextStyle(fontSize: 15, height: 1.4)),
        );

      case 'FOOTER':
        return Padding(
          padding: const EdgeInsets.only(top: 8),
          child: Text(text, style: const TextStyle(fontSize: 12, color: Colors.grey)),
        );

      case 'BUTTONS':
        final buttons = (component['buttons'] as List?) ?? [];
        return Padding(
          padding: const EdgeInsets.only(top: 16),
          child: Column(
            children: buttons.map((b) => _buildButton(b)).toList(),
          ),
        );

      default:
        return const SizedBox.shrink();
    }
  }

  Widget _buildButton(Map<String, dynamic> button) {
    final type = button['type']?.toString().toUpperCase();
    final text = button['text']?.toString() ?? 'Button';
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 8),
      child: OutlinedButton(
        onPressed: () {},
        style: OutlinedButton.styleFrom(
          side: BorderSide(color: isDark ? Colors.white.withOpacity(0.15) : const Color(0xFFE9EDEF)),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
          padding: const EdgeInsets.symmetric(vertical: 12),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              _getButtonIcon(type),
              size: 18,
              color: isDark ? Theme.of(context).colorScheme.secondary : const Color(0xFF008069),
            ),
            const SizedBox(width: 8),
            Text(
              text,
              style: TextStyle(
                color: isDark ? Theme.of(context).colorScheme.secondary : const Color(0xFF008069),
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ),
    );
  }

  IconData _getMediaIcon(String? format) {
    switch (format) {
      case 'IMAGE': return Icons.image;
      case 'VIDEO': return Icons.videocam;
      case 'DOCUMENT': return Icons.description;
      default: return Icons.perm_media;
    }
  }

  IconData _getButtonIcon(String? type) {
    switch (type) {
      case 'QUICK_REPLY': return Icons.reply;
      case 'PHONE_NUMBER': return Icons.phone;
      case 'URL': return Icons.open_in_new;
      default: return Icons.touch_app;
    }
  }

  Widget _buildActions() {
    final authState = context.read<AuthBloc>().state;
    final bool isAdmin = authState is Authenticated && (authState.user?['role'] == 'admin' || authState.user?['role'] == 'owner');

    return Column(
      children: [
        if (isAdmin) ...[
          Card(
            elevation: 0,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            child: ListTile(
              title: const Text('Template Active'),
              subtitle: Text(!(_template?['is_paused'] ?? false) ? 'Ready to send' : 'Paused'),
              trailing: Switch(
                value: !(_template?['is_paused'] ?? false),
                onChanged: (val) => _toggleStatus(),
                activeColor: const Color(0xFF128C7E),
              ),
            ),
          ),
          const SizedBox(height: 16),
        ],
        ElevatedButton.icon(
          onPressed: _showSendTestDialog,
          icon: const Icon(Icons.send),
          label: const Text('Send Test Message'),
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF128C7E),
            foregroundColor: Colors.white,
            minimumSize: const Size(double.infinity, 50),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
        ),
        const SizedBox(height: 12),
        OutlinedButton.icon(
          onPressed: () => Navigator.pop(context, _template),
          icon: const Icon(Icons.check_circle_outline),
          label: const Text('Use in Conversation'),
          style: OutlinedButton.styleFrom(
            minimumSize: const Size(double.infinity, 50),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
        ),
      ],
    );
  }

  Future<void> _toggleStatus() async {
    try {
      await context.read<ApiService>().toggleTemplate(widget.templateId);
      setState(() {
        _template?['is_paused'] = !(_template?['is_paused'] ?? false);
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  void _showSendTestDialog() {
    final phoneController = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Send Test Message'),
        content: TextField(
          controller: phoneController,
          decoration: const InputDecoration(
            labelText: 'Phone Number',
            hintText: 'e.g. +1234567890',
            border: OutlineInputBorder(),
          ),
          keyboardType: TextInputType.phone,
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            onPressed: () async {
              final phone = phoneController.text.trim();
              if (phone.length < 8) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Invalid phone number')));
                return;
              }
              
              Navigator.pop(ctx);
              _sendTest(phone);
            },
            child: const Text('Send'),
          ),
        ],
      ),
    );
  }

  Future<void> _sendTest(String phone) async {
    try {
      final authState = context.read<AuthBloc>().state;
      final numberId = authState is Authenticated ? int.tryParse(authState.activeNumberId ?? '') ?? 1 : 1;

      await context.read<ApiService>().sendTemplateTest(
        widget.templateId, 
        phone,
        numberId,
      );
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Test message sent!'), backgroundColor: Colors.green),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _showDeleteConfirm() {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Draft?'),
        content: const Text('This will permanently remove this template draft. This action cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              _deleteTemplate();
            },
            child: const Text('Delete', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  Future<void> _deleteTemplate() async {
    try {
      await context.read<ApiService>().deleteTemplate(widget.templateId);
      if (mounted) {
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Template draft deleted')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    }
  }
}
