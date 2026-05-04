import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../core/api_service.dart';

class BotEditScreen extends StatefulWidget {
  final Map<String, dynamic>? bot;
  const BotEditScreen({super.key, this.bot});

  @override
  State<BotEditScreen> createState() => _BotEditScreenState();
}

class _BotEditScreenState extends State<BotEditScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _replyController;
  late List<String> _triggers;
  final TextEditingController _triggerInputController = TextEditingController();
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.bot?['name'] ?? '');
    _replyController = TextEditingController(text: widget.bot?['reply_text'] ?? '');
    _triggers = List<String>.from(widget.bot?['trigger'] ?? []);
  }

  @override
  void dispose() {
    _nameController.dispose();
    _replyController.dispose();
    _triggerInputController.dispose();
    super.dispose();
  }

  void _addTrigger() {
    final val = _triggerInputController.text.trim();
    if (val.isNotEmpty && !_triggers.contains(val)) {
      setState(() {
        _triggers.add(val);
        _triggerInputController.clear();
      });
    }
  }

  void _removeTrigger(String val) {
    setState(() {
      _triggers.remove(val);
    });
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);
    final data = {
      'name': _nameController.text.trim(),
      'reply_text': _replyController.text.trim(),
      'trigger': _triggers,
    };

    final messenger = ScaffoldMessenger.of(context);
    try {
      if (widget.bot == null) {
        debugPrint('[DEBUG] [API] Creating bot: $data');
        await context.read<ApiService>().createBot(data);
      } else {
        debugPrint('[DEBUG] [API] Updating bot: ${widget.bot!['id']} with $data');
        await context.read<ApiService>().updateBot(widget.bot!['id'], data);
      }

      if (mounted) {
        Navigator.pop(context, true);
        messenger.showSnackBar(
          SnackBar(content: Text(widget.bot == null ? 'Bot created successfully' : 'Bot updated successfully')),
        );
      }
    } catch (e) {
      debugPrint('[DEBUG] [API] Error saving bot: $e');
      setState(() => _isSaving = false);
      if (mounted) {
        messenger.showSnackBar(
          SnackBar(content: Text('Error saving: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final bool isEdit = widget.bot != null;

    return Scaffold(
      backgroundColor: const Color(0xFFF0F2F5),
      appBar: AppBar(
        title: Text(isEdit ? 'Edit Bot' : 'New Custom Bot'),
        backgroundColor: const Color(0xFF128C7E),
        foregroundColor: Colors.white,
        actions: [
          if (_isSaving)
            const Center(child: Padding(
              padding: EdgeInsets.symmetric(horizontal: 16.0),
              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
            ))
          else
            IconButton(
              icon: const Icon(Icons.check),
              onPressed: _save,
            ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _buildSection(
              title: 'Basic Info',
              child: Column(
                children: [
                  TextFormField(
                    controller: _nameController,
                    decoration: const InputDecoration(
                      labelText: 'Bot Name',
                      hintText: 'e.g. Sales Assistant',
                      border: OutlineInputBorder(),
                    ),
                    validator: (v) => v == null || v.isEmpty ? 'Required' : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _replyController,
                    maxLines: 5,
                    decoration: const InputDecoration(
                      labelText: 'Reply Text',
                      hintText: 'What should the bot say?',
                      border: OutlineInputBorder(),
                      alignLabelWithHint: true,
                    ),
                    validator: (v) => v == null || v.isEmpty ? 'Required' : null,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            _buildSection(
              title: 'Triggers (Keywords)',
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _triggerInputController,
                          decoration: const InputDecoration(
                            hintText: 'Add keyword...',
                            border: OutlineInputBorder(),
                          ),
                          onSubmitted: (_) => _addTrigger(),
                        ),
                      ),
                      const SizedBox(width: 8),
                      IconButton.filled(
                        onPressed: _addTrigger,
                        icon: const Icon(Icons.add),
                        style: IconButton.styleFrom(backgroundColor: const Color(0xFF128C7E)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: _triggers.map((t) => Chip(
                      label: Text(t),
                      onDeleted: () => _removeTrigger(t),
                      backgroundColor: Colors.white,
                      deleteIconColor: Colors.red,
                    )).toList(),
                  ),
                  if (_triggers.isEmpty)
                    const Padding(
                      padding: EdgeInsets.only(top: 8.0),
                      child: Text('No triggers added. This bot will not fire.', style: TextStyle(color: Colors.orange, fontSize: 12)),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSection({required String title, required Widget child}) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF128C7E))),
            const SizedBox(height: 16),
            child,
          ],
        ),
      ),
    );
  }
}
