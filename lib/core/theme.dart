import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// Same tokens as the website (resources/design/tokens.cjs) and the design file's Mobile app page.
class C {
  static const ink = Color(0xFF151A2E);
  static const blue = Color(0xFF1B2B6B);
  static const link = Color(0xFF2B4FD8);
  static const mist = Color(0xFFC9D6F0);
  static const cloud = Color(0xFFEEF2FA);
  static const line = Color(0xFFDDE3EF);
  static const slate = Color(0xFF5A6178);
  static const royalTint = Color(0xFFE6ECFC);
  static const sage = Color(0xFF2F6B4F);
  static const sageBg = Color(0xFFDCEBE2);
  static const rose = Color(0xFF9B2C3B);
  static const roseBg = Color(0xFFF6DCE0);
  static const sand = Color(0xFF7A5B2E);
  static const sandBg = Color(0xFFF4EDE2);
  static const amber = Color(0xFF7A5200);
  static const amberBg = Color(0xFFFDF1D6);
}

/// Type runs ~10% under the design file so everything fits a small phone (owner, 15 Nov 2026).
const _scale = 0.9;

TextStyle sora(double size, {FontWeight w = FontWeight.w700, Color c = C.ink}) => GoogleFonts.sora(fontSize: size * _scale, fontWeight: w, color: c, height: 1.2);
TextStyle fig(double size, {FontWeight w = FontWeight.w400, Color c = C.ink}) => GoogleFonts.figtree(fontSize: size * _scale, fontWeight: w, color: c, height: 1.4);
TextStyle outfit(double size, {FontWeight w = FontWeight.w600, Color c = C.ink}) => GoogleFonts.outfit(fontSize: size * _scale, fontWeight: w, color: c, height: 1.1);

ThemeData appTheme() {
  final base = ThemeData(useMaterial3: true, colorScheme: ColorScheme.fromSeed(seedColor: C.blue, primary: C.blue, surface: Colors.white));
  return base.copyWith(
    scaffoldBackgroundColor: C.cloud,
    textTheme: base.textTheme.apply(fontFamily: GoogleFonts.figtree().fontFamily, bodyColor: C.ink, displayColor: C.ink),
    appBarTheme: AppBarTheme(backgroundColor: C.cloud, foregroundColor: C.ink, elevation: 0, scrolledUnderElevation: 0, titleTextStyle: sora(18)),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: C.line)),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: C.line)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: C.blue, width: 1.5)),
      labelStyle: fig(14, c: C.slate),
    ),
  );
}
