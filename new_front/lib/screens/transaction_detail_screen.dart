import 'package:flutter/material.dart';

class TransactionDetailScreen extends StatelessWidget {
  final Map<String, dynamic> transaction;
  const TransactionDetailScreen({super.key, required this.transaction});

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final navy = const Color(0xFF0F2A44);
    final primary = const Color(0xFFF2A945);
    final bg = isDark ? navy : const Color(0xFFF4F6F8);
    final cardBg = isDark ? const Color(0xFF1B3B5A) : Colors.white;

    final amount = double.tryParse(transaction['amount']?.toString() ?? '0') ?? 0;
    final liters = transaction['quantity_liters']?.toString() ?? '0';
    final price = transaction['price_per_liter']?.toString() ?? '0';
    final station = transaction['station_name']?.toString() ?? 'Unknown Station';
    final date = transaction['date']?.toString() ?? '';
    final time = transaction['time']?.toString() ?? '';
    final id = transaction['id']?.toString() ?? '';
    final shortId = id.length > 8 ? 'TX-${id.substring(0, 8).toUpperCase()}' : 'TX-$id';

    return Scaffold(
      backgroundColor: bg,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        title: Image.asset(isDark ? 'assets/images/logo_fuelix_2.png' : 'assets/images/logo_fuelix.png', width: 90),
      ),
      body: SingleChildScrollView(
        child: Column(
          children: [
            // Header bar
            Container(
              color: navy,
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
              child: Row(
                children: [
                  GestureDetector(
                    onTap: () => Navigator.pop(context),
                    child: const Icon(Icons.arrow_back, color: Colors.white),
                  ),
                  const SizedBox(width: 12),
                  const Text('Transactions Details',
                      style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
                ],
              ),
            ),

            Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  // Top card
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: cardBg,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: isDark ? [] : [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10)],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.local_gas_station, color: primary, size: 36),
                            const SizedBox(width: 12),
                            Text('Fuel',
                                style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold,
                                    color: isDark ? Colors.white : navy)),
                            const Spacer(),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.green.withOpacity(0.15),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: const Text('Completed',
                                  style: TextStyle(color: Colors.green, fontSize: 12, fontWeight: FontWeight.bold)),
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Text('$date - $time', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                        const SizedBox(height: 12),
                        Text('-${amount.toStringAsFixed(3)} TND',
                            style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Colors.redAccent)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Transaction info
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: cardBg,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: isDark ? [] : [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10)],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Transaction Info',
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold,
                                color: isDark ? Colors.white : navy)),
                        const SizedBox(height: 16),
                        _buildRow('Transaction ID', shortId, isDark),
                        _buildDivider(isDark),
                        _buildRow('Date', date, isDark),
                        _buildDivider(isDark),
                        _buildRow('Time', time, isDark),
                        _buildDivider(isDark),
                        _buildRow('Station', station, isDark),
                        _buildDivider(isDark),
                        _buildRow('Vehicle', transaction['vehicle']?['model'] ?? '—', isDark),
                        _buildDivider(isDark),
                        _buildRow('Payment Method', 'Digital Card', isDark),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Fuel details
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: cardBg,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: isDark ? [] : [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10)],
                    ),
                    child: Column(
                      children: [
                        _buildRow('Fuel Quantity', '$liters L', isDark),
                        _buildDivider(isDark),
                        _buildRow('Price / L', '$price TND', isDark),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Report issue
                  TextButton(
                    onPressed: () {},
                    child: const Text('Report an issue',
                        style: TextStyle(
                          color: Colors.grey,
                          decoration: TextDecoration.underline,
                          decorationColor: Colors.grey,
                        )),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: _buildBottomNav(isDark, primary, navy, context),
    );
  }

  Widget _buildRow(String label, String value, bool isDark) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey, fontSize: 14)),
          Text(value,
              style: TextStyle(
                fontSize: 14, fontWeight: FontWeight.w500,
                color: isDark ? Colors.white : const Color(0xFF0F2A44),
              )),
        ],
      ),
    );
  }

  Widget _buildDivider(bool isDark) {
    return Divider(color: isDark ? Colors.white12 : Colors.grey[200], height: 1);
  }

  Widget _buildBottomNav(bool isDark, Color primary, Color navy, BuildContext context) {
    return BottomNavigationBar(
      currentIndex: 2,
      onTap: (i) {
        if (i == 0) Navigator.pushReplacementNamed(context, '/home');
        if (i == 1) Navigator.pushReplacementNamed(context, '/card');
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
