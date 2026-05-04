import 'package:flutter/material.dart';
import 'dart:async';

class CallScreen extends StatefulWidget {
  final String name;
  final bool isIncoming;
  const CallScreen({super.key, required this.name, this.isIncoming = false});

  @override
  State<CallScreen> createState() => _CallScreenState();
}

class _CallScreenState extends State<CallScreen> {
  int _duration = 0;
  Timer? _timer;
  bool _isMuted = false;
  bool _isSpeaker = false;

  @override
  void initState() {
    super.initState();
    if (!widget.isIncoming) {
      _startTimer();
    }
  }

  void _startTimer() {
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      setState(() => _duration++);
    });
  }

  String _formatDuration(int sec) {
    final m = (sec ~/ 60).toString().padLeft(2, '0');
    final s = (sec % 60).toString().padLeft(2, '0');
    return '$m:$s';
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF075E54),
      body: Container(
        width: double.infinity,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [const Color(0xFF075E54), const Color(0xFF128C7E).withValues(alpha: 0.8)],
          ),
        ),
        child: Column(
          children: [
            const SizedBox(height: 100),
            const Icon(Icons.lock_outline, size: 14, color: Colors.white54),
            const Text('End-to-end encrypted', style: TextStyle(color: Colors.white54, fontSize: 12)),
            const SizedBox(height: 40),
            CircleAvatar(radius: 60, child: Text(widget.name[0], style: const TextStyle(fontSize: 40))),
            const SizedBox(height: 20),
            Text(widget.name, style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.bold)),
            const SizedBox(height: 10),
            Text(
              widget.isIncoming ? 'WHATSAPP CALL' : ( _duration > 0 ? _formatDuration(_duration) : 'Ringing...'),
              style: const TextStyle(color: Colors.white70, fontSize: 16),
            ),
            const Spacer(),
            if (widget.isIncoming) _buildIncomingActions() else _buildActiveActions(),
            const SizedBox(height: 60),
          ],
        ),
      ),
    );
  }

  Widget _buildActiveActions() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
      children: [
        _buildRoundButton(Icons.volume_up, _isSpeaker ? Colors.white : Colors.white24, () => setState(() => _isSpeaker = !_isSpeaker)),
        _buildRoundButton(Icons.videocam_off, Colors.white24, () {}),
        _buildRoundButton(Icons.mic_off, _isMuted ? Colors.white : Colors.white24, () => setState(() => _isMuted = !_isMuted)),
        _buildRoundButton(Icons.call_end, Colors.red, () => Navigator.pop(context)),
      ],
    );
  }

  Widget _buildIncomingActions() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
      children: [
        _buildRoundButton(Icons.call_end, Colors.red, () => Navigator.pop(context)),
        _buildRoundButton(Icons.call, Colors.green, () {
          setState(() => _startTimer());
          // In a real app, transition to active call state
        }),
      ],
    );
  }

  Widget _buildRoundButton(IconData icon, Color color, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(color: color == Colors.red || color == Colors.green ? color : color.withValues(alpha: 0.2), shape: BoxShape.circle),
        child: Icon(icon, color: Colors.white, size: 30),
      ),
    );
  }
}
