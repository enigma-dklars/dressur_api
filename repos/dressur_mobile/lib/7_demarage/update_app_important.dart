// ignore_for_file: use_build_context_synchronously, prefer_const_constructors

import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:animate_do/animate_do.dart';

// --- Importez vos constantes ---
import 'package:dressur/components/constant.dart';

class ImportantUpdate extends StatelessWidget {
  const ImportantUpdate({Key? key}) : super(key: key);

  // --- LOGIQUE DE SORTIE DE L'APP ---
  Future<bool> _onWillPop(BuildContext context) async {
    return (await showDialog(
          context: context,
          builder: (context) => AlertDialog(
            title: Text((langUserPhone == "fr")
                ? "Quitter l'application ?"
                : 'Exit Application?'),
            content: Text((langUserPhone == "fr")
                ? "Une mise à jour est requise pour continuer."
                : "An update is required to continue."),
            actions: <Widget>[
              TextButton(
                onPressed: () => Navigator.of(context).pop(false),
                child: Text((langUserPhone == "fr") ? 'Rester' : 'Stay'),
              ),
              TextButton(
                onPressed: () {
                  if (Platform.isAndroid) {
                    SystemNavigator.pop();
                  } else if (Platform.isIOS) {
                    exit(0);
                  }
                },
                child: Text((langUserPhone == "fr") ? 'Quitter' : 'Exit'),
              ),
            ],
          ),
        )) ??
        false;
  }

  // --- LOGIQUE POUR LANCER L'URL DU STORE ---
  Future<void> _launchStoreURL() async {
    final Uri url = Uri.parse(dressurUrlPlaystore);
    if (!await launchUrl(url, mode: LaunchMode.externalApplication)) {
      // Gérer l'erreur si le store ne peut pas être ouvert
      print('Could not launch $url');
    }
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () => _onWillPop(context),
      child: Scaffold(
        backgroundColor: Color(0xFF1A237E), // Un fond sombre et sérieux
        body: SafeArea(
          child: Padding(
            padding:
                const EdgeInsets.symmetric(horizontal: 24.0, vertical: 20.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Spacer(),

                // --- ICÔNE CENTRALE ANIMÉE ---
                Center(
                  child: BounceInDown(
                    // Animation d'entrée
                    duration: Duration(milliseconds: 800),
                    child: Container(
                      padding: EdgeInsets.all(24),
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: Colors.white.withOpacity(0.1),
                      ),
                      child: FaIcon(
                        FontAwesomeIcons.arrowDown,
                        size: 80,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
                SizedBox(height: 40),

                // --- TEXTES D'INFORMATION ---
                FadeInUp(
                  duration: Duration(milliseconds: 600),
                  delay: Duration(milliseconds: 200),
                  child: Text(
                    (langUserPhone == "fr")
                        ? "Mise à Jour Requise"
                        : "Update Required",
                    textAlign: TextAlign.center,
                    style: GoogleFonts.poppins(
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                ),
                SizedBox(height: 15),
                FadeInUp(
                  duration: Duration(milliseconds: 600),
                  delay: Duration(milliseconds: 400),
                  child: Text(
                    (langUserPhone == "fr")
                        ? "Une nouvelle version de Dressur est disponible avec des améliorations de sécurité et de nouvelles fonctionnalités."
                        : "A new version of Dressur is available with security improvements and new features.",
                    textAlign: TextAlign.center,
                    style: GoogleFonts.poppins(
                      fontSize: 16,
                      color: Colors.white.withOpacity(0.8),
                      height: 1.5,
                    ),
                  ),
                ),

                Spacer(),

                // --- BOUTON DE MISE À JOUR ---
                FadeInUp(
                  duration: Duration(milliseconds: 600),
                  delay: Duration(milliseconds: 600),
                  child: ElevatedButton.icon(
                    onPressed: _launchStoreURL,
                    icon:
                        FaIcon(FontAwesomeIcons.download, color: primaryColor),
                    label: Text((langUserPhone == "fr")
                        ? "Mettre à Jour Maintenant"
                        : "Update Now"),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: primaryColor,
                      padding: EdgeInsets.symmetric(vertical: 16),
                      textStyle: GoogleFonts.poppins(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
