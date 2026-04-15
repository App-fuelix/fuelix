import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import '../services/firebase_auth_service.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import 'home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
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
    final email = _emailController.text.trim();
    final password = _passwordController.text.trim();

    if (email.isEmpty || password.isEmpty) {
      _showErrorDialog("Please fill in all fields to continue.");
      return;
    }

    if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(email)) {
      _showErrorDialog("The email address format is incorrect.");
      return;
    }

    setState(() => _isLoading = true);

    try {
      final firebaseResult = await FirebaseAuthService.login(email: email, password: password);
      final firebaseToken = firebaseResult['token'] as String;

      final result = await ApiService.loginWithFirebase(firebaseToken);
      final status = result['status'] as int;
      final body = result['body'] as Map<String, dynamic>;

      if (status == 200) {
        await AuthService.saveSession(
          token: body['token'],
          name: body['user']['name'] ?? '',
          email: body['user']['email'] ?? '',
        );
        if (mounted) {
          Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const DashboardScreen()));
        }
      } else {
        _showErrorDialog(body['message'] ?? 'Login failed.');
      }
    } on FirebaseAuthException catch (e) {
      switch (e.code) {
        case 'user-not-found': _showErrorDialog('No account found with this email.'); break;
        case 'wrong-password': _showErrorDialog('Incorrect password.'); break;
        case 'invalid-credential': _showErrorDialog('Email or password is incorrect.'); break;
        default: _showErrorDialog('Login failed. Please try again.');
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
    final Color textColor = isDark ? const Color(0xFFF4F6F8) : const Color(0xFF0F2A44);

    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 30.0),
          child: Column(
            children: [
              const SizedBox(height: 80),
              Image.asset(isDark ? 'assets/images/logo_fuelix_2.png' : 'assets/images/logo_fuelix.png', width: 130),
              const SizedBox(height: 60),
              Text("Welcome Back", style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: textColor)),
              const SizedBox(height: 10),
              Text("Sign in to continue", style: TextStyle(fontSize: 16, color: isDark ? Colors.white70 : Colors.blueGrey)),
              const SizedBox(height: 45),
              _buildTextField(context: context, controller: _emailController, hintText: "Email address", obscureText: false),
              const SizedBox(height: 20),
              _buildTextField(context: context, controller: _passwordController, hintText: "Password", obscureText: true),
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
                      : const Text("Sign In", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
                ),
              ),
              const SizedBox(height: 25),
              TextButton(
                onPressed: () => Navigator.pushNamed(context, '/forgot-password'),
                child: Text("Forgot password?", style: TextStyle(color: textColor, fontSize: 15, decoration: TextDecoration.underline, decorationColor: textColor)),
              ),
              const SizedBox(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text("Don't have an account? ", style: TextStyle(color: isDark ? Colors.white70 : const Color(0xFF0F2A44))),
                  GestureDetector(
                    onTap: () => Navigator.pushNamed(context, '/signup'),
                    child: const Text("Sign Up", style: TextStyle(color: Color(0xFFF2A945), fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTextField({required BuildContext context, required TextEditingController controller, required String hintText, required bool obscureText}) {
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
        style: const TextStyle(color: Color(0xFF0F2A44), fontSize: 16),
        decoration: InputDecoration(hintText: hintText, contentPadding: const EdgeInsets.symmetric(horizontal: 25, vertical: 20), border: InputBorder.none, hintStyle: const TextStyle(color: Colors.grey)),
      ),
    );
  }
}
