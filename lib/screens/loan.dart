import 'package:flutter/material.dart';

import '../core/theme.dart';
import '../main.dart';
import '../ui/widgets.dart';
import 'repay.dart';

/// Loan details: what is due, by when, the timeline and the payments so far.
class LoanScreen extends StatefulWidget {
  const LoanScreen({super.key, required this.id});
  final int id;
  @override
  State<LoanScreen> createState() => _LoanScreenState();
}

class _LoanScreenState extends State<LoanScreen> {
  late Future<Map<String, dynamic>> _f = session.api.get('/loans/${widget.id}');
  void _reload() => setState(() => _f = session.api.get('/loans/${widget.id}'));

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: const BackBar('Loan details'),
        body: FutureBuilder(
          future: _f,
          builder: (context, s) {
            if (s.hasError) return Padding(padding: const EdgeInsets.all(20), child: Notice(title: 'Could not load', message: '${s.error}', tone: 'rose'));
            if (!s.hasData) return const Center(child: CircularProgressIndicator());
            final d = s.data!;
            final loan = d['loan'] as Map<String, dynamic>;
            final bal = (d['balance'] as Map?) ?? {};
            final pay = d['pay'] as Map<String, dynamic>;
            final late = d['is_late'] == true;
            final due = (bal['due'] as num?) ?? 0;
            return RefreshIndicator(
              onRefresh: () async => _reload(),
              child: ListView(padding: const EdgeInsets.fromLTRB(20, 8, 20, 24), children: [
                Panel(
                  color: late ? C.rose : C.blue,
                  padding: const EdgeInsets.all(22),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Row(children: [
                      Text('${d['reference']}', style: fig(14, c: Colors.white70)),
                      const Spacer(),
                      StatusChip('${loan['status']}'),
                    ]),
                    const SizedBox(height: 10),
                    Text(loan['status'] == 'repaid' ? 'Fully repaid' : 'Left to pay', style: fig(14, c: Colors.white70)),
                    Text(kes(due, cents: due % 1 != 0), style: outfit(34, c: Colors.white)),
                    if (d['due_date'] != null && loan['status'] != 'repaid')
                      Text('${late ? 'Was due' : 'Due'} ${day(d['due_date'] as String?)}', style: fig(14, c: Colors.white)),
                    const SizedBox(height: 14),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(99),
                      child: LinearProgressIndicator(value: ((d['progress'] as num?) ?? 0) / 100, minHeight: 8, backgroundColor: Colors.white24, color: Colors.white),
                    ),
                    const SizedBox(height: 6),
                    Text('Paid ${kes(bal['paid'] as num?)} of ${kes(bal['total'] as num?)}', style: fig(13, c: Colors.white70)),
                  ]),
                ),
                const SizedBox(height: 16),
                if (late) ...[
                  const Notice(title: 'This loan is late', message: 'Pay what you can today. Talk to us if you need more time.', tone: 'rose'),
                  const SizedBox(height: 16),
                ],
                if (pay['recovery'] == true) ...[
                  const Notice(title: 'With our recovery team', message: 'Pay by Paybill or Sharet Pay and our team will be in touch about a plan.', tone: 'rose'),
                  const SizedBox(height: 16),
                ],
                if ((pay['can_pay'] == true || pay['recovery'] == true) && due > 0) ...[
                  PillButton('Repay', icon: 'wallet', onPressed: () async {
                    await Navigator.push(context, MaterialPageRoute(builder: (_) => RepayScreen(loanId: loan['id'] as int, due: due.toDouble(), pay: pay)));
                    _reload();
                    session.refresh();
                  }),
                  const SizedBox(height: 20),
                ],
                Text('Progress', style: sora(17)),
                const SizedBox(height: 10),
                Panel(
                  child: Column(children: [
                    for (final step in (d['timeline'] as List).cast<Map>())
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          HIcon(step['done'] == true ? 'check-circle' : 'clock', color: step['tone'] == 'red' ? C.rose : (step['done'] == true ? C.sage : C.mist)),
                          const SizedBox(width: 12),
                          Expanded(
                              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text('${step['label']}', style: fig(15, w: FontWeight.w600)),
                            if (step['detail'] != null) Text('${step['detail']}', style: fig(13, c: C.slate)),
                          ])),
                          if (step['date'] != null) Text(day(step['date'] as String?), style: fig(12, c: C.slate)),
                        ]),
                      ),
                  ]),
                ),
                const SizedBox(height: 20),
                Text('Payments', style: sora(17)),
                const SizedBox(height: 10),
                if ((d['repayments'] as List).isEmpty)
                  Text('No payments yet.', style: fig(14, c: C.slate))
                else
                  Panel(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                    child: Column(children: [for (final r in (d['repayments'] as List).cast<Map>()) PaymentRow(r: r)]),
                  ),
              ]),
            );
          },
        ),
      );
}

class PaymentRow extends StatelessWidget {
  const PaymentRow({super.key, required this.r});
  final Map r;
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 10),
        child: Row(children: [
          const HIcon('check-circle', color: C.sage),
          const SizedBox(width: 12),
          Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(kes(r['amount'] as num?, cents: ((r['amount'] as num?) ?? 0) % 1 != 0), style: fig(15, w: FontWeight.w600)),
            Text([day(r['date'] as String?), if (r['reference'] != null) '${r['reference']}', if (r['loan_id'] != null) 'Loan #${r['loan_id']}'].join(' · '),
                style: fig(13, c: C.slate)),
          ])),
          if (r['kind'] != null && r['kind'] != 'cash') Text('${r['kind']}'.replaceAll('_', ' '), style: fig(12, c: C.slate)),
        ]),
      );
}
