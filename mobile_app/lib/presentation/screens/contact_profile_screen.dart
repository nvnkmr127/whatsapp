import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:timeago/timeago.dart' as timeago;
import '../../core/api_service.dart';
import '../widgets/chat_avatar.dart';

class ContactProfileScreen extends StatefulWidget {
  final Map<String, dynamic>? contact;
  final int? id;

  const ContactProfileScreen({super.key, this.contact, this.id});

  @override
  State<ContactProfileScreen> createState() => _ContactProfileScreenState();
}

class _ContactProfileScreenState extends State<ContactProfileScreen> with SingleTickerProviderStateMixin {
  late TextEditingController _nameController;
  late TabController _tabController;
  Map<String, dynamic>? _contact;
  List<dynamic> _activity = [];
  List<dynamic> _schema = [];
  bool _loading = false;
  bool _loadingActivity = false;

  @override
  void initState() {
    super.initState();
    _contact = widget.contact;
    _nameController = TextEditingController(text: _contact?['name']?.toString() ?? '');
    _tabController = TabController(length: 2, vsync: this);
    _fetch();
    _fetchActivity();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _nameController.dispose();
    super.dispose();
  }

  Future<void> _fetch() async {
    final contactId = widget.id;
    if (contactId == null) return;

    setState(() => _loading = true);
    try {
      final response = await context.read<ApiService>().getContact(contactId);
      final data = response.data;
      if (!mounted) return;
      if (data is Map) {
        setState(() {
          _contact = Map<String, dynamic>.from(data['contact'] ?? {});
          _schema = List<dynamic>.from(data['schema'] ?? []);
          _nameController.text = _contact?['name']?.toString() ?? '';
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Failed to load contact: $e')));
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _fetchActivity() async {
    final contactId = widget.id;
    if (contactId == null) return;

    setState(() => _loadingActivity = true);
    try {
      final response = await context.read<ApiService>().getContactActivity(contactId);
      final data = response.data;
      if (mounted && data is Map) {
        setState(() => _activity = data['data'] ?? []);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Failed to load activity: $e')));
      }
    } finally {
      if (mounted) setState(() => _loadingActivity = false);
    }
  }

  Future<void> _toggleTag(int tagId) async {
    final contactId = widget.id;
    if (contactId == null) return;
    try {
      await context.read<ApiService>().toggleContactTag(contactId, tagId);
      await _fetch();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    }
  }

  Future<void> _openTagPicker() async {
    final contactId = widget.id;
    if (contactId == null) return;

    try {
      final response = await context.read<ApiService>().getTags();
      final data = response.data;
      final tags = data is List ? data : (data is Map ? (data['data'] ?? []) : []);
      if (!mounted) return;

      await showModalBottomSheet(
        context: context,
        builder: (ctx) => SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Padding(
                padding: EdgeInsets.all(16),
                child: Text('Manage Tags', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              ),
              Flexible(
                child: ListView.builder(
                  shrinkWrap: true,
                  itemCount: tags.length,
                  itemBuilder: (ctx, i) {
                    final t = tags[i];
                    final id = (t is Map ? t['id'] : null) as num?;
                    final name = (t is Map ? t['name'] : null)?.toString() ?? 'Tag';
                    final color = (t is Map ? t['color'] : null)?.toString() ?? '#6366F1';
                    
                    return ListTile(
                      leading: CircleAvatar(radius: 8, backgroundColor: _parseColor(color)),
                      title: Text(name),
                      onTap: id == null ? null : () async {
                        Navigator.pop(ctx);
                        await _toggleTag(id.toInt());
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    }
  }

  @override
  Widget build(BuildContext context) {

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: const Text('Contact Details'),
        actions: [
          IconButton(
            icon: const Icon(Icons.save),
            tooltip: 'Save Profile',
            onPressed: _saveProfile,
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          indicatorColor: Colors.white,
          tabs: const [
            Tab(text: 'Profile'),
            Tab(text: 'Activity'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildProfileTab(),
          _buildActivityTab(),
        ],
      ),
    );
  }

  Widget _buildProfileTab() {
    final tags = (_contact?['tags'] as List?) ?? [];
    final rawAttrs = _contact?['custom_attributes'];
    final attrs = rawAttrs is Map ? rawAttrs : {};
    final optIn = (_contact?['opt_in_status'] ?? 'unknown').toString();

    return ListView(
      padding: const EdgeInsets.all(24),
      children: [
        if (_loading) const LinearProgressIndicator(),
        Center(
          child: Column(
            children: [
              ChatAvatar(name: _contact?['name'], radius: 40),
              const SizedBox(height: 16),
              Text(_contact?['phone_number'] ?? 'No Phone', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                decoration: BoxDecoration(
                  color: optIn == 'opted_in' ? Colors.green.shade50 : Colors.grey.shade200,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  optIn.toUpperCase(),
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: optIn == 'opted_in' ? Colors.green : Colors.grey),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 40),
        _buildSectionTitle('DISPLAY NAME'),
        TextField(
          controller: _nameController,
          decoration: InputDecoration(
            filled: true,
            fillColor: Theme.of(context).cardColor,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
          ),
        ),
        const SizedBox(height: 24),
        _buildSectionTitle('TAGS'),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            ...tags.map((t) => Chip(
              label: Text(t['name']),
              backgroundColor: _parseColor(t['color']).withValues(alpha: 0.1),
              labelStyle: TextStyle(color: _parseColor(t['color']), fontSize: 12),
              onDeleted: () => _toggleTag(t['id']),
            )),
            ActionChip(
              label: const Icon(Icons.add, size: 18),
              tooltip: 'Add Tag',
              onPressed: _openTagPicker,
            ),
          ],
        ),
        const SizedBox(height: 24),
        _buildSectionTitle('CUSTOM ATTRIBUTES'),
        if (_schema.isEmpty)
          const Text('No custom fields defined.', style: TextStyle(color: Colors.grey)),
        ..._schema.map((field) {
           final key = field['key']?.toString() ?? '';
           final label = field['label']?.toString() ?? key;
           final val = attrs[key];

           return ListTile(
              title: Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
              subtitle: Text(val?.toString() ?? '---', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              trailing: IconButton(
                icon: const Icon(Icons.edit, size: 18),
                tooltip: 'Edit $label',
                onPressed: () => _editSchemaField(field, val),
              ),
           );
        }),
      ],
    );
  }

  void _editSchemaField(dynamic field, dynamic currentVal) async {
    final key = field['key'].toString();
    final type = field['type'].toString();
    final label = field['label'].toString();
    final options = field['options'] as List? ?? [];

    if (type == 'date') {
       final DateTime initialDate = DateTime.tryParse(currentVal?.toString() ?? '') ?? DateTime.now();
       final picked = await showDatePicker(
         context: context, 
         initialDate: initialDate, 
         firstDate: DateTime(1900), 
         lastDate: DateTime(2100)
       );
       if (picked != null) {
         _updateAttribute(key, picked.toIso8601String().split('T')[0]);
       }
       return;
    }

    if (type == 'select') {
        showModalBottomSheet(
          context: context,
          builder: (ctx) => SafeArea(
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Padding(padding: const EdgeInsets.all(16), child: Text('Select $label', style: const TextStyle(fontWeight: FontWeight.bold))),
                  ...options.map((opt) => ListTile(
                    title: Text(opt.toString()),
                    onTap: () {
                      _updateAttribute(key, opt);
                      Navigator.pop(ctx);
                    },
                  )),
                  const SizedBox(height: 20),
                ],
              ),
            ),
          ),
        );
       return;
    }

    // Default: text or number
    final controller = TextEditingController(text: currentVal?.toString() ?? '');
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Edit $label'),
        content: TextField(
          controller: controller,
          keyboardType: type == 'number' ? TextInputType.number : TextInputType.text,
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          TextButton(
            onPressed: () {
               _updateAttribute(key, type == 'number' ? (num.tryParse(controller.text) ?? controller.text) : controller.text);
               Navigator.pop(ctx);
            }, 
            child: const Text('Save')
          ),
        ],
      ),
    );
  }

  Future<void> _updateAttribute(String key, dynamic val) async {
    final rawAttrs = _contact?['custom_attributes'];
    final attrs = rawAttrs is Map ? Map<String, dynamic>.from(rawAttrs) : <String, dynamic>{};
    attrs[key] = val;
    try {
      await context.read<ApiService>().updateContact(widget.id!, {'custom_attributes': attrs});
      if (mounted) _fetch();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    }
  }


  Widget _buildActivityTab() {
    if (_loadingActivity) return const Center(child: CircularProgressIndicator());
    if (_activity.isEmpty) return const Center(child: Text('No activity history found.'));

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: _activity.length,
      separatorBuilder: (_, __) => const Divider(),
      itemBuilder: (context, index) {
        final event = _activity[index];
        final type = event['event_type'] ?? 'unknown';
        final createdAt = DateTime.tryParse(event['created_at'] ?? '');

        return ListTile(
          leading: _getActivityIcon(type),
          title: Text(event['description'] ?? type.toString().replaceAll('_', ' ').toUpperCase()),
          subtitle: Text(createdAt != null ? timeago.format(createdAt) : ''),
          trailing: const Icon(Icons.chevron_right, size: 16),
        );
      },
    );
  }

  Widget _getActivityIcon(String type) {
    switch (type) {
      case 'message_sent': return const Icon(Icons.send, color: Colors.blue);
      case 'message_received': return const Icon(Icons.call_received, color: Colors.green);
      case 'note_added': return const Icon(Icons.note, color: Colors.orange);
      case 'automation_triggered': return const Icon(Icons.auto_fix_high, color: Colors.purple);
      default: return const Icon(Icons.info_outline);
    }
  }

  Future<void> _saveProfile() async {
    final id = widget.id;
    if (id == null) return;
    try {
      await context.read<ApiService>().updateContact(id, {
        'name': _nameController.text,
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profile updated!')));
      _fetch();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    }
  }


  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(title, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey, letterSpacing: 1.1)),
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

