import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../core/api_service.dart';

class TemplateDraftScreen extends StatefulWidget {
  final Map<String, dynamic>? template; // If null, we're creating a new one
  const TemplateDraftScreen({super.key, this.template});

  @override
  State<TemplateDraftScreen> createState() => _TemplateDraftScreenState();
}

class _TemplateDraftScreenState extends State<TemplateDraftScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _headerController;
  late TextEditingController _bodyController;
  late TextEditingController _footerController;
  late List<TextEditingController> _buttonControllers;
  String _selectedCategory = 'UTILITY';
  String _selectedLanguage = 'en_US';
  bool _isSaving = false;

  final List<String> _categories = ['MARKETING', 'UTILITY', 'AUTHENTICATION'];
  final List<Map<String, String>> _languages = [
    {'code': 'en_US', 'name': 'English (US)'},
    {'code': 'en_GB', 'name': 'English (UK)'},
    {'code': 'hi', 'name': 'Hindi'},
    {'code': 'te', 'name': 'Telugu'},
    {'code': 'es', 'name': 'Spanish'},
  ];

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.template?['name'] ?? '');
    
    // Extract components
    final components = (widget.template?['components'] as List?) ?? [];
    String headerText = '';
    String bodyText = '';
    String footerText = '';
    List<String> buttons = [];
    for (var c in components) {
      if (c['type'] == 'HEADER' && c['format'] == 'TEXT') headerText = c['text'] ?? '';
      if (c['type'] == 'BODY') bodyText = c['text'] ?? '';
      if (c['type'] == 'FOOTER') footerText = c['text'] ?? '';
      if (c['type'] == 'BUTTONS') {
        final btnList = (c['buttons'] as List?) ?? [];
        for (var b in btnList) {
          if (b['type'] == 'QUICK_REPLY') buttons.add(b['text'] ?? '');
        }
      }
    }

    _headerController = TextEditingController(text: headerText);
    _bodyController = TextEditingController(text: bodyText);
    _footerController = TextEditingController(text: footerText);
    _buttonControllers = buttons.map((b) => TextEditingController(text: b)).toList();
    if (_buttonControllers.isEmpty) _buttonControllers.add(TextEditingController());

    _selectedCategory = widget.template?['category'] ?? 'UTILITY';
    _selectedLanguage = widget.template?['language'] ?? 'en_US';
  }

  @override
  void dispose() {
    _nameController.dispose();
    _headerController.dispose();
    _bodyController.dispose();
    _footerController.dispose();
    for (var c in _buttonControllers) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);
    
    final components = [];
    
    if (_headerController.text.trim().isNotEmpty) {
      components.add({
        'type': 'HEADER',
        'format': 'TEXT',
        'text': _headerController.text.trim(),
      });
    }

    components.add({
      'type': 'BODY',
      'text': _bodyController.text.trim(),
    });

    if (_footerController.text.trim().isNotEmpty) {
      components.add({
        'type': 'FOOTER',
        'text': _footerController.text.trim(),
      });
    }

    final buttons = _buttonControllers
        .map((c) => c.text.trim())
        .where((t) => t.isNotEmpty)
        .map((t) => {'type': 'QUICK_REPLY', 'text': t})
        .toList();

    if (buttons.isNotEmpty) {
      components.add({
        'type': 'BUTTONS',
        'buttons': buttons,
      });
    }

    final data = {
      'name': _nameController.text.trim(),
      'category': _selectedCategory,
      'language': _selectedLanguage,
      'components': components,
    };

    try {
      if (widget.template == null) {
        await context.read<ApiService>().createTemplateDraft(data);
      } else {
        await context.read<ApiService>().updateTemplateDraft(widget.template!['id'], data);
      }

      if (mounted) {
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(widget.template == null ? 'Draft created' : 'Draft updated')),
        );
      }
    } catch (e) {
      setState(() => _isSaving = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final bool isEdit = widget.template != null;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text(isEdit ? 'Edit Draft' : 'New Template Draft'),
        backgroundColor: const Color(0xFF128C7E),
        foregroundColor: Colors.white,
        actions: [
          if (_isSaving)
            const Center(child: Padding(
              padding: EdgeInsets.symmetric(horizontal: 16.0),
              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
            ))
          else
            IconButton(icon: const Icon(Icons.check), onPressed: _save),
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _buildSection(
              title: 'Template Identity',
              child: Column(
                children: [
                  TextFormField(
                    controller: _nameController,
                    enabled: !isEdit, // Name cannot be changed after creation
                    decoration: InputDecoration(
                      labelText: 'Template Name',
                      hintText: 'e.g. welcome_message',
                      helperText: isEdit ? 'Name cannot be changed' : 'Use lowercase and underscores only',
                      border: const OutlineInputBorder(),
                    ),
                    validator: (v) {
                      if (v == null || v.isEmpty) return 'Required';
                      if (!RegExp(r'^[a-z0-9_]+$').hasMatch(v)) return 'Invalid name format';
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    value: _selectedCategory,
                    decoration: const InputDecoration(labelText: 'Category', border: OutlineInputBorder()),
                    items: _categories.map((c) => DropdownMenuItem(value: c, child: Text(c))).toList(),
                    onChanged: (val) => setState(() => _selectedCategory = val!),
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    value: _selectedLanguage,
                    decoration: const InputDecoration(labelText: 'Language', border: OutlineInputBorder()),
                    items: _languages.map((l) => DropdownMenuItem(value: l['code'], child: Text(l['name']!))).toList(),
                    onChanged: (val) => setState(() => _selectedLanguage = val!),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            _buildSection(
              title: 'Content Components',
              child: Column(
                children: [
                  TextFormField(
                    controller: _headerController,
                    decoration: const InputDecoration(
                      labelText: 'Header Text (Optional)',
                      hintText: 'e.g. Welcome to Our Store',
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _bodyController,
                    maxLines: 6,
                    decoration: const InputDecoration(
                      labelText: 'Body Text',
                      hintText: 'Enter your message content here...',
                      helperText: 'Use {{1}}, {{2}} for variables',
                      border: OutlineInputBorder(),
                      alignLabelWithHint: true,
                    ),
                    validator: (v) => v == null || v.isEmpty ? 'Body is required' : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _footerController,
                    decoration: const InputDecoration(
                      labelText: 'Footer (Optional)',
                      hintText: 'e.g. Reply STOP to opt out',
                      border: OutlineInputBorder(),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            _buildSection(
              title: 'Buttons (Quick Replies)',
              child: Column(
                children: [
                  ..._buttonControllers.asMap().entries.map((entry) {
                    final index = entry.key;
                    final controller = entry.value;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8.0),
                      child: Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: controller,
                              decoration: InputDecoration(
                                labelText: 'Button ${index + 1}',
                                hintText: 'e.g. Learn More',
                                border: const OutlineInputBorder(),
                              ),
                            ),
                          ),
                          IconButton(
                            icon: const Icon(Icons.remove_circle_outline, color: Colors.red),
                            onPressed: () {
                              setState(() {
                                _buttonControllers.removeAt(index);
                                if (_buttonControllers.isEmpty) _buttonControllers.add(TextEditingController());
                              });
                            },
                          ),
                        ],
                      ),
                    );
                  }),
                  TextButton.icon(
                    icon: const Icon(Icons.add),
                    label: const Text('Add Button'),
                    onPressed: _buttonControllers.length < 3
                        ? () => setState(() => _buttonControllers.add(TextEditingController()))
                        : null,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            const Card(
              color: Color(0xFFFFF9C4),
              child: Padding(
                padding: EdgeInsets.all(12.0),
                child: Row(
                  children: [
                    Icon(Icons.info_outline, color: Colors.orange),
                    SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'Templates created here are saved as LOCAL DRAFTS. You must submit them to Meta via the Web Dashboard for approval.',
                        style: TextStyle(fontSize: 12, color: Colors.brown),
                      ),
                    ),
                  ],
                ),
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
