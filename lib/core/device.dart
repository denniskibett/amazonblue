import 'dart:io';
import 'dart:math';

import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:package_info_plus/package_info_plus.dart';

/// This install and phone. The install ID is random and stays on the phone; model and OS go to the server only
/// when the borrower agreed (Account › What the app uses). Never contacts, photos, call logs, SMS or location.
class Device {
  static String installId = '';
  static String appVersion = '0';
  static String osVersion = Platform.operatingSystemVersion;
  static String model = 'unknown';
  static String get platform => Platform.isIOS ? 'ios' : 'android';

  static Future<void> load() async {
    const store = FlutterSecureStorage();
    installId = await store.read(key: 'install_id') ?? '';
    if (installId.isEmpty) {
      final r = Random.secure();
      installId = List.generate(16, (_) => r.nextInt(256).toRadixString(16).padLeft(2, '0')).join();
      await store.write(key: 'install_id', value: installId);
    }
    try {
      final pkg = await PackageInfo.fromPlatform();
      appVersion = pkg.version;
    } catch (_) {}
    try {
      if (Platform.isAndroid) {
        final a = await DeviceInfoPlugin().androidInfo;
        osVersion = 'Android ${a.version.release}';
        model = '${a.manufacturer} ${a.model}';
      } else if (Platform.isIOS) {
        final i = await DeviceInfoPlugin().iosInfo;
        osVersion = 'iOS ${i.systemVersion}';
        model = i.utsname.machine;
      }
    } catch (_) {}
  }
}
