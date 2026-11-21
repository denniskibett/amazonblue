import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../main.dart';
import '../ui/widgets.dart';
import 'account.dart';
import 'borrow.dart';
import 'consent.dart';
import 'loan.dart';
import 'statement.dart';

/// Signed in: Home, Loans, Statement, Account along the bottom.
class HomeShell extends StatefulWidget {
  const HomeShell({super.key});
  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _tab = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => ConsentScreen.askOnce(context));
  }

  @override
  Widget build(BuildContext context) {
    const tabs = [('home', 'Home'), ('banknotes', 'Loans'), ('document-text', 'Statement'), ('user-circle', 'Account')];
    return Scaffold(
      body: IndexedStack(index: _tab, children: const [HomeTab(), LoansTab(), StatementTab(), AccountTab()]),
      bottomNavigationBar: NavigationBar(
        backgroundColor: Colors.white,
        indicatorColor: C.royalTint,
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        destinations: [
          for (final (icon, label) in tabs)
            NavigationDestination(icon: HIcon(icon, color: C.slate), selectedIcon: HIcon(icon, color: C.blue), label: label),
        ],
      ),
    );
  }
}

class HomeTab extends StatelessWidget {
  const HomeTab({super.key});

  @override
  Widget build(BuildContext context) => ListenableBuilder(
        listenable: session,
        builder: (context, _) {
          final loan = session.openLoan;
          final verified = session.me?['verified'] == true;
          final blockers = session.blockers.cast<Map>().where((b) => b['title'] != 'You already have a loan').toList();
          var i = 0;
          Widget fx(Widget w) => FadeIn(index: i++, child: w);
          return RefreshIndicator(
            onRefresh: () async {
              try {
                await session.refresh();
              } on ApiException catch (e) {
                if (context.mounted) toast(context, e.message);
              }
            },
            child: ListView(padding: const EdgeInsets.fromLTRB(18, 52, 18, 24), children: [
              fx(Row(children: [
                const Avatar(radius: 21),
                const SizedBox(width: 12),
                Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('Hello, ${session.firstName}', style: sora(22), maxLines: 1, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 2),
                  Text(verified ? 'Your account is verified' : 'Finish verifying to borrow', style: fig(13, c: C.slate)),
                ])),
              ])),
              const SizedBox(height: 18),
              // owner, 15 Nov 2026: with a loan out, home is that loan; the borrow card only when nothing is out
              if (loan != null) fx(_CurrentLoan(loan: loan)) else fx(_LimitCard(limit: session.limit, canBorrow: session.blockers.isEmpty)),
              const SizedBox(height: 14),
              for (final b in blockers) ...[
                fx(Notice(title: '${b['title']}', message: '${b['message']}')),
                const SizedBox(height: 10),
              ],
              if (!verified) ...[
                fx(PillButton('Verify on the website', outline: true, icon: 'arrow-top-right-on-square',
                    onPressed: () => launchUrl(Uri.parse('https://loans.sharet.africa/kyc'), mode: LaunchMode.externalApplication))),
                const SizedBox(height: 14),
              ],
              const SizedBox(height: 6),
              fx(Text('More from Aimzone', style: sora(16))),
              const SizedBox(height: 10),
              fx(const _Soon(icon: 'truck', title: 'Electric bike with Spiro', body: 'Ride now, pay daily. Coming soon.')),
              const SizedBox(height: 10),
              fx(const _Soon(icon: 'building-office-2', title: 'Trade finance', body: 'Stock and supplier payments, here and abroad. Coming soon.')),
            ]),
          );
        },
      );
}

class _LimitCard extends StatelessWidget {
  const _LimitCard({required this.limit, required this.canBorrow});
  final int limit;
  final bool canBorrow;
  @override
  Widget build(BuildContext context) => Panel(
        color: C.blue,
        padding: const EdgeInsets.all(20),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('You can borrow up to', style: fig(13, c: C.mist)),
          const SizedBox(height: 6),
          CountUp(limit, style: outfit(34, c: Colors.white)),
          const SizedBox(height: 4),
          Text('${session.me?['product']?['name'] ?? ''} · ${session.me?['product']?['period'] ?? ''}', style: fig(12, c: C.mist)),
          const SizedBox(height: 16),
          SizedBox(
            height: 46,
            width: double.infinity,
            child: FilledButton(
              onPressed: canBorrow ? () => Navigator.push(context, MaterialPageRoute(builder: (_) => const BorrowScreen())) : null,
              style: FilledButton.styleFrom(backgroundColor: Colors.white, foregroundColor: C.blue, disabledBackgroundColor: Colors.white24, shape: const StadiumBorder()),
              child: Text('Borrow now', style: fig(15, w: FontWeight.w600, c: canBorrow ? C.blue : Colors.white70)),
            ),
          ),
        ]),
      );
}

