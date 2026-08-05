// ignore_for_file: unnecessary_brace_in_string_interps, use_build_context_synchronously

import 'dart:convert';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:dressur/components/constant.dart';

class AdminPromosDressurStatusPage extends StatefulWidget {
  const AdminPromosDressurStatusPage({Key? key}) : super(key: key);

  @override
  State<AdminPromosDressurStatusPage> createState() =>
      _AdminPromosDressurStatusPageState();
}

class _AdminPromosDressurStatusPageState
    extends State<AdminPromosDressurStatusPage> {
  bool _loading = true;
  bool _hasError = false;
  List<dynamic> _promotions = [];
  final Set<int> _sharing = {};

  @override
  void initState() {
    super.initState();
    _fetchPromos();
  }

  Future<void> _fetchPromos() async {
    if (mounted) setState(() { _loading = true; _hasError = false; });
    try {
      final response = await http.get(
        Uri.parse('$generalRouteForApi/getPromotionsDressurStatus?uid=$uidUser'),
      ).timeout(const Duration(seconds: 20));

      if (!mounted) return;

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body) as Map<String, dynamic>;
        if (data['error'] == false) {
          setState(() {
            _promotions = (data['promotions'] as List<dynamic>?) ?? [];
            _hasError = false;
          });
        } else {
          setState(() => _hasError = true);
        }
      } else {
        setState(() => _hasError = true);
      }
    } catch (_) {
      if (mounted) setState(() => _hasError = true);
    }
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _partagerPromo(Map<String, dynamic> p) async {
    final int id = p['id'] is int ? p['id'] as int : int.tryParse('${p['id']}') ?? 0;
    if (_sharing.contains(id)) return;

    final String rawImage = p['image'] as String? ?? '';
    if (rawImage.isEmpty) {
      _showSnack(
        (langUserPhone == "fr") ? 'Image introuvable' : 'Image not found',
        false,
      );
      return;
    }

    setState(() => _sharing.add(id));

    try {
      final String imageUrl = '$generalRouteForPromotionImage$rawImage';
      final String imageName = rawImage;
      final String whatsapp = p['whatsapp'] as String? ?? '';
      final String description = p['description'] as String? ?? '';
      final String pseudo = p['pseudo'] as String? ?? '';

      String message = description;
      if (pseudo.isNotEmpty) {
        message += '\n\n👤 $pseudo';
      }
      if (whatsapp.isNotEmpty) {
        message +=
            '\n📲 ${(langUserPhone == "fr") ? "Contact WhatsApp" : "WhatsApp Contact"} : $whatsapp';
      }

      await sharePromotion(context, imageUrl, imageName, message);
    } catch (_) {
      _showSnack(
        (langUserPhone == "fr") ? 'Erreur lors du partage' : 'Share error',
        false,
      );
    }

    if (mounted) setState(() => _sharing.remove(id));
  }

  void _showSnack(String msg, bool ok) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      backgroundColor: ok ? Colors.green : Colors.red,
      behavior: SnackBarBehavior.floating,
      content: Text(msg, style: GoogleFonts.poppins(color: Colors.white)),
    ));
  }

  String _labelType(String? type) {
    switch (type) {
      case 'dmd_emploi':
        return (langUserPhone == "fr") ? "Demande d'emploi" : "Job Application";
      case 'offre_emploi':
        return (langUserPhone == "fr") ? "Offre d'emploi" : "Job Offer";
      default:
        return 'Produit / Service';
    }
  }

  Widget _badge(String label, MaterialColor color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color[100],
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: GoogleFonts.poppins(
            fontSize: 10, fontWeight: FontWeight.bold, color: color[800]),
      ),
    );
  }

  Widget _buildCard(Map<String, dynamic> p, bool isDark) {
    final int id = p['id'] is int ? p['id'] as int : int.tryParse('${p['id']}') ?? 0;
    final bool isSharing = _sharing.contains(id);
    final String imageUrl = '$generalRouteForPromotionImage${p['image']}';
    final String pseudo = p['pseudo'] ?? '';
    final String whatsapp = p['whatsapp'] ?? '';
    final String dateExp = p['dateExp'] ?? '';

    return Card(
      color: isDark ? const Color(0xFF1E1E1E) : Colors.white,
      margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Image ──
          ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(14)),
            child: CachedNetworkImage(
              imageUrl: imageUrl,
              width: double.infinity,
              fit: BoxFit.contain,
              placeholder: (_, __) => AspectRatio(
                aspectRatio: 4 / 3,
                child: Container(
                  color: isDark ? Colors.grey[850] : Colors.grey[200],
                  child: const Center(
                    child: CircularProgressIndicator(color: primaryColor),
                  ),
                ),
              ),
              errorWidget: (_, __, ___) => AspectRatio(
                aspectRatio: 4 / 3,
                child: Container(
                  color: isDark ? Colors.grey[850] : Colors.grey[200],
                  child: Icon(Icons.image_not_supported,
                      size: 48, color: Colors.grey[500]),
                ),
              ),
            ),
          ),

          Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // ── Badges ──
                Row(
                  children: [
                    _badge('#$id', Colors.deepPurple),
                    const SizedBox(width: 6),
                    _badge(_labelType(p['type'] as String?), Colors.blue),
                  ],
                ),
                const SizedBox(height: 8),

                // ── Description ──
                Text(
                  '${p['description'] ?? ''}',
                  style: GoogleFonts.poppins(
                    fontSize: 13,
                    color: isDark ? Colors.grey[300] : Colors.grey[800],
                  ),
                ),
                const SizedBox(height: 8),

                // ── Annonceur ──
                if (pseudo.isNotEmpty)
                  Row(
                    children: [
                      FaIcon(FontAwesomeIcons.user,
                          size: 11, color: Colors.grey[500]),
                      const SizedBox(width: 6),
                      Text(
                        pseudo,
                        style: GoogleFonts.poppins(
                          fontSize: 11,
                          color: isDark ? Colors.grey[400] : Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                if (whatsapp.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Row(
                    children: [
                      FaIcon(FontAwesomeIcons.whatsapp,
                          size: 11, color: Colors.green),
                      const SizedBox(width: 6),
                      Text(
                        whatsapp,
                        style: GoogleFonts.poppins(
                          fontSize: 11,
                          color: isDark ? Colors.grey[400] : Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                ],
                if (dateExp.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    '${(langUserPhone == "fr") ? "Expire le" : "Expires"} $dateExp',
                    style: GoogleFonts.poppins(
                      fontSize: 10,
                      color: isDark ? Colors.grey[600] : Colors.grey[400],
                    ),
                  ),
                ],

                const SizedBox(height: 12),

                // ── Bouton Partager ──
                SizedBox(
                  width: double.infinity,
                  child: isSharing
                      ? const Center(
                          child:
                              CircularProgressIndicator(color: primaryColor),
                        )
                      : ElevatedButton.icon(
                          onPressed: () => _partagerPromo(p),
                          icon: const FaIcon(FontAwesomeIcons.shareNodes,
                              size: 14),
                          label: Text(
                            (langUserPhone == "fr") ? 'Partager' : 'Share',
                            style: GoogleFonts.poppins(
                                fontWeight: FontWeight.bold),
                          ),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: primaryColor,
                            foregroundColor: Colors.white,
                            shape: const StadiumBorder(),
                            padding:
                                const EdgeInsets.symmetric(vertical: 12),
                          ),
                        ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      backgroundColor:
          isDark ? const Color(0xFF121212) : const Color(0xFFF8F9FA),
      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.red,
        leading: IconButton(
          onPressed: () => Navigator.pop(context),
          icon: const FaIcon(FontAwesomeIcons.chevronLeft, color: Colors.white),
        ),
        title: Text(
          (langUserPhone == "fr")
              ? 'Statut Dressur'
              : 'Dressur Status',
          style: GoogleFonts.poppins(
              color: Colors.white, fontWeight: FontWeight.w400, fontSize: 18),
        ),
        actions: [
          IconButton(
            onPressed: _fetchPromos,
            tooltip: (langUserPhone == "fr") ? 'Actualiser' : 'Refresh',
            icon: const FaIcon(FontAwesomeIcons.arrowsRotate,
                color: Colors.white, size: 18),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: primaryColor))
          : _hasError
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      FaIcon(FontAwesomeIcons.triangleExclamation,
                          size: 52, color: Colors.orange),
                      const SizedBox(height: 16),
                      Text(
                        (langUserPhone == "fr")
                            ? 'Erreur de chargement'
                            : 'Loading error',
                        style: GoogleFonts.poppins(
                            fontSize: 16,
                            color: isDark ? Colors.grey[400] : Colors.grey[600]),
                      ),
                      const SizedBox(height: 16),
                      ElevatedButton.icon(
                        onPressed: _fetchPromos,
                        icon: const FaIcon(FontAwesomeIcons.arrowsRotate, size: 14),
                        label: Text(
                          (langUserPhone == "fr") ? 'Réessayer' : 'Retry',
                          style: GoogleFonts.poppins(fontWeight: FontWeight.bold),
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: primaryColor,
                          foregroundColor: Colors.white,
                          shape: const StadiumBorder(),
                        ),
                      ),
                    ],
                  ),
                )
              : _promotions.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          FaIcon(FontAwesomeIcons.broadcastTower,
                              size: 52,
                              color: isDark ? Colors.grey[600] : Colors.grey[400]),
                          const SizedBox(height: 16),
                          Text(
                            (langUserPhone == "fr")
                                ? 'Aucune promotion à partager'
                                : 'No promotions to share',
                            style: GoogleFonts.poppins(
                              fontSize: 16,
                              color: isDark ? Colors.grey[400] : Colors.grey[600],
                            ),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _fetchPromos,
                      child: ListView.builder(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        itemCount: _promotions.length,
                        itemBuilder: (_, i) =>
                            _buildCard(_promotions[i] as Map<String, dynamic>, isDark),
                      ),
                    ),
    );
  }
}
