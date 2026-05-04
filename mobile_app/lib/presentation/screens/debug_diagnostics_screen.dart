import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../core/storage_keys.dart';
import '../../core/api_service.dart';
import '../../core/config_service.dart';

class DebugDiagnosticsScreen extends StatefulWidget {
  const DebugDiagnosticsScreen({super.key});

  @override
  State<DebugDiagnosticsScreen> createState() => _DebugDiagnosticsScreenState();
}

class _DebugDiagnosticsScreenState extends State<DebugDiagnosticsScreen> {
  final _storage = const FlutterSecureStorage();
  Map<String, dynamic>? _meResult;
  bool _isLoadingMe = false;
  String? _meError;

  Future<void> _fetchMe() async {
    setState(() {
      _isLoadingMe = true;
      _meError = null;
    });
    try {
      final apiService = context.read<ApiService>();
      final response = await apiService.getMe();
      if (!mounted) return;
      setState(() {
        _meResult = response.data;
        _isLoadingMe = false;
      });
    } catch (e) {
      setState(() {
        _meError = e.toString();
        _isLoadingMe = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Debug Diagnostics'),
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildSectionTitle('Storage & Tokens'),
          FutureBuilder(
            future: _storage.read(key: StorageKeys.authToken),
            builder: (context, snapshot) {
              final hasToken = snapshot.data != null;
              return _buildInfoRow('Stored Token Present?', hasToken ? 'YES ✅' : 'NO ❌', color: hasToken ? Colors.green : Colors.red);
            },
          ),
          FutureBuilder(
            future: _storage.read(key: StorageKeys.lastLoginTimestamp),
            builder: (context, snapshot) {
              return _buildInfoRow('Last Login', snapshot.data ?? 'Never');
            },
          ),
          const Divider(),
          _buildSectionTitle('Network Config'),
          FutureBuilder(
            future: ConfigService.getApiBaseUrl(),
            builder: (context, snapshot) {
              return _buildInfoRow('API Base URL', snapshot.data ?? 'Loading...');
            },
          ),
          const Divider(),
          _buildSectionTitle('API Connectivity'),
          ElevatedButton(
            onPressed: _isLoadingMe ? null : _fetchMe,
            child: _isLoadingMe ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2)) : const Text('Test /me Endpoint'),
          ),
          if (_meResult != null) ...[
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: Colors.grey[200], borderRadius: BorderRadius.circular(8)),
              child: Text(
                'Result: ${_meResult.toString()}',
                style: const TextStyle(fontFamily: 'monospace', fontSize: 12),
              ),
            ),
          ],
          if (_meError != null) ...[
            const SizedBox(height: 10),
            Text('Error: $_meError', style: const TextStyle(color: Colors.red, fontSize: 12)),
          ],
          const Divider(),
          _buildSectionTitle('Cookie Check'),
          _buildInfoRow('Cookie Support', 'Not using CookieJar (Bearer Only)', color: Colors.blueGrey),
          const SizedBox(height: 40),
          Center(
            child: Text(
              'Only visible in DEBUG mode',
              style: TextStyle(color: Colors.grey[400], fontSize: 12),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.teal)),
    );
  }

  Widget _buildInfoRow(String label, String value, {Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 14)),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.end,
              style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: color),
            ),
          ),
        ],
      ),
    );
  }
}
