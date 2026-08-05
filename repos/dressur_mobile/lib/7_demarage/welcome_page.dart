// ignore_for_file: use_build_context_synchronously

import 'dart:io';
import 'package:dressur/7_demarage/permissions_required_page.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:dressur/7_demarage/update_app_important.dart';
import 'package:dressur/7_demarage/presentation_ds.dart';
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/sql_helper.dart';
import 'package:dressur/components/bottomBar.dart';
import 'package:dressur/components/noti_sys.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert' as convert;

class WelcomePage extends StatelessWidget {
  const WelcomePage(this.notificationAppLaunchDetails, {Key? key})
      : super(key: key);
  static const String routeName = '/';
  final NotificationAppLaunchDetails? notificationAppLaunchDetails;
  bool get didNotificationLaunchApp =>
      notificationAppLaunchDetails?.didNotificationLaunchApp ?? false;

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: Colors.black,
      body: PageDepart(),
    );
  }
}

class PageDepart extends StatefulWidget {
  const PageDepart({Key? key}) : super(key: key);

  @override
  State<PageDepart> createState() => _PageDepartState();
}

class _PageDepartState extends State<PageDepart> {
  Future<void> _checkAndRequestPermissions() async {
    Map<Permission, bool> permissionsStatus =
        await _requestCriticalPermissions();

    bool allGranted = permissionsStatus.values.every((granted) => granted);

    if (!allGranted) {
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(
            builder: (context) => const PermissionsRequiredPage()),
        (route) => false,
      );
      return;
    }

    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (context) => PresentationPage()),
      (route) => false,
    );
  }

  Future<Map<Permission, bool>> _requestCriticalPermissions() async {
    Map<Permission, bool> results = {};

    PermissionStatus contactStatus = await Permission.contacts.request();
    results[Permission.contacts] = contactStatus.isGranted;

    PermissionStatus storageStatus = await Permission.storage.request();
    results[Permission.storage] = storageStatus.isGranted;

    PermissionStatus alarmStatus =
        await Permission.scheduleExactAlarm.request();
    results[Permission.scheduleExactAlarm] = alarmStatus.isGranted;

    return results;
  }

  Future<Future<Object?>> directConnect() async {
    setState(() {
      textChargementEvolution =
          langUserPhone != "fr" ? "Loading ..." : "Chargement ...";
    });

    // Durée minimale du splash pour laisser l'animation se dérouler
    final splashMinimum = Future.delayed(const Duration(milliseconds: 6000));

    var request = http.MultipartRequest(
        'POST', Uri.parse('$generalRouteForApi/getVersionApp'));
    request.fields.addAll({});
    http.StreamedResponse response = await request.send();
    if (response.statusCode == 200) {
      var data1 = await response.stream.bytesToString();
      var data = convert.jsonDecode(data1);
      if (data["error"] == false) {
        if (data["importantUpdate"] == true) {
          myDressurVersion = data["versionApp"];
          if (int.parse(versionApp.toString().replaceAll(".", "")) <
              int.parse(myDressurVersion.toString().replaceAll(".", ""))) {
            return Navigator.of(context).push(
                MaterialPageRoute(builder: (context) => ImportantUpdate()));
          }
        }
      }
    }

    try {
      try {
        var __yo_uidUser = (await SQLHelper.getUidUser())[0]['uid'];
        setState(() {
          if (__yo_uidUser.toString().isNotEmpty) {
            uidUser = __yo_uidUser;
          }
        });
      } catch (e) {
        try {
          var __yo_uidUser = (await SQLHelper.getUidUserOld())[0]['uid'];
          setState(() {
            if (__yo_uidUser.toString().isNotEmpty) {
              uidUser = __yo_uidUser;
            }
          });
        } catch (e) {}
      }
    } on SocketException catch (_) {}

    if (uidUser != null && uidUser.toString().isNotEmpty) {
      setState(() {
        textChargementEvolution =
            langUserPhone != "fr" ? "Login ..." : "Connexion ...";
      });

      await getUserInfo();
      await _checkSynchroAvanceReminder();

      setState(() {
        textChargementEvolution = langUserPhone != "fr"
            ? "Initialization Finish"
            : "Initialisation Terminée";
      });

      final numsTelUser = await SQLHelper.getAll("numsTelUser");

      // Attendre que l'animation soit terminée avant de naviguer
      await splashMinimum;

      if (numsTelUser.isEmpty) {
        setState(() {
          modeReconnaissanceContactArrierePlan = true;
        });
        return Navigator.of(context)
            .push(MaterialPageRoute(builder: (context) => const BottomBar()));
      } else {
        return Navigator.of(context)
            .push(MaterialPageRoute(builder: (context) => const BottomBar()));
      }
    } else {
      // Attendre que l'animation soit terminée avant de naviguer
      await splashMinimum;
      await _checkAndRequestPermissions();
      return Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (context) => PresentationPage()),
        (route) => false,
      );
    }
  }

  Future<void> _checkSynchroAvanceReminder() async {
    final prefs = await SharedPreferences.getInstance();
    final String? lastDateStr = prefs.getString('lastSynchroAvanceDate');
    final bool shouldNotify = lastDateStr == null ||
        DateTime.now().difference(DateTime.parse(lastDateStr)).inDays >= 30;
    if (shouldNotify) {
      final bool isFr = langUserPhone == 'fr';
      await showNotification(
        isFr ? 'Synchronisation conseillée' : 'Sync recommended',
        isFr
            ? 'Mettez à jour vos contacts Dressur en lançant une Synchronisation Avancée.'
            : 'Update your Dressur contacts by running an Advanced Synchronization.',
      );
    }
  }

  Future<void> getUserInfo() async {
    setState(() {
      textChargementEvolution =
          langUserPhone != "fr" ? "Initialization ..." : "Initialisation ...";
    });

    var request = http.MultipartRequest(
        'POST', Uri.parse('$generalRouteForApi/getUserInfo'));
    request.fields.addAll({
      'uid': uidUser,
    });

    http.StreamedResponse response = await request.send();

    if (response.statusCode == 200) {
      var data1 = await response.stream.bytesToString();
      var data = convert.jsonDecode(data1);
      if (data["error"] == false) {
        await initUserInformations(data["user"]);
      } else {
        Navigator.of(context)
            .push(MaterialPageRoute(builder: (context) => PresentationPage()));
      }
    }
  }

  @override
  void initState() {
    super.initState();
    directConnect();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: RadialGradient(
            center: Alignment.center,
            radius: 0.9,
            colors: [Color(0xFF1A237E), Colors.black],
            stops: [0.0, 1.0],
          ),
        ),
        child: SafeArea(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Spacer(flex: 2),
              const LogoAnimation(),
              const Spacer(flex: 2),
              // Texte de chargement discret en bas
              Padding(
                padding: const EdgeInsets.only(bottom: 48),
                child: Text(
                  textChargementEvolution,
                  style: GoogleFonts.poppins(
                    fontWeight: FontWeight.w300,
                    fontSize: 13,
                    color: Colors.white38,
                    letterSpacing: 1.5,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ─── Peintre des anneaux radar ────────────────────────────────────────────────

class _RadarPainter extends CustomPainter {
  final double animValue;
  final Color color;

  _RadarPainter({required this.animValue, required this.color});

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final baseRadius = size.width * 0.38;
    final maxRadius = size.width * 0.95;

    for (int i = 0; i < 3; i++) {
      final phase = (animValue + i / 3) % 1.0;
      final radius = baseRadius + (maxRadius - baseRadius) * phase;
      final opacity = (1.0 - phase) * 0.35;

      if (opacity > 0.01) {
        final paint = Paint()
          ..style = PaintingStyle.stroke
          ..strokeWidth = 1.5
          ..color = color.withOpacity(opacity);
        canvas.drawCircle(center, radius, paint);
      }
    }
  }

  @override
  bool shouldRepaint(_RadarPainter old) => old.animValue != animValue;
}

// ─── Animation du logo ───────────────────────────────────────────────────────

class LogoAnimation extends StatefulWidget {
  const LogoAnimation({Key? key}) : super(key: key);

  @override
  _LogoAnimationState createState() => _LogoAnimationState();
}

class _LogoAnimationState extends State<LogoAnimation>
    with TickerProviderStateMixin {
  // Contrôleur d'entrée (joue une fois)
  late AnimationController _entryCtrl;
  // Contrôleur de pulse radar (boucle)
  late AnimationController _radarCtrl;

  late Animation<double> _logoFade;
  late Animation<double> _logoScale;
  late Animation<double> _nameFade;
  late Animation<Offset> _nameSlide;
  late Animation<double> _lineScale;
  late Animation<double> _taglineFade;

  @override
  void initState() {
    super.initState();

    _entryCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 5500),
    )..forward();

    _radarCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 3200),
    )..repeat();

    // Logo : fade + scale (0 → 55% du timeline)
    _logoFade = Tween(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _entryCtrl,
        curve: const Interval(0.0, 0.5, curve: Curves.easeOut),
      ),
    );
    _logoScale = Tween(begin: 0.55, end: 1.0).animate(
      CurvedAnimation(
        parent: _entryCtrl,
        curve: const Interval(0.0, 0.6, curve: Curves.easeOutBack),
      ),
    );

    // Nom : slide vers le haut + fade (40% → 75%)
    _nameFade = Tween(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _entryCtrl,
        curve: const Interval(0.4, 0.72, curve: Curves.easeOut),
      ),
    );
    _nameSlide = Tween(
      begin: const Offset(0, 0.6),
      end: Offset.zero,
    ).animate(
      CurvedAnimation(
        parent: _entryCtrl,
        curve: const Interval(0.4, 0.72, curve: Curves.easeOut),
      ),
    );

    // Ligne décorative (65% → 88%)
    _lineScale = Tween(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _entryCtrl,
        curve: const Interval(0.65, 0.88, curve: Curves.easeOut),
      ),
    );

    // Tagline (75% → 100%)
    _taglineFade = Tween(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _entryCtrl,
        curve: const Interval(0.75, 1.0, curve: Curves.easeOut),
      ),
    );
  }

  @override
  void dispose() {
    _entryCtrl.dispose();
    _radarCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: Listenable.merge([_entryCtrl, _radarCtrl]),
      builder: (context, child) {
        return Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Zone logo + anneaux radar
            SizedBox(
              width: 240,
              height: 240,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  // Anneaux radar en arrière-plan
                  CustomPaint(
                    size: const Size(240, 240),
                    painter: _RadarPainter(
                      animValue: _radarCtrl.value,
                      color: const Color(0xFF5B8DEF),
                    ),
                  ),

                  // Halo lumineux derrière le logo
                  Opacity(
                    opacity: _logoFade.value * 0.6,
                    child: Container(
                      width: 150,
                      height: 150,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFF2a4b9a).withOpacity(0.8),
                            blurRadius: 60,
                            spreadRadius: 20,
                          ),
                        ],
                      ),
                    ),
                  ),

                  // Logo avec fade + scale
                  Opacity(
                    opacity: _logoFade.value,
                    child: Transform.scale(
                      scale: _logoScale.value,
                      child: Image.asset(
                        'images/dressur_logo.png',
                        width: 170,
                        height: 170,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 28),

            // Nom de l'app
            FadeTransition(
              opacity: _nameFade,
              child: SlideTransition(
                position: _nameSlide,
                child: Text(
                  'DRESSUR',
                  style: GoogleFonts.poppins(
                    fontSize: 30,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                    letterSpacing: 10,
                  ),
                ),
              ),
            ),

            const SizedBox(height: 14),

            // Ligne décorative dégradée
            Transform.scale(
              scaleX: _lineScale.value,
              child: Container(
                width: 200,
                height: 2,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      Colors.transparent,
                      const Color(0xFF5B8DEF),
                      Colors.transparent,
                    ],
                  ),
                  borderRadius: BorderRadius.circular(1),
                ),
              ),
            ),

            const SizedBox(height: 12),

            // Tagline
            Opacity(
              opacity: _taglineFade.value,
              child: Text(
                langUserPhone == "fr"
                    ? 'Connecte · Booste · Grandit'
                    : 'Connect · Boost · Grow',
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  color: Colors.white54,
                  letterSpacing: 2,
                  fontWeight: FontWeight.w300,
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}
