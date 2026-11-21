import 'package:flutter/material.dart';

import '../core/theme.dart';
import '../main.dart';
import '../ui/widgets.dart';

/// Welcome 1–5 in the design file: three short slides, then sign in.
class WelcomeScreen extends StatefulWidget {
  const WelcomeScreen({super.key});
  @override
  State<WelcomeScreen> createState() => _WelcomeScreenState();
}

class _WelcomeScreenState extends State<WelcomeScreen> {
  final _pages = PageController();
  int _i = 0;
  static const _slides = [
    ('bolt', 'Money in minutes', 'Borrow from KES 1,000 straight to your M-Pesa. No paperwork, no queue.'),
    ('wallet', 'Repay your way', 'M-Pesa prompt, paybill or Sharet Pay. Pay part or all, any day before it is due.'),
    ('arrow-trending-up', 'Your limit grows', 'Every loan you repay on time raises what you can borrow next time.'),
  ];

  @override
  Widget build(BuildContext context) => Scaffold(
        body: SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(24, 12, 24, 24),
            child: Column(children: [
              Row(children: [
                Text('Aimzone', style: sora(20, c: C.blue)),
                const Spacer(),
                TextButton(onPressed: session.markWelcomeSeen, child: Text('Skip', style: fig(15, w: FontWeight.w600, c: C.slate))),
              ]),
              Expanded(
                child: PageView(
                  controller: _pages,
                  onPageChanged: (i) => setState(() => _i = i),
                  children: [
                    for (final (icon, title, body) in _slides)
                      Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                        Container(
                          width: 168, height: 168,
                          decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                          child: Center(child: HIcon(icon, size: 76, color: C.blue)),
                        ),
                        const SizedBox(height: 40),
                        Text(title, style: sora(28), textAlign: TextAlign.center),
                        const SizedBox(height: 12),
                        Text(body, style: fig(16, c: C.slate), textAlign: TextAlign.center),
                      ]),
                  ],
                ),
              ),
              Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                for (var d = 0; d < _slides.length; d++)
                  AnimatedContainer(
                    duration: const Duration(milliseconds: 200),
                    margin: const EdgeInsets.symmetric(horizontal: 4),
                    width: d == _i ? 24 : 8, height: 8,
                    decoration: BoxDecoration(color: d == _i ? C.blue : C.mist, borderRadius: BorderRadius.circular(99)),
                  ),
              ]),
              const SizedBox(height: 28),
              PillButton(_i < _slides.length - 1 ? 'Next' : 'Get started', onPressed: () {
                if (_i < _slides.length - 1) {
                  _pages.nextPage(duration: const Duration(milliseconds: 280), curve: Curves.easeOut);
                } else {
                  session.markWelcomeSeen();
                }
              }),
              const SizedBox(height: 10),
              TextButton(onPressed: session.markWelcomeSeen, child: Text('I have an account', style: fig(15, w: FontWeight.w600, c: C.blue))),
            ]),
          ),
        ),
      );
}
