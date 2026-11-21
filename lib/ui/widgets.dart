import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:intl/intl.dart';

import '../core/api.dart';
import '../core/theme.dart';
import '../main.dart';

/// A Heroicon (outline, 24px grid) from assets/icons, tinted.
class HIcon extends StatelessWidget {
  const HIcon(this.name, {super.key, this.size = 22, this.color = C.ink});
  final String name;
  final double size;
  final Color color;
  @override
  Widget build(BuildContext context) => SvgPicture.asset('assets/icons/$name.svg',
      width: size, height: size, colorFilter: ColorFilter.mode(color, BlendMode.srcIn));
}

final _kes = NumberFormat('#,##0', 'en_US');
final _kes2 = NumberFormat('#,##0.00', 'en_US');
String kes(num? v, {bool cents = false}) => 'KES ${(cents ? _kes2 : _kes).format(v ?? 0)}';
String day(String? iso) => iso == null ? '' : DateFormat('d MMM yyyy').format(DateTime.parse(iso));

class Panel extends StatelessWidget {
  const Panel({super.key, required this.child, this.color = Colors.white, this.padding = const EdgeInsets.all(16), this.onTap});
  final Widget child;
  final Color color;
  final EdgeInsets padding;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) => Material(
        color: color,
        borderRadius: BorderRadius.circular(20),
        child: InkWell(borderRadius: BorderRadius.circular(20), onTap: onTap, child: Padding(padding: padding, child: child)),
      );
}

class PillButton extends StatelessWidget {
  const PillButton(this.label, {super.key, this.onPressed, this.busy = false, this.outline = false, this.icon});
  final String label;
  final VoidCallback? onPressed;
  final bool busy;
  final bool outline;
  final String? icon;
  @override
  Widget build(BuildContext context) {
    final fg = outline ? C.blue : Colors.white;
    final child = busy
        ? SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4, color: fg))
        : Row(mainAxisSize: MainAxisSize.min, children: [
            if (icon != null) ...[HIcon(icon!, size: 20, color: fg), const SizedBox(width: 8)],
            Text(label, style: fig(16, w: FontWeight.w600, c: fg)),
          ]);
    return SizedBox(
      width: double.infinity,
      height: 50,
      child: outline
          ? OutlinedButton(
              onPressed: busy ? null : onPressed,
              style: OutlinedButton.styleFrom(shape: const StadiumBorder(), side: const BorderSide(color: C.mist, width: 1.5)),
              child: child)
          : FilledButton(
              onPressed: busy ? null : onPressed,
              style: FilledButton.styleFrom(backgroundColor: C.blue, disabledBackgroundColor: C.blue.withValues(alpha: .45), shape: const StadiumBorder()),
              child: child),
    );
  }
}

class StatusChip extends StatelessWidget {
  const StatusChip(this.status, {super.key});
  final String status;
  @override
  Widget build(BuildContext context) {
    final (bg, fg, label) = switch (status) {
      'pending' => (C.amberBg, C.amber, 'With our team'),
      'approved' => (C.royalTint, C.blue, 'Approved'),
      'disbursed' || 'active' => (C.royalTint, C.blue, 'Active'),
      'overdue' => (C.roseBg, C.rose, 'Late'),
      'forbearance' => (C.amberBg, C.amber, 'More time'),
      'repaid' => (C.sageBg, C.sage, 'Repaid'),
      'defaulted' || 'recovery' => (C.roseBg, C.rose, 'In recovery'),
      'rejected' => (C.line, C.slate, 'Not approved'),
      'written_off' => (C.line, C.slate, 'Written off'),
      _ => (C.line, C.slate, status),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(99)),
      child: Text(label, style: fig(12, w: FontWeight.w600, c: fg)),
    );
  }
}

class Notice extends StatelessWidget {
  const Notice({super.key, required this.title, required this.message, this.tone = 'amber'});
  final String title;
  final String message;
  final String tone;
  @override
  Widget build(BuildContext context) {
    final (bg, fg, icon) = switch (tone) {
      'rose' => (C.roseBg, C.rose, 'exclamation-triangle'),
      'sage' => (C.sageBg, C.sage, 'check-circle'),
      'blue' => (C.royalTint, C.blue, 'information-circle'),
      _ => (C.amberBg, C.amber, 'information-circle'),
    };
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        HIcon(icon, size: 22, color: fg),
        const SizedBox(width: 12),
        Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(title, style: fig(15, w: FontWeight.w600, c: fg)),
          const SizedBox(height: 2),
          Text(message, style: fig(14, c: C.ink)),
        ])),
      ]),
    );
  }
}

void toast(BuildContext context, String message) => ScaffoldMessenger.of(context)
  ..hideCurrentSnackBar()
  ..showSnackBar(SnackBar(content: Text(message), behavior: SnackBarBehavior.floating, backgroundColor: C.ink));

class BackBar extends StatelessWidget implements PreferredSizeWidget {
  const BackBar(this.title, {super.key});
  final String title;
  @override
  Size get preferredSize => const Size.fromHeight(56);
  @override
  Widget build(BuildContext context) => AppBar(
        leading: Navigator.canPop(context)
            ? IconButton(icon: const HIcon('chevron-left'), onPressed: () => Navigator.pop(context), tooltip: 'Back')
            : null,
        title: Text(title),
      );
}

/// Fades and slides a child in; [index] staggers a list (owner, 15 Nov 2026: "cool animations").
class FadeIn extends StatelessWidget {
  const FadeIn({super.key, required this.child, this.index = 0});
  final Widget child;
  final int index;
  @override
  Widget build(BuildContext context) => TweenAnimationBuilder<double>(
        tween: Tween(begin: 0, end: 1),
        duration: Duration(milliseconds: 380 + 70 * index.clamp(0, 8)),
        curve: Curves.easeOutCubic,
        builder: (context, t, child) => Opacity(
          opacity: (t * 1.4 - 0.4 * (index.clamp(0, 8) / 8)).clamp(0.0, 1.0),
          child: Transform.translate(offset: Offset(0, 18 * (1 - t)), child: child),
        ),
        child: child,
      );
}

/// A shilling amount that counts up to its value.
class CountUp extends StatelessWidget {
  const CountUp(this.value, {super.key, required this.style});
  final num value;
  final TextStyle style;
  @override
  Widget build(BuildContext context) => TweenAnimationBuilder<double>(
        tween: Tween(begin: 0, end: value.toDouble()),
        duration: const Duration(milliseconds: 900),
        curve: Curves.easeOutCubic,
        builder: (context, v, _) => Text(kes(v), style: style),
      );
}

/// The signed-in person's photo (through the API), or their first letter.
class Avatar extends StatelessWidget {
  const Avatar({super.key, this.radius = 22});
  final double radius;
  @override
  Widget build(BuildContext context) {
    final letter = session.firstName.isEmpty ? '?' : session.firstName[0].toUpperCase();
    final fallback = CircleAvatar(radius: radius, backgroundColor: C.royalTint, child: Text(letter, style: sora(radius * 0.8, c: C.blue)));
    if (session.user['has_photo'] != true) return fallback;
    return ClipOval(
      child: Image.network('$apiBase/me/photo', headers: session.api.imageHeaders, width: radius * 2, height: radius * 2, fit: BoxFit.cover,
          errorBuilder: (_, _, _) => fallback,
          loadingBuilder: (context, child, progress) => progress == null ? child : SizedBox(width: radius * 2, height: radius * 2, child: fallback)),
    );
  }
}
