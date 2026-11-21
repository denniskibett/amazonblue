import 'package:flutter/material.dart';

import '../core/theme.dart';
import '../main.dart';
import '../ui/widgets.dart';
import 'loan.dart';

class StatementTab extends StatefulWidget {
  const StatementTab({super.key});
  @override
  State<StatementTab> createState() => _StatementTabState();
}

class _StatementTabState extends State<StatementTab> {
  late Future<Map<String, dynamic>> _f = session.api.get('/statement');

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: const BackBar('Statement'),
        body: RefreshIndicator(
          onRefresh: () async => setState(() => _f = session.api.get('/statement')),
          child: FutureBuilder(
            future: _f,
            builder: (context, s) {
              if (s.hasError) return ListView(children: [Padding(padding: const EdgeInsets.all(20), child: Notice(title: 'Could not load', message: '${s.error}', tone: 'rose'))]);
              if (!s.hasData) return const Center(child: CircularProgressIndicator());
              final rows = (s.data!['repayments'] as List).cast<Map>();
              return ListView(padding: const EdgeInsets.fromLTRB(18, 8, 18, 24), children: [
                FadeIn(child: Panel(
                  color: C.blue,
                  child: Row(children: [
                    const HIcon('receipt-percent', color: Colors.white),
                    const SizedBox(width: 12),
                    Expanded(child: Text('Paid in total', style: fig(14, c: C.mist))),
                    CountUp((s.data!['total_paid'] as num?) ?? 0, style: outfit(22, c: Colors.white)),
                  ]),
                )),
                const SizedBox(height: 16),
                if (rows.isEmpty)
                  const Notice(title: 'No payments yet', message: 'Your payments show here once they are recorded.', tone: 'blue')
                else
                  FadeIn(index: 1, child: Panel(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                    child: Column(children: [for (final r in rows) PaymentRow(r: r)]),
                  )),
              ]);
            },
          ),
        ),
      );
}
