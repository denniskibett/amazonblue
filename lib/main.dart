import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'core/session.dart';
import 'core/theme.dart';
import 'screens/home.dart';
import 'screens/sign_in.dart';
import 'screens/welcome.dart';

const testBuild = bool.fromEnvironment('TEST_BUILD', defaultValue: false);

final session = Session();

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(statusBarColor: Colors.transparent, statusBarIconBrightness: Brightness.dark));
  session.start();
  runApp(const AimzoneApp());
}

class AimzoneApp extends StatelessWidget {
  const AimzoneApp({super.key});
  @override
  Widget build(BuildContext context) => MaterialApp(
        title: 'Aimzone',
        debugShowCheckedModeBanner: false,
        // test builds carry a TEST ribbon (owner, 15 Nov 2026); a store build passes --dart-define=TEST_BUILD=false
        builder: (context, child) => testBuild
            ? Banner(message: 'TEST', location: BannerLocation.topEnd, color: const Color(0xFFB54708), child: child ?? const SizedBox())
            : (child ?? const SizedBox()),
        theme: appTheme(),
        home: ListenableBuilder(
          listenable: session,
          builder: (context, _) {
            if (!session.ready) return const Splash();
            if (!session.seenWelcome) return const WelcomeScreen();
            if (!session.signedIn) return const SignInScreen();
            return const HomeShell();
          },
        ),
      );
}

class Splash extends StatelessWidget {
  const Splash({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(
        backgroundColor: C.blue,
        body: Center(child: Text('Aimzone', style: sora(34, c: Colors.white))),
      );
}