/// What is out now: balance, due day, how much is paid, and the way to pay.
class _CurrentLoan extends StatelessWidget {
  const _CurrentLoan({required this.loan});
  final Map<String, dynamic> loan;
  @override
  Widget build(BuildContext context) {
    final bal = loan['balance'] as Map?;
    final owed = (bal?['due'] ?? bal?['total'] ?? loan['amount']) as num? ?? 0;
    final progress = ((loan['progress'] as num?) ?? 0).toDouble().clamp(0, 100) / 100;
    final late = loan['is_late'] == true;
    final status = '${loan['status']}';
    final waiting = status == 'pending' || status == 'approved';
    void open() => Navigator.push(context, MaterialPageRoute(builder: (_) => LoanScreen(id: loan['id'] as int)));
    return Panel(
      color: C.blue,
      padding: const EdgeInsets.all(20),
      onTap: open,
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Expanded(child: Text(waiting ? 'Your loan request' : 'You owe', style: fig(13, c: C.mist))),
          StatusChip(status),
        ]),
        const SizedBox(height: 6),
        CountUp(waiting ? (loan['amount'] as num? ?? 0) : owed, style: outfit(34, c: Colors.white)),
        const SizedBox(height: 4),
        Text(
            [
              '${loan['reference']}',
              if (loan['due_date'] != null && !waiting) '${late ? 'was due' : 'due'} ${day(loan['due_date'] as String?)}',
              if (waiting) 'we are reviewing it',
            ].join(' · '),
            style: fig(12, c: late ? const Color(0xFFFFC9C9) : C.mist)),
        if (!waiting) ...[
          const SizedBox(height: 14),
          ClipRRect(
            borderRadius: BorderRadius.circular(99),
            child: TweenAnimationBuilder<double>(
              tween: Tween(begin: 0, end: progress.toDouble()),
              duration: const Duration(milliseconds: 900),
              curve: Curves.easeOutCubic,
              builder: (context, v, _) => LinearProgressIndicator(value: v, minHeight: 6, backgroundColor: Colors.white24, color: Colors.white),
            ),
          ),
          const SizedBox(height: 6),
          Text('${(progress * 100).round()}% paid of ${kes(bal?['total'] as num? ?? loan['amount'] as num?)}', style: fig(12, c: C.mist)),
        ],
        const SizedBox(height: 16),
        SizedBox(
          height: 46,
          width: double.infinity,
          child: FilledButton(
            onPressed: open,
            style: FilledButton.styleFrom(backgroundColor: Colors.white, foregroundColor: C.blue, shape: const StadiumBorder()),
            child: Text(waiting ? 'See your loan' : 'Pay now', style: fig(15, w: FontWeight.w600, c: C.blue)),
          ),
        ),
      ]),
    );
  }
}

class _Soon extends StatelessWidget {
  const _Soon({required this.icon, required this.title, required this.body});
  final String icon, title, body;
  @override
  Widget build(BuildContext context) => Panel(
        child: Row(children: [
          Container(
            width: 40, height: 40,
            decoration: BoxDecoration(color: C.sandBg, borderRadius: BorderRadius.circular(12)),
            child: Center(child: HIcon(icon, color: C.sand)),
          ),
          const SizedBox(width: 12),
          Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: fig(15, w: FontWeight.w600)),
            Text(body, style: fig(13, c: C.slate)),
          ])),
        ]),
      );
}

class LoansTab extends StatefulWidget {
  const LoansTab({super.key});
  @override
  State<LoansTab> createState() => _LoansTabState();
}

class _LoansTabState extends State<LoansTab> {
  Future<Map<String, dynamic>>? _f;
  @override
  void initState() {
    super.initState();
    _f = session.api.get('/loans');
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: const BackBar('Your loans'),
        body: RefreshIndicator(
          onRefresh: () async => setState(() => _f = session.api.get('/loans')),
          child: FutureBuilder(
            future: _f,
            builder: (context, s) {
              if (s.hasError) return ListView(children: [Padding(padding: const EdgeInsets.all(20), child: Notice(title: 'Could not load', message: '${s.error}', tone: 'rose'))]);
              if (!s.hasData) return const Center(child: CircularProgressIndicator());
              final loans = (s.data!['loans'] as List).cast<Map<String, dynamic>>();
              if (loans.isEmpty) {
                return ListView(padding: const EdgeInsets.all(20), children: [
                  const Notice(title: 'No loans yet', message: 'Your first loan will show here.', tone: 'blue'),
                ]);
              }
              return ListView.separated(
                padding: const EdgeInsets.all(20),
                itemCount: loans.length,
                separatorBuilder: (_, _) => const SizedBox(height: 10),
                itemBuilder: (context, i) {
                  final l = loans[i];
                  return FadeIn(index: i, child: Panel(
                    onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => LoanScreen(id: l['id'] as int))),
                    child: Row(children: [
                      Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text('${l['reference']}', style: fig(15, w: FontWeight.w600)),
                        Text('${kes(l['amount'] as num?)} · ${day(l['borrow_date'] as String?)}', style: fig(13, c: C.slate)),
                      ])),
                      StatusChip('${l['status']}'),
                    ]),
                  ));
                },
              );
            },
          ),
        ),
      );
}
