import 'package:firebase_auth/firebase_auth.dart';

class FirebaseAuthService {
  static final FirebaseAuth _auth = FirebaseAuth.instance;

  /// Register with email/password — returns ID token on success
  static Future<Map<String, dynamic>> register({
    required String email,
    required String password,
  }) async {
    final credential = await _auth.createUserWithEmailAndPassword(
      email: email,
      password: password,
    );
    final token = await credential.user!.getIdToken();
    return {'uid': credential.user!.uid, 'token': token};
  }

  /// Login with email/password — returns ID token on success
  static Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final credential = await _auth.signInWithEmailAndPassword(
      email: email,
      password: password,
    );
    final token = await credential.user!.getIdToken();
    return {'uid': credential.user!.uid, 'token': token};
  }

  /// Send password reset email
  static Future<void> sendPasswordReset(String email) async {
    await _auth.sendPasswordResetEmail(email: email);
  }

  /// Logout
  static Future<void> logout() async {
    await _auth.signOut();
  }

  /// Get current Firebase ID token (refreshed)
  static Future<String?> getIdToken() async {
    return await _auth.currentUser?.getIdToken(true);
  }

  static User? get currentUser => _auth.currentUser;
  static bool get isLoggedIn => _auth.currentUser != null;
}
