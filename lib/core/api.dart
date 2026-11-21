import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import 'device.dart';

/// The Aimzone mobile API (Laravel: routes/api.php, prefix mobile/v1; docs/MOBILE_API.md).
/// Build with --dart-define=API_BASE=... and --dart-define=API_KEY=... (a "Mobile app" key from /staff/api-keys).
const apiBase = String.fromEnvironment('API_BASE', defaultValue: 'https://loans.sharet.africa/api/mobile/v1');
const apiKey = String.fromEnvironment('API_KEY');

class ApiException implements Exception {
  ApiException(this.status, this.message, [this.title]);
  final int status;
  final String message;
  final String? title;
  bool get signedOut => status == 401;
  @override
  String toString() => message;
}

class Api {
  String? token;
  final _client = http.Client();

  /// For Image.network: the key, this phone and the sign-in (the photo needs all three).
  Map<String, String> get imageHeaders => _headers;

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'X-Api-Key': apiKey,
        'X-Install-Id': Device.installId,
        'X-App-Version': Device.appVersion,
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Future<Map<String, dynamic>> get(String path) async =>
      _decode(await _send(() => _client.get(Uri.parse('$apiBase$path'), headers: _headers)));

  Future<Map<String, dynamic>> post(String path, [Map<String, dynamic>? body]) async => _decode(await _send(() => _client
      .post(Uri.parse('$apiBase$path'), headers: {..._headers, 'Content-Type': 'application/json'}, body: jsonEncode(body ?? {}))));

  Future<http.Response> _send(Future<http.Response> Function() call) async {
    try {
      return await call().timeout(const Duration(seconds: 40));
    } on SocketException {
      throw ApiException(0, 'No internet. Check your connection and try again.');
    } on HttpException {
      throw ApiException(0, 'Could not reach Aimzone. Try again.');
    } on Exception catch (e) {
      if (e.toString().contains('TimeoutException')) throw ApiException(0, 'Aimzone is taking too long. Try again.');
      rethrow;
    }
  }

  Map<String, dynamic> _decode(http.Response r) {
    Map<String, dynamic> body = {};
    try {
      final d = jsonDecode(r.body);
      if (d is Map) body = Map<String, dynamic>.from(d);
    } catch (_) {}
    if (r.statusCode >= 200 && r.statusCode < 300) return body;
    var message = body['message'] as String? ?? 'Something went wrong (${r.statusCode}). Try again.';
    final errors = body['errors'];
    if (errors is Map && errors.isNotEmpty) {
      final first = errors.values.first;
      if (first is List && first.isNotEmpty) message = '${first.first}';
    }
    throw ApiException(r.statusCode, message, body['title'] as String?);
  }
}
