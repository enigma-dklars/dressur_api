// ignore_for_file: use_build_context_synchronously, prefer_const_constructors

import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:dressur/components/constant.dart';

class AproposPage extends StatelessWidget {
  // --- CORRECTION DU BUG ---
  // La fonction est maintenant asynchrone et utilise `await`
  Future<void> _launchURL(String url) async {
    final Uri uri = Uri.parse(url);
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      // Si le lancement échoue, on peut afficher un message d'erreur
      print('Could not launch $url');
      // Optionnel : Afficher un SnackBar à l'utilisateur
      // ScaffoldMessenger.of(context).showSnackBar(
      //   SnackBar(content: Text('Impossible d\'ouvrir le lien.')),
      // );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Scaffold(
      backgroundColor: isDark ? Color(0xFF121212) : Color(0xFFF8F9FA),
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          (langUserPhone == "fr") ? "À Propos de Dressur" : "About Dressur",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
        leading: IconButton(
          onPressed: () {
            Navigator.pop(context);
          },
          icon: const FaIcon(
            FontAwesomeIcons.chevronLeft,
            color: Colors.white,
            size: 20,
          ),
        ),
      ),
      body: SingleChildScrollView(
        physics: BouncingScrollPhysics(),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20.0, vertical: 10.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              SizedBox(height: 20),
              Image.asset("images/dressur_logo.png", height: 120),
              SizedBox(height: 10),
              Text(
                "Dressur",
                style: GoogleFonts.poppins(
                  fontSize: 28,
                  fontWeight: FontWeight.bold,
                  color: primaryColor,
                ),
              ),
              SizedBox(height: 30),

              _buildSection(
                icon: FontAwesomeIcons.rocket,
                title:
                    (langUserPhone == "fr") ? "Notre Mission" : "Our Mission",
                content: (langUserPhone == "fr")
                    ? "Accélérer votre croissance digitale. Nous vous donnons les outils pour augmenter votre visibilité, élargir votre réseau et atteindre vos objectifs."
                    : "To accelerate your digital growth. We give you the tools to increase your visibility, expand your network, and achieve your goals.",
              ),
              SizedBox(height: 25),

              // --- ENRICHISSEMENT DU CONTENU ---
              _buildSection(
                icon: FontAwesomeIcons.star,
                title: (langUserPhone == "fr")
                    ? "Ce que vous pouvez faire"
                    : "What You Can Do",
                isList: true,
                listItems: [
                  (langUserPhone == "fr")
                      ? "Promouvoir produits, services et événements."
                      : "Promote products, services, and events.",
                  (langUserPhone == "fr")
                      ? "Booster vos réseaux (likes, abonnés, vues...)."
                      : "Boost your social media (likes, followers, views...).",
                  (langUserPhone == "fr")
                      ? "Publier des offres ou des recherches d'emploi gratuitement."
                      : "Post job offers or job searches for free.",
                  (langUserPhone == "fr")
                      ? "Acquérir des contacts WhatsApp ciblés par pays."
                      : "Acquire targeted WhatsApp contacts by country.",
                ],
              ),
              SizedBox(height: 30),

              _buildLinkRow(
                text: (langUserPhone == "fr")
                    ? "Conditions d'utilisation"
                    : "Terms of Use",
                onTap: () => _launchURL(dressurConditionUtilisation),
              ),
              Divider(color: isDark ? Colors.grey[800]! : Colors.grey[200]!),
              _buildLinkRow(
                text: (langUserPhone == "fr")
                    ? "Politique de confidentialité"
                    : "Privacy Policy",
                onTap: () => _launchURL(dressurPolitiqueConfidentialite),
              ),
              SizedBox(height: 40),

              Text(
                (langUserPhone == "fr")
                    ? "Version $versionApp"
                    : "Version $versionApp",
                style:
                    GoogleFonts.poppins(fontSize: 14, color: Colors.grey[500]),
              ),
              SizedBox(height: 5),
              Text(
                "© 2022 - ${DateTime.now().year} Dressur. ${(langUserPhone == 'fr') ? 'Tous droits réservés.' : 'All rights reserved.'}",
                style:
                    GoogleFonts.poppins(fontSize: 12, color: Colors.grey[500]),
              ),
              SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSection({
    required IconData icon,
    required String title,
    String? content,
    bool isList = false,
    List<String>? listItems,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            FaIcon(icon, color: primaryColor, size: 24),
            SizedBox(width: 12),
            Text(
              title,
              style: GoogleFonts.poppins(
                  fontSize: 18, fontWeight: FontWeight.w600),
            ),
          ],
        ),
        SizedBox(height: 10),
        if (!isList && content != null)
          Padding(
            padding: const EdgeInsets.only(left: 36.0),
            child: Text(
              content,
              style: GoogleFonts.poppins(
                  fontSize: 15, color: Colors.grey[600], height: 1.6),
            ),
          ),
        if (isList && listItems != null)
          Padding(
            padding: const EdgeInsets.only(left: 36.0),
            child: Column(
              children: listItems.map((item) => _buildListItem(item)).toList(),
            ),
          ),
      ],
    );
  }

  Widget _buildListItem(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 6.0),
            child: FaIcon(FontAwesomeIcons.circleCheck,
                color: Colors.green, size: 16),
          ),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: GoogleFonts.poppins(
                  fontSize: 15, color: Colors.grey[600], height: 1.5),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLinkRow({required String text, required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 12.0),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              text,
              style: GoogleFonts.poppins(
                  fontSize: 15, fontWeight: FontWeight.w500),
            ),
            FaIcon(FontAwesomeIcons.chevronRight,
                size: 16, color: Colors.grey[400]),
          ],
        ),
      ),
    );
  }
}
