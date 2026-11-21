import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../main.dart';
import '../ui/widgets.dart';
import 'loan.dart';

/// Borrow: amount, what it costs (from the server's quote), the terms, then the application.
class BorrowScreen extends StatefulWidget {
  const BorrowScreen({super.key});
  @override
  State<BorrowScreen> createState() => _BorrowScreenState();
}

class _BorrowScreenState extends State<BorrowScreen> {
  final _amount = TextEditingController();
  Map<String, dynamic>? _quote;
  Timer? _debounce;
  bool _agree = false, _busy = false;
  String? _error;

  int get _min => (session.me?['min_amount'] as num?)?.toInt() ?? 1000;
  int get _value => int.tryParse(_amount.text.replaceAll(RegExp(r'\D'), '')) ?? 0;

  void _changed(String _) {
    _debounce?.cancel();
    setState(() => _error = null);
    _debounce = Timer(const Duration(milliseconds: 350), _fetchQuote);
  }

  Future<void> _fetchQuote() async {
    if (_value < 1) return setState(() => _quote = null);
    try {
      final q = await session.api.post('/quotes', {'amount': _value});
      if (mounted) setState(() => _quote = q);
    } catch (_) {}
  }

  Future<void> _apply() async {
    final v = _value;
    if (v < _min || v > session.limit) {
      return setState(() => _error = 'Choose between ${kes(_min)} and ${kes(session.limit)}.');
    }
    if (!_agree) return setState(() => _error = 'Please accept the loan terms.');
    setState(() { _busy = true; _error = null; });
    try {
      final r = await session.api.post('/loans', {'amount': v, 'consent': true});
      await session.refresh();
      if (!mounted) return;
      final loan = r['loan'] as Map<String, dynamic>;
      Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => _Sent(loan: loan, quote: _quote)));
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final limit = session.limit;
    final picks = {for (final p in [_min, (limit / 2).round(), limit]) if (p >= _min && p <= limit) p}.toList();
    return Scaffold(
      appBar: const BackBar('Borrow'),
      body: ListView(padding: const EdgeInsets.fromLTRB(20, 8, 20, 24), children: [
        Text('How much do you need?', style: sora(22)),
        const SizedBox(height: 6),
        Text('Between ${kes(_min)} and ${kes(limit)}', style: fig(14, c: C.slate)),
        const SizedBox(height: 18),
        TextField(
          controller: _amount,
          keyboardType: TextInputType.number,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          style: outfit(30),
          onChanged: _changed,
          decoration: InputDecoration(prefixText: 'KES  ', prefixStyle: outfit(30, c: C.slate), hintText: '0'),
        ),
        const SizedBox(height: 12),
        Wrap(spacing: 8, children: [
          for (final p in picks)
            ChoiceChip(
              label: Text(kes(p)),
              selected: _value == p,
              shape: const StadiumBorder(),
              onSelected: (_) {
                _amount.text = '$p';
                _changed('');
              },
            ),
        ]),
        const SizedBox(height: 20),
        if (_quote != null)
          Panel(
            child: Column(children: [
              _row('You receive on M-Pesa', kes(_quote!['amount'] as num)),
              _row('Interest (${_quote!['rate']}%)', kes(_quote!['interest'] as num)),
              const Divider(height: 24, color: C.line),
              _row('You repay', kes(_quote!['total'] as num), bold: true),
              _row('Due by', day(_quote!['due_date'] as String?)),
              const SizedBox(height: 8),
              Text('Paying late adds ${_quote!['penalty_rate']}% of the balance as a penalty.', style: fig(13, c: C.slate)),
            ]),
          ),
        const SizedBox(height: 16),
        CheckboxListTile(
          value: _agree,
          onChanged: (v) => setState(() => _agree = v ?? false),
          contentPadding: EdgeInsets.zero,
          controlAffinity: ListTileControlAffinity.leading,
          title: Text('I accept the loan terms and agree to repay on time. My signature on file signs the agreement.', style: fig(14)),
        ),
        if (_error != null) ...[const SizedBox(height: 8), Notice(title: 'Check your application', message: _error!, tone: 'rose')],
        const SizedBox(height: 16),
        PillButton('Send application', busy: _busy, onPressed: _apply),
      ]),
    );
  }

  Widget _row(String a, String b, {bool bold = false}) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(children: [
          Expanded(child: Text(a, style: fig(14, c: C.slate))),
          Text(b, style: bold ? outfit(20) : fig(15, w: FontWeight.w600)),
        ]),
      );
}

class _Sent extends StatelessWidget {
  const _Sent({required this.loan, this.quote});
  final Map<String, dynamic> loan;
  final Map<String, dynamic>? quote;
  @override
  Widget build(BuildContext context) => Scaffold(
        body: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(children: [
              const Spacer(),
              Container(width: 112, height: 112, decoration: const BoxDecoration(color: C.sageBg, shape: BoxShape.circle),
                  child: const Center(child: HIcon('check-circle', size: 56, color: C.sage))),
              const SizedBox(height: 28),
              Text('Application sent', style: sora(26)),
              const SizedBox(height: 10),
              Text('${loan['reference']} for ${kes(loan['amount'] as num?)} is with our team. Once approved it goes to your M-Pesa.',
                  style: fig(16, c: C.slate), textAlign: TextAlign.center),
              if (quote != null) ...[
                const SizedBox(height: 8),
                Text('Repay ${kes(quote!['total'] as num)} by ${day(quote!['due_date'] as String?)}.', style: fig(16, w: FontWeight.w600), textAlign: TextAlign.center),
              ],
              const Spacer(),
              PillButton('See the loan', onPressed: () => Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => LoanScreen(id: loan['id'] as int)))),
              const SizedBox(height: 10),
              PillButton('Back home', outline: true, onPressed: () => Navigator.pop(context)),
            ]),
          ),
        ),
      );
}
