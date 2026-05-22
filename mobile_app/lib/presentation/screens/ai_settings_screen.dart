import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../core/api_service.dart';

class AiSettingsScreen extends StatefulWidget {
  const AiSettingsScreen({super.key});

  @override
  State<AiSettingsScreen> createState() => _AiSettingsScreenState();
}

class _AiSettingsScreenState extends State<AiSettingsScreen> {
  bool _isLoading = true;
  bool _isSaving = false;

  bool _enabled = false;
  final TextEditingController _apiKeyController = TextEditingController();
  final TextEditingController _personaController = TextEditingController();
  String _selectedProvider = 'openai';
  String _selectedModel = 'gpt-4o';
  bool _useKb = false;
  bool _kbStrict = true;
  double _confidence = 0.7;
  bool _operatingHoursOnly = false;

  static const Map<String, List<String>> _providerModels = {
    'openai': ['gpt-4o', 'gpt-4-turbo', 'gpt-3.5-turbo'],
    'gemini': ['gemini-1.5-pro', 'gemini-1.5-flash', 'gemini-pro'],
    'anthropic': ['claude-3-5-sonnet-20241022', 'claude-3-haiku-20240307', 'claude-3-opus-20240229'],
    'deepseek': ['deepseek-chat', 'deepseek-reasoner'],
  };

  static const Map<String, String> _providerLabels = {
    'openai': '🤖 OpenAI',
    'gemini': '✨ Google Gemini',
    'anthropic': '🧠 Anthropic Claude',
    'deepseek': '🔍 DeepSeek',
  };

  @override
  void initState() {
    super.initState();
    _fetchSettings();
  }

