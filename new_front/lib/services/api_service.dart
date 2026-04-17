import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  // Change this to your backend URL
  // Android emulator: http://10.0.2.2:8000/api
  // Physical device:  http://YOUR_LOCAL_IP:8000/api
  // Web / Windows:    http://127.0.0.1:8000/api
  static const String baseUrl = 'http://10.0.2.2:8000/api';

  static Map<String, String> _headers({String? token}) {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (token != null) headers['Authorization'] = 'Bearer $token';
    return headers;
  }

  static Future<Map<String, dynamic>> login(String email, String password) async {
    final res = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: _headers(),
      body: jsonEncode({'email': email, 'password': password}),
    );
    return {'status': res.statusCode, 'body': jsonDecode(res.body)};
  }

  /// Login via Firebase ID token
  static Future<Map<String, dynamic>> loginWithFirebase(String firebaseToken) async {
    final res = await http.post(
      Uri.parse('$baseUrl/firebase/login'),
      headers: _headers(),
      body: jsonEncode({'firebase_token': firebaseToken}),
    );
    return {'status': res.statusCode, 'body': jsonDecode(res.body)};
  }

  /// Register via Firebase ID token + profile data
  static Future<Map<String, dynamic>> registerWithFirebase({
    required String firebaseToken,
    required String name,
    required String email,
    String? phone,
    String? city,
  }) async {
    final res = await http.post(
      Uri.parse('$baseUrl/firebase/register'),
      headers: _headers(),
      body: jsonEncode({
        'firebase_token': firebaseToken,
        'name': name,
        'email': email,
        if (phone != null && phone.isNotEmpty) 'phone': phone,
        if (city != null && city.isNotEmpty) 'city': city,
      }),
    );
    return {'status': res.statusCode, 'body': jsonDecode(res.body)};
  }

  static Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    String? phone,
    String? city,
  }) async {
    final res = await http.post(
      Uri.parse('$baseUrl/register'),
      headers: _headers(),
      body: jsonEncode({
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        if (phone != null && phone.isNotEmpty) 'phone': phone,
        if (city != null && city.isNotEmpty) 'city': city,
      }),
    );
    return {'status': res.statusCode, 'body': jsonDecode(res.body)};
  }

  static Future<Map<String, dynamic>> forgotPassword(String email) async {
    final res = await http.post(
      Uri.parse('$baseUrl/forgot-password'),
      headers: _headers(),
      body: jsonEncode({'email': email}),
    );
    return {'status': res.statusCode, 'body': jsonDecode(res.body)};
  }

  static Future<Map<String, dynamic>> logout(String token) async {
    final res = await http.post(
      Uri.parse('$baseUrl/logout'),
      headers: _headers(token: token),
    );
    return {'status': res.statusCode, 'body': jsonDecode(res.body)};
  }

  static Future<Map<String, dynamic>> getMe(String token) async {
    final res = await http.get(
      Uri.parse('$baseUrl/me'),
      headers: _headers(token: token),
    );
    return {'status': res.statusCode, 'body': jsonDecode(res.body)};
  }

  static Future<Map<String, dynamic>> getDashboard(String token) async {
    final res = await http.get(
      Uri.parse('$baseUrl/dashboard'),
      headers: _headers(token: token),
    );
    return {'status': res.statusCode, 'body': jsonDecode(res.body)};
  }

  static Future<Map<String, dynamic>> updateProfile({
    required String token,
    String? name,
    String? phone,
    String? city,
  }) async {
    final body = <String, dynamic>{};
    if (name != null) body['name'] = name;
    if (phone != null) body['phone'] = phone;
    if (city != null) body['city'] = city;

    final res = await http.put(
      Uri.parse('$baseUrl/profile'),
      headers: _headers(token: token),
      body: jsonEncode(body),
    );
    return {'status': res.statusCode, 'body': jsonDecode(res.body)};
  }

  static Future<Map<String, dynamic>> changePassword({
    required String token,
    required String currentPassword,
    required String newPassword,
  }) async {
    final res = await http.put(
      Uri.parse('$baseUrl/change-password'),
      headers: _headers(token: token),
      body: jsonEncode({
        'current_password': currentPassword,
        'password': newPassword,
        'password_confirmation': newPassword,
      }),
    );
    return {'status': res.statusCode, 'body': jsonDecode(res.body)};
  }
}
