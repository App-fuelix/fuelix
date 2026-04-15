import 'dart:async';
import 'package:flutter/material.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    // Redirect to Login after 3 seconds
    Timer(const Duration(seconds: 3), () {
      if (mounted) {
        // Use the named route we defined in main.dart
        Navigator.pushReplacementNamed(context, '/login');
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    // 1. Detect if the system is in Dark Mode
    final bool isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      // The background color is now automatically handled by 
      // the 'scaffoldBackgroundColor' we set in main.dart!
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // 2. Logic to switch the logo based on theme
            Image.asset(
              isDark 
                  ? 'assets/images/logo_fuelix_2.png' // Dark mode logo
                  : 'assets/images/logo_fuelix.png',  // Light mode logo
              width: 250,
            ),
            const SizedBox(height: 12),
            // 3. Logic to switch text color based on theme
            Text(
              "Fuel management. Smarter.",
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w400,
                letterSpacing: 0.5,
                // Pick the contrast color
                color: isDark ? const Color(0xFFF4F6F8) : const Color(0xFF0F2A44),
              ),
            ),
          ],
        ),
      ),
    );
  }
}