  Future<void> _fetchSettings() async {
    try {
      final response = await context.read<ApiService>().getAiSettings();
      final data = response.data;
      setState(() {
        _enabled = data['enabled'] ?? false;
        _selectedProvider = data['provider'] ?? 'openai';
        _apiKeyController.text = data['api_key'] ?? '';
        _personaController.text = data['persona'] ?? '';
        // Ensure the saved model is valid for the saved provider
        final validModels = _providerModels[_selectedProvider] ?? ['gpt-4o'];
        final savedModel = data['model'] ?? validModels.first;
        _selectedModel = validModels.contains(savedModel) ? savedModel : validModels.first;
        _useKb = data['use_kb'] ?? false;
        _kbStrict = data['kb_strict'] ?? true;
        _confidence = (data['confidence_threshold'] as num?)?.toDouble() ?? 0.7;
        _operatingHoursOnly = data['operating_hours_only'] ?? false;
        _isLoading = false;
      });
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to load AI settings: $e'))
        );
      }
    }
  }

  Future<void> _saveSettings() async {
    setState(() => _isSaving = true);
    try {
      await context.read<ApiService>().updateAiSettings({
        'enabled': _enabled,
        'provider': _selectedProvider,
        'api_key': _apiKeyController.text,
        'persona': _personaController.text,
        'model': _selectedModel,
        'use_kb': _useKb,
        'kb_strict': _kbStrict,
        'confidence_threshold': _confidence,
        'operating_hours_only': _operatingHoursOnly,
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('AI settings updated successfully!'), backgroundColor: Colors.green),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to update: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF128C7E))),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('AI Assistant Settings'),
        backgroundColor: const Color(0xFF128C7E),
        foregroundColor: Colors.white,
        actions: [
          if (_isSaving)
            const Center(child: Padding(padding: EdgeInsets.all(16), child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))))
          else
            IconButton(
              icon: const Icon(Icons.refresh), 
              tooltip: 'Refresh Settings',
              onPressed: _fetchSettings
            ),
            IconButton(
              icon: const Icon(Icons.check), 
              tooltip: 'Save Settings',
              onPressed: _saveSettings
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(20),
              children: [
                _buildSectionHeader('GLOBAL CONTROLS'),
                SwitchListTile(
                  title: const Text('AI Auto-Reply Enabled'),
                  subtitle: const Text('When enabled, the AI will automatically respond to customer messages.'),
                  value: _enabled,
                  activeThumbColor: const Color(0xFF128C7E),
                  onChanged: (val) => setState(() => _enabled = val),
                ),
                const Divider(),
                
                _buildSectionHeader('MODEL & AUTHENTICATION'),
                // AI Provider Selector
                const Text('AI Provider', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.grey)),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  decoration: BoxDecoration(
                    color: Theme.of(context).brightness == Brightness.dark
                        ? Theme.of(context).cardColor
                        : Colors.white,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: DropdownButton<String>(
                    value: _selectedProvider,
                    isExpanded: true,
                    underline: const SizedBox(),
                    items: _providerLabels.entries.map((e) {
                      return DropdownMenuItem(value: e.key, child: Text(e.value));
                    }).toList(),
                    onChanged: (val) {
                      if (val != null) {
                        setState(() {
                          _selectedProvider = val;
                          // Reset model to first valid option for new provider
                          _selectedModel = (_providerModels[val] ?? ['gpt-4o']).first;
                        });
                      }
                    },
                  ),
                ),
                const SizedBox(height: 16),
                _buildTextField('API Key', _apiKeyController, isPassword: true, hint: 'Enter your API key...'),
                const SizedBox(height: 16),
                const Text('AI Model', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.grey)),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  decoration: BoxDecoration(
                    color: Theme.of(context).brightness == Brightness.dark
                        ? Theme.of(context).cardColor
                        : Colors.white,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: DropdownButton<String>(
                    value: _selectedModel,
                    isExpanded: true,
                    underline: const SizedBox(),
                    items: (_providerModels[_selectedProvider] ?? ['gpt-4o']).map((m) {
                      return DropdownMenuItem(value: m, child: Text(m));
                    }).toList(),
                    onChanged: (val) => setState(() => _selectedModel = val!),
                  ),
                ),
                const SizedBox(height: 24),

                _buildSectionHeader('PERSONALITY & KNOWLEDGE'),
                _buildTextField('Persona / Instructions', _personaController, maxLines: 4, hint: 'You are a helpful customer service representative...'),
                const SizedBox(height: 16),
                SwitchListTile(
                  title: const Text('Use Knowledge Base'),
                  subtitle: const Text('Ground AI responses using your uploaded documents.'),
                  value: _useKb,
                  activeThumbColor: const Color(0xFF128C7E),
                  onChanged: (val) => setState(() => _useKb = val),
                ),
                if (_useKb) ...[
                  CheckboxListTile(
                    title: const Text('Strict Knowledge Base Mode'),
                    subtitle: const Text('AI will only answer if information is found in your documents.'),
                    value: _kbStrict,
                    activeColor: const Color(0xFF128C7E),
                    onChanged: (val) => setState(() => _kbStrict = val ?? true),
                  ),
                ],
                const SizedBox(height: 24),

                _buildSectionHeader('SAFETY & THRESHOLDS'),
                Text('Confidence Threshold: ${(_confidence * 100).toInt()}%', style: const TextStyle(fontWeight: FontWeight.bold)),
                const Text('AI will only respond if its confidence score is above this value.', style: TextStyle(fontSize: 12, color: Colors.grey)),
                Slider(
                  value: _confidence,
                  min: 0.1,
                  max: 1.0,
                  activeColor: const Color(0xFF128C7E),
                  onChanged: (val) => setState(() => _confidence = val),
                ),
                SwitchListTile(
                  title: const Text('Operating Hours Only'),
                  subtitle: const Text('Only auto-reply when your team is away or after business hours.'),
                  value: _operatingHoursOnly,
                  activeThumbColor: const Color(0xFF128C7E),
                  onChanged: (val) => setState(() => _operatingHoursOnly = val),
                ),
                const SizedBox(height: 40),
              ],
            ),
    );
  }

  Widget _buildTextField(String label, TextEditingController controller, {bool isPassword = false, int maxLines = 1, String? hint}) {
    return _PasswordToggleField(
      label: label,
      controller: controller,
      isPassword: isPassword,
      maxLines: maxLines,
      hint: hint,
    );
  }
}

class _PasswordToggleField extends StatefulWidget {
  final String label;
  final TextEditingController controller;
  final bool isPassword;
  final int maxLines;
  final String? hint;

  const _PasswordToggleField({
    required this.label,
    required this.controller,
    this.isPassword = false,
    this.maxLines = 1,
    this.hint,
  });

  @override
  State<_PasswordToggleField> createState() => _PasswordToggleFieldState();
}

class _PasswordToggleFieldState extends State<_PasswordToggleField> {
  late bool _obscure;

  @override
  void initState() {
    super.initState();
    _obscure = widget.isPassword;
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(widget.label, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.grey)),
        const SizedBox(height: 8),
        TextField(
          controller: widget.controller,
          obscureText: _obscure,
          maxLines: _obscure ? 1 : widget.maxLines,
          decoration: InputDecoration(
            hintText: widget.hint,
            filled: true,
            fillColor: Theme.of(context).brightness == Brightness.dark
                ? Theme.of(context).cardColor
                : Colors.white,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
            contentPadding: const EdgeInsets.all(16),
            suffixIcon: widget.isPassword ? IconButton(
              icon: Icon(_obscure ? Icons.visibility : Icons.visibility_off),
              onPressed: () => setState(() => _obscure = !_obscure),
            ) : null,
          ),
        ),
      ],
    );
  }
}
