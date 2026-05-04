import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';
import 'package:whatsapp_pro_mobile/presentation/screens/call_log_screen.dart';
import '../../helpers/native_test_helpers.mocks.dart';

void main() {
  late MockApiService mockApiService;

  setUp(() {
    mockApiService = MockApiService();
  });

  Widget createWidgetUnderTest() {
    return MaterialApp(
      home: Scaffold(body: CallLogScreen(apiService: mockApiService)),
    );
  }

  testWidgets('CallLogScreen shows calls list on success', (WidgetTester tester) async {
    final mockCalls = [
      {
        'status': 'completed',
        'direction': 'incoming',
        'created_at': '2024-05-01T10:00:00Z',
        'contact_name': 'John Doe'
      },
      {
        'status': 'missed',
        'direction': 'incoming',
        'created_at': '2024-05-01T11:00:00Z',
        'contact_name': 'Jane Doe'
      }
    ];

    when(mockApiService.getCalls()).thenAnswer((_) async => Response(
      data: {'data': mockCalls},
      statusCode: 200,
      requestOptions: RequestOptions(path: ''),
    ));

    await tester.pumpWidget(createWidgetUnderTest());
    expect(find.byType(CircularProgressIndicator), findsOneWidget);

    await tester.pumpAndSettle();

    expect(find.text('John Doe'), findsOneWidget);
    expect(find.text('Jane Doe'), findsOneWidget);
    // Jane Doe is missed, should have red color (though checking color in widget tests is more complex, 
    // we can check if it exists)
  });

  testWidgets('CallLogScreen shows empty state when no calls', (WidgetTester tester) async {
    when(mockApiService.getCalls()).thenAnswer((_) async => Response(
      data: {'data': []},
      statusCode: 200,
      requestOptions: RequestOptions(path: ''),
    ));

    await tester.pumpWidget(createWidgetUnderTest());
    await tester.pumpAndSettle();

    expect(find.text('No recent calls'), findsOneWidget);
  });
}
