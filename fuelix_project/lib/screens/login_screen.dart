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

  Future<void> _login() async {
    final email = _emailController.text.trim();
    final password = _passwordController.text;

    if (email.isEmpty || password.isEmpty) {
      _showError('Please fill in all fields.');
      return;
    }

    setState(() => _isLoading = true);

    try {
      // 1. Firebase Auth login
      final firebaseResult = await FirebaseAuthService.login(email: email, password: password);
      final firebaseToken = firebaseResult['token'] as String;

      // 2. Send Firebase token to Laravel to get Sanctum token
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
          Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const HomeScreen()));
        }
      } else {
        _showError(body['message'] ?? 'Login failed.');
      }
    } on FirebaseAuthException catch (e) {
      _showError(_firebaseError(e.code));
    } catch (_) {
      _showError('Could not connect to server. Check your network.');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  String _firebaseError(String code) {
    switch (code) {
      case 'user-not-found': return 'No account found with this email.';
      case 'wrong-password': return 'Incorrect password.';
      case 'invalid-email': return 'Invalid email address.';
      case 'user-disabled': return 'This account has been disabled.';
      default: return 'Login failed. Please try again.';
    }
  }

  void _showError(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: Colors.redAccent),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F6F8),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 30.0),
          child: Column(
            children: [
              const SizedBox(height: 80),
              Image.asset('assets/images/logo_fuelix.png', width: 130),
              const SizedBox(height: 60),
              const Text("Welcome Back",
                  style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Color(0xFF0F2A44))),
              const SizedBox(height: 10),
              const Text("Sign in to continue",
                  style: TextStyle(fontSize: 16, color: Colors.blueGrey)),
              const SizedBox(height: 45),
              _buildTextField(controller: _emailController, hintText: "Email address", obscureText: false),
              const SizedBox(height: 20),
              _buildTextField(controller: _passwordController, hintText: "Password", obscureText: true),
              const SizedBox(height: 35),
              SizedBox(
                width: double.infinity,
                height: 58,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _login,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFF2A945),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
                    elevation: 0,
                  ),
                  child: _isLoading
                      ? const CircularProgressIndicator(color: Colors.white, strokeWidth: 2)
                      : const Text("Sign In",
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
                ),
              ),
              const SizedBox(height: 25),
              TextButton(
                onPressed: () => Navigator.pushNamed(context, '/forgot-password'),
                child: const Text("Forgot password?",
                    style: TextStyle(color: Color(0xFF0F2A44), fontSize: 15, decoration: TextDecoration.underline)),
              ),
              const SizedBox(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text("Don't have an account? ", style: TextStyle(color: Color(0xFF0F2A44))),
                  GestureDetector(
                    onTap: () => Navigator.pushNamed(context, '/signup'),
                    child: const Text("Sign Up",
                        style: TextStyle(color: Color(0xFFF2A945), fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTextField(
      {required TextEditingController controller, required String hintText, required bool obscureText}) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(30),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 4))],
      ),
      child: TextField(
        controller: controller,
        obscureText: obscureText,
        style: const TextStyle(color: Color(0xFF0F2A44), fontSize: 16),
        decoration: InputDecoration(
          hintText: hintText,
          contentPadding: const EdgeInsets.symmetric(horizontal: 25, vertical: 20),
          border: InputBorder.none,
          hintStyle: const TextStyle(color: Colors.grey),
        ),
      ),
    );
  }
}
