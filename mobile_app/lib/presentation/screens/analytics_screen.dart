import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shimmer/shimmer.dart';
import '../../logic/blocs/auth_bloc.dart';
import '../../core/api_service.dart';
import '../../data/models/api_models.dart';
import '../widgets/stat_chart.dart';
import '../widgets/error_view.dart';

class AnalyticsScreen extends StatefulWidget {
  final ApiService apiService;
  const AnalyticsScreen({super.key, required this.apiService});

  @override
  State<AnalyticsScreen> createState() => _AnalyticsScreenState();
}

class _AnalyticsScreenState extends State<AnalyticsScreen> {
  AnalyticsData? _data;
  bool _loading = true;
  String? _errorMessage;
  final String _period = 'today';

  @override
  void initState() {
    super.initState();
    _fetch();
  }

  Future<void> _fetch() async {
    try {
      setState(() {
        _loading = true;
        _errorMessage = null;
      });
      
      final response = await widget.apiService.getDashboardAnalytics(period: _period);
      
      if (!mounted) return;
      
      setState(() {
        _data = AnalyticsData.fromJson(response.data);
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _errorMessage = 'Unable to load dashboard data. Please check your connection.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        if (state is Authenticated) {
          _fetch();
        }
      },
      child: Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        appBar: AppBar(
          elevation: 0,
          backgroundColor: const Color(0xFF128C7E),
          foregroundColor: Colors.white,
          title: const Text('Dashboard', style: TextStyle(fontWeight: FontWeight.bold)),
          actions: [
            IconButton(
              onPressed: _fetch, 
              icon: const Icon(Icons.refresh),
              tooltip: 'Refresh Data',
            ),
          ],
        ),
        body: _buildBody(),
      ),
    );
  }

  Widget _buildBody() {
    if (_errorMessage != null) {
      return ErrorView(
        message: _errorMessage!,
        onRetry: _fetch,
      );
    }

    return RefreshIndicator(
      onRefresh: _fetch,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _loading ? _buildCreditShimmer() : _buildCreditCard(),
          const SizedBox(height: 20),
          
          _buildSectionTitle('Active Overview'),
          const SizedBox(height: 8),
          _loading ? _buildStatsShimmer() : _buildActiveStats(),
          
          const SizedBox(height: 24),
          _buildSectionTitle('Message Activity (7D)'),
          const SizedBox(height: 12),
          _loading ? _buildChartShimmer() : _buildActivityChart(),
          
          const SizedBox(height: 24),
          _buildSectionTitle('Delivery Performance (30D)'),
          const SizedBox(height: 12),
          _loading ? _buildPerformanceShimmer() : _buildPerformanceMetrics(),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.blueGrey, letterSpacing: 0.5));
  }

  Widget _buildCreditCard() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF075E54), Color(0xFF128C7E)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(color: const Color(0xFF128C7E).withValues(alpha: 0.3), blurRadius: 10, offset: const Offset(0, 5)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Available Credits', style: TextStyle(color: Colors.white70, fontSize: 14)),
          const SizedBox(height: 4),
          Text('\$${_data?.credits.toStringAsFixed(2) ?? '0.00'}', 
            style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),
          Wrap(
            spacing: 10,
            runSpacing: 8,
            children: [
              _buildSmallChip(Icons.trending_up, 'Sent Today: ${_data?.summary['sent_today'] ?? 0}'),
              _buildSmallChip(Icons.calendar_today, '30D: ${_data?.summary['total_30d'] ?? 0}'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSmallChip(IconData icon, String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(8)),
      child: Row(
        children: [
          Icon(icon, size: 12, color: Colors.white),
          const SizedBox(width: 4),
          Text(label, style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }

  Widget _buildActiveStats() {
    return Row(
      children: [
        Expanded(child: _buildSimpleStatCard('Conversations', _data?.conversations['active']?.toString() ?? '0', Colors.blue)),
        const SizedBox(width: 12),
        Expanded(child: _buildSimpleStatCard('Unread Chats', _data?.conversations['unread']?.toString() ?? '0', Colors.red)),
      ],
    );
  }

  Widget _buildSimpleStatCard(String label, String value, Color color) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Text(value, style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: color)),
            const SizedBox(height: 4),
            Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
          ],
        ),
      ),
    );
  }

  Widget _buildActivityChart() {
    final activity = _data?.messageActivity;
    final labels = activity?['labels'] as List?;
    final values = activity?['values'] as List?;

    if (labels == null || values == null || values.isEmpty) {
      return const Center(child: Text('No activity data available.'));
    }

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: SimpleBarChart(
          values: values.map((v) => (v as num).toDouble()).toList(),
          labels: labels.map((l) => l.toString()).toList(),
          color: const Color(0xFF128C7E),
        ),
      ),
    );
  }

  Widget _buildPerformanceMetrics() {
    final metrics = _data?.messages30d;
    return Column(
      children: [
        _buildMetricRow('Delivery Rate', '${metrics?['delivery_rate'] ?? 0}%', Icons.done_all, Colors.green),
        const SizedBox(height: 10),
        _buildMetricRow('Read Rate', '${metrics?['read_rate'] ?? 0}%', Icons.visibility, Colors.orange),
      ],
    );
  }

  Widget _buildMetricRow(String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: Theme.of(context).cardColor, borderRadius: BorderRadius.circular(15)),
      child: Row(
        children: [
          Icon(icon, color: color),
          const SizedBox(width: 16),
          Text(label, style: const TextStyle(fontWeight: FontWeight.w500)),
          const Spacer(),
          Text(value, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        ],
      ),
    );
  }

  // --- Shimmers ---

  Widget _buildCreditShimmer() {
    return Shimmer.fromColors(
      baseColor: Colors.grey[300]!,
      highlightColor: Colors.grey[100]!,
      child: Container(
        height: 140,
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20)),
      ),
    );
  }

  Widget _buildStatsShimmer() {
    return Row(
      children: [
        Expanded(child: _buildBoxShimmer(80)),
        const SizedBox(width: 12),
        Expanded(child: _buildBoxShimmer(80)),
      ],
    );
  }

  Widget _buildChartShimmer() {
    return _buildBoxShimmer(180);
  }

  Widget _buildPerformanceShimmer() {
    return Column(
      children: [
        _buildBoxShimmer(60),
        const SizedBox(height: 10),
        _buildBoxShimmer(60),
      ],
    );
  }

  Widget _buildBoxShimmer(double height) {
    return Shimmer.fromColors(
      baseColor: Colors.grey[300]!,
      highlightColor: Colors.grey[100]!,
      child: Container(
        height: height,
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(15)),
      ),
    );
  }
}

