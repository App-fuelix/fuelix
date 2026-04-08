import 'package:flutter/material.dart';

class SplashScreenDark extends StatelessWidget {
  const SplashScreenDark({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: Color(0xFF0F2A44),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Image(image: AssetImage('assets/images/logo_fuelix_2.png'), width: 250),
            SizedBox(height: 12),
            Text(
              "Fuel management. Smarter.",
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w400,
                color: Color(0xFFF4F6F8),
                letterSpacing: 0.5,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
