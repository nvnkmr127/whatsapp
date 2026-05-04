import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';
import 'package:whatsapp_pro_mobile/presentation/screens/broadcasting_screen.dart';
import '../../helpers/native_test_helpers.mocks.dart';

void main() {
  late MockApiService mockApiService;

  setUp(() {
    mockApiService = MockApiService();
  });

  Widget createWidgetUnderTest() {
    return MaterialApp(
      home: BroadcastingScreen(apiService: mockApiService),
    );
  }

  testWidgets('BroadcastingScreen shows loading initially and then data', (WidgetTester tester) async {
    when(mockApiService.getTemplates()).thenAnswer((_) async => Response(
      data: {'data': [{'id': 1, 'name': 'Template 1', 'body': 'Hello'}]},
      statusCode: 200,
      requestOptions: RequestOptions(path: ''),
    ));
    when(mockApiService.getTags()).thenAnswer((_) async => Response(
      data: [{'id': 1, 'name': 'Tag 1'}],
      statusCode: 200,
      requestOptions: RequestOptions(path: ''),
    ));

    await tester.pumpWidget(createWidgetUnderTest());

    expect(find.byType(CircularProgressIndicator), findsOneWidget);

    await tester.pumpAndSettle();

    expect(find.byType(CircularProgressIndicator), findsNothing);
    expect(find.text('Start Broadcast'), findsOneWidget);
    expect(find.text('Campaign Name'), findsOneWidget);
  });

}
