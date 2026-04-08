import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import 'home_screen.dart';

class SignUpScreenDark extends StatefulWidget {
  const SignUpScreenDark({super.key});
  @override
  State<SignUpScreenDark> createState() => _SignUpScreenDarkState();
}

class _SignUpScreenDarkState extends State<SignUpScreenDark> {
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _cityController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _confirmPasswordController = TextEditingController();
  bool _isLoading = false;

  Future<void> _register() async {
    if (_passwordController.text != _confirmPasswordController.text) {
      _showError('Passwords do not match!');
      return;
    }

    final name = _nameController.text.trim();
    final email = _emailController.text.trim();
    final password = _passwordController.text;

    if (name.isEmpty || email.isEmpty || password.isEmpty) {
      _showError('Please fill in all required fields.');
      return;
    }

    setState(() => _isLoading = true);

    try {
      final result = await ApiService.register(
        name: name,
        email: email,
        password: password,
        passwordConfirmation: _confirmPasswordController.text,
        phone: _phoneController.text.trim(),
        city: _cityController.text.trim(),
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
          Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const HomeScreen()));
        }
      } else {
        final errors = body['errors'] as Map<String, dynamic>?;
        if (errors != null) {
          final firstError = errors.values.first;
          _showError(firstError is List ? firstError.first : firstError.toString());
        } else {
          _showError(body['message'] ?? 'Registration failed.');
        }
      }
    } catch (_) {
      _showError('Could not connect to server. Check your network.');
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
    return Scaffold(
      backgroundColor: const Color(0xFF0F2A44),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFFF4F6F8)),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 30.0),
          child: Column(
            children: [
              const SizedBox(height: 10),
              Image.asset('assets/images/logo_fuelix_2.png', width: 70),
              const SizedBox(height: 20),
              const Text("Create Account",
                  style: TextStyle(fontSize: 26, fontWeight: FontWeight.bold, color: Color(0xFFF4F6F8))),
              const SizedBox(height: 8),
              const Text("Fill in the details to get started",
                  style: TextStyle(fontSize: 14, color: Colors.white70)),
              const SizedBox(height: 25),
              _buildTextField(controller: _nameController, hintText: "Full Name"),
              const SizedBox(height: 12),
              _buildTextField(controller: _emailController, hintText: "Email address", keyboardType: TextInputType.emailAddress),
              const SizedBox(height: 12),
              _buildTextField(controller: _phoneController, hintText: "Phone Number", keyboardType: TextInputType.phone),
              const SizedBox(height: 12),
              _buildTextField(controller: _cityController, hintText: "City"),
              const SizedBox(height: 12),
              _buildTextField(controller: _passwordController, hintText: "Password", obscureText: true),
              const SizedBox(height: 12),
              _buildTextField(controller: _confirmPasswordController, hintText: "Confirm Password", obscureText: true),
              const SizedBox(height: 30),
              SizedBox(
                width: double.infinity,
                height: 55,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _register,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFF2A945),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                    elevation: 0,
                  ),
                  child: _isLoading
                      ? const CircularProgressIndicator(color: Colors.white, strokeWidth: 2)
                      : const Text("Sign Up",
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
                ),
              ),
              const SizedBox(height: 30),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String hintText,
    bool obscureText = false,
    TextInputType keyboardType = TextInputType.text,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.9),
        borderRadius: BorderRadius.circular(15),
      ),
      child: TextField(
        controller: controller,
        obscureText: obscureText,
        keyboardType: keyboardType,
        style: const TextStyle(fontSize: 15, color: Color(0xFF0F2A44)),
        decoration: InputDecoration(
          hintText: hintText,
          contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
          border: InputBorder.none,
          hintStyle: const TextStyle(color: Colors.grey),
        ),
      ),
    );
  }
}
