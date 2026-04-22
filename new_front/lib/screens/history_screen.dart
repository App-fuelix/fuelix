import 'dart:convert';
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import 'transaction_detail_screen.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});
  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  bool _isLoading = true;
  Map<String, dynamic> _grouped = {};
  String _totalSpent = '0 TND';
  String _totalLiters = '0 L';
  String _selectedFilter = 'All';
  String? _selectedVehicleId;
  String? _selectedVehicleName;
  List<Map<String, dynamic>> _vehicles = [];
  DateTime? _selectedMonth;

  final List<String> _filters = ['All', 'Fuel', 'Car Wash', 'Lubrifiants'];

  @override
  void initState() {
    super.initState();
    _loadHistory();
  }

  Future<void> _loadHistory() async {
    setState(() => _isLoading = true);
    final token = await AuthService.getToken();
    if (token == null) { setState(() => _isLoading = false); return; }

    try {
      // Load vehicles for filter
      final vehicleResult = await ApiService.getVehicles(token);
      if (vehicleResult['status'] == 200) {
        final list = vehicleResult['body']['vehicles'] as List? ?? [];
        setState(() => _vehicles = list.map((v) => Map<String, dynamic>.from(v as Map)).toList());
      }

      final result = await ApiService.getHistory(
        token,
        month: _selectedMonth != null ? _selectedMonth!.month.toString() : null,
        year: _selectedMonth != null ? _selectedMonth!.year.toString() : null,
      );
      if (result['status'] == 200) {
        final body = result['body'] as Map<String, dynamic>;
        setState(() {
          _grouped = Map<String, dynamic>.from(body['transactions'] ?? {});
          _totalSpent = body['total_spent'] ?? '0 TND';
          _totalLiters = body['total_liters'] ?? '0 L';
        });
      }
    } catch (_) {}
    finally { if (mounted) setState(() => _isLoading = false); }
  }

  // Filter by product type locally
  Map<String, List<Map<String, dynamic>>> _filteredGrouped() {
    final result = <String, List<Map<String, dynamic>>>{};
    for (final entry in _grouped.entries) {
      final list = (entry.value as List)
          .map((t) => Map<String, dynamic>.from(t as Map))
          .where((t) {
            // Filter by product type
            if (_selectedFilter != 'All') {
              final products = _parseProducts(t['authorized_products']);
              final filterKey = _filterToKey(_selectedFilter);
              if (!products.contains(filterKey)) return false;
            }
            // Filter by vehicle
            if (_selectedVehicleId != null) {
              final vehicleId = (t['vehicle'] as Map?)?['id']?.toString();
              if (vehicleId != _selectedVehicleId) return false;
            }
            return true;
          })
          .toList();
      if (list.isNotEmpty) result[entry.key] = list;
    }
    return result;
  }

  String _filterToKey(String filter) {
    switch (filter) {
      case 'Car Wash': return 'carwash';
      case 'Lubrifiants': return 'lubricants';
      default: return 'fuel';
    }
  }

  List<String> _parseProducts(dynamic raw) {
    if (raw is String && raw.isNotEmpty) {
      try {
        final decoded = jsonDecode(raw);
        if (decoded is List) return decoded.map((e) => e.toString()).toList();
      } catch (_) {}
    } else if (raw is List) {
      return raw.map((e) => e.toString()).toList();
    }
    return ['fuel'];
  }

  String _groupLabel(String key) {
    final now = DateTime.now();
    final thisMonth = '${_monthName(now.month)} ${now.year}';
    if (key == thisMonth) return 'This Month';
    return key;
  }

  String _monthName(int m) {
    const names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return names[m - 1];
  }

  // -------------------------------------------------------------------------
  // Filter dialogs
  // -------------------------------------------------------------------------

  void _showVehicleFilter() {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    if (_vehicles.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No vehicles found')),
      );
      return;
    }
    showModalBottomSheet(
      context: context,
      backgroundColor: isDark ? const Color(0xFF1B3B5A) : Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Filter by Vehicle',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold,
                    color: isDark ? Colors.white : const Color(0xFF0F2A44))),
            const SizedBox(height: 16),
            // All option
            GestureDetector(
              onTap: () {
                setState(() { _selectedVehicleId = null; _selectedVehicleName = null; });
                Navigator.pop(ctx);
              },
              child: Container(
                margin: const EdgeInsets.only(bottom: 10),
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: _selectedVehicleId == null
                      ? const Color(0xFFF2A945).withOpacity(0.15)
                      : (isDark ? const Color(0xFF0F2A44) : const Color(0xFFF4F6F8)),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: _selectedVehicleId == null
                        ? const Color(0xFFF2A945)
                        : Colors.transparent,
                  ),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.all_inclusive, color: Color(0xFFF2A945)),
                    const SizedBox(width: 12),
                    Text('All Vehicles',
                        style: TextStyle(fontWeight: FontWeight.w600,
                            color: isDark ? Colors.white : const Color(0xFF0F2A44))),
                  ],
                ),
              ),
            ),
            ..._vehicles.map((v) {
              final isSelected = _selectedVehicleId == v['id'];
              return GestureDetector(
                onTap: () {
                  setState(() {
                    _selectedVehicleId = v['id']?.toString();
                    _selectedVehicleName = v['model']?.toString();
                  });
                  Navigator.pop(ctx);
                },
                child: Container(
                  margin: const EdgeInsets.only(bottom: 10),
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: isSelected
                        ? const Color(0xFFF2A945).withOpacity(0.15)
                        : (isDark ? const Color(0xFF0F2A44) : const Color(0xFFF4F6F8)),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: isSelected ? const Color(0xFFF2A945) : Colors.transparent,
                    ),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.directions_car, color: Color(0xFFF2A945)),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(v['model'] ?? '—',
                              style: TextStyle(fontWeight: FontWeight.w600,
                                  color: isDark ? Colors.white : const Color(0xFF0F2A44))),
                          Text(v['plate_number'] ?? '—',
                              style: const TextStyle(fontSize: 12, color: Colors.grey)),
                        ],
                      ),
                    ],
                  ),
                ),
              );
            }),
          ],
        ),
      ),
    );
  }

  void _showMonthPicker() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedMonth ?? now,
      firstDate: DateTime(now.year - 2),
      lastDate: now,
      helpText: 'Select Month',
      builder: (ctx, child) => Theme(
        data: Theme.of(ctx).copyWith(
          colorScheme: const ColorScheme.light(primary: Color(0xFFF2A945)),
        ),
        child: child!,
      ),
    );
    if (picked != null) {
      setState(() => _selectedMonth = DateTime(picked.year, picked.month));
      _loadHistory();
    }
  }

  void _clearFilters() {
    setState(() {
      _selectedMonth = null;
      _selectedVehicleId = null;
      _selectedVehicleName = null;
      _selectedFilter = 'All';
    });
    _loadHistory();
  }

  // -------------------------------------------------------------------------
  // Build
  // -------------------------------------------------------------------------

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final navy = const Color(0xFF0F2A44);
    final primary = const Color(0xFFF2A945);
    final bg = isDark ? navy : const Color(0xFFF4F6F8);
    final filtered = _filteredGrouped();
    final hasActiveFilters = _selectedMonth != null || _selectedVehicleId != null;

    return Scaffold(
      backgroundColor: bg,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        automaticallyImplyLeading: false,
        title: Image.asset(isDark ? 'assets/images/logo_fuelix_2.png' : 'assets/images/logo_fuelix.png', width: 90),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFFF2A945)))
          : RefreshIndicator(
              onRefresh: _loadHistory,
              child: Column(
                children: [
                  // Header bar
                  Container(
                    color: navy,
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                    child: Row(
                      children: [
                        const Text('Transactions',
                            style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold)),
                        const Spacer(),
                        // Vehicle filter
                        GestureDetector(
                          onTap: _showVehicleFilter,
                          child: Icon(Icons.directions_car_outlined,
                              color: _selectedVehicleId != null ? primary : Colors.white70),
                        ),
                        const SizedBox(width: 12),
                        // Month filter
                        GestureDetector(
                          onTap: _showMonthPicker,
                          child: Icon(Icons.calendar_month_outlined,
                              color: _selectedMonth != null ? primary : Colors.white70),
                        ),
                        if (hasActiveFilters) ...[
                          const SizedBox(width: 12),
                          GestureDetector(
                            onTap: _clearFilters,
                            child: const Icon(Icons.close, color: Colors.redAccent, size: 20),
                          ),
                        ],
                      ],
                    ),
                  ),

                  // Active filter chips
                  if (hasActiveFilters)
                    Container(
                      color: bg,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      child: Row(
                        children: [
                          if (_selectedMonth != null)
                            _buildActiveChip('${_monthName(_selectedMonth!.month)} ${_selectedMonth!.year}', isDark, primary),
                          if (_selectedVehicleId != null && _selectedVehicleName != null)
                            _buildActiveChip(_selectedVehicleName!, isDark, primary),
                        ],
                      ),
                    ),

                  // Product filter chips
                  Container(
                    color: bg,
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    child: SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: _filters.map((f) {
                          final selected = _selectedFilter == f;
                          return Padding(
                            padding: const EdgeInsets.only(right: 8),
                            child: GestureDetector(
                              onTap: () => setState(() => _selectedFilter = f),
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 8),
                                decoration: BoxDecoration(
                                  color: selected ? primary : (isDark ? const Color(0xFF1B3B5A) : Colors.white),
                                  borderRadius: BorderRadius.circular(20),
                                  boxShadow: selected ? [] : [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 4)],
                                ),
                                child: Text(f, style: TextStyle(
                                  color: selected ? Colors.white : (isDark ? Colors.white70 : Colors.black87),
                                  fontWeight: selected ? FontWeight.bold : FontWeight.normal,
                                )),
                              ),
                            ),
                          );
                        }).toList(),
                      ),
                    ),
                  ),

                  // Stats row
                  if (_grouped.isNotEmpty)
                    Container(
                      color: bg,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                      child: Row(
                        children: [
                          Text('Total: $_totalSpent',
                              style: TextStyle(fontSize: 13, color: isDark ? Colors.white60 : Colors.grey[600])),
                          const SizedBox(width: 16),
                          Text('$_totalLiters',
                              style: TextStyle(fontSize: 13, color: isDark ? Colors.white60 : Colors.grey[600])),
                        ],
                      ),
                    ),

                  // Transaction list
                  Expanded(
                    child: filtered.isEmpty
                        ? Center(child: Text('No transactions found',
                            style: TextStyle(color: isDark ? Colors.white54 : Colors.grey)))
                        : ListView.builder(
                            padding: const EdgeInsets.symmetric(horizontal: 16),
                            itemCount: filtered.length,
                            itemBuilder: (ctx, i) {
                              final groupKey = filtered.keys.elementAt(i);
                              final txList = filtered[groupKey]!;
                              return Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Padding(
                                    padding: const EdgeInsets.symmetric(vertical: 10),
                                    child: Text(_groupLabel(groupKey),
                                        style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600,
                                            color: isDark ? Colors.white60 : Colors.grey[600])),
                                  ),
                                  ...txList.map((t) => _buildTransactionTile(t, isDark, primary)),
                                ],
                              );
                            },
                          ),
                  ),
                ],
              ),
            ),
      bottomNavigationBar: _buildBottomNav(isDark, primary, navy),
    );
  }

  Widget _buildActiveChip(String label, bool isDark, Color primary) {
    return Container(
      margin: const EdgeInsets.only(right: 8),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      decoration: BoxDecoration(
        color: primary.withOpacity(0.15),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: primary.withOpacity(0.4)),
      ),
      child: Text(label, style: TextStyle(fontSize: 12, color: primary, fontWeight: FontWeight.w600)),
    );
  }

  Widget _buildTransactionTile(Map<String, dynamic> t, bool isDark, Color primary) {
    final amount = double.tryParse(t['amount']?.toString() ?? '0') ?? 0;
    final date = t['date']?.toString() ?? '';
    final time = t['time']?.toString() ?? '';
    final products = _parseProducts(t['authorized_products']);
    final firstProduct = products.isNotEmpty ? products[0] : 'fuel';
    final iconData = _productIcon(firstProduct);
    final label = _productLabel(products);

    return GestureDetector(
      onTap: () => Navigator.push(context,
          MaterialPageRoute(builder: (_) => TransactionDetailScreen(transaction: t))),
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1B3B5A) : Colors.white,
          borderRadius: BorderRadius.circular(14),
          boxShadow: isDark ? [] : [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 8)],
        ),
        child: Row(
          children: [
            Container(
              width: 48, height: 48,
              decoration: BoxDecoration(color: primary.withOpacity(0.1), borderRadius: BorderRadius.circular(12)),
              child: Icon(iconData, color: primary, size: 26),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label, style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold,
                      color: isDark ? Colors.white : const Color(0xFF0F2A44))),
                  const SizedBox(height: 3),
                  Text('$date - $time', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                ],
              ),
            ),
            Text('-${amount.toStringAsFixed(0)} TND',
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.redAccent)),
          ],
        ),
      ),
    );
  }

  IconData _productIcon(String product) {
    switch (product) {
      case 'carwash': return Icons.local_car_wash;
      case 'lubricants': return Icons.oil_barrel;
      default: return Icons.local_gas_station;
    }
  }

  String _productLabel(List<String> products) {
    if (products.isEmpty) return 'Fuel';
    return products.map((p) {
      switch (p) {
        case 'carwash': return 'Car Wash';
        case 'lubricants': return 'Lubrifiants';
        default: return 'Fuel';
      }
    }).join(' + ');
  }

  Widget _buildBottomNav(bool isDark, Color primary, Color navy) {
    return BottomNavigationBar(
      currentIndex: 2,
      onTap: (i) {
        if (i == 0) Navigator.pushReplacementNamed(context, '/home');
        if (i == 1) Navigator.pushReplacementNamed(context, '/card');
        if (i == 3) Navigator.pushReplacementNamed(context, '/profile');
      },
      type: BottomNavigationBarType.fixed,
      backgroundColor: isDark ? navy : Colors.white,
      selectedItemColor: primary,
      unselectedItemColor: Colors.grey,
      items: const [
        BottomNavigationBarItem(icon: Icon(Icons.home_filled), label: 'Home'),
        BottomNavigationBarItem(icon: Icon(Icons.credit_card), label: 'Card'),
        BottomNavigationBarItem(icon: Icon(Icons.history), label: 'History'),
        BottomNavigationBarItem(icon: Icon(Icons.person_outline), label: 'Profile'),
      ],
    );
  }
}
