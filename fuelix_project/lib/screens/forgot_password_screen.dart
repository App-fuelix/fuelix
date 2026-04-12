import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import '../services/firebase_auth_service.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});
  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final TextEditingController _emailController = TextEditingController();
  bool _isLoading = false;
  bool _sent = false;

  Future<void> _sendResetLink() async {
    final email = _emailController.text.trim();
    if (email.isEmpty) {
      _showMessage('Please enter your email address.', isError: true);
      return;
    }

    setState(() => _isLoading = true);

    try {
      await FirebaseAuthService.sendPasswordReset(email);
      setState(() => _sent = true);
      _showMessage('Reset link sent! Check your inbox.');
    } on FirebaseAuthException catch (e) {
      _showMessage(e.code == 'user-not-found'
          ? 'No account found with this email.'
          : 'Failed to send reset link.', isError: true);
    } catch (_) {
      _showMessage('Could not connect. Try again.', isError: true);
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showMessage(String msg, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: isError ? Colors.redAccent : Colors.green),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F6F8),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF0F2A44)),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 30.0),
          child: Column(
            children: [
              const SizedBox(height: 30),
              Image.asset('assets/images/logo_fuelix.png', width: 120),
              const SizedBox(height: 50),
              const Text("Forgot Password",
                  style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Color(0xFF0F2A44))),
              const SizedBox(height: 15),
              const Text(
                "Enter your email address and we'll send you a link to reset your password.",
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 16, height: 1.4, color: Color(0xFF0F2A44)),
              ),
              const SizedBox(height: 40),
              Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(30),
                  boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 4))],
                ),
                child: TextField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  style: const TextStyle(color: Color(0xFF0F2A44), fontSize: 16),
                  decoration: const InputDecoration(
                    hintText: "Email address",
                    contentPadding: EdgeInsets.symmetric(horizontal: 25, vertical: 20),
                    border: InputBorder.none,
                    hintStyle: TextStyle(color: Colors.grey),
                  ),
                ),
              ),
              const SizedBox(height: 30),
              SizedBox(
                width: double.infinity,
                height: 58,
                child: ElevatedButton(
                  onPressed: (_isLoading || _sent) ? null : _sendResetLink,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _sent ? Colors.green : const Color(0xFFF2A945),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
                    elevation: 0,
                  ),
                  child: _isLoading
                      ? const CircularProgressIndicator(color: Colors.white, strokeWidth: 2)
                      : Text(_sent ? "Email sent ✓" : "Send reset link",
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
                ),
              ),
              const SizedBox(height: 20),
              const Text("If the email exists, a reset link will be sent.",
                  style: TextStyle(fontSize: 13, color: Colors.grey)),
            ],
          ),
        ),
      ),
    );
  }
}
