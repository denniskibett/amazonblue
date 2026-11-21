import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../main.dart';
import '../ui/widgets.dart';

/// KYC 3 in the design file: what the app uses, each item switchable. Asked once per install, changed in Account.
/// Never contacts, photos, call logs, SMS or precise location.
class ConsentScreen extends StatefulWidget {
  const ConsentScreen({super.key, this.first = false});
  final bool first;

  static Future<void> askOnce(BuildContext context) async {
    const store = FlutterSecureStorage();
    if (await store.read(key: 'consents_saved') == '1' || !context.mounted) return;
    await Navigator.push(context, MaterialPageRoute(fullscreenDialog: true, builder: (_) => const ConsentScreen(first: true)));
  }

  @override
  State<ConsentScreen> createState() => _ConsentScreenState();
}

class _ConsentScreenState extends State<ConsentScreen> {
  final _on = {'device': true, 'area': false, 'push': true};
  bool _busy = false;
  static const _items = [
    ('device', 'device-phone-mobile', 'Phone model and app version', 'Helps us fix problems on your kind of phone.'),
    ('area', 'map-pin', 'Approximate area', 'Your town or estate when you apply or pay. Never your exact location.'),
    ('push', 'bell', 'Reminders', 'Due dates, money sent and payments received.'),
  ];

  Future<void> _save() async {
    setState(() => _busy = true);
    try {
      await session.saveConsents(_on);
      await const FlutterSecureStorage().write(key: 'consents_saved', value: '1');
      if (mounted) Navigator.pop(context);
    } on ApiException catch (e) {
      if (mounted) toast(context, e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: BackBar(widget.first ? '' : 'What the app uses'),
        body: ListView(padding: const EdgeInsets.fromLTRB(20, 0, 20, 24), children: [
          if (widget.first) ...[Text('What the app uses', style: sora(24)), const SizedBox(height: 8)],
          Text('You choose. You can change this any time in Account.', style: fig(15, c: C.slate)),
          const SizedBox(height: 18),
          for (final (key, icon, title, sub) in _items) ...[
            Panel(
              padding: const EdgeInsets.fromLTRB(16, 12, 8, 12),
              child: Row(children: [
                HIcon(icon, color: C.blue),
                const SizedBox(width: 14),
                Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(title, style: fig(15, w: FontWeight.w600)),
                  Text(sub, style: fig(13, c: C.slate)),
                ])),
                Switch(value: _on[key]!, activeTrackColor: C.blue, onChanged: (v) => setState(() => _on[key] = v)),
              ]),
            ),
            const SizedBox(height: 10),
          ],
          const SizedBox(height: 6),
          const Notice(
              tone: 'blue',
              title: 'Never read',
              message: 'Contacts, photos, call logs, SMS and your exact location. The app cannot see them.'),
          const SizedBox(height: 20),
          PillButton('Save', busy: _busy, onPressed: _save),
        ]),
      );
}
