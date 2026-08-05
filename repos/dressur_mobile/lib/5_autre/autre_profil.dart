// ignore_for_file: prefer_const_constructors

import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/widgets.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'package:url_launcher/url_launcher.dart';

class AutreProfilPage extends StatefulWidget {
  @override
  State<AutreProfilPage> createState() => _AutreProfilPageState();
}

class _AutreProfilPageState extends State<AutreProfilPage> {
  bool _loading = false;
  var autre_name_complete;
  var autre_avatar;
  var autre_banniere;
  var autre_pseudo;
  var autre_nom;
  var autre_mail;
  var autre_pays;
  var autre_tel;
  var autre_apropos;
  var autre_tiktok;
  var autre_instagram;
  var autre_facebook;
  var autre_youtube;
  var autre_affUserName;
  var autre_profilePic;
  var autre_bannerPic;

  Future<void> fetchAutreProfil() async {
    setState(() {
      _loading = true;
    });

    var request = http.MultipartRequest(
        'POST', Uri.parse('$generalRouteForApi/getUserInfo'));
    request.fields.addAll(
        {'uid': uidAutreUser,});

    http.StreamedResponse response = await request.send();

    if (response.statusCode == 200) {
      var data1 = await response.stream.bytesToString();
      var data = jsonDecode(data1);
      if (data["error"] == false) {
        setState(() {
          var userAutreInfos = data['user'];
          autre_name_complete = userAutreInfos["name_complete"];
          autre_avatar = userAutreInfos["avatar"];
          autre_banniere = userAutreInfos["banniere"];
          autre_pseudo = userAutreInfos["pseudo"];
          autre_nom = userAutreInfos["nom"];
          autre_mail = userAutreInfos["mail"];
          autre_pays = userAutreInfos["pays"];
          autre_tel = userAutreInfos["tel"];
          autre_apropos = userAutreInfos["apropos"];
          autre_tiktok = userAutreInfos["tiktok"];
          autre_instagram = userAutreInfos["instagram"];
          autre_facebook = userAutreInfos["facebook"];
          autre_youtube = userAutreInfos["youtube"];
          autre_affUserName = userAutreInfos["affUserName"];
          // autre_profilePic = userAutreInfos["profilePic"];
          // autre_bannerPic = userAutreInfos["bannerPic"];
          _loading = false;
        });
      } else {
        setState(() {
          _loading = false;
        });
        showDialog(
          context: context,
          builder: (BuildContext context) {
            return AlertDialog(
              title: Text((langUserPhone == "fr") ? 'Erreur' : 'Error'),
              content: Text('Erreur AFTER 200: ${response.statusCode}'),
              actions: <Widget>[
                TextButton(
                  child: const Text('OK'),
                  onPressed: () {
                    Navigator.of(context).pop();
                  },
                ),
              ],
            );
          },
        );
      }
    } else {
      setState(() {
        _loading = false;
      });
      showDialog(
        context: context,
        builder: (BuildContext context) {
          return AlertDialog(
            title: Text((langUserPhone == "fr") ? 'Erreur' : 'Error'),
            content: Text('Erreur: ${response.statusCode}'),
            actions: <Widget>[
              TextButton(
                child: const Text('OK'),
                onPressed: () {
                  Navigator.of(context).pop();
                },
              ),
            ],
          );
        },
      );
    }
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      fetchAutreProfil();
    });
  }

  String _getInitials() {
    final String name = (autre_nom ?? autre_pseudo ?? "?").toString().trim();
    final List<String> parts = name.split(" ").where((p) => p.isNotEmpty).toList();
    if (parts.isEmpty) return "?";
    if (parts.length == 1) return parts[0][0].toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }

  static const List<Color> _avatarColors = [
    Color(0xFF1565C0),
    Color(0xFF2E7D32),
    Color(0xFF6A1B9A),
    Color(0xFFC62828),
    Color(0xFF00838F),
    Color(0xFFE65100),
    Color(0xFF4527A0),
    Color(0xFF00695C),
    Color(0xFF558B2F),
    Color(0xFF283593),
    Color(0xFF880E4F),
    Color(0xFF37474F),
  ];

  Color _getAvatarColor() {
    final String key = ((autre_nom ?? autre_pseudo) ?? "?").toLowerCase().trim();
    int hash = 0;
    for (final int c in key.codeUnits) {
      hash = (hash * 31 + c) & 0x7FFFFFFF;
    }
    return _avatarColors[hash % _avatarColors.length];
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        leading: IconButton(
          onPressed: () => Navigator.of(context).pop(),
          icon: const FaIcon(FontAwesomeIcons.chevronLeft, color: Colors.white),
        ),
        title: Text(
          (langUserPhone == "fr") ? "Profil" : "Profile",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
      ),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: primaryColor))
          : SingleChildScrollView(
              physics: const BouncingScrollPhysics(),
              child: Column(
                children: [
                  // --- EN-TÊTE AVEC INITIALES ---
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(vertical: 32),
                    decoration: BoxDecoration(
                      color: _getAvatarColor().withOpacity(0.06),
                      border: Border(
                        bottom: BorderSide(
                            color: _getAvatarColor().withOpacity(0.12), width: 1),
                      ),
                    ),
                    child: Column(
                      children: [
                        CircleAvatar(
                          radius: 44,
                          backgroundColor: _getAvatarColor(),
                          child: Text(
                            _getInitials(),
                            style: GoogleFonts.poppins(
                              fontSize: 30,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                          ),
                        ),
                        const SizedBox(height: 14),
                        if (autre_nom != null) ...[
                          Text(
                            autre_nom,
                            style: GoogleFonts.poppins(
                              fontSize: 20,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          const SizedBox(height: 2),
                        ],
                        if (autre_pseudo != null) ...[
                          Text(
                            "@$autre_pseudo",
                            style: GoogleFonts.poppins(
                                fontSize: 14, color: Colors.grey[600]),
                          ),
                          const SizedBox(height: 16),
                        ],
                        // --- BOUTONS D'ACTION ---
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            _buildActionIcon(
                              icon: FontAwesomeIcons.phone,
                              tooltip: (langUserPhone == "fr") ? "Appeler" : "Call",
                              onTap: () => launchPhoneCall(autre_tel),
                            ),
                            const SizedBox(width: 12),
                            _buildActionIcon(
                              icon: FontAwesomeIcons.solidMessage,
                              tooltip: "SMS",
                              onTap: () => launchSMS(autre_tel),
                            ),
                            const SizedBox(width: 12),
                            _buildActionIcon(
                              icon: FontAwesomeIcons.solidEnvelope,
                              tooltip: "Email",
                              onTap: () => launchEmail(autre_mail),
                            ),
                            const SizedBox(width: 12),
                            _buildActionIcon(
                              icon: FontAwesomeIcons.whatsapp,
                              tooltip: "WhatsApp",
                              onTap: () => launchWhatsApp(autre_tel),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),

                  // --- INFORMATIONS ---
                  Padding(
                    padding: const EdgeInsets.all(14.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (autre_mail != null)
                          buildInfoCard("E-Mail", autre_mail),
                        if (autre_tel != null)
                          buildInfoCard(
                              (langUserPhone == "fr")
                                  ? "Numéro de Téléphone"
                                  : "Phone number",
                              autre_tel),
                        if (autre_tiktok != null)
                          buildSocialMediaCard("TikTok", autre_tiktok),
                        if (autre_youtube != null)
                          buildSocialMediaCard("Youtube", autre_youtube),
                        if (autre_facebook != null)
                          buildSocialMediaCard("Facebook", autre_facebook),
                        if (autre_instagram != null)
                          buildSocialMediaCard("Instagram", autre_instagram),
                        if (autre_apropos != null)
                          buildInfoCard(
                              (langUserPhone == "fr") ? "À propos" : "About",
                              autre_apropos),
                      ],
                    ),
                  ),
                ],
              ),
            ),
    );
  }

// Collez cette fonction à l'intérieur de votre classe _AutreProfilPageState

  Widget _buildActionIcon({
    required IconData icon,
    required String tooltip,
    required VoidCallback onTap,
  }) {
    return Tooltip(
      message: tooltip,
      child: InkWell(
        onTap: onTap,
        borderRadius:
            BorderRadius.circular(50), // Pour un effet "splash" circulaire
        child: Container(
          padding: const EdgeInsets.all(12.0),
          decoration: BoxDecoration(
            color: primaryColor,
            shape: BoxShape.circle,
            boxShadow: [
              BoxShadow(
                color: primaryColor.withOpacity(0.3),
                blurRadius: 8,
                offset: Offset(0, 4),
              ),
            ],
          ),
          child: FaIcon(
            icon,
            size:
                24, // Taille d'icône légèrement plus grande pour un meilleur impact
            color: Colors.white,
          ),
        ),
      ),
    );
  }

  Widget buildInfoCard(String title, String content) {
    return SizedBox(
      width: double.infinity,
      child: Card(
        margin: EdgeInsets.only(bottom: 10),
        child: Padding(
          padding: const EdgeInsets.all(10.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: GoogleFonts.poppins(fontSize: 12, color: Colors.grey),
              ),
              Text(
                content,
                style: GoogleFonts.poppins(fontSize: 18),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget buildSocialMediaCard(String platform, String url) {
    return Card(
      margin: EdgeInsets.only(bottom: 10),
      child: ListTile(
        // Le changement est ici : la fonction retourne directement le bon widget
        leading: getSocialMediaIcon(platform),
        title: Text(
          platform,
          style: GoogleFonts.poppins(fontSize: 18),
        ),
        onTap: () async {
          final Uri _url = Uri.parse(url);
          if (!await launchUrl(_url, mode: LaunchMode.externalApplication)) {
            throw 'Could not launch $_url';
          }
        },
      ),
    );
  }

// La fonction améliorée qui retourne un FaIcon
  FaIcon getSocialMediaIcon(String platform) {
    switch (platform.toLowerCase()) {
      // Utiliser toLowerCase() pour plus de robustesse
      case "tiktok":
        // FontAwesome a une icône spécifique pour TikTok
        return FaIcon(FontAwesomeIcons.tiktok);

      case "youtube":
        return FaIcon(FontAwesomeIcons.youtube);

      case "facebook":
        return FaIcon(
            FontAwesomeIcons.facebook); // Couleur officielle de Facebook

      case "instagram":
        // L'icône Instagram est souvent représentée par un dégradé,
        // mais une couleur unie comme le magenta est une bonne alternative.
        return FaIcon(FontAwesomeIcons.instagram);

      default:
        // Une icône de lien générique si la plateforme n'est pas reconnue
        return FaIcon(FontAwesomeIcons.link);
    }
  }
}
