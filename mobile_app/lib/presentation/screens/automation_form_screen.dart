import 'package:flutter/material.dart';
import '../../core/api_service.dart';

class AutomationFormScreen extends StatefulWidget {
  final ApiService apiService;
  final dynamic initialData;

  const AutomationFormScreen({super.key, required this.apiService, this.initialData});

  @override
  State<AutomationFormScreen> createState() => _AutomationFormScreenState();
}

class _AutomationFormScreenState extends State<AutomationFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  String _triggerType = 'message_received';
  bool _isActive = true;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.initialData?['name'] ?? '');
    _triggerType = widget.initialData?['trigger_type'] ?? 'message_received';
    _isActive = widget.initialData?['is_active'] ?? true;
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);
    try {
      if (widget.initialData == null) {
        await widget.apiService.createAutomation({
          'name': _nameController.text,
          'trigger_type': _triggerType,
          'is_active': _isActive,
        });
      } else {
        await widget.apiService.updateAutomation(widget.initialData['id'], {
          'name': _nameController.text,
          'is_active': _isActive,
        });
      }
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      setState(() => _isSaving = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final bool isEdit = widget.initialData != null;

    return Scaffold(
      appBar: AppBar(
        title: Text(isEdit ? 'Edit Automation' : 'New Automation'),
        actions: [
          if (_isSaving)
            const Center(child: Padding(padding: EdgeInsets.all(16), child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)))
          else
            TextButton(
              onPressed: _save,
              child: const Text('SAVE', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            TextFormField(
              controller: _nameController,
              decoration: const InputDecoration(
                labelText: 'Rule Name',
                hintText: 'e.g. Auto-assign Sales Leads',
                border: OutlineInputBorder(),
              ),
              validator: (v) => v == null || v.isEmpty ? 'Required' : null,
            ),
            const SizedBox(height: 24),
            
            const Text('Trigger Type', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 12),
            _buildTriggerOption('message_received', 'Message Received', 'Triggers when a new customer message arrives.', Icons.message),
            _buildTriggerOption('out_of_office', 'Business Hours', 'Triggers outside of configured operating hours.', Icons.schedule),
            _buildTriggerOption('contact_tagged', 'Contact Tagged', 'Triggers when a specific tag is applied to a contact.', Icons.local_offer),
            
            const SizedBox(height: 24),
            SwitchListTile(
              title: const Text('Active', style: TextStyle(fontWeight: FontWeight.bold)),
              subtitle: const Text('Enable or disable this rule globally.'),
              value: _isActive,
              onChanged: (v) => setState(() => _isActive = v),
              activeThumbColor: const Color(0xFF128C7E),
            ),
            
            if (isEdit) ...[
              const SizedBox(height: 40),
              TextButton.icon(
                icon: const Icon(Icons.delete, color: Colors.red),
                label: const Text('Delete Automation', style: TextStyle(color: Colors.red)),
                onPressed: _confirmDelete,
              ),
            ]
          ],
        ),
      ),
    );
  }

  Widget _buildTriggerOption(String type, String title, String description, IconData icon) {
    final bool isSelected = _triggerType == type;
    final bool isEdit = widget.initialData != null;

    return Card(
      elevation: 0,
      color: isSelected ? const Color(0xFF128C7E).withValues(alpha: 0.05) : Colors.transparent,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: isSelected ? const Color(0xFF128C7E) : Colors.grey.withValues(alpha: 0.2)),
      ),
      child: ListTile(
        onTap: isEdit ? null : () => setState(() => _triggerType = type),
        leading: Icon(icon, color: isSelected ? const Color(0xFF128C7E) : Colors.grey),
        title: Text(title, style: TextStyle(fontWeight: isSelected ? FontWeight.bold : FontWeight.normal)),
        subtitle: Text(description, style: const TextStyle(fontSize: 12)),
        trailing: isSelected ? const Icon(Icons.check_circle, color: Color(0xFF128C7E)) : null,
      ),
    );
  }

  void _confirmDelete() {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Automation?'),
        content: const Text('This action cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('CANCEL')),
          TextButton(
            onPressed: () async {
              Navigator.pop(ctx);
              await widget.apiService.deleteAutomation(widget.initialData['id']);
              if (mounted) Navigator.pop(context, true);
            }, 
            child: const Text('DELETE', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }
}
