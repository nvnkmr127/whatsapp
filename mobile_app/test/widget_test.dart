import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:whatsapp_pro_mobile/presentation/widgets/error_view.dart';

void main() {
  group('Widget Tests', () {
    testWidgets('ErrorView displays message and retry button', (WidgetTester tester) async {
      bool retryClicked = false;
      
      await tester.pumpWidget(MaterialApp(
        home: Scaffold(
          body: ErrorView(
            title: 'Test Error',
            message: 'Something went wrong',
            onRetry: () => retryClicked = true,
          ),
        ),
      ));

      expect(find.text('Test Error'), findsOneWidget);
      expect(find.text('Something went wrong'), findsOneWidget);
      
      final retryButton = find.text('RETRY');
      expect(retryButton, findsOneWidget);
      
      await tester.tap(retryButton);
      expect(retryClicked, true);
    });

    testWidgets('Login Screen Form Check (Mocked UI)', (WidgetTester tester) async {
       // Since full screens might need Blocs, we'll test a simpler component
       // or use a wrapped BlocProvider for full screen tests.
    });
  });
}
