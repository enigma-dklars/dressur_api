import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:dressur/components/constant.dart';
import 'package:dressur/6_assistant/assistant_page.dart';

class SupportPage extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        leading: IconButton(
          onPressed: () {
            Navigator.pop(context);
          },
          icon: const FaIcon(
            FontAwesomeIcons.chevronLeft,
            color: Colors.white,
          ),
        ),
        title: Text(
          (langUserPhone == "fr") ? "Support" : "Support",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
      ),
      body: SingleChildScrollView(
        physics: BouncingScrollPhysics(),
        padding: const EdgeInsets.symmetric(horizontal: 15.0, vertical: 10.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            SizedBox(height: 20),

            // --- EN-TÊTE VISUELLE ---
            FaIcon(FontAwesomeIcons.headset, color: primaryColor, size: 70),
            SizedBox(height: 15),
            Text(
              (langUserPhone == "fr") ? "Besoin d'aide ?" : "Need help?",
              style: GoogleFonts.poppins(
                  fontSize: 24, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 10),
            Text(
              (langUserPhone == "fr")
                  ? "Notre équipe est là pour répondre à toutes vos questions."
                  : "Our team is here to answer all your questions.",
              textAlign: TextAlign.center,
              style: GoogleFonts.poppins(fontSize: 15, color: Colors.grey[600]),
            ),
            SizedBox(height: 40),

            // --- SECTION "COMMENT AIDER" ---
            _buildHelpSection(
              context,
              title: (langUserPhone == "fr")
                  ? "Comment pouvons-nous vous aider ?"
                  : "How can we help you?",
              content: (langUserPhone == "fr")
                  ? "Que ce soit pour une question sur nos services de promotion, un problème technique ou une suggestion, n'hésitez pas à nous contacter via l'un des canaux ci-dessous."
                  : "Whether it's a question about our promotion services, a technical issue, or a suggestion, feel free to contact us through one of the channels below.",
            ),
            SizedBox(height: 30),

            // --- LISTE DES CANAUX DE CONTACT ---
            _buildContactChannel(
              context,
              icon: FontAwesomeIcons.robot,
              channelName: (langUserPhone == "fr") ? "Assistant IA" : "AI Assistant",
              description: (langUserPhone == "fr")
                  ? "Réponses instantanées 24h/24"
                  : "Instant answers 24/7",
              color: primaryColor,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const AssistantPage()),
                );
              },
            ),
            SizedBox(height: 15),
            _buildContactChannel(
              context,
              icon: FontAwesomeIcons.whatsapp,
              channelName: "WhatsApp",
              description: (langUserPhone == "fr")
                  ? "Pour une réponse rapide"
                  : "For a quick response",
              color: Color(0xFF25D366),
              onTap: () async {
                final Uri _url = Uri.parse(whatsappDSURL);
                if (!await launchUrl(_url,
                    mode: LaunchMode.externalApplication)) {
                  // Gérer l'erreur
                }
              },
            ),
            SizedBox(height: 15),
            _buildContactChannel(
              context,
              icon: FontAwesomeIcons.solidEnvelope,
              channelName: "E-mail",
              description: (langUserPhone == "fr")
                  ? "Pour les demandes détaillées"
                  : "For detailed requests",
              color: secondaryColor,
              onTap: () async {
                final Uri _url = Uri.parse('mailto:dressur.ds@gmail.com');
                if (!await launchUrl(_url)) {
                  // Gérer l'erreur
                }
              },
            ),
            SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  Widget _buildHelpSection(BuildContext context,
      {required String title, required String content}) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      padding: EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: isDark ? Colors.grey[850] : Colors.grey[100],
        borderRadius: BorderRadius.circular(15),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style:
                GoogleFonts.poppins(fontSize: 16, fontWeight: FontWeight.w600),
          ),
          SizedBox(height: 10),
          Text(
            content,
            style: GoogleFonts.poppins(
                fontSize: 14, color: Colors.grey[600], height: 1.5),
          ),
        ],
      ),
    );
  }

  Widget _buildContactChannel(
    BuildContext context, {
    required IconData icon,
    required String channelName,
    required String description,
    required Color color,
    required VoidCallback onTap,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        padding: EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isDark ? Color(0xFF1E1E1E) : Colors.white,
          borderRadius: BorderRadius.circular(18),
          border:
              Border.all(color: isDark ? Colors.grey[800]! : Colors.grey[200]!),
        ),
        child: Row(
          children: [
            FaIcon(icon, color: color, size: 28),
            SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    channelName,
                    style: GoogleFonts.poppins(
                        fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  SizedBox(height: 2),
                  Text(
                    description,
                    style: GoogleFonts.poppins(
                        fontSize: 13, color: Colors.grey[600]),
                  ),
                ],
              ),
            ),
            FaIcon(FontAwesomeIcons.chevronRight,
                size: 16, color: Colors.grey[400]),
          ],
        ),
      ),
    );
  }
}
