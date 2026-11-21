import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/theme.dart';
import '../main.dart';
import '../ui/widgets.dart';
import 'home.dart';

/// Partners (owner, 21 Nov 2026: RBAC): their own portfolio, read only. Adding funds and withdrawing stay on the website.
class PartnerTab extends StatefulWidget {
  const PartnerTab({super.key});
  @override
  State<PartnerTab> createState() => _PartnerTabState();
}

class _PartnerTabState extends State<PartnerTab> {
  late Future<Map<String, dynamic>> _f = session.api.get('/partner');

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: const BackBar('Portfolio'),
        body: RefreshIndicator(
          onRefresh: () async {
            setState(() => _f = session.api.get('/partner'));
            await session.refresh();
          },
          child: FutureBuilder(
            future: _f,
            builder: (context, s) {
              if (s.hasError) return ListView(children: [Padding(padding: const EdgeInsets.all(20), child: Notice(title: 'Could not load', message: '${s.error}', tone: 'rose'))]);
              if (!s.hasData) return const Center(child: CircularProgressIndicator());
              final d = s.data!;
              var i = 0;
              Widget fx(Widget w) => FadeIn(index: i++, child: w);
              return ListView(padding: const EdgeInsets.fromLTRB(18, 8, 18, 24), children: [
                if (session.onHold && !session.may('loans')) ...[fx(const KycHoldCard()), const SizedBox(height: 14)],
                if (d['linked'] != true)
                  fx(Notice(title: 'Almost there', message: '${d['message']}', tone: 'blue'))
                else ...[
                  fx(Panel(
                    color: C.blue,
                    padding: const EdgeInsets.all(20),
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text('Available to withdraw', style: fig(13, c: C.mist)),
                      const SizedBox(height: 6),
                      CountUp((d['available'] as num?) ?? 0, style: outfit(32, c: Colors.white)),
                      const SizedBox(height: 4),
                      Text('${d['name']} · ${kes(d['lent_out'] as num?)} lent out now', style: fig(12, c: C.mist)),
                    ]),
                  )),
                  const SizedBox(height: 12),
                  fx(Row(children: [
                    Expanded(child: _Figure('Earned', d['earned'] as num?)),
                    const SizedBox(width: 10),
                    Expanded(child: _Figure('This month', d['earned_this_month'] as num?)),
                  ])),
                  const SizedBox(height: 10),
                  fx(Row(children: [
                    Expanded(child: _Figure('Committed', d['committed'] as num?)),
                    const SizedBox(width: 10),
                    Expanded(child: _Figure('Returned', d['returned'] as num?)),
                  ])),
                  const SizedBox(height: 16),
                  fx(Text('Recent', style: sora(16))),
                  const SizedBox(height: 8),
                  if ((d['recent'] as List).isEmpty)
                    fx(const Notice(title: 'Nothing yet', message: 'Your entries show here once money moves.', tone: 'blue'))
                  else
                    fx(Panel(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                      child: Column(children: [
                        for (final e in (d['recent'] as List).cast<Map>())
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            child: Row(children: [
                              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                Text('${e['type']}', style: fig(14, w: FontWeight.w600)),
                                Text(day(e['date'] as String?), style: fig(12, c: C.slate)),
                              ])),
                              Text(kes(e['amount'] as num?), style: fig(14, w: FontWeight.w600)),
                            ]),
                          ),
                      ]),
                    )),
                  const SizedBox(height: 16),
                  fx(PillButton('Add funds or withdraw on the website', outline: true, icon: 'arrow-top-right-on-square',
                      onPressed: () => launchUrl(Uri.parse('${d['website']}'), mode: LaunchMode.externalApplication))),
                ],
              ]);
            },
          ),
        ),
      );
}

class _Figure extends StatelessWidget {
  const _Figure(this.label, this.value);
  final String label;
  final num? value;
  @override
  Widget build(BuildContext context) => Panel(
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(label, style: fig(12, c: C.slate)),
          const SizedBox(height: 4),
          Text(kes(value), style: outfit(18)),
        ]),
      );
}
