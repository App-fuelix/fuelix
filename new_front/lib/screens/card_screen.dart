import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';

class CardScreen extends StatefulWidget {
  const CardScreen({super.key});
  @override
  State<CardScreen> createState() => _CardScreenState();
}

class _CardScreenState extends State<CardScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _card;
  List<Map<String, dynamic>> _vehicles = [];
  String _userName = '';
  final Set<String> _selectedProducts = {};

  static const _products = [
    {'key': 'fuel', 'label': 'Fuel', 'icon': Icons.local_gas_station},
    {'key': 'carwash', 'label': 'Car wash', 'icon': Icons.local_car_wash},
    {'key': 'lubricants', 'label': 'Lubrifiants', 'icon': Icons.oil_barrel},
  ];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final token = await AuthService.getToken();
    final user = await AuthService.getUser();
    setState(() => _userName = user['name'] ?? '');

    if (token == null) { setState(() => _isLoading = false); return; }

    try {
      final cardResult = await ApiService.getCard(token);
      if (cardResult['status'] == 200) {
        setState(() => _card = cardResult['body'] as Map<String, dynamic>);
      }

      final vehicleResult = await ApiService.getVehicles(token);
      if (vehicleResult['status'] == 200) {
        final list = vehicleResult['body']['vehicles'] as List? ?? [];
        setState(() => _vehicles = list.map((v) => Map<String, dynamic>.from(v as Map)).toList());
      }
    } catch (_) {}
    finally { if (mounted) setState(() => _isLoading = false); }
  }

  // -------------------------------------------------------------------------
  // Scan to Pay — vehicle selection then QR
  // -------------------------------------------------------------------------

  void _onScanToPay() {
    if (_vehicles.isEmpty) {
      _showNoVehicleDialog();
      return;
    }
    if (_vehicles.length == 1) {
      _showQrCode(_vehicles[0]);
      return;
    }
    _showVehicleSelector();
  }

  void _showNoVehicleDialog() {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: isDark ? const Color(0xFF1B3B5A) : Colors.white,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('No Vehicle Found', style: TextStyle(fontWeight: FontWeight.bold)),
        content: const Text('Please add a vehicle to your account before paying.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('OK', style: TextStyle(color: Color(0xFFF2A945)))),
        ],
      ),
    );
  }

  void _showVehicleSelector() {
    final isDark = Theme.of(context).brightness == Brightness.dark;
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
            Text('Select Vehicle', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: isDark ? Colors.white : const Color(0xFF0F2A44))),
            const SizedBox(height: 6),
            Text('Choose the vehicle for this payment', style: TextStyle(fontSize: 13, color: isDark ? Colors.white54 : Colors.grey)),
            const SizedBox(height: 20),
            ..._vehicles.map((v) => GestureDetector(
              onTap: () {
                Navigator.pop(ctx);
                _showQrCode(v);
              },
              child: Container(
                margin: const EdgeInsets.only(bottom: 12),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: isDark ? const Color(0xFF0F2A44) : const Color(0xFFF4F6F8),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFF2A945).withOpacity(0.3)),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF2A945).withOpacity(0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.directions_car, color: Color(0xFFF2A945)),
                    ),
                    const SizedBox(width: 14),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(v['model'] ?? '—', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: isDark ? Colors.white : const Color(0xFF0F2A44))),
                        Text(v['plate_number'] ?? '—', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                      ],
                    ),
                    const Spacer(),
                    const Icon(Icons.arrow_forward_ios, size: 14, color: Colors.grey),
                  ],
                ),
              ),
            )),
          ],
        ),
      ),
    );
  }

  void _showQrCode(Map<String, dynamic> vehicle) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final qrData = jsonEncode({
      'card_id': _card!['id'],
      'vehicle_id': vehicle['id'],
      'vehicle_model': vehicle['model'],
      'plate': vehicle['plate_number'],
      'balance': _card!['balance_raw'],
      'products': _selectedProducts.toList(),
      'timestamp': DateTime.now().toIso8601String(),
    });

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? const Color(0xFF1B3B5A) : Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(30),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              children: [
                GestureDetector(onTap: () => Navigator.pop(ctx), child: const Icon(Icons.close, color: Colors.grey)),
                const SizedBox(width: 12),
                Text('Scan to Pay', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: isDark ? Colors.white : const Color(0xFF0F2A44))),
              ],
            ),
            const SizedBox(height: 24),

            // Vehicle info
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              decoration: BoxDecoration(
                color: const Color(0xFFF2A945).withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.directions_car, color: Color(0xFFF2A945), size: 18),
                  const SizedBox(width: 8),
                  Text('${vehicle['model']} — ${vehicle['plate_number']}',
                      style: const TextStyle(color: Color(0xFFF2A945), fontWeight: FontWeight.w600)),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // QR Code
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 20)],
              ),
              child: QrImageView(
                data: qrData,
                version: QrVersions.auto,
                size: 220,
                backgroundColor: Colors.white,
              ),
            ),
            const SizedBox(height: 20),

            Text('Present this QR code at the station',
                style: TextStyle(color: isDark ? Colors.white54 : Colors.grey, fontSize: 13)),
            const SizedBox(height: 6),
            Text('Balance: ${_card!['balance']}',
                style: const TextStyle(color: Color(0xFFF2A945), fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  // -------------------------------------------------------------------------

  // -------------------------------------------------------------------------





  // -------------------------------------------------------------------------
  // Build
  // -------------------------------------------------------------------------

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final navy = const Color(0xFF0F2A44);
    final primary = const Color(0xFFF2A945);
    final bg = isDark ? navy : const Color(0xFFF4F6F8);

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
          : _card == null
              ? _buildNoCard(isDark)
              : RefreshIndicator(
                  onRefresh: _loadData,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Digital Card', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: isDark ? Colors.white : navy)),
                        const SizedBox(height: 4),
                        if (_card!['card_plan_name'] != null)
                          Text(
                            _card!['card_plan_name'],
                            style: TextStyle(
                              fontSize: 14,
                              color: isDark ? Colors.white60 : Colors.grey,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        const SizedBox(height: 24),
                        _buildCardWidget(isDark),
                        const SizedBox(height: 28),

                        // Balance
                        Text('Balance', style: TextStyle(fontSize: 14, color: isDark ? Colors.white60 : Colors.grey)),
                        const SizedBox(height: 4),
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text(
                              (_card!['balance_raw'] as num?)?.toStringAsFixed(0) ?? '0',
                              style: TextStyle(fontSize: 40, fontWeight: FontWeight.bold, color: primary),
                            ),
                            const SizedBox(width: 6),
                            Padding(
                              padding: const EdgeInsets.only(bottom: 6),
                              child: Text('TND', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: primary)),
                            ),
                          ],
                        ),
                        const SizedBox(height: 24),

                        // Authorized products
                        Text('Authorized products', style: TextStyle(fontSize: 14, color: isDark ? Colors.white60 : Colors.grey)),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            _buildProduct('fuel', Icons.local_gas_station, 'Fuel', isDark),
                            const SizedBox(width: 12),
                            _buildProduct('carwash', Icons.local_car_wash, 'Car wash', isDark),
                            const SizedBox(width: 12),
                            _buildProduct('lubricants', Icons.oil_barrel, 'Lubrifiants', isDark),
                          ],
                        ),
                        const SizedBox(height: 32),

                        // Scan to Pay
                        SizedBox(
                          width: double.infinity,
                          height: 60,
                          child: ElevatedButton.icon(
                            onPressed: _onScanToPay,
                            icon: const Icon(Icons.qr_code_scanner, color: Colors.white, size: 28),
                            label: const Text('Scan to Pay', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white)),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: primary,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                              elevation: 0,
                            ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        const Center(child: Text('Secured digital payment', style: TextStyle(fontSize: 12, color: Colors.grey))),
                        const SizedBox(height: 24),



                      ],
                    ),
                  ),
                ),
      bottomNavigationBar: _buildBottomNav(isDark, primary, navy),
    );
  }

  Widget _buildCardWidget(bool isDark) {
    final maskedNumber = _card!['masked_number'] ?? '**** ****';
    final validThru = _card!['valid_thru'] ?? '12/27';
    final issuer = _card!['issuer'] ?? 'Fuelix';
    final cardColor = _card!['color'] ?? '#1B3A6B';
    
    // Parse card color
    Color primaryColor;
    try {
      primaryColor = Color(int.parse(cardColor.replaceFirst('#', '0xFF')));
    } catch (_) {
      primaryColor = const Color(0xFF1B3A6B);
    }
    
    // Create gradient with card color
    final gradientColors = [
      primaryColor,
      primaryColor.withOpacity(0.7),
    ];

    return Container(
      width: double.infinity,
      height: 200,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: LinearGradient(
          colors: gradientColors,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        boxShadow: [BoxShadow(color: primaryColor.withOpacity(0.4), blurRadius: 20, offset: const Offset(0, 10))],
      ),
      child: Stack(
        children: [
          Positioned(right: -30, top: -30, child: Container(width: 150, height: 150, decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white.withOpacity(0.05)))),
          Positioned(right: 20, top: 20, child: Container(width: 100, height: 100, decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white.withOpacity(0.05)))),
          Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Icon(Icons.wifi, color: Colors.white70, size: 28),
                    Text(issuer, style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.bold, fontStyle: FontStyle.italic)),
                  ],
                ),
                const Spacer(),
                Text(maskedNumber, style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w600, letterSpacing: 3)),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(_userName, style: const TextStyle(color: Colors.white70, fontSize: 14, fontWeight: FontWeight.w500)),
                    Text('Valid thru $validThru', style: const TextStyle(color: Colors.white70, fontSize: 13)),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProduct(String key, IconData icon, String label, bool isDark) {
    // Check if product is authorized by the card plan
    final authorizedProducts = _card!['authorized_products'];
    bool isAuthorized = false;
    
    if (authorizedProducts is String) {
      try {
        final decoded = jsonDecode(authorizedProducts);
        if (decoded is List) {
          isAuthorized = decoded.contains(key);
        }
      } catch (_) {
        isAuthorized = false;
      }
    } else if (authorizedProducts is List) {
      isAuthorized = authorizedProducts.contains(key);
    }
    
    final selected = _selectedProducts.contains(key);
    final canSelect = isAuthorized;
    
    return Expanded(
      child: GestureDetector(
        onTap: canSelect ? () {
          setState(() {
            if (selected) {
              _selectedProducts.remove(key);
            } else {
              _selectedProducts.add(key);
            }
          });
        } : null,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 16),
          decoration: BoxDecoration(
            color: !canSelect
                ? (isDark ? const Color(0xFF1B3B5A).withOpacity(0.3) : Colors.grey.withOpacity(0.1))
                : selected
                    ? Colors.redAccent.withOpacity(0.12)
                    : (isDark ? const Color(0xFF1B3B5A) : Colors.white),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: !canSelect
                  ? Colors.grey.withOpacity(0.3)
                  : selected
                      ? Colors.redAccent
                      : Colors.transparent,
              width: 2,
            ),
            boxShadow: isDark || !canSelect ? [] : [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 8)],
          ),
          child: Column(
            children: [
              Icon(
                icon,
                color: !canSelect
                    ? Colors.grey
                    : selected
                        ? Colors.redAccent
                        : const Color(0xFFF2A945),
                size: 32,
              ),
              const SizedBox(height: 8),
              Text(
                label,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: selected ? FontWeight.bold : FontWeight.normal,
                  color: !canSelect
                      ? Colors.grey
                      : selected
                          ? Colors.redAccent
                          : (isDark ? Colors.white70 : Colors.black87),
                ),
              ),
              if (!canSelect)
                const Padding(
                  padding: EdgeInsets.only(top: 4),
                  child: Icon(Icons.lock, size: 14, color: Colors.grey),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildNoCard(bool isDark) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.credit_card_off, size: 80, color: Colors.grey.withOpacity(0.5)),
          const SizedBox(height: 16),
          Text('No card found', style: TextStyle(fontSize: 18, color: isDark ? Colors.white60 : Colors.grey)),
        ],
      ),
    );
  }

  Widget _buildBottomNav(bool isDark, Color primary, Color navy) {
    return BottomNavigationBar(
      currentIndex: 1,
      onTap: (i) {
        if (i == 0) Navigator.pushReplacementNamed(context, '/home');
        if (i == 2) Navigator.pushReplacementNamed(context, '/history');
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
