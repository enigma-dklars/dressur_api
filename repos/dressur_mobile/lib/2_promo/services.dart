// ignore_for_file: unused_import

import 'dart:async';
import 'dart:convert';
import 'package:dressur/2_promo/liste_promo_reseau_sociaux.dart';
import 'package:dressur/2_promo/new_promo_reseau_sociaux.dart';
import 'package:dressur/components/noti.dart';
import 'package:dressur/components/notification_bell.dart';
import 'package:dressur/components/padding_and_divider.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/2_promo/liste_promo_affaire.dart';
import 'package:dressur/2_promo/liste_boost_contact.dart';
import 'package:dressur/2_promo/new_boost_contact.dart';
import 'package:dressur/2_promo/new_promo_affaire.dart';
import 'package:dressur/5_autre/support_assistance.dart';
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/sociaux.dart';
import 'package:http/http.dart' as http;
import 'package:dressur/8_admin/admin.dart';
import 'package:dressur/6_assistant/assistant_page.dart';

class BoostPage extends StatefulWidget {
  @override
  State<BoostPage> createState() => _BoostPageState();
}

class _BoostPageState extends State<BoostPage>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _fetchCounts();
  }

  Future<void> _fetchCounts() async {
    try {
      final results = await Future.wait([
        http.get(Uri.parse('$generalRouteForApi/listBoost/$uidUser/fr')),
        http.get(Uri.parse('$generalRouteForApi/listPromotion/$uidUser/fr')),
        http.get(Uri.parse('$generalRouteForApi/listPromoReseau/$uidUser/fr')),
      ]);
      if (!mounted) return;
      setState(() {
        if (results[0].statusCode == 200) {
          final d = jsonDecode(results[0].body);
          if (d is List) nbrBoostContact = d.length;
        }
        if (results[1].statusCode == 200) {
          final d = jsonDecode(results[1].body);
          if (d is List) nbrPromoAffaire = d.length;
        }
        if (results[2].statusCode == 200) {
          final d = jsonDecode(results[2].body);
          if (d is List) nbrPromoReseau = d.length;
        }
      });
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return Scaffold(
      appBar: AppBar(
        automaticallyImplyLeading: false,
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          "Services",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
        actions: [
          if (admin) ...[
            IconButton(
              icon: const FaIcon(FontAwesomeIcons.userShield,
                  size: 20, color: Colors.amber),
              onPressed: () => Navigator.push(context,
                  MaterialPageRoute(builder: (_) => AdministrationPage())),
            ),
            const Padding(
              padding: EdgeInsets.fromLTRB(0, 10, 0, 10),
              child:
                  VerticalDivider(width: 0, color: Colors.white, thickness: 1),
            ),
          ],
          const NotificationBellAction(),
          const Padding(
            padding: EdgeInsets.fromLTRB(0, 10, 0, 10),
            child: VerticalDivider(width: 0, color: Colors.white, thickness: 1),
          ),
          PopupMenuButton<dynamic>(
            itemBuilder: (context) => [
              PopupMenuItem(
                value: 1,
                child: Row(
                  children: [
                    Text(
                      (langUserPhone == "fr") ? "Aide" : "Help",
                      style: GoogleFonts.poppins(
                          color: Colors.white, fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
              PopupMenuItem(
                value: 2,
                child: Row(
                  children: [
                    Text(
                      (langUserPhone == "fr") ? "Assistant IA" : "AI Assistant",
                      style: GoogleFonts.poppins(
                          color: Colors.white, fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
            ],
            offset: const Offset(0, 60),
            color: primaryColor,
            icon: const FaIcon(FontAwesomeIcons.bars,
                color: Colors.white, size: 20),
            elevation: 2,
            onSelected: (value) {
              if (value == 1) {
                Navigator.push(
                    context, MaterialPageRoute(builder: (_) => SupportPage()));
              } else if (value == 2) {
                Navigator.push(
                    context, MaterialPageRoute(builder: (_) => const AssistantPage()));
              }
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _fetchCounts,
        color: primaryColor,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Builder(
            builder: (context) {
              final boostContactCard = _buildServiceCard(
                context: context,
                icon: FontAwesomeIcons.addressBook,
                title: "Boost Contact",
                description: (langUserPhone == "fr")
                    ? "Rendez visible le ($tel) aux contacts correspondant à vos préférences pays."
                    : "Make the ($tel) visible to contacts corresponding to your country preferences.",
                primaryActionText:
                    (langUserPhone == "fr") ? "Faire un Boost" : "Boost",
                onPrimaryAction: () => Navigator.push(
                    context,
                    MaterialPageRoute(
                        builder: (context) => NewBoostContactPage())),
                secondaryActionText:
                    (langUserPhone == "fr") ? "Voir la liste" : "See the list",
                onSecondaryAction: () => Navigator.push(
                    context,
                    MaterialPageRoute(
                        builder: (context) => ListeBoostContactPage())),
              );

              return Column(
                children: [
                  const SizedBox(height: 10),
                  _buildStatsBanner(),
                  const SizedBox(height: 10),
                  if (addPageActu) ...[
                    boostContactCard,
                    const SizedBox(height: 10),
                  ],
                  _buildServiceCard(
                    context: context,
                    icon: FontAwesomeIcons.store,
                    title: (langUserPhone == "fr")
                        ? "Promotion Affaire"
                        : "Business Promotion",
                    description: (langUserPhone == "fr")
                        ? "Faite la promotion de vos produits et services. Les utilisateurs intéressés vous contacterons."
                        : "Promote your products and services. Interested users will contact you.",
                    primaryActionText: (langUserPhone == "fr")
                        ? "Faire une Promo"
                        : "Make a Promo",
                    onPrimaryAction: () => Navigator.push(
                        context,
                        MaterialPageRoute(
                            builder: (context) => PromotionFormPage())),
                    secondaryActionText: (langUserPhone == "fr")
                        ? "Voir la liste"
                        : "See the list",
                    onSecondaryAction: () => Navigator.push(
                        context,
                        MaterialPageRoute(
                            builder: (context) => PromotionListPage())),
                  ),
                  const SizedBox(height: 10),
                  if (mailIsMaxxFire == false) ...[
                    _buildServiceCard(
                      context: context,
                      icon: FontAwesomeIcons.chartLine,
                      title: (langUserPhone == "fr")
                          ? "Promotion Réseau Sociaux"
                          : "Social Network Promotion",
                      description: (langUserPhone == "fr")
                          ? "Payer pour des abonnés, des vues, des likes, etc. sur TikTok, Instagram, Telegram, YouTube, etc."
                          : "Pay for subscribers, views, likes, etc. on TikTok, Instagram, Telegram, YouTube, etc.",
                      primaryActionText:
                          (langUserPhone == "fr") ? "Démarrer" : "To start up",
                      onPrimaryAction: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                              builder: (context) =>
                                  PromotionReseauSociauxFormPage())),
                      secondaryActionText: (langUserPhone == "fr")
                          ? "Voir la liste"
                          : "See the list",
                      onSecondaryAction: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                              builder: (context) =>
                                  PromotionReseauSociauxListePage())),
                    ),
                    const SizedBox(height: 10),
                  ],
                  if (!addPageActu) ...[
                    boostContactCard,
                    const SizedBox(height: 10),
                  ],
                  SociauxPage(),
                  const SizedBox(height: 10),
                ],
              );
            },
          ),
        ),
      ),
    );
  }

  Widget _buildStatsBanner() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 10),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [primaryColor, primaryColor.withOpacity(0.75)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: primaryColor.withOpacity(0.35),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: _buildStatCell(
              icon: FontAwesomeIcons.addressBook,
              label: "Boost Contact",
              value: "$nbrBoostContact",
            ),
          ),
          Container(
            height: 50,
            width: 1,
            color: Colors.white24,
            margin: const EdgeInsets.symmetric(horizontal: 4),
          ),
          Expanded(
            child: _buildStatCell(
              icon: FontAwesomeIcons.store,
              label: (langUserPhone == "fr") ? "Promo Affaire" : "Biz Promo",
              value: "$nbrPromoAffaire",
            ),
          ),
          Container(
            height: 50,
            width: 1,
            color: Colors.white24,
            margin: const EdgeInsets.symmetric(horizontal: 4),
          ),
          Expanded(
            child: _buildStatCell(
              icon: FontAwesomeIcons.chartLine,
              label: (langUserPhone == "fr") ? "Promo Réseau" : "Social Promo",
              value: "$nbrPromoReseau",
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatCell({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        FaIcon(icon, color: Colors.white70, size: 18),
        const SizedBox(height: 6),
        Text(
          value,
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w700,
            fontSize: 14,
          ),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 2),
        Text(
          label,
          style: GoogleFonts.poppins(color: Colors.white60, fontSize: 11),
          textAlign: TextAlign.center,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
      ],
    );
  }

  Widget _buildServiceCard({
    required BuildContext context,
    required IconData icon,
    required String title,
    required String description,
    required String primaryActionText,
    required VoidCallback onPrimaryAction,
    required String secondaryActionText,
    required VoidCallback onSecondaryAction,
  }) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: isDark ? Color(0xFF1E1E1E) : Colors.white,
        borderRadius: BorderRadius.circular(15),
        border: Border.all(
            color: isDark ? Colors.grey[800]! : Colors.grey[200]!, width: 1),
        boxShadow: [
          BoxShadow(
            color: isDark
                ? Colors.black.withOpacity(0.2)
                : Colors.grey.withOpacity(0.1),
            blurRadius: 10,
            offset: Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: primaryColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: FaIcon(icon, color: primaryColor, size: 28),
              ),
              SizedBox(width: 15),
              Expanded(
                child: Text(
                  title,
                  style: GoogleFonts.poppins(
                    color: isDark ? Colors.white : Colors.black87,
                    fontWeight: FontWeight.w600,
                    fontSize: 18,
                  ),
                ),
              ),
            ],
          ),
          SizedBox(height: 15),
          Text(
            description,
            style: GoogleFonts.poppins(
              color: isDark ? Colors.grey[400] : Colors.grey[700],
              fontSize: 14,
              height: 1.5,
            ),
          ),
          SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: ElevatedButton(
                  onPressed: onPrimaryAction,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
                    foregroundColor: Colors.white,
                    padding: EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                    elevation: 2,
                  ),
                  child: Text(
                    primaryActionText,
                    style: GoogleFonts.poppins(
                        fontWeight: FontWeight.w600, fontSize: 14),
                  ),
                ),
              ),
              SizedBox(width: 12),
              Expanded(
                child: OutlinedButton(
                  onPressed: onSecondaryAction,
                  style: OutlinedButton.styleFrom(
                    side: BorderSide(color: secondaryColor.withOpacity(0.7)),
                    foregroundColor: secondaryColor,
                    padding: EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                  child: Text(
                    secondaryActionText,
                    style: GoogleFonts.poppins(
                        fontWeight: FontWeight.w600, fontSize: 14),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
