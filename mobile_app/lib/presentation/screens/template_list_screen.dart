import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../logic/blocs/auth_bloc.dart';
import '../../core/api_service.dart';
import '../../core/cache_service.dart';
import 'template_preview_screen.dart';
import 'template_draft_screen.dart';

class TemplateListScreen extends StatefulWidget {
  const TemplateListScreen({super.key});

  @override
  State<TemplateListScreen> createState() => _TemplateListScreenState();
}

class _TemplateListScreenState extends State<TemplateListScreen> {
  final List<dynamic> _templates = [];
  bool _isLoading = false;
  bool _isLoadingMore = false;
  bool _hasNextPage = true;
  int _currentPage = 1;
  String _searchQuery = '';
  String _selectedStatus = 'all';

  final TextEditingController _searchController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _fetchTemplates(isRefresh: true);
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _scrollController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
      if (!_isLoadingMore && _hasNextPage) {
        _fetchTemplates(isRefresh: false);
      }
    }
  }

  Future<void> _fetchTemplates({bool isRefresh = false}) async {
    if (isRefresh) {
      setState(() {
        _isLoading = true;
        _currentPage = 1;
        _templates.clear();
        _hasNextPage = true;
      });

      if (_searchQuery.isEmpty && _selectedStatus == 'all') {
        final cached = await CacheService.getCachedTemplates();
        if (!mounted) return;
        if (cached.isNotEmpty) {
          setState(() {
            _templates.addAll(cached);
            _isLoading = false;
          });
        }
      }
    } else {
      setState(() => _isLoadingMore = true);
    }

    try {
      final response = await context.read<ApiService>().getTemplates(
        search: _searchQuery,
        status: _selectedStatus,
        page: _currentPage,
      );
      if (!mounted) return;

      final data = response.data;
      final List newItems = data['data'] ?? [];

      if (_currentPage == 1 && _searchQuery.isEmpty && _selectedStatus == 'all') {
        await CacheService.cacheTemplates(newItems);
      }

      setState(() {
        if (_currentPage == 1) _templates.clear();
        _templates.addAll(newItems);
        _hasNextPage = data['next_page_url'] != null;
        _isLoading = false;
        _isLoadingMore = false;
        _currentPage++;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
        _isLoadingMore = false;
      });
    }
  }

  Future<void> _toggleTemplate(int id) async {
    try {
      await context.read<ApiService>().toggleTemplate(id);
      setState(() {
        final index = _templates.indexWhere((t) => t['id'] == id);
        if (index != -1) {
          _templates[index]['is_paused'] = !(_templates[index]['is_paused'] ?? false);
        }
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    }
  }

  void _onSearchChanged(String query) {
    if (_debounce?.isActive ?? false) _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 500), () {
      setState(() => _searchQuery = query);
      _fetchTemplates(isRefresh: true);
    });
  }

  @override
  Widget build(BuildContext context) {
    final authState = context.read<AuthBloc>().state;
    final bool isAdmin = authState is Authenticated && (authState.user?['role'] == 'admin' || authState.user?['role'] == 'owner');

    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        if (state is Authenticated) {
          _fetchTemplates(isRefresh: true);
        }
      },
      child: Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        appBar: AppBar(
          title: const Text('WhatsApp Templates', style: TextStyle(fontWeight: FontWeight.bold)),
          backgroundColor: Theme.of(context).appBarTheme.backgroundColor ?? const Color(0xFF128C7E),
          foregroundColor: Theme.of(context).appBarTheme.foregroundColor ?? Colors.white,
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(110),
            child: Column(
              children: [
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  child: TextField(
                    controller: _searchController,
                    onChanged: _onSearchChanged,
                    decoration: InputDecoration(
                      hintText: 'Search templates...',
                      prefixIcon: const Icon(Icons.search),
                      filled: true,
                      fillColor: Theme.of(context).brightness == Brightness.dark
                          ? Colors.white.withValues(alpha: 0.15)
                          : Colors.white,
                      contentPadding: EdgeInsets.zero,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                        borderSide: BorderSide.none,
                      ),
                    ),
                  ),
                ),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.only(bottom: 12, left: 12),
                  child: Row(
                    children: [
                      _buildFilterChip('all', 'All'),
                      _buildFilterChip('approved', 'Approved'),
                      _buildFilterChip('pending', 'Pending'),
                      _buildFilterChip('rejected', 'Rejected'),
                      _buildFilterChip('draft', 'Draft'),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        body: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : RefreshIndicator(
                onRefresh: () => _fetchTemplates(isRefresh: true),
                child: _templates.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.description_outlined, size: 64, color: Colors.grey[300]),
                            const SizedBox(height: 16),
                            Text('No templates found', style: TextStyle(color: Colors.grey[600], fontSize: 18, fontWeight: FontWeight.bold)),
                            const SizedBox(height: 8),
                            Text('Create a draft to get started', style: TextStyle(color: Colors.grey[400])),
                          ],
                        ),
                      )
                    : ListView.builder(
                        controller: _scrollController,
                        padding: const EdgeInsets.all(12),
                        itemCount: _templates.length + (_isLoadingMore ? 1 : 0),
                        itemBuilder: (context, index) {
                          if (index == _templates.length) {
                            return const Padding(
                              padding: EdgeInsets.all(20),
                              child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                            );
                          }
                          final template = _templates[index];
                          final bool isDraft = template['status'] == 'DRAFT';

                          if (isDraft) {
                            return Dismissible(
                              key: Key('template_${template['id']}'),
                              direction: DismissDirection.endToStart,
                              background: Container(
                                alignment: Alignment.centerRight,
                                padding: const EdgeInsets.only(right: 20),
                                margin: const EdgeInsets.only(bottom: 12),
                                decoration: BoxDecoration(color: Colors.red, borderRadius: BorderRadius.circular(12)),
                                child: const Icon(Icons.delete, color: Colors.white),
                              ),
                              confirmDismiss: (dir) => _showDeleteTemplateConfirm(template['id']),
                              child: _buildTemplateCard(template, isAdmin),
                            );
                          }
                          return _buildTemplateCard(template, isAdmin);
                        },
                      ),
              ),
        floatingActionButton: isAdmin
            ? FloatingActionButton(
                heroTag: 'template_list_fab',
                backgroundColor: const Color(0xFF128C7E),
                onPressed: () async {
                  final result = await Navigator.push(
                    context,
                    MaterialPageRoute(builder: (context) => const TemplateDraftScreen()),
                  );
                  if (result == true) _fetchTemplates(isRefresh: true);
                },
                child: const Icon(Icons.add, color: Colors.white),
              )
            : null,
      ),
    );
  }

  Widget _buildTemplateCard(Map<String, dynamic> template, bool isAdmin) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 0.5,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        title: Text(template['name'] ?? 'Unnamed', style: const TextStyle(fontWeight: FontWeight.bold)),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(template['category'] ?? 'Utility', style: const TextStyle(fontSize: 12, color: Colors.grey)),
            const SizedBox(height: 4),
            Text(
              template['content'] ?? '',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 13,
                color: Theme.of(context).brightness == Brightness.dark
                    ? Colors.white60
                    : Colors.black54,
              ),
            ),
          ],
        ),
        trailing: Column(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            _buildStatusBadge(template['status']),
            if (isAdmin) ...[
              const SizedBox(height: 2),
              SizedBox(
                height: 20,
                child: Transform.scale(
                  scale: 0.75,
                  child: Switch(
                    value: !(template['is_paused'] ?? false),
                    onChanged: (val) => _toggleTemplate(template['id']),
                    activeColor: const Color(0xFF128C7E),
                    materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                ),
              ),
            ] else ...[
              const SizedBox(height: 2),
              Text(template['language'] ?? 'en', style: const TextStyle(fontSize: 10, color: Colors.grey)),
            ],
          ],
        ),
        onTap: () async {
          final result = await Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => TemplatePreviewScreen(
                templateId: template['id'],
                templateName: template['name'] ?? 'Template',
              ),
            ),
          );
          if (result == true) _fetchTemplates(isRefresh: true);
        },
      ),
    );
  }

  Future<bool?> _showDeleteTemplateConfirm(int id) async {
    return await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Draft?'),
        content: const Text('This will permanently remove this template draft.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx, true);
              _deleteTemplate(id);
            },
            child: const Text('Delete', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  Future<void> _deleteTemplate(int id) async {
    try {
      await context.read<ApiService>().deleteTemplate(id);
      setState(() {
        _templates.removeWhere((t) => t['id'] == id);
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Template draft deleted')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    }
  }

  Widget _buildFilterChip(String status, String label) {
    final isSelected = _selectedStatus == status;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: FilterChip(
        label: Text(label),
        selected: isSelected,
        onSelected: (val) {
          setState(() => _selectedStatus = status);
          _fetchTemplates(isRefresh: true);
        },
        selectedColor: Colors.white,
        checkmarkColor: Theme.of(context).primaryColor,
        backgroundColor: Colors.white.withValues(alpha: 0.15),
        labelStyle: TextStyle(
          color: isSelected ? Theme.of(context).primaryColor : Colors.white,
          fontSize: 12,
          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
        ),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20), side: BorderSide.none),
      ),
    );
  }

  Widget _buildStatusBadge(String? status) {
    final color = _getStatusColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        (status ?? 'pending').toString().toUpperCase(),
        style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: color),
      ),
    );
  }

  Color _getStatusColor(String? status) {
    switch (status?.toLowerCase()) {
      case 'approved': return Colors.green;
      case 'rejected': return Colors.red;
      case 'pending': return Colors.orange;
      case 'draft': return Colors.blue;
      default: return Colors.grey;
    }
  }
}
