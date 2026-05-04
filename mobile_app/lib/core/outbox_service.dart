import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import '../data/repositories/chat_repository.dart';

class OutboxService {
  final ChatRepository _repository;
  StreamSubscription<List<ConnectivityResult>>? _subscription;
  bool _isSyncing = false;

  OutboxService(this._repository);

  void init() {
    _subscription = Connectivity().onConnectivityChanged.listen((results) {
      if (results.any((r) => r != ConnectivityResult.none)) {
        _triggerSync();
      }
    });
    
    // Initial check
    _triggerSync();
  }

  Future<void> _triggerSync() async {
    if (_isSyncing) return;
    _isSyncing = true;
    
    try {
      await _repository.retryFailedMessages();
    } finally {
      _isSyncing = false;
    }
  }

  void dispose() {
    _subscription?.cancel();
  }
}
