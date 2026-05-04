import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:whatsapp_pro_mobile/presentation/screens/contact_profile_screen.dart';
import 'package:whatsapp_pro_mobile/core/api_service.dart';
import '../../helpers/native_test_helpers.mocks.dart';

void main() {
  late MockApiService mockApiService;

  setUp(() {
    mockApiService = MockApiService();
  });

  Widget createWidgetUnderTest({Map<String, dynamic>? contact, int? id}) {
    return MaterialApp(
      home: RepositoryProvider<ApiService>.value(
        value: mockApiService,
        child: ContactProfileScreen(contact: contact, id: id),
      ),
    );
  }

  testWidgets('ContactProfileScreen shows contact info and tabs', (WidgetTester tester) async {
    final mockContact = {'id': 1, 'name': 'John Doe', 'phone_number': '1234567890'};
    
    when(mockApiService.getContact(1)).thenAnswer((_) async => Response(
      data: {'contact': mockContact, 'schema': []},
      statusCode: 200,
      requestOptions: RequestOptions(path: ''),
    ));
    when(mockApiService.getContactActivity(1)).thenAnswer((_) async => Response(
      data: {'data': []},
      statusCode: 200,
      requestOptions: RequestOptions(path: ''),
    ));

    await tester.pumpWidget(createWidgetUnderTest(id: 1, contact: mockContact));
    await tester.pumpAndSettle();

    expect(find.text('Contact Details'), findsOneWidget);
    expect(find.text('John Doe'), findsOneWidget);
    expect(find.text('1234567890'), findsOneWidget);
    expect(find.text('Profile'), findsOneWidget);
    expect(find.text('Activity'), findsOneWidget);
  });

  testWidgets('ContactProfileScreen switches to activity tab', (WidgetTester tester) async {
    final mockContact = {'id': 1, 'name': 'John Doe', 'phone_number': '1234567890'};
    final mockActivity = [
      {'event_type': 'message_sent', 'description': 'Message sent to John', 'created_at': DateTime.now().toIso8601String()}
    ];
    
    when(mockApiService.getContact(1)).thenAnswer((_) async => Response(
      data: {'contact': mockContact, 'schema': []},
      statusCode: 200,
      requestOptions: RequestOptions(path: ''),
    ));
    when(mockApiService.getContactActivity(1)).thenAnswer((_) async => Response(
      data: {'data': mockActivity},
      statusCode: 200,
      requestOptions: RequestOptions(path: ''),
    ));

    await tester.pumpWidget(createWidgetUnderTest(id: 1, contact: mockContact));
    await tester.pumpAndSettle();

    await tester.tap(find.text('Activity'));
    await tester.pumpAndSettle();

    expect(find.text('Message sent to John'), findsOneWidget);
  });
}
