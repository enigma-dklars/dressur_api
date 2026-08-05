// ignore_for_file: prefer_const_constructors, use_build_context_synchronously

import 'dart:async';
import 'dart:convert';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:dressur/components/noti.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:dressur/components/constant.dart';
import 'package:url_launcher/url_launcher.dart';

// --- RÉUTILISATION DU BADGE ANIMÉ ---
class AnimatedRewardBadge extends StatefulWidget {
  final VoidCallback onTap;
  const AnimatedRewardBadge({Key? key, required this.onTap}) : super(key: key);

  @override
  State<AnimatedRewardBadge> createState() => _AnimatedRewardBadgeState();
}

class _AnimatedRewardBadgeState extends State<AnimatedRewardBadge>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _scaleAnimation;
  late Animation<Color?> _colorAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2000),
    )..repeat(reverse: true);

    _scaleAnimation = Tween<double>(begin: 1.0, end: 1.1).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
    );

    _colorAnimation = ColorTween(
      begin: primaryColor,
      end: Colors.orangeAccent,
    ).animate(CurvedAnimation(parent: _controller, curve: Curves.linear));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _controller,
      builder: (context, child) {
        final currentColor = _colorAnimation.value ?? primaryColor;
        return Transform.scale(
          scale: _scaleAnimation.value,
          child: GestureDetector(
            onTap: widget.onTap,
            child: Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: currentColor,
                border: Border.all(color: Colors.white, width: 2),
                boxShadow: [
                  BoxShadow(
                    color: currentColor.withOpacity(0.5),
                    blurRadius: 10,
                    spreadRadius: 2,
                  ),
                ],
              ),
              child: const FaIcon(FontAwesomeIcons.star,
                  color: Colors.white, size: 20),
            ),
          ),
        );
      },
    );
  }
}

class Advertisement {
  final String uidUser;
  final int id;
  final String image;
  final String imageName;
  final String description;
  final String whatsappNumber;
  final String pseudoAnnonceur;
  final String nombreDeVues;
  final String nombreImpression;
  final String typePromotionAffaire;
  final String annotherInfo;
  final bool inProgrammeRecompense;

  Advertisement({
    required this.uidUser,
    required this.id,
    required this.image,
    required this.imageName,
    required this.description,
    required this.whatsappNumber,
    required this.pseudoAnnonceur,
    required this.nombreDeVues,
    required this.nombreImpression,
    required this.typePromotionAffaire,
    required this.annotherInfo,
    required this.inProgrammeRecompense,
  });
}

class BusinessPromotionsPage extends StatefulWidget {
  const BusinessPromotionsPage({Key? key}) : super(key: key);

  @override
  State<BusinessPromotionsPage> createState() => _BusinessPromotionsPageState();
}

class _BusinessPromotionsPageState extends State<BusinessPromotionsPage> {
  late Future<List<Advertisement>> _futurePromotions;

  int? _sharingId; // Stocke l'ID de la promotion en cours de partage
  var data;

  @override
  void initState() {
    super.initState();
    _futurePromotions = getPromotionAffaireInProgrammeRecompense();
  }

  Future<void> _refresh() {
    setState(() {
      _futurePromotions = getPromotionAffaireInProgrammeRecompense();
    });
    return _futurePromotions;
  }

  Future<List<Advertisement>> getPromotionAffaireInProgrammeRecompense() async {
    // Remplacez par votre route API réelle pour les promotions affaires
    var request = http.MultipartRequest(
        'POST',
        Uri.parse(
            '$generalRouteForApi/getPromotionAffaireInProgrammeRecompense'));
    request.fields.addAll({
      'uid': uidUser,
    });

    http.StreamedResponse response = await request.send();

    if (response.statusCode == 200) {
      var data1 = await response.stream.bytesToString();
      data = convert.jsonDecode(data1);
      if (data["error"] == true) {
        dangerNoti(data["titre"], data["message"], context);
        return [];
      } else {
        // 1. On récupère d'abord la liste des promotions depuis le JSON
        final List<dynamic> promoList = data["promotions"];
        print(data["promotions"]);

        return promoList.map((data) {
          return Advertisement(
            uidUser: data['uidUser'] ?? "",
            id: data['id'],
            imageName: data['image'],
            image: generalRouteForPromotionImage + data['image'],
            description: data['description'] ?? "",
            whatsappNumber: data['whatsappNumber'] ?? "",
            pseudoAnnonceur: data['pseudoAnnonceur'] ?? "",
            nombreDeVues: data['nombreDeVues']?.toString() ?? "0",
            nombreImpression: data['nombreImpression']?.toString() ?? "0",
            typePromotionAffaire: data['typePromotionAffaire'] ?? "",
            annotherInfo: data['annotherInfo'] != null
                ? jsonEncode(data['annotherInfo'])
                : "",
            inProgrammeRecompense: data['inProgrammeRecompense'] == 1,
          );
        }).toList();
      }
    } else {
      return [];
    }
  }

