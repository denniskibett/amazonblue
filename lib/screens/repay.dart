import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../main.dart';
import '../ui/widgets.dart';

/// Repay 1–3 in the design file: how much, then M-Pesa prompt, Sharet Pay, or "I already paid" with the code.
/// Only the payment callbacks record money; the app asks for it and watches the loan.
class RepayScreen extends StatefulWidget {
  const RepayScreen({super.key, required this.loanId, required this.due, required this.pay});
  final int loanId;
  final double due;
  final Map<String, dynamic> pay;
  @override
  State<RepayScreen> createState() => _RepayScreenState();
}

class _RepayScreenState extends State<RepayScreen> {
  late final _amount = TextEditingController(text: '${widget.due.ceil()}');
  String _how = '';
  bool _busy = false;
  String? _error, _waiting;
  Timer? _poll;

  int get _value => int.tryParse(_amount.text) ?? 0;
  String get _base => '/loans/${widget.loanId}/repay';

  @override
  void initState() {
    super.initState();
    _how = widget.pay['mpesa_prompt'] == true ? 'stk' : (widget.pay['sharet'] == true ? 'sharet' : 'paybill');
  }

  @override
  void dispose() {
    _poll?.cancel();
    super.dispose();
  }

  Future<void> _go() async {
    if (_value < 1 || _value > widget.due.ceil()) return setState(() => _error = 'Pay between KES 1 and ${kes(widget.due.ceil())}.');
    setState(() { _busy = true; _error = null; });
    try {
      if (_how == 'stk') {
        final r = await session.api.post('$_base/stk', {'amount': _value});
        setState(() => _waiting = 'Check your phone and enter your M-Pesa PIN.');
        _watchStk(r['request_id'] as int);
      } else if (_how == 'sharet') {
        final r = await session.api.post('$_base/sharet', {'amount': _value});
        await launchUrl(Uri.parse(r['pay_url'] as String), mode: LaunchMode.inAppBrowserView);
        setState(() => _waiting = 'Finish paying on Sharet Pay. We update your loan as soon as Sharet tells us.');
        _watchLoan();
      }
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _watchStk(int id) {
    var tries = 0;
    _poll = Timer.periodic(const Duration(seconds: 4), (t) async {
      if (++tries > 30) {
        t.cancel();
        return setState(() => _waiting = 'No answer from M-Pesa yet. If you paid, it will show on your loan shortly.');
      }
      try {
        final s = await session.api.get('$_base/stk/$id');
        if (s['status'] == 'matched') {
          t.cancel();
          if (mounted) _paid();
        } else if (s['status'] == 'failed') {
          t.cancel();
          setState(() { _waiting = null; _error = '${s['message'] ?? 'The M-Pesa payment did not go through.'}'; });
        }
      } catch (_) {}
    });
  }

  void _watchLoan() {
    final before = widget.due;
    var tries = 0;
    _poll = Timer.periodic(const Duration(seconds: 5), (t) async {
      if (++tries > 36) return t.cancel();
      try {
        final d = await session.api.get('/loans/${widget.loanId}');
        final due = ((d['balance'] as Map?)?['due'] as num?) ?? before;
        if (due < before) {
          t.cancel();
          if (mounted) _paid();
        }
      } catch (_) {}
    });
  }

  void _paid() => Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => _Paid(amount: _value)));

