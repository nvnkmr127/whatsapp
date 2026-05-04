import 'package:flutter/material.dart';
import 'package:isar/isar.dart';
import '../../data/models/message.dart';
import '../widgets/chat_bubble.dart';

class StarredMessagesScreen extends StatefulWidget {
  final Isar isar;
  const StarredMessagesScreen({super.key, required this.isar});

  @override
  State<StarredMessagesScreen> createState() => _StarredMessagesScreenState();
}

class _StarredMessagesScreenState extends State<StarredMessagesScreen> {
  late Stream<List<LocalMessage>> _starredStream;

  @override
  void initState() {
    super.initState();
    _starredStream = widget.isar.collection<LocalMessage>().filter().isStarredEqualTo(true).watch(fireImmediately: true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Starred Messages'),
        backgroundColor: const Color(0xFF075E54),
      ),
      body: StreamBuilder<List<LocalMessage>>(
        stream: _starredStream,
        builder: (context, snapshot) {
          if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.star_outline, size: 80, color: Colors.grey[300]),
                  const SizedBox(height: 20),
                  Text('No starred messages yet', style: TextStyle(color: Colors.grey[600], fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  const Text('Long-press messages to star them.', style: TextStyle(color: Colors.grey)),
                  const SizedBox(height: 16),
                  TextButton(
                    onPressed: () => Navigator.pop(context), 
                    child: const Text('Go back to Conversations')
                  ),
                ],
              ),
            );
          }

          final messages = snapshot.data!;
          return ListView.builder(
            itemCount: messages.length,
            padding: const EdgeInsets.symmetric(vertical: 20),
            itemBuilder: (context, i) {
              final msg = messages[i];
              return Padding(
                padding: const EdgeInsets.only(bottom: 20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Padding(
                      padding: const EdgeInsets.only(left: 15, bottom: 5),
                      child: Text('From: ${msg.direction == 'inbound' ? 'Customer' : 'You'}', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.grey)),
                    ),
                    ChatBubble(
                        content: msg.content ?? '',
                        isOutbound: msg.direction == 'outbound',
                        time: '${msg.createdAt.hour}:${msg.createdAt.minute}',
                        type: msg.type,
                        status: msg.status,
                        readAt: msg.readAt,
                    ),
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}
