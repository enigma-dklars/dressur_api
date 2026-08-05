// ignore_for_file: use_key_in_widget_constructors, prefer_const_constructors
import 'dart:convert';

import 'package:dressur/5_autre/support_assistance.dart';
import 'package:flutter_linkify/flutter_linkify.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:dressur/components/constant.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class PromotionReseauSociaux {
  final String id;
  final String titre;
  final String qteDemander;
  final String prixFixer;
  final String url;
  final String reference;
  final String status;
  final String compteurDebut;
  final String compteurRestant;
  final String createdAt;
  final String updatedAt;

  PromotionReseauSociaux({
    required this.id,
    required this.titre,
    required this.qteDemander,
    required this.prixFixer,
    required this.url,
    required this.reference,
    required this.status,
    required this.compteurDebut,
    required this.compteurRestant,
    required this.createdAt,
    required this.updatedAt,
  });
}

class PromotionReseauSociauxListePage extends StatefulWidget {
  @override
  State<PromotionReseauSociauxListePage> createState() =>
      _PromotionReseauSociauxListePageState();
}

class _PromotionReseauSociauxListePageState
    extends State<PromotionReseauSociauxListePage> {
  bool _isLoading = false;
  List<PromotionReseauSociaux> _promotionReseauSociaux = [];

  Future<void> fetchPromotionReseauSociaux() async {
    setState(() {
      _isLoading = true;
    });
    final url = Uri.parse('$generalRouteForApi/listPromoReseau/$uidUser/fr');

    final response = await http.get(url);

    if (response.statusCode == 200) {
      final jsonData = jsonDecode(response.body) as List<dynamic>;

      final promotionReseauSociaux = jsonData.map((data) {
        return PromotionReseauSociaux(
          id: data['id'],
          titre: data['titre'],
          qteDemander: data['qteDemander'],
          prixFixer: data['prixFixer'],
          url: data['url'],
          reference: data['reference'],
          status: data['status'],
          compteurDebut: data['compteurDebut'],
          compteurRestant: data['compteurRestant'],
          createdAt: data['createdAt'],
          updatedAt: data['updatedAt'],
        );
      }).toList();

      setState(() {
        _promotionReseauSociaux = promotionReseauSociaux;
        _isLoading = false;
      });
    } else {
      showDialog(
        context: context,
        builder: (BuildContext context) {
          return AlertDialog(
            title: Text((langUserPhone == "fr") ? 'Erreur' : 'Error'),
            content: Text((langUserPhone == "fr")
                ? "Échec de récupération des promotionReseauSociaux. Code d'erreur:"
                : "Failed to retrieve promotionReseauSociaux. Error code:"
                    "${response.statusCode}"),
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
    fetchPromotionReseauSociaux(); // Loading the diary when the app starts
  }

  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          (langUserPhone == "fr")
              ? "Liste Promotion Réseau Sociaux"
              : "Social Network Promotion List",
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
        actions: [
          PopupMenuButton<dynamic>(
            itemBuilder: (context) => [
              PopupMenuItem(
                value: 1,
                onTap: () {
                  fetchPromotionReseauSociaux();
                },
                child: Row(
                  children: [
                    Text(
                      (langUserPhone == "fr") ? "Actualiser" : "Refresh",
                      style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
              PopupMenuItem(
                value: 2,
                child: Row(
                  children: [
                    Text(
                      (langUserPhone == "fr") ? "Aide" : "Help",
                      style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            ],
            offset: const Offset(0, 60),
            color: primaryColor,
            icon: const FaIcon(
              FontAwesomeIcons.bars,
              color: Colors.white,
              size: 20,
            ),
            elevation: 2,
            onSelected: (value) {
              if (value == 2) {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (context) => SupportPage()),
                );
              }
            },
          ),
        ],
      ),
      body: _isLoading
          ? _buildShimmerList()
          : _promotionReseauSociaux.isEmpty
              ? _buildEmptyState()
              : RefreshIndicator(
                  onRefresh: fetchPromotionReseauSociaux,
                  color: primaryColor,
                  child: ListView.builder(
                    physics: const BouncingScrollPhysics(
                        parent: AlwaysScrollableScrollPhysics()),
                    cacheExtent: 500,
                    padding: const EdgeInsets.all(10),
                    itemCount: _promotionReseauSociaux.length,
                    itemBuilder: (context, index) {
                      return RepaintBoundary(
                          child: _buildPromotionCard(
                              _promotionReseauSociaux[index]));
                    },
                  ),
                ),
    );
  }

  // --- WIDGET DE CARTE "APERÇU" ---
  Widget _buildPromotionCard(PromotionReseauSociaux promo) {
    final statusInfo = _getStatusInfo(promo.status);
    final int qteDemandee = int.tryParse(promo.qteDemander) ?? 0;
    final int qteRestante = int.tryParse(promo.compteurRestant) ?? 0;
    final int qteCompletee = qteDemandee - qteRestante;
    final double progress =
        (qteDemandee > 0) ? qteCompletee / qteDemandee : 0.0;
    final bool showProgress =
        statusInfo['label'] == 'En cours' || statusInfo['label'] == 'Terminé';

    return Card(
      elevation: 0.5,
      margin: EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
      child: InkWell(
        onTap: () => _showDetailsModal(context, promo),
        borderRadius: BorderRadius.circular(15),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // --- EN-TÊTE : TITRE ET STATUT ---
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      promo.titre,
                      style: GoogleFonts.poppins(
                          fontSize: 17, fontWeight: FontWeight.bold),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  SizedBox(width: 10),
                  _buildBadge(statusInfo['label']!, statusInfo['color']!),
                ],
              ),
              SizedBox(height: 12),
              // --- BARRE DE PROGRESSION (si applicable) ---
              if (showProgress) ...[
                ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: LinearProgressIndicator(
                    value: progress,
                    minHeight: 6,
                    backgroundColor: Colors.grey[200],
                    color: statusInfo['color'],
                  ),
                ),
                SizedBox(height: 6),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      "$qteCompletee / $qteDemandee",
                      style: GoogleFonts.poppins(
                          fontSize: 12,
                          color: Colors.grey[600],
                          fontWeight: FontWeight.w500),
                    ),
                    Text(
                      "${(progress * 100).toStringAsFixed(0)}%",
                      style: GoogleFonts.poppins(
                          fontSize: 12,
                          color: statusInfo['color'],
                          fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ] else ...[
                // Message alternatif si pas de progression
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      "${(langUserPhone == "fr") ? "Référence" : "Reference"}: ${promo.reference}",
                      style: GoogleFonts.poppins(
                          fontSize: 13, color: Colors.grey[600]),
                    ),
                    // Option 3: Style "Bouton Discret"

                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        FaIcon(
                          FontAwesomeIcons.circleInfo,
                          size: 14,
                          color:
                              primaryColor, // Utilise la couleur principale de votre app
                        ),
                        SizedBox(width: 8),
                        Text(
                          (langUserPhone == "fr") ? "Autres Détails" : "Other Details",
                          style: GoogleFonts.poppins(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: primaryColor,
                          ),
                        ),
                        SizedBox(width: 8),
                        FaIcon(
                          FontAwesomeIcons
                              .chevronRight, // Flèche pour indiquer une action
                          size: 12,
                          color: primaryColor,
                        )
                      ],
                    )
                  ],
                )
              ],
            ],
          ),
        ),
      ),
    );
  }

  // --- MODAL SHEET POUR LES DÉTAILS ---
  void _showDetailsModal(BuildContext context, PromotionReseauSociaux promo) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(25))),
      backgroundColor: isDark ? Color(0xFF1E1E1E) : Colors.white,
      builder: (context) {
        return Padding(
          padding: EdgeInsets.only(
              top: 12,
              left: 20,
              right: 20,
              bottom: MediaQuery.of(context).viewInsets.bottom + 30),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 5,
                  decoration: BoxDecoration(
                      color: Colors.grey[300],
                      borderRadius: BorderRadius.circular(10)),
                ),
              ),
              SizedBox(height: 25),
              Text(promo.titre,
                  style: GoogleFonts.poppins(
                      fontSize: 22, fontWeight: FontWeight.bold)),
              SizedBox(height: 20),
              _buildSectionTitle((langUserPhone == "fr") ? "Détails de la Commande" : "Order Details"),
              _buildDetailRow(
                icon: FontAwesomeIcons.boxOpen,
                label: (langUserPhone == "fr") ? "Quantité Demandée" : "Requested Quantity",
                value: promo.qteDemander,
              ),
              _buildDetailRow(
                icon: FontAwesomeIcons.moneyBillTrendUp,
                label: (langUserPhone == "fr") ? "Prix Fixé" : "Fixed Price",
                value: promo.prixFixer,
              ),
              SizedBox(height: 15),
              _buildSectionTitle((langUserPhone == "fr") ? "Suivi de la Campagne" : "Campaign Tracking"),
              _buildDetailRow(
                icon: FontAwesomeIcons.flag,
                label: (langUserPhone == "fr") ? "Compteur au Début" : "Starting Count",
                value: promo.compteurDebut,
              ),
              _buildDetailRow(
                icon: FontAwesomeIcons.hourglass,
                label: (langUserPhone == "fr") ? "Compteur Restant" : "Remaining Count",
                value: promo.compteurRestant,
              ),
              SizedBox(height: 15),
              _buildSectionTitle((langUserPhone == "fr") ? "Informations Techniques" : "Technical Information"),
              _buildDetailRow(
                icon: FontAwesomeIcons.tag,
                label: (langUserPhone == "fr") ? "Référence" : "Reference",
                value: promo.reference,
              ),
              _buildUrlItem(promo.url),
              SizedBox(height: 15),
              _buildSectionTitle((langUserPhone == "fr") ? "Historique" : "History"),
              _buildDetailRow(
                icon: FontAwesomeIcons.calendar,
                label: (langUserPhone == "fr") ? "Créé le" : "Created on",
                value: promo.createdAt,
              ),
              _buildDetailRow(
                icon: FontAwesomeIcons.penToSquare,
                label: (langUserPhone == "fr") ? "Modifié le" : "Updated on",
                value: promo.updatedAt,
              ),
            ],
          ),
        );
      },
    );
  }

  // --- WIDGETS HELPERS ---

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10.0),
      child: Text(title,
          style: GoogleFonts.poppins(
              fontSize: 16, fontWeight: FontWeight.bold, color: primaryColor)),
    );
  }

  Widget _buildDetailRow(
      {required IconData icon, required String label, required String value}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: Row(
        children: [
          FaIcon(icon, color: Colors.grey[500], size: 18),
          SizedBox(width: 12),
          Text("$label:", style: GoogleFonts.poppins(color: Colors.grey[600])),
          SizedBox(width: 8),
          Expanded(
            child: Text(
              value,
              style: GoogleFonts.poppins(fontWeight: FontWeight.w600),
              textAlign: TextAlign.end,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildUrlItem(String url) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: Row(
        children: [
          FaIcon(FontAwesomeIcons.link, color: Colors.grey[500], size: 18),
          SizedBox(width: 12),
          Text("URL:", style: GoogleFonts.poppins(color: Colors.grey[600])),
          SizedBox(width: 8),
          Expanded(
            child: Linkify(
              onOpen: (link) => launchUrl(Uri.parse(link.url)),
              text: url,
              textAlign: TextAlign.end,
              style: GoogleFonts.poppins(
                  fontWeight: FontWeight.w600, color: Colors.blue),
              linkStyle: GoogleFonts.poppins(decoration: TextDecoration.none),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }

  Map<String, dynamic> _getStatusInfo(String status) {
    if (["Completed", "Terminé", "In progress", "En cours"].contains(status)) {
      return {'label': status, 'color': Colors.green};
    } else if (["On hold", "En attente"].contains(status)) {
      return {'label': status, 'color': Colors.orange};
    } else {
      return {'label': status, 'color': Colors.red};
    }
  }

  Widget _buildBadge(String text, Color color) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration:
          BoxDecoration(color: color, borderRadius: BorderRadius.circular(20)),
      child: Text(text,
          style: GoogleFonts.poppins(
              color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
    );
  }

  Widget _buildEmptyState() {
    /* ... */ return Center(child: Text((langUserPhone == "fr") ? "Aucune promotion" : "No promotions found"));
  }

  Widget _buildShimmerList() {
    /* ... */ return Center(
        child: CircularProgressIndicator(color: primaryColor));
  }
}

class InfoColumn extends StatelessWidget {
  final String label;
  final String value;

  const InfoColumn({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.poppins(
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        const SizedBox(height: 1),
        Text(
          value,
          style: GoogleFonts.poppins(
            fontSize: 14,
          ),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
      ],
    );
  }
}
