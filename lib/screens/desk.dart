import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/theme.dart';
import '../main.dart';
import '../ui/widgets.dart';

/// Loan staff (owner, 21 Nov 2026: RBAC): the desk queues they are allowed to see, as counts. Decisions are made on
/// the website, where each queue opens.
class DeskTab extends StatefulWidget {
  const DeskTab({super.key});
  @override
  State<DeskTab> createState() => _DeskTabState();
}

class _DeskTabState extends State<DeskTab> {
  late Future<Map<String, dynamic>> _f = session.api.get('/desk');

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: const BackBar('Loan desk'),
        body: RefreshIndicator(
          onRefresh: () async => setState(() => _f = session.api.get('/desk')),
          child: FutureBuilder(
            future: _f,
            builder: (context, s) {
              if (s.hasError) return ListView(children: [Padding(padding: const EdgeInsets.all(20), child: Notice(title: 'Could not load', message: '${s.error}', tone: 'rose'))]);
              if (!s.hasData) return const Center(child: CircularProgressIndicator());
              final queues = (s.data!['queues'] as List).cast<Map>();
              return ListView(padding: const EdgeInsets.fromLTRB(18, 8, 18, 24), children: [
                FadeIn(child: Text('Hello, ${session.firstName}', style: sora(22))),
                const SizedBox(height: 4),
                FadeIn(index: 1, child: Text('What is waiting on the team', style: fig(13, c: C.slate))),
                const SizedBox(height: 16),
                for (final (n, q) in queues.indexed) ...[
                  FadeIn(index: n + 2, child: Panel(
                    onTap: () => launchUrl(Uri.parse('${q['url']}'), mode: LaunchMode.externalApplication),
                    child: Row(children: [
                      Expanded(child: Text('${q['label']}', style: fig(15, w: FontWeight.w600))),
                      Text('${q['count']}', style: outfit(22, c: (q['count'] as num? ?? 0) > 0 ? C.blue : C.slate)),
                      const SizedBox(width: 8),
                      const HIcon('chevron-right', size: 18, color: C.slate),
                    ]),
                  )),
                  const SizedBox(height: 10),
                ],
                const SizedBox(height: 6),
                PillButton('Open the dashboard', outline: true, icon: 'arrow-top-right-on-square',
                    onPressed: () => launchUrl(Uri.parse('${s.data!['website']}'), mode: LaunchMode.externalApplication)),
              ]);
            },
          ),
        ),
      );
}
