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

  Future<void> _validateAndSend() async {
    final email = _emailController.text.trim();

    if (email.isEmpty) {
      _showErrorDialog("Please enter your email address.");
      return;
    }
    if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(email)) {
      _showErrorDialog("The email address format is incorrect.");
      return;
    }

    setState(() => _isLoading = true);

    try {
      await FirebaseAuthService.sendPasswordReset(email);
      setState(() => _sent = true);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Reset link sent! Check your inbox."), backgroundColor: Colors.green),
        );
      }
    } on FirebaseAuthException catch (e) {
      _showErrorDialog(e.code == 'user-not-found'
          ? 'No account found with this email.'
          : 'Failed to send reset link.');
    } catch (_) {
      _showErrorDialog('Could not connect. Try again.');
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
              const SizedBox(height: 30),
              Image.asset(isDark ? 'assets/images/logo_fuelix_2.png' : 'assets/images/logo_fuelix.png', width: 120),
              const SizedBox(height: 50),
              Text("Forgot Password", style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: mainTextColor)),
              const SizedBox(height: 15),
              Text(
                "Enter your email address and we'll send you a link to reset your password.",
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 16, height: 1.4, color: isDark ? Colors.white70 : const Color(0xFF0F2A44)),
              ),
              const SizedBox(height: 40),
              Container(
                decoration: BoxDecoration(
                  color: isDark ? const Color(0xFFF4F6F8) : Colors.white,
                  borderRadius: BorderRadius.circular(30),
                  boxShadow: isDark ? [] : [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 4))],
                ),
                child: TextField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  style: const TextStyle(color: Color(0xFF0F2A44), fontSize: 16),
                  decoration: const InputDecoration(hintText: "Email address", contentPadding: EdgeInsets.symmetric(horizontal: 25, vertical: 20), border: InputBorder.none, hintStyle: TextStyle(color: Colors.grey)),
                ),
              ),
              const SizedBox(height: 30),
              SizedBox(
                width: double.infinity,
                height: 58,
                child: ElevatedButton(
                  onPressed: (_isLoading || _sent) ? null : _validateAndSend,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _sent ? Colors.green : const Color(0xFFF2A945),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
                    elevation: 0,
                  ),
                  child: _isLoading
                      ? const CircularProgressIndicator(color: Colors.white, strokeWidth: 2)
                      : Text(_sent ? "Email sent ✓" : "Send reset link", style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
                ),
              ),
              const SizedBox(height: 20),
              Text("If the email exists, a reset link will be sent.", style: TextStyle(fontSize: 13, color: isDark ? Colors.white54 : Colors.grey)),
            ],
          ),
        ),
      ),
    );
  }
}
