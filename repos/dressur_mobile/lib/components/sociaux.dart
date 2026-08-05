// ignore_for_file: prefer_const_constructors

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:dressur/components/constant.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart'; // Import du package d'icônes

class SociauxPage extends StatelessWidget {
  const SociauxPage({super.key});

  // Fonction helper pour lancer les URLs de manière sécurisée
  void _launchURL(String url) async {
    final Uri uri = Uri.parse(url);
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      print('Could not launch $url');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            primaryColor,
            primaryColor.withOpacity(0.85),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(15),
        boxShadow: [
          BoxShadow(
            color: primaryColor.withOpacity(0.3),
            blurRadius: 15,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              // 🔹 Partie Texte
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      (langUserPhone == "fr")
                          ? "Abonnez-vous et partagez!"
                          : "Subscribe and share!",
                      style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      (langUserPhone == "fr")
                          ? "Ne manquez aucune offre et faites-en profiter vos proches."
                          : "Don't miss any offers and share them with your loved ones.",
                      style: GoogleFonts.poppins(
                        color: Colors.white.withOpacity(0.85),
                        fontSize: 14,
                      ),
                    ),
                  ],
                ),
              ),

              // 🔹 Icônes sociales modernisées
              Column(
                children: [
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      _socialIcon(
                        FontAwesomeIcons.tiktok,
                        18,
                        () => _launchURL(tiktokDS),
                      ),
                      _socialIcon(
                        FontAwesomeIcons.facebook,
                        18,
                        () => _launchURL(facebookDS),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      _socialIcon(
                        FontAwesomeIcons.instagram,
                        20,
                        () => _launchURL(instagramDS),
                      ),
                      _socialIcon(
                        FontAwesomeIcons.whatsapp,
                        20,
                        () => _launchURL(chaineWhatsApp),
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),

          const SizedBox(height: 10),

          // 🔹 Bouton plus visible
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: primaryColor,
                shape: const StadiumBorder(),
                padding: const EdgeInsets.symmetric(vertical: 10),
                elevation: 0,
              ),
              icon: const FaIcon(FontAwesomeIcons.shareNodes),
              label: Text(
                (langUserPhone == "fr") ? 'PARTAGER DRESSUR' : 'SHARE DRESSUR',
                style: GoogleFonts.poppins(
                  fontWeight: FontWeight.w600,
                ),
              ),
              onPressed: () async {
                shareMessageWithImage(context);
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _socialIcon(IconData icon, double sizeIcon, VoidCallback onTap) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 6),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(50),
        child: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.15),
            shape: BoxShape.circle,
          ),
          child: FaIcon(
            icon,
            size: sizeIcon,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}
