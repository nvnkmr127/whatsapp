import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../../core/config_service.dart';

class AppConfigScreen extends StatefulWidget {
  const AppConfigScreen({super.key});

  @override
  State<AppConfigScreen> createState() => _AppConfigScreenState();
}

class _AppConfigScreenState extends State<AppConfigScreen> {
  bool _isScanning = true;
  
  @override
  void initState() {
    super.initState();
    if (kDebugMode) {
      debugPrint('[DEBUG] [QR] QR Configuration screen opened');
    }
  }

  void _onDetect(BarcodeCapture capture) async {
    if (!_isScanning) return;
    setState(() => _isScanning = false);

    final List<Barcode> barcodes = capture.barcodes;
    for (final barcode in barcodes) {
      final String? code = barcode.rawValue;
      if (code != null) {
        try {
          final Map<String, dynamic> config = jsonDecode(code);
            if (kDebugMode) {
              debugPrint('[DEBUG] [QR] QR code detected and decoded successfully');
              debugPrint('[DEBUG] [QR] Config payload: $config');
            }

            await ConfigService.saveConfig(config);

            if (mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Configuration updated successfully!')),
              );
              Navigator.pop(context, true);
            }
            break;
          } catch (e) {
            if (kDebugMode) {
              debugPrint('[DEBUG] [QR] Failed to decode QR or invalid config: $e');
            }
            // Not a valid JSON or not our config
            setState(() => _isScanning = true);
          }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Scan Configuration'),
        backgroundColor: const Color(0xFF075E54),
        foregroundColor: Colors.white,
      ),
      body: Stack(
        children: [
          MobileScanner(
            onDetect: _onDetect,
            errorBuilder: (context, error) {
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.error_outline, color: Colors.white, size: 60),
                    const SizedBox(height: 16),
                    Text(
                      error.errorCode == MobileScannerErrorCode.permissionDenied 
                          ? 'Camera permission denied' 
                          : 'Camera error: ${error.errorCode}',
                      style: const TextStyle(color: Colors.white),
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: _showManualEntryDialog,
                      child: const Text('Enter Manually'),
                    ),
                  ],
                ),
              );
            },
          ),
          Center(
            child: Container(
              width: 250,
              height: 250,
              decoration: BoxDecoration(
                border: Border.all(color: Colors.white, width: 2),
                borderRadius: BorderRadius.circular(20),
              ),
            ),
          ),
          Positioned(
            bottom: 50,
            left: 0,
            right: 0,
            child: Column(
              children: [
                const Text(
                  'Align the QR code from the web settings\nwithin the frame to configure the app.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: Colors.white, fontSize: 16, shadows: [
                    Shadow(blurRadius: 10, color: Colors.black, offset: Offset(2, 2))
                  ]),
                ),
                const SizedBox(height: 20),
                TextButton.icon(
                  onPressed: _showManualEntryDialog,
                  icon: const Icon(Icons.edit, color: Colors.white70),
                  label: const Text('Manual Setup', style: TextStyle(color: Colors.white70)),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showManualEntryDialog() {
    final controller = TextEditingController();
    final _nameController = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Manual Configuration'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: _nameController, 
              decoration: const InputDecoration(
                labelText: 'Name',
                hintText: 'e.g. Summer Promo 2024',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: controller,
              maxLines: 5,
              decoration: const InputDecoration(
                hintText: 'Paste configuration JSON here...',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            onPressed: () async {
              try {
                final config = jsonDecode(controller.text);
                await ConfigService.saveConfig(config);
                if (mounted) {
                  Navigator.pop(ctx);
                  Navigator.pop(context, true);
                }
              } catch (e) {
                ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Invalid JSON: $e')));
              }
            },
            child: const Text('Save'),
          ),
        ],
      ),
    );
  }
}