  @override
  Widget build(BuildContext context) {
    final methods = [
      if (widget.pay['mpesa_prompt'] == true) ('stk', 'device-phone-mobile', 'M-Pesa prompt', 'We send a prompt to your phone'),
      if (widget.pay['sharet'] == true) ('sharet', 'credit-card', 'Sharet Pay', 'M-Pesa or card on Sharet\'s page'),
      ('paybill', 'banknotes', 'Paybill', 'Pay from M-Pesa, then send us the code'),
    ];
    return Scaffold(
      appBar: const BackBar('Repay'),
      body: ListView(padding: const EdgeInsets.fromLTRB(20, 8, 20, 24), children: [
        Text('How much?', style: sora(22)),
        const SizedBox(height: 4),
        Text('You owe ${kes(widget.due, cents: widget.due % 1 != 0)}. Pay part or all.', style: fig(14, c: C.slate)),
        const SizedBox(height: 14),
        TextField(
          controller: _amount, keyboardType: TextInputType.number, inputFormatters: [FilteringTextInputFormatter.digitsOnly], style: outfit(30),
          decoration: InputDecoration(prefixText: 'KES  ', prefixStyle: outfit(30, c: C.slate)),
          onChanged: (_) => setState(() {}),
        ),
        const SizedBox(height: 22),
        Text('How do you want to pay?', style: sora(17)),
        const SizedBox(height: 10),
        for (final (key, icon, title, sub) in methods) ...[
          Panel(
            color: _how == key ? C.royalTint : Colors.white,
            padding: const EdgeInsets.all(16),
            onTap: () => setState(() { _how = key; _error = null; }),
            child: Row(children: [
              HIcon(icon, color: C.blue),
              const SizedBox(width: 14),
              Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(title, style: fig(15, w: FontWeight.w600)),
                Text(sub, style: fig(13, c: C.slate)),
              ])),
              if (_how == key) const HIcon('check-circle', color: C.blue),
            ]),
          ),
          const SizedBox(height: 10),
        ],
        const SizedBox(height: 8),
        if (_how == 'paybill') _PaybillSteps(pay: widget.pay, loanId: widget.loanId, amount: _value, max: widget.due)
        else ...[
          if (_waiting != null) ...[Notice(title: 'Waiting for your payment', message: _waiting!, tone: 'blue'), const SizedBox(height: 12)],
          if (_error != null) ...[Notice(title: 'Payment not started', message: _error!, tone: 'rose'), const SizedBox(height: 12)],
          PillButton(_how == 'stk' ? 'Send the prompt' : 'Open Sharet Pay', busy: _busy || (_waiting != null && _poll?.isActive == true), onPressed: _go),
        ],
      ]),
    );
  }
}

class _PaybillSteps extends StatefulWidget {
  const _PaybillSteps({required this.pay, required this.loanId, required this.amount, required this.max});
  final Map<String, dynamic> pay;
  final int loanId, amount;
  final double max;
  @override
  State<_PaybillSteps> createState() => _PaybillStepsState();
}

class _PaybillStepsState extends State<_PaybillSteps> {
  final _code = TextEditingController();
  bool _busy = false;
  String? _error, _done;

  Future<void> _send() async {
    setState(() { _busy = true; _error = null; });
    try {
      final r = await session.api.post('/loans/${widget.loanId}/repay/claim', {'code': _code.text, 'amount': widget.amount});
      setState(() => _done = r['status'] == 'matched'
          ? 'Payment ${r['code']} is on your loan. Thank you!'
          : 'We are checking ${r['code']}. It shows on your loan once it matches the M-Pesa record.');
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Panel(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('On M-Pesa: Lipa na M-Pesa › Pay Bill', style: fig(14, c: C.slate)),
            const SizedBox(height: 10),
            _line('Business number', '${widget.pay['paybill'] ?? 'Ask us'}'),
            _line('Account number', '${widget.pay['account']}'),
            _line('Amount', kes(widget.amount)),
          ]),
        ),
        const SizedBox(height: 16),
        Text('Already paid? Send us the M-Pesa code', style: fig(15, w: FontWeight.w600)),
        const SizedBox(height: 8),
        TextField(controller: _code, textCapitalization: TextCapitalization.characters, decoration: const InputDecoration(labelText: 'M-Pesa code, e.g. QJK8X2LM4P')),
        const SizedBox(height: 12),
        if (_done != null) ...[Notice(title: 'Thank you', message: _done!, tone: 'sage'), const SizedBox(height: 12)],
        if (_error != null) ...[Notice(title: 'Check the code', message: _error!, tone: 'rose'), const SizedBox(height: 12)],
        PillButton('I already paid', busy: _busy, onPressed: _done == null ? _send : null),
      ]);

  Widget _line(String a, String b) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(children: [
          Expanded(child: Text(a, style: fig(14, c: C.slate))),
          Text(b, style: outfit(18)),
          IconButton(
            visualDensity: VisualDensity.compact,
            tooltip: 'Copy',
            icon: const HIcon('document-text', size: 18, color: C.slate),
            onPressed: () {
              Clipboard.setData(ClipboardData(text: b.replaceAll(RegExp(r'[^0-9A-Za-z]'), '')));
              toast(context, 'Copied');
            },
          ),
        ]),
      );
}

class _Paid extends StatelessWidget {
  const _Paid({required this.amount});
  final int amount;
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
              Text('Payment received', style: sora(26)),
              const SizedBox(height: 10),
              Text('${kes(amount)} is on your loan. Repaying on time grows your limit.', style: fig(16, c: C.slate), textAlign: TextAlign.center),
              const Spacer(),
              PillButton('Done', onPressed: () => Navigator.pop(context)),
            ]),
          ),
        ),
      );
}
