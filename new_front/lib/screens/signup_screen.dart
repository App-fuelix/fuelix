import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import '../services/firebase_auth_service.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import 'home_screen.dart';

class SignUpScreen extends StatefulWidget {
  const SignUpScreen({super.key});
  @override
  State<SignUpScreen> createState() => _SignUpScreenState();
}

class _SignUpScreenState extends State<SignUpScreen> {
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _cityController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _confirmPasswordController = TextEditingController();
  bool _isLoading = false;

  void _showErrorDialog(String message) {
    final bool isDark = Theme.of(context).brightness == Brightness.dark;
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: isDark ? const Color(0xFF0F2A44) : Colors.white,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            const Icon(Icons.error_outline, color: Color(0xFFF2A945), size: 30),
            const SizedBox(width: 10),
            Text("Opps!", style: TextStyle(color: isDark ? Colors.white : const Color(0xFF0F2A44), fontWeight: FontWeight.bold)),
          ],
        ),
        content: Text(message, style: TextStyle(color: isDark ? Colors.white70 : Colors.black87)),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text("OK", style: TextStyle(color: Color(0xFFF2A945), fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Future<void> _submit() async {
    final name = _nameController.text.trim();
    final email = _emailController.text.trim();
    final phone = _phoneController.text.trim();
    final city = _cityController.text.trim();
    final password = _passwordController.text;
    final confirm = _confirmPasswordController.text;

    if (name.isEmpty || email.isEmpty || phone.isEmpty || city.isEmpty || password.isEmpty) {
      _showErrorDialog("Please fill in all fields.");
      return;
    }
    if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(email)) {
      _showErrorDialog("Please enter a valid email address.");
      return;
    }
    if (!RegExp(r'^[2459]\d{7}$').hasMatch(phone)) {
      _showErrorDialog("Please enter a valid Tunisian phone number.");
      return;
    }
    if (password.length < 6) {
      _showErrorDialog("Password must be at least 6 characters.");
      return;
    }
    if (password != confirm) {
      _showErrorDialog("Passwords do not match!");
      return;
    }

    setState(() => _isLoading = true);

    try {
      final firebaseResult = await FirebaseAuthService.register(email: email, password: password);
      final firebaseToken = firebaseResult['token'] as String;

      final result = await ApiService.registerWithFirebase(
        firebaseToken: firebaseToken,
        name: name,
        email: email,
        phone: phone,
        city: city,
      );
      final status = result['status'] as int;
      final body = result['body'] as Map<String, dynamic>;

      if (status == 201) {
        await AuthService.saveSession(
          token: body['token'],
          name: body['user']['name'] ?? '',
          email: body['user']['email'] ?? '',
        );
        if (mounted) {
          Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const DashboardScreen()));
        }
      } else {
        await FirebaseAuthService.currentUser?.delete();
        final errors = body['errors'] as Map<String, dynamic>?;
        if (errors != null) {
          final firstError = errors.values.first;
          _showErrorDialog(firstError is List ? firstError.first : firstError.toString());
        } else {
          _showErrorDialog(body['message'] ?? 'Registration failed.');
        }
      }
    } on FirebaseAuthException catch (e) {
      switch (e.code) {
        case 'email-already-in-use': _showErrorDialog('This email is already registered.'); break;
        case 'invalid-email': _showErrorDialog('Invalid email address.'); break;
        case 'weak-password': _showErrorDialog('Password must be at least 6 characters.'); break;
        default: _showErrorDialog('Registration failed. Please try again.');
      }
    } catch (_) {
      _showErrorDialog('Could not connect to server. Check your network.');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bool isDark = Theme.of(context).brightness == Brightness.dark;
    final Color mainTextColor = isDark ? const Color(0xFFF4F6F8) : const Color(0xFF0F2A44);

    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(icon: Icon(Icons.arrow_back, color: mainTextColor), onPressed: () => Navigator.pop(context)),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 30.0),
          child: Column(
            children: [
              const SizedBox(height: 10),
              Image.asset(isDark ? 'assets/images/logo_fuelix_2.png' : 'assets/images/logo_fuelix.png', width: 100),
              const SizedBox(height: 20),
              Text("Create Account", style: TextStyle(fontSize: 26, fontWeight: FontWeight.bold, color: mainTextColor)),
              const SizedBox(height: 8),
              Text("Fill in the details to get started", style: TextStyle(fontSize: 14, color: isDark ? Colors.white70 : Colors.grey)),
              const SizedBox(height: 25),
              _buildTextField(context: context, controller: _nameController, hintText: "Full Name"),
              const SizedBox(height: 15),
              _buildTextField(context: context, controller: _emailController, hintText: "Email address", keyboardType: TextInputType.emailAddress),
              const SizedBox(height: 15),
              _buildTextField(context: context, controller: _phoneController, hintText: "Phone Number", keyboardType: TextInputType.phone),
              const SizedBox(height: 15),
              _buildTextField(context: context, controller: _cityController, hintText: "City"),
              const SizedBox(height: 15),
              _buildTextField(context: context, controller: _passwordController, hintText: "Password", obscureText: true),
              const SizedBox(height: 15),
              _buildTextField(context: context, controller: _confirmPasswordController, hintText: "Confirm Password", obscureText: true),
              const SizedBox(height: 35),
              SizedBox(
                width: double.infinity,
                height: 58,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFF2A945),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
                    elevation: 0,
                  ),
                  child: _isLoading
                      ? const CircularProgressIndicator(color: Colors.white, strokeWidth: 2)
                      : const Text("Sign Up", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
                ),
              ),
              const SizedBox(height: 30),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTextField({required BuildContext context, required TextEditingController controller, required String hintText, bool obscureText = false, TextInputType keyboardType = TextInputType.text}) {
    final bool isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFFF4F6F8) : Colors.white,
        borderRadius: BorderRadius.circular(30),
        boxShadow: isDark ? [] : [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 4))],
      ),
      child: TextField(
        controller: controller,
        obscureText: obscureText,
        keyboardType: keyboardType,
        style: const TextStyle(color: Color(0xFF0F2A44), fontSize: 16),
        decoration: InputDecoration(hintText: hintText, contentPadding: const EdgeInsets.symmetric(horizontal: 25, vertical: 20), border: InputBorder.none, hintStyle: const TextStyle(color: Colors.grey)),
      ),
    );
  }
}
