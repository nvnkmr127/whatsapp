import 'package:flutter/material.dart';

class ErrorView extends StatelessWidget {
  final String title;
  final String message;
  final VoidCallback? onRetry;
  final IconData icon;

  const ErrorView({
    super.key,
    this.title = 'Oops! Something went wrong',
    this.message = 'We encountered an unexpected error. Please try again.',
    this.onRetry,
    this.icon = Icons.error_outline,
  });

  factory ErrorView.network({VoidCallback? onRetry}) {
    return ErrorView(
      title: 'No Internet Connection',
      message: 'Please check your network settings and try again.',
      icon: Icons.wifi_off_rounded,
      onRetry: onRetry,
    );
  }

  factory ErrorView.auth({VoidCallback? onLogin}) {
    return ErrorView(
      title: 'Session Expired',
      message: 'Your session has timed out. Please log in again to continue.',
      icon: Icons.lock_clock_rounded,
      onRetry: onLogin,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.red.withValues(alpha: 0.05),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, size: 64, color: Colors.red[400]),
          ),
          const SizedBox(height: 24),
          Text(
            title,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 12),
          Text(
            message,
            style: TextStyle(fontSize: 14, color: Colors.grey[600]),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 32),
          if (onRetry != null)
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: onRetry,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF128C7E),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: const Text('RETRY', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ),
        ],
      ),
    ),
  );
  }
}

class ErrorHandler {
  static void showSnackBar(BuildContext context, String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        behavior: SnackBarBehavior.floating,
        backgroundColor: Colors.red[800],
        action: SnackBarAction(
          label: 'OK',
          textColor: Colors.white,
          onPressed: () {},
        ),
      ),
    );
  }
}
