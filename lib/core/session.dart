import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'api.dart';
import 'device.dart';

/// The signed-in borrower on this phone. The token sits in the phone's keystore; the server decides everything
/// about money (limit, blockers, what is due), the app only shows it.
class Session extends ChangeNotifier {
  static const _store = FlutterSecureStorage();
  final api = Api();
  Map<String, dynamic>? me;
  bool ready = false;
  bool seenWelcome = false;

  bool get signedIn => api.token != null && me != null;
  Map<String, dynamic> get user => Map<String, dynamic>.from(me?['user'] as Map? ?? {});
  String get firstName => user['first_name'] as String? ?? '';
  Map<String, dynamic>? get openLoan => me?['open_loan'] as Map<String, dynamic>?;
  List get blockers => me?['blockers'] as List? ?? [];
  int get limit => ((me?['limit'] as Map?)?['amount'] as num?)?.toInt() ?? 0;

  Future<void> start() async {
    await Device.load();
    seenWelcome = await _store.read(key: 'seen_welcome') == '1';
    api.token = await _store.read(key: 'token');
    if (api.token != null) {
      try {
        await refresh();
      } on ApiException catch (e) {
        if (e.signedOut) await _clear();
      } catch (_) {}
    }
    ready = true;
    notifyListeners();
  }

  Future<void> markWelcomeSeen() async {
    seenWelcome = true;
    await _store.write(key: 'seen_welcome', value: '1');
    notifyListeners();
  }

  Future<void> signIn(String login, String password) async {
    final r = await api.post('/auth/login', {'login': login, 'password': password, 'install_id': Device.installId, 'device_name': Device.model});
    api.token = r['token'] as String;
    await _store.write(key: 'token', value: api.token);
    await refresh();
    try {
      await api.post('/devices/sighting', {'event': 'signed_in'});
    } catch (_) {}
  }

  Future<void> refresh() async {
    me = await api.get('/me');
    notifyListeners();
  }

  /// Registers this phone with what the borrower agreed to share.
  Future<List> saveConsents(Map<String, bool> consents) async {
    final r = await api.post('/devices', {
      'install_id': Device.installId, 'platform': Device.platform, 'model': Device.model, 'os_version': Device.osVersion,
      'app_version': Device.appVersion, 'consents': consents,
    });
    return r['consents'] as List? ?? [];
  }

  Future<void> signOut() async {
    try {
      await api.post('/auth/logout');
    } catch (_) {}
    await _clear();
  }

  Future<void> _clear() async {
    api.token = null;
    me = null;
    await _store.delete(key: 'token');
    notifyListeners();
  }
}
