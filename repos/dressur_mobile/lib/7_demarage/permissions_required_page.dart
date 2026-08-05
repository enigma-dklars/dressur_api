import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:dressur/components/constant.dart';

class PermissionsRequiredPage extends StatelessWidget {
  const PermissionsRequiredPage({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: primaryColor,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Image.asset(
                'images/dressur_logo.png',
                width: 120,
                height: 120,
              ),
              const SizedBox(height: 40),
              Text(
                langUserPhone == "fr"
                    ? "Autorisations requises"
                    : "Permissions Required",
                style: GoogleFonts.poppins(
                  fontSize: 28,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 20),
              Text(
                langUserPhone == "fr"
                    ? "Pour que Dressur fonctionne correctement, vous devez autoriser :\n\n"
                        "• Accès aux contacts\n"
                        "• Notifications (alarmes exactes)\n"
                        "• Accès au stockage"
                    : "For Dressur to work properly, you must allow:\n\n"
                        "• Access to contacts\n"
                        "• Notifications (exact alarms)\n"
                        "• Storage access",
                style: GoogleFonts.poppins(
                  fontSize: 18,
                  color: Colors.white70,
                  height: 1.6,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 40),
              ElevatedButton.icon(
                onPressed: () async {
                  await openAppSettings();
                },
                icon: const FaIcon(FontAwesomeIcons.gear, color: Colors.black),
                label: Text(
                  langUserPhone == "fr"
                      ? "Ouvrir les paramètres"
                      : "Open Settings",
                  style: GoogleFonts.poppins(
                      color: Colors.black, fontWeight: FontWeight.w600),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.white,
                  padding:
                      const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(30)),
                ),
              ),
              const SizedBox(height: 20),
              Text(
                langUserPhone == "fr"
                    ? "Après avoir accordé les autorisations,\nfermez et relancez l'application."
                    : "After granting permissions,\nclose and restart the app.",
                style: GoogleFonts.poppins(
                  fontSize: 16,
                  color: Colors.white60,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
