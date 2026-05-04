import 'package:flutter/material.dart';

class ErrorScreen extends StatelessWidget {
  final String title;
  final String message;
  final VoidCallback? onRetry;

  const ErrorScreen({
    super.key,
    this.title = 'Oops!',
    this.message = 'Something went wrong on our end. Please try again.',
    this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, size: 100, color: Colors.redAccent),
            const SizedBox(height: 24),
            Text(title, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center, style: const TextStyle(color: Colors.grey)),
            const SizedBox(height: 40),
            if (onRetry != null)
              SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton(
                  onPressed: onRetry,
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF128C7E)),
                  child: const Text('RETRY CONNECTION', style: TextStyle(color: Colors.white)),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class OfflineScreen extends StatelessWidget {
  final VoidCallback? onRetry;
  const OfflineScreen({super.key, this.onRetry});

  @override
  Widget build(BuildContext context) {
    return ErrorScreen(
      title: 'No Connection',
      message: 'You are currently offline. Please check your internet settings.',
      onRetry: onRetry,
    );
  }
}
