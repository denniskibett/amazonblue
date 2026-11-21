import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../main.dart';
import '../ui/widgets.dart';

/// Welcome 5: sign in with the phone number or email and the website password. New borrowers sign up on the website
/// for now (the test app has no sign-up or KYC capture yet).
class SignInScreen extends StatefulWidget {
  const SignInScreen({super.key});
  @override
  State<SignInScreen> createState() => _SignInScreenState();
}

class _SignInScreenState extends State<SignInScreen> {
  final _login = TextEditingController();
  final _password = TextEditingController();
  bool _busy = false, _hide = true;
  String? _error;

  Future<void> _go() async {
    if (_login.text.trim().isEmpty || _password.text.isEmpty) {
      setState(() => _error = 'Enter your phone number or email and your password.');
      return;
    }
    setState(() { _busy = true; _error = null; });
    try {
      await session.signIn(_login.text.trim(), _password.text);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (e) {
      setState(() => _error = 'Something went wrong. Try again.');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        body: SafeArea(
          child: ListView(padding: const EdgeInsets.fromLTRB(24, 32, 24, 24), children: [
            Container(
              width: 56, height: 56, alignment: Alignment.center,
              decoration: BoxDecoration(color: C.blue, borderRadius: BorderRadius.circular(18)),
              child: Text('A', style: sora(26, c: Colors.white)),
            ),
            const SizedBox(height: 28),
            Text('Welcome back', style: sora(28)),
            const SizedBox(height: 8),
            Text('Sign in with the phone number or email and password you use on the Aimzone website.', style: fig(16, c: C.slate)),
            const SizedBox(height: 28),
            TextField(
              controller: _login, keyboardType: TextInputType.emailAddress, autofillHints: const [AutofillHints.username],
              decoration: const InputDecoration(labelText: 'Phone number or email', prefixIcon: Padding(padding: EdgeInsets.all(14), child: HIcon('device-phone-mobile', color: C.slate))),
            ),
            const SizedBox(height: 14),
            TextField(
              controller: _password, obscureText: _hide, autofillHints: const [AutofillHints.password], onSubmitted: (_) => _go(),
              decoration: InputDecoration(
                labelText: 'Password',
                prefixIcon: const Padding(padding: EdgeInsets.all(14), child: HIcon('lock-closed', color: C.slate)),
                suffixIcon: IconButton(onPressed: () => setState(() => _hide = !_hide), icon: HIcon(_hide ? 'eye' : 'eye-slash', color: C.slate), tooltip: _hide ? 'Show' : 'Hide'),
              ),
            ),
            if (_error != null) ...[const SizedBox(height: 14), Notice(title: 'Could not sign in', message: _error!, tone: 'rose')],
            const SizedBox(height: 24),
            PillButton('Sign in', busy: _busy, onPressed: _go),
            const SizedBox(height: 12),
            PillButton('New here? Sign up on the website', outline: true, icon: 'arrow-top-right-on-square',
                onPressed: () => launchUrl(Uri.parse('https://loans.sharet.africa/register'), mode: LaunchMode.externalApplication)),
            const SizedBox(height: 24),
            Row(mainAxisAlignment: MainAxisAlignment.center, children: [
              const HIcon('shield-check', size: 18, color: C.slate),
              const SizedBox(width: 6),
              Text('Aimzone test build', style: fig(12, c: C.slate)),
            ]),
          ]),
        ),
      );
}
