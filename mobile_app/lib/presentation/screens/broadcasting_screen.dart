import 'package:flutter/material.dart';
import '../../core/api_service.dart';
import 'broadcast_history_screen.dart';

class BroadcastingScreen extends StatefulWidget {
  final ApiService apiService;
  const BroadcastingScreen({super.key, required this.apiService});

  @override
  State<BroadcastingScreen> createState() => _BroadcastingScreenState();
}

class _BroadcastingScreenState extends State<BroadcastingScreen> {
  final _nameController = TextEditingController();
  int? _selectedTemplateId;
  int? _selectedTagId;
  List<dynamic> _templates = [];
  List<dynamic> _tags = [];
  bool _loading = true;
  
  // Variables & Scheduling
  final Map<int, TextEditingController> _variableControllers = {};
  DateTime? _scheduledAt;
  bool _isScheduled = false;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final results = await Future.wait([
        widget.apiService.getTemplates(),
        widget.apiService.getTags(),
      ]);

      if (mounted) {
        setState(() {
          _templates = results[0].data is List ? results[0].data : (results[0].data['data'] ?? []);
          _tags = results[1].data is List ? results[1].data : (results[1].data['data'] ?? []);
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _loading = false);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Failed to load data: $e')));
      }
    }
  }

  void _onTemplateChanged(int? id) {
    setState(() {
      _selectedTemplateId = id;
      _variableControllers.clear();
      if (id != null) {
        final template = _templates.firstWhere((t) => t['id'] == id);
        final String body = template['body'] ?? '';
        final matches = RegExp(r'\{\{(\d+)\}\}').allMatches(body);
        for (final m in matches) {
           final varIndex = int.parse(m.group(1)!);
           _variableControllers[varIndex] = TextEditingController();
        }
      }
    });
  }

  Future<void> _startCampaign() async {
    if (_nameController.text.isEmpty || _selectedTemplateId == null || _selectedTagId == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please fill all fields')));
      return;
    }

    if (_isScheduled && _scheduledAt == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please select a schedule time')));
      return;
    }

    final variables = _variableControllers.map((key, controller) => MapEntry(key.toString(), controller.text));

    try {
      await widget.apiService.startCampaign({
        'name': _nameController.text,
        'template_id': _selectedTemplateId,
        'tag_id': _selectedTagId,
        'variables': variables,
        'scheduled_at': _isScheduled ? _scheduledAt?.toIso8601String() : null,
      });

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Campaign started successfully!')));
      Navigator.pop(context);
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
        backgroundColor: const Color(0xFF128C7E),
        foregroundColor: Colors.white,
        title: const Text('Start Broadcast', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        actions: [
          IconButton(
            icon: const Icon(Icons.history),
            tooltip: 'Broadcast History',
            onPressed: () {
              Navigator.push(context, MaterialPageRoute(builder: (ctx) => BroadcastHistoryScreen(apiService: widget.apiService)));
            },
          )
        ],
      ),
      body: _loading 
        ? const Center(child: CircularProgressIndicator())
        : ListView(
            padding: const EdgeInsets.all(20),
            children: [
                const Text('Campaign Name', style: TextStyle(fontWeight: FontWeight.bold)),
                TextField(
                  controller: _nameController, 
                  decoration: const InputDecoration(
                    labelText: 'Name',
                    hintText: 'e.g. Summer Promo 2024',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 20),
                const Text('Select Template', style: TextStyle(fontWeight: FontWeight.bold)),
                DropdownButton<int>(
                  isExpanded: true,
                  value: _selectedTemplateId,
                  hint: const Text('Pick an approved template'),
                  items: _templates.map((t) => DropdownMenuItem<int>(
                    value: t['id'],
                    child: Text(t['name']),
                  )).toList(),
                  onChanged: _onTemplateChanged,
                ),
                if (_variableControllers.isNotEmpty) ...[
                   const SizedBox(height: 20),
                   const Text('Template Variables', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF128C7E))),
                   ..._variableControllers.entries.map((entry) => Padding(
                     padding: const EdgeInsets.only(top: 10),
                     child: TextField(
                       controller: entry.value,
                       decoration: InputDecoration(
                         labelText: 'Variable {{${entry.key}}}',
                         border: const OutlineInputBorder(),
                         prefixIcon: const Icon(Icons.abc),
                       ),
                     ),
                   )),
                ],
                const SizedBox(height: 20),
                const Text('Select Audience (Tag)', style: TextStyle(fontWeight: FontWeight.bold)),
                DropdownButton<int>(
                  isExpanded: true,
                  value: _selectedTagId,
                  hint: const Text('Pick a contact segment'),
                  items: _tags.map((t) => DropdownMenuItem<int>(
                    value: t['id'],
                    child: Text(t['name']),
                  )).toList(),
                  onChanged: (val) => setState(() => _selectedTagId = val),
                ),
                const SizedBox(height: 20),
                SwitchListTile(
                  title: const Text('Schedule for later', style: TextStyle(fontWeight: FontWeight.bold)),
                  value: _isScheduled, 
                  activeThumbColor: const Color(0xFF128C7E),
                  onChanged: (val) => setState(() => _isScheduled = val)
                ),
                if (_isScheduled) 
                  ListTile(
                    leading: const Icon(Icons.calendar_today),
                    title: Text(_scheduledAt == null 
                      ? 'Tap to pick date/time' 
                      : '${_scheduledAt!.day}/${_scheduledAt!.month}/${_scheduledAt!.year} at ${_scheduledAt!.hour}:${_scheduledAt!.minute.toString().padLeft(2, '0')}'),
                    onTap: () async {
                        final date = await showDatePicker(context: context, initialDate: DateTime.now(), firstDate: DateTime.now(), lastDate: DateTime.now().add(const Duration(days: 30)));
                        if (!mounted) return;
                        if (date != null && context.mounted) {
                          final time = await showTimePicker(context: context, initialTime: TimeOfDay.now());
                          if (!mounted) return;
                         if (time != null) {
                           setState(() => _scheduledAt = DateTime(date.year, date.month, date.day, time.hour, time.minute));
                         }
                       }
                    },
                  ),
                const SizedBox(height: 40),
                SizedBox(
                  width: double.infinity,
                  height: 50,
                  child: ElevatedButton(
                    onPressed: _startCampaign,
                    style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF25D366)),
                    child: const Text('Launch Broadcast'),
                  ),
                ),
              ],
            ),
    );
  }
}
