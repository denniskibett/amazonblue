import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/device.dart';
import '../core/theme.dart';
import '../main.dart';
import '../ui/widgets.dart';
import 'consent.dart';

class AccountTab extends StatelessWidget {
  const AccountTab({super.key});
  @override
  Widget build(BuildContext context) {
    final u = session.user;
    Widget item(String icon, String title, VoidCallback onTap, {Color color = C.ink}) => ListTile(
          leading: HIcon(icon, color: color),
          title: Text(title, style: fig(15, w: FontWeight.w600, c: color)),
          trailing: const HIcon('chevron-right', size: 18, color: C.slate),
          onTap: onTap,
        );
    return Scaffold(
      appBar: const BackBar('Account'),
      body: ListView(padding: const EdgeInsets.fromLTRB(20, 8, 20, 24), children: [
        Panel(
          child: Row(children: [
            const Avatar(radius: 26),
            const SizedBox(width: 14),
            Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('${u['name'] ?? ''}', style: fig(16, w: FontWeight.w600)),
              Text([u['phone'], u['email']].whereType<String>().join(' · '), style: fig(13, c: C.slate)),
            ])),
          ]),
        ),
        const SizedBox(height: 16),
        Panel(
          padding: const EdgeInsets.symmetric(vertical: 6),
          child: Column(children: [
            item('shield-check', 'What the app uses', () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ConsentScreen()))),
            item('globe-alt', 'Open the website', () => launchUrl(Uri.parse('https://loans.sharet.africa/account'), mode: LaunchMode.externalApplication)),
            item('chat-bubble-left-right', 'Help', () => launchUrl(Uri.parse('https://loans.sharet.africa/contact'), mode: LaunchMode.externalApplication)),
          ]),
        ),
        const SizedBox(height: 16),
        Panel(
          padding: const EdgeInsets.symmetric(vertical: 6),
          child: item('arrow-right-start-on-rectangle', 'Sign out', () async {
            await session.signOut();
          }, color: C.rose),
        ),
        const SizedBox(height: 20),
        Center(child: Text('Aimzone ${Device.appVersion} · ${Device.model}', style: fig(12, c: C.slate))),
      ]),
    );
  }
}
