import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';
import 'screens/splash_screen.dart';
import 'screens/splash_screen_dark.dart';
import 'screens/login_screen.dart';
import 'screens/login_screen_dark.dart';
import 'screens/signup_screen.dart';
import 'screens/signup_screen_dark.dart';
import 'screens/forgot_password_screen.dart';
import 'screens/forgot_password_dark.dart';
import 'screens/home_screen.dart';
import 'services/firebase_auth_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  runApp(const FuelixApp());
}

class FuelixApp extends StatelessWidget {
  const FuelixApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'FueliX',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        brightness: Brightness.light,
        scaffoldBackgroundColor: const Color(0xFFF4F6F8),
      ),
      darkTheme: ThemeData(
        brightness: Brightness.dark,
        scaffoldBackgroundColor: const Color(0xFF0F2A44),
      ),
      themeMode: ThemeMode.system,
      home: const AppEntry(),
      routes: {
        '/login': (ctx) => _themed(ctx, const LoginScreen(), const LoginScreenDark()),
        '/signup': (ctx) => _themed(ctx, const SignUpScreen(), const SignUpScreenDark()),
        '/forgot-password': (ctx) => _themed(ctx, const ForgotPasswordScreen(), const ForgotPasswordDark()),
        '/home': (ctx) => const HomeScreen(),
      },
    );
  }

  static Widget _themed(BuildContext ctx, Widget light, Widget dark) {
    final isDark = MediaQuery.of(ctx).platformBrightness == Brightness.dark;
    return isDark ? dark : light;
  }
}

class AppEntry extends StatefulWidget {
  const AppEntry({super.key});
  @override
  State<AppEntry> createState() => _AppEntryState();
}

class _AppEntryState extends State<AppEntry> {
  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    await Future.delayed(const Duration(seconds: 3));
    if (!mounted) return;

    final loggedIn = FirebaseAuthService.isLoggedIn;

    if (!mounted) return;
    final isDark = MediaQuery.of(context).platformBrightness == Brightness.dark;

    if (loggedIn) {
      Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const HomeScreen()));
    } else {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => isDark ? const LoginScreenDark() : const LoginScreen()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = MediaQuery.of(context).platformBrightness == Brightness.dark;
    return isDark ? const SplashScreenDark() : const SplashScreen();
  }
}
