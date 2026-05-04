import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../core/api_service.dart';

class BroadcastHistoryScreen extends StatefulWidget {
  final ApiService apiService;
  const BroadcastHistoryScreen({super.key, required this.apiService});

  @override
  State<BroadcastHistoryScreen> createState() => _BroadcastHistoryScreenState();
}

class _BroadcastHistoryScreenState extends State<BroadcastHistoryScreen> {
  List<dynamic> _campaigns = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _fetch();
  }

  Future<void> _fetch() async {
    setState(() => _loading = true);
    try {
      final response = await widget.apiService.getRecentCampaigns();
      setState(() {
        final data = response.data;
        _campaigns = data is List ? data : (data['data'] ?? []);
        _loading = false;
      });
    } catch (e) {
      if (mounted) {
        setState(() {
          _loading = false;
          _campaigns = [];
        });
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Broadcast History'),
        backgroundColor: const Color(0xFF128C7E),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh), 
            tooltip: 'Refresh History',
            onPressed: _fetch
          ),
        ],
      ),
      body: _loading 
        ? const Center(child: CircularProgressIndicator())
        : RefreshIndicator(
            onRefresh: _fetch,
            child: _campaigns.isEmpty 
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.campaign_outlined, size: 80, color: Colors.grey[300]),
                      const SizedBox(height: 16),
                      const Text('No broadcast history found.', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey)),
                      const SizedBox(height: 8),
                      TextButton(onPressed: _fetch, child: const Text('RETRY')),
                    ],
                  ),
                )
              : ListView.builder(
                  itemCount: _campaigns.length,
                  itemBuilder: (context, index) {
                    final c = _campaigns[index];
                    final date = DateTime.tryParse(c['created_at'] ?? '') ?? DateTime.now();
                    
                    return Card(
                      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      child: ListTile(
                        title: Text(c['name'] ?? 'Untitled Campaign', style: const TextStyle(fontWeight: FontWeight.bold)),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Sent: ${DateFormat('MMM dd, yyyy HH:mm').format(date)}'),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                _buildBadge(c['status'] ?? 'PENDING', _getStatusColor(c['status'])),
                                const SizedBox(width: 8),
                                Text('Recipients: ${c['total_recipients'] ?? 0}'),
                              ],
                            ),
                          ],
                        ),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () => _showCampaignDetails(c),
                      ),
                    );
                  },
                ),
          ),
    );
  }

  void _showCampaignDetails(dynamic c) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(c['name'] ?? 'Campaign Details'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _detailRow('Status', c['status'] ?? 'UNKNOWN', color: _getStatusColor(c['status'])),
            _detailRow('Total Recipients', (c['total_recipients'] ?? 0).toString()),
            _detailRow('Sent Successfully', (c['sent_count'] ?? 0).toString()),
            _detailRow('Failed', (c['failed_count'] ?? 0).toString()),
            const Divider(),
            _detailRow('Template', c['template']?['name'] ?? 'Deleted Template'),
            _detailRow('Audience', c['tag']?['name'] ?? 'All Contacts'),
            const SizedBox(height: 10),
            Text('Created: ${DateFormat('yyyy-MM-dd HH:mm').format(DateTime.parse(c['created_at']))}', style: const TextStyle(fontSize: 11, color: Colors.grey)),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('CLOSE')),
        ],
      ),
    );
  }

  Widget _detailRow(String label, String value, {Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey, fontSize: 13)),
          Text(value, style: TextStyle(fontWeight: FontWeight.bold, color: color, fontSize: 13)),
        ],
      ),
    );
  }

  Widget _buildBadge(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)),
      child: Text(text, style: TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.bold)),
    );
  }

  Color _getStatusColor(String? status) {
    switch (status?.toUpperCase()) {
      case 'COMPLETED': return Colors.green;
      case 'SENDING': return Colors.blue;
      case 'FAILED': return Colors.red;
      default: return Colors.orange;
    }
  }
}