  partageInProgrammeRecompense(Advertisement advertisement) async {
    setState(() {
      _sharingId = advertisement.id; // On définit l'ID actuel
    });

    try {
      var request = http.MultipartRequest('POST',
          Uri.parse('$generalRouteForApi/partageInProgrammeRecompense'));
      request.fields.addAll({
        'uid': uidUser,
        'idPromoAffaire': "${advertisement.id}",
        
      });

      http.StreamedResponse response = await request.send();

      if (response.statusCode == 200) {
        var data1 = await response.stream.bytesToString();
        var data = convert.jsonDecode(data1);

        if (data["error"] == true) {
          dangerNoti(data["titre"], data["message"], context);
        } else {
          final String referenceParticipation = data["referenceParticipation"];
          var descripionPromoAffaire =
              "Ref : $referenceParticipation\n\n${advertisement.description}\n\nRef : $referenceParticipation";

          sharePromotion(context, advertisement.image, advertisement.imageName,
              descripionPromoAffaire);
        }
      }
    } catch (e) {
      print("Erreur: $e");
    } finally {
      setState(() {
        _sharingId = null; // On réinitialise après la requête
      });
    }
  }

  Widget _infoRow(
      BuildContext context, IconData icon, String label, String value) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Padding(
      padding: const EdgeInsets.only(bottom: 15),
      child: Row(
        children: [
          Container(
            padding: EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: primaryColor.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: FaIcon(icon, color: primaryColor, size: 20),
          ),
          SizedBox(width: 15),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: GoogleFonts.poppins(
                  fontSize: 12,
                  color: isDark ? Colors.grey[400] : Colors.grey[600],
                ),
              ),
              Text(
                value,
                style: GoogleFonts.poppins(
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                  color: Theme.of(context).textTheme.bodyLarge?.color,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  void _showRewardInfo(BuildContext context, Advertisement advertisement) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) => Container(
        padding: EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: theme.scaffoldBackgroundColor,
          borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Barre de drag
            Container(
              width: 40,
              height: 5,
              decoration: BoxDecoration(
                color: Colors.grey[300],
                borderRadius: BorderRadius.circular(10),
              ),
            ),
            SizedBox(height: 25),

            // Icône et Titre
            FaIcon(FontAwesomeIcons.star, color: primaryColor, size: 50),
            SizedBox(height: 15),
            Text(
              (langUserPhone == "fr")
                  ? "Promotion Éligible !"
                  : "Eligible Promotion!",
              style: GoogleFonts.poppins(
                fontSize: 22,
                fontWeight: FontWeight.bold,
                color: theme.textTheme.titleLarge?.color,
              ),
            ),
            SizedBox(height: 10),
            Text(
              (langUserPhone == "fr")
                  ? "Cette promotion fait partie du programme de récompenses Dressur."
                  : "This promotion is part of the Dressur rewards program.",
              textAlign: TextAlign.center,
              style: GoogleFonts.poppins(
                fontSize: 14,
                color: isDark ? Colors.grey[400] : Colors.grey[600],
              ),
            ),

            SizedBox(height: 30),

            // Détails fictifs
            _infoRow(
                context,
                FontAwesomeIcons.eye,
                (langUserPhone == "fr") ? "Objectif" : "Goal",
                (langUserPhone == "fr")
                    ? "Atteindre min. 250 vues"
                    : "Reach min. 250 views"),
            _infoRow(
                context,
                FontAwesomeIcons.wallet,
                (langUserPhone == "fr") ? "Gain estimé" : "Estimated reward",
                (langUserPhone == "fr")
                    ? "Jusqu'à 2 500 FCFA"
                    : "Up to 2,500 FCFA"),
            _infoRow(
                context,
                FontAwesomeIcons.stopwatch,
                (langUserPhone == "fr") ? "Délai" : "Duration",
                (langUserPhone == "fr")
                    ? "20 heures de visibilité"
                    : "20 hours of visibility"),

            SizedBox(height: 30),

            // Bouton d'action
            SizedBox(
              width: double.infinity,
              height: 55,
              child: ElevatedButton(
                onPressed: () => Navigator.pop(context),
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(15),
                  ),
                ),
                child: Text(
                  (langUserPhone == "fr") ? "J'ai compris" : "Got it",
                  style: GoogleFonts.poppins(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),
            SizedBox(height: 10),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: primaryColor,
        elevation: 0,
        title: Text(
          (langUserPhone == "fr")
              ? "Promotions Affaires"
              : "Business Promotions",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
        leading: IconButton(
          icon: FaIcon(FontAwesomeIcons.chevronLeft, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: FutureBuilder<List<Advertisement>>(
        future: _futurePromotions,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(color: primaryColor),
            );
          } else if (snapshot.hasError) {
            return Center(
              child: Text((langUserPhone == "fr")
                  ? 'Erreur: ${snapshot.error}'
                  : 'Error: ${snapshot.error}'),
            );
          } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return Center(
              child: Padding(
                padding: EdgeInsetsGeometry.fromLTRB(20, 0, 20, 0),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    FaIcon(FontAwesomeIcons.clock,
                        size: 50, color: Colors.grey[300]),
                    const SizedBox(height: 10),
                    Text((langUserPhone == "fr")
                          ? "Aucune promotion d'affaires disponible pour le programme de récompenses selon vos préférences de pays."
                          : "No business promotions available for the rewards program based on your country preferences.",
                      textAlign: TextAlign.center,
                      style: GoogleFonts.poppins(
                        fontSize: 14,
                        color: Colors.grey,
                      ),
                    ),
                  ],
                ),
              ),
            );
          } else {
            return RefreshIndicator(
              onRefresh: _refresh,
              color: primaryColor,
              child: ListView.builder(
              physics: const BouncingScrollPhysics(
                  parent: AlwaysScrollableScrollPhysics()),
              cacheExtent: 600,
              itemCount: snapshot.data!.length,
              itemBuilder: (context, index) {
                Advertisement advertisement = snapshot.data![index];
                bool isThisItemSharing =
                    _sharingId == advertisement.id; // Vérification spécifique
                return Container(
                  margin: const EdgeInsets.only(
                      left: 7, top: 0, right: 7, bottom: 0),
                  child: Column(
                    children: [
                      const SizedBox(height: 1),
                      Card(
                        child: Column(
                          children: [
                            GestureDetector(
                              onTap: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) =>
                                        AdvertisementDetailPage(
                                      advertisement: advertisement,
                                    ),
                                  ),
                                );
                              },
                              child: Column(
                                children: [
                                  Stack(
                                    children: [
                                      ClipRRect(
                                        borderRadius: BorderRadius.circular(3),
                                        child: CachedNetworkImage(
                                          imageUrl: advertisement.image,
                                          placeholder: (context, url) =>
                                              Image.asset(
                                                  'images/placeholder.png'),
                                          errorWidget: (context, url, error) =>
                                              Image.asset(
                                                  'images/error_image.png'),
                                          fit: BoxFit.cover,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 10),
                                  Padding(
                                    padding: const EdgeInsets.fromLTRB(
                                        10, 0, 10, 10),
                                    child: Column(
                                      children: [
                                        Text(
                                          advertisement.description
                                              .replaceAll('\n', ' '),
                                          maxLines: 4,
                                          overflow: TextOverflow.ellipsis,
                                          style: GoogleFonts.poppins(
                                            fontWeight: FontWeight.w400,
                                            fontSize: 16,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            // les icons
                            const SizedBox(height: 5),
                            Padding(
                              padding: const EdgeInsets.fromLTRB(10, 0, 10, 10),
                              child: Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  GestureDetector(
                                    onTap: () =>
                                        _showRewardInfo(context, advertisement),
                                    child: Row(
                                      children: [
                                        FaIcon(FontAwesomeIcons.circleInfo),
                                        const SizedBox(width: 5),
                                        Text(
                                          (langUserPhone == "fr"
                                              ? "Informations"
                                              : "Informations"),
                                          style: GoogleFonts.poppins(
                                            fontSize: 15,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  GestureDetector(
                                    onTap: _sharingId !=
                                            null // Si un partage est en cours (n'importe lequel), on bloque
                                        ? null
                                        : () async {
                                            await partageInProgrammeRecompense(
                                                advertisement);
                                          },
                                    child: Row(
                                      children: [
                                        isThisItemSharing
                                            ? SizedBox(
                                                width: 20,
                                                height: 20,
                                                child:
                                                    CircularProgressIndicator(
                                                  strokeWidth: 2,
                                                  color: primaryColor,
                                                ),
                                              )
                                            : FaIcon(
                                                FontAwesomeIcons.shareNodes),
                                        const SizedBox(width: 5),
                                        Text(
                                          isThisItemSharing
                                              ? (langUserPhone == "fr"
                                                  ? "Patientez..."
                                                  : "Please wait...")
                                              : (langUserPhone == "fr"
                                                  ? "Partager"
                                                  : "Share"),
                                          style: GoogleFonts.poppins(
                                            fontSize: 15,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 1),
                    ],
                  ),
                );
              },
            ),
          );
          }
        },
      ),
    );
  }
}

class AdvertisementDetailPage extends StatelessWidget {
  final Advertisement advertisement;

  AdvertisementDetailPage({required this.advertisement});

  void openWhatsAppChat() async {
    String text;
    if (langUserPhone == "fr") {
      text =
          "Bonjour/Bonsoir *${advertisement.pseudoAnnonceur}*, j'ai une question concernant la promotion ci-dessous: \n\n";
    } else {
      text =
          "Good morning or Good evening *${advertisement.pseudoAnnonceur}*, I have a question regarding the promotion below: \n\n";
    }

    // Vérification de la longueur de la description
    if (advertisement.description.length >= 100) {
      text +=
          "<<${advertisement.description.substring(0, 100)}...>>\n\n*Depuis Dressur.*";
    } else {
      text += "<<${advertisement.description}>>\n\n*Depuis Dressur.*";
    }

    String encodedText = Uri.encodeComponent(text);

    String url =
        'https://wa.me/${advertisement.whatsappNumber}?text=$encodedText';

    final Uri _url = Uri.parse(url);
    if (!await launchUrl(_url, mode: LaunchMode.externalApplication)) {
      throw 'Could not launch $_url';
    }
  }

  @override
  Widget build(BuildContext context) {
    final Map<String, dynamic> infoMap = (advertisement.annotherInfo != "")
        ? jsonDecode(advertisement.annotherInfo)
        : {};
    return Scaffold(
      appBar: AppBar(
        backgroundColor: primaryColor,
        title: Text(
          (langUserPhone == "fr")
              ? 'Détails de la promotion'
              : 'Details of the promotion',
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
          ),
        ),
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Stack(
              children: [
                // L'image de la promotion
                ClipRRect(
                  borderRadius: BorderRadius.circular(3),
                  child: CachedNetworkImage(
                    imageUrl: advertisement.image,
                    placeholder: (context, url) =>
                        Image.asset('images/placeholder.png'),
                    errorWidget: (context, url, error) =>
                        Image.asset('images/error_image.png'),
                    fit: BoxFit.cover,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 5),
            Container(
              margin: const EdgeInsets.only(
                  left: 10, top: 0, right: 10, bottom: 20),
              child: Column(
                children: [
                  const SizedBox(height: 5),
                  Text(
                    advertisement.description,
                    style: GoogleFonts.poppins(
                      fontSize: 16,
                    ),
                  ),
                ],
              ),
            ),
            Container(
              margin: const EdgeInsets.only(
                  left: 10, top: 0, right: 10, bottom: 20),
              child: Column(
                children: infoMap.entries.map((entry) {
                  final key = entry.key.replaceAll('_', ' ').capitalize();
                  final value = entry.value;

                  return Padding(
                    padding: const EdgeInsets.only(bottom: 8.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '$key :',
                          style: GoogleFonts.poppins(
                            fontWeight: FontWeight.bold,
                            color: Colors
                                .blue, // Replace with your theme's primary color if needed
                            fontSize: 16,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          value,
                          style: GoogleFonts.poppins(
                            fontSize: 14,
                          ),
                          softWrap: true, // Wraps text within the container
                        ),
                        const Divider(),
                      ],
                    ),
                  );
                }).toList(),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
