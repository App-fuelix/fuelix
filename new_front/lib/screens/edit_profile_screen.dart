import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';

class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key});
  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _cityController = TextEditingController();
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    final token = await AuthService.getToken();
    if (token == null) return;
    final result = await ApiService.getMe(token);
    if (result['status'] == 200) {
      final body = result['body'] as Map<String, dynamic>;
      setState(() {
        _nameController.text = body['name'] ?? '';
        _emailController.text = body['email'] ?? '';
        _phoneController.text = body['phone'] ?? '';
        _cityController.text = body['city'] ?? '';
      });
    }
  }

  Future<void> _save() async {
    setState(() => _isLoading = true);
    final token = await AuthService.getToken();
    if (token == null) return;

    try {
      final result = await ApiService.updateProfile(
        token: token,
        name: _nameController.text.trim(),
        phone: _phoneController.text.trim(),
        city: _cityController.text.trim(),
      );
      final status = result['status'] as int;
      final body = result['body'] as Map<String, dynamic>;

      if (status == 200) {
        await AuthService.saveSession(
          token: token,
          name: body['user']['name'] ?? '',
          email: body['user']['email'] ?? '',
        );
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Profile updated!'), backgroundColor: Colors.green),
          );
          Navigator.pop(context);
        }
      } else {
        _showError(body['message'] ?? 'Update failed.');
      }
    } catch (_) {
      _showError('Could not connect to server.');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showError(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: Colors.redAccent),
    );
  }

  @override
  Widget build(BuildContext context) {
    final bool isDark = Theme.of(context).brightness == Brightness.dark;
    final Color primaryColor = const Color(0xFFF2A945);
    final Color textColor = isDark ? Colors.white : const Color(0xFF0F2A44);

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF0F2A44) : const Color(0xFFF4F6F8),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.close, color: textColor),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text("Edit Profile", style: TextStyle(color: textColor, fontWeight: FontWeight.bold)),
        actions: [
          _isLoading
              ? const Padding(
                  padding: EdgeInsets.all(16),
                  child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFFF2A945))),
                )
              : TextButton(
                  onPressed: _save,
                  child: Text("Save", style: TextStyle(color: primaryColor, fontWeight: FontWeight.bold, fontSize: 16)),
                ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(25),
        child: Column(
          children: [
            Center(
              child: Stack(
                children: [
                  CircleAvatar(
                    radius: 60,
                    backgroundColor: primaryColor.withOpacity(0.1),
                    child: Icon(Icons.person, size: 70, color: primaryColor),
                  ),
                  Positioned(
                    bottom: 0, right: 0,
                    child: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: const BoxDecoration(color: Color(0xFFF2A945), shape: BoxShape.circle),
                      child: const Icon(Icons.camera_alt, color: Colors.white, size: 20),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 40),
            _buildField(label: "Full Name", controller: _nameController, icon: Icons.person_outline, isDark: isDark),
            const SizedBox(height: 20),
            _buildField(label: "Email Address", controller: _emailController, icon: Icons.email_outlined, isDark: isDark, enabled: false),
            const SizedBox(height: 20),
            _buildField(label: "Phone Number", controller: _phoneController, icon: Icons.phone_android_outlined, isDark: isDark, keyboardType: TextInputType.phone),
            const SizedBox(height: 20),
            _buildField(label: "City", controller: _cityController, icon: Icons.location_city_outlined, isDark: isDark),
          ],
        ),
      ),
    );
  }

  Widget _buildField({
    required String label,
    required TextEditingController controller,
    required IconData icon,
    required bool isDark,
    bool enabled = true,
    TextInputType keyboardType = TextInputType.text,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(label, style: const TextStyle(color: Colors.grey, fontWeight: FontWeight.w500)),
            if (!enabled) ...[
              const SizedBox(width: 6),
              const Icon(Icons.lock_outline, size: 14, color: Colors.grey),
            ],
          ],
        ),
        const SizedBox(height: 8),
        Container(
          decoration: BoxDecoration(
            color: enabled
                ? (isDark ? const Color(0xFF1B3B5A) : Colors.white)
                : (isDark ? Colors.black26 : Colors.grey[200]),
            borderRadius: BorderRadius.circular(15),
          ),
          child: TextField(
            controller: controller,
            enabled: enabled,
            keyboardType: keyboardType,
            style: TextStyle(color: isDark ? Colors.white : const Color(0xFF0F2A44)),
            decoration: InputDecoration(
              prefixIcon: Icon(icon, color: enabled ? const Color(0xFFF2A945) : Colors.grey),
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(vertical: 15),
            ),
          ),
        ),
      ],
    );
  }
}
