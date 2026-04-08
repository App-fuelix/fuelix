import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../services/api_service.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  String _userName = '';
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    final user = await AuthService.getUser();
    setState(() => _userName = user['name'] ?? 'User');
  }

  Future<void> _logout() async {
    setState(() => _isLoading = true);
    final token = await AuthService.getToken();
    if (token != null) {
      try {
        await ApiService.logout(token);
      } catch (_) {}
    }
    await AuthService.clearSession();
    if (mounted) Navigator.pushReplacementNamed(context, '/login');
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Scaffold(
      appBar: AppBar(
        title: const Text('FueliX', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: isDark ? const Color(0xFF0F2A44) : const Color(0xFFF4F6F8),
        foregroundColor: isDark ? Colors.white : const Color(0xFF0F2A44),
        elevation: 0,
        automaticallyImplyLeading: false,
        actions: [
          _isLoading
              ? const Padding(
                  padding: EdgeInsets.all(16),
                  child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)),
                )
              : IconButton(
                  icon: const Icon(Icons.logout),
                  onPressed: _logout,
                  tooltip: 'Logout',
                ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.local_gas_station, size: 80, color: Color(0xFFF2A945)),
            const SizedBox(height: 20),
            Text(
              'Welcome, $_userName',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: isDark ? Colors.white : const Color(0xFF0F2A44),
              ),
            ),
            const SizedBox(height: 10),
            Text(
              'You are connected to the Fuelix API.',
              style: TextStyle(color: isDark ? Colors.white70 : Colors.blueGrey),
            ),
          ],
        ),
      ),
    );
  }
}
