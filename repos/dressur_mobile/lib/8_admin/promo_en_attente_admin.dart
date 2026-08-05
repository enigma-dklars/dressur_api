// ignore_for_file: unnecessary_brace_in_string_interps

import 'dart:convert';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:dressur/components/constant.dart';

class AdminPromoEnAttentePage extends StatefulWidget {
  const AdminPromoEnAttentePage({Key? key}) : super(key: key);

  @override
  State<AdminPromoEnAttentePage> createState() =>
      _AdminPromoEnAttentePageState();
}

class _AdminPromoEnAttentePageState extends State<AdminPromoEnAttentePage> {
  bool _loading = true;
  List<dynamic> _promotions = [];
  final Set<int> _processing = {};

  @override
  void initState() {
    super.initState();
    _fetchPromos();
  }

  Future<void> _fetchPromos() async {
    setState(() => _loading = true);
    try {
      final response = await http.get(
        Uri.parse('$generalRouteForApi/admin/promos-en-attente?uid=$uidUser'),
      );
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['error'] == false) {
          setState(() => _promotions = data['promotions'] ?? []);
        }
      }
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _accepter(int id) async {
    setState(() => _processing.add(id));
    try {
      final response = await http.post(
        Uri.parse('$generalRouteForApi/admin/promos/$id/accepter'),
        body: {'uid': '$uidUser'},
      );
      final data = jsonDecode(response.body);
      _showSnack(data['message'] ?? ((langUserPhone == "fr") ? 'Fait' : 'Done'), data['error'] == false);
      if (data['error'] == false) {
        setState(() => _promotions.removeWhere((p) => p['id'] == id));
      }
    } catch (_) {
      _showSnack((langUserPhone == "fr") ? 'Erreur réseau' : 'Network error', false);
    }
    if (mounted) setState(() => _processing.remove(id));
  }

  Future<void> _refuser(int id, String motif) async {
    setState(() => _processing.add(id));
    try {
      final response = await http.post(
        Uri.parse('$generalRouteForApi/admin/promos/$id/refuser'),
        body: {'uid': '$uidUser', 'motif': motif},
      );
      final data = jsonDecode(response.body);
      _showSnack(data['message'] ?? ((langUserPhone == "fr") ? 'Fait' : 'Done'), data['error'] == false);
      if (data['error'] == false) {
        setState(() => _promotions.removeWhere((p) => p['id'] == id));
      }
    } catch (_) {
      _showSnack((langUserPhone == "fr") ? 'Erreur réseau' : 'Network error', false);
    }
    if (mounted) setState(() => _processing.remove(id));
  }

  void _showSnack(String msg, bool ok) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      backgroundColor: ok ? Colors.green : Colors.red,
      behavior: SnackBarBehavior.floating,
      content: Text(msg, style: GoogleFonts.poppins(color: Colors.white)),
    ));
  }

  void _showRefuserDialog(int id) {
    final controller = TextEditingController();
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text(
          (langUserPhone == "fr") ? 'Motif de refus' : 'Reason for rejection',
          style: GoogleFonts.poppins(fontWeight: FontWeight.bold),
        ),
        content: TextField(
          controller: controller,
          maxLines: 3,
          decoration: InputDecoration(
            hintText: (langUserPhone == "fr") ? 'Expliquer pourquoi cette promotion est refusée...' : 'Explain why this promotion is rejected...',
            border: const OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text((langUserPhone == "fr") ? 'Annuler' : 'Cancel', style: GoogleFonts.poppins()),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              _refuser(id, controller.text.trim());
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: Text(
              (langUserPhone == "fr") ? 'Refuser' : 'Reject',
              style: GoogleFonts.poppins(
                  color: Colors.white, fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
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

  Widget _buildCard(Map<String, dynamic> p, bool isDark) {
    final int id = p['id'];
    final bool isProcessing = _processing.contains(id);
    final user = p['user'] as Map<String, dynamic>?;
    final String imageUrl = '$generalRouteForPromotionImage${p['image']}';

    return Card(
      color: isDark ? const Color(0xFF1E1E1E) : Colors.white,
      margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Image complète sans recadrage ──
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
                  child: const Center(child: CircularProgressIndicator(color: primaryColor)),
                ),
              ),
              errorWidget: (_, __, ___) => AspectRatio(
                aspectRatio: 4 / 3,
                child: Container(
                  color: isDark ? Colors.grey[850] : Colors.grey[200],
                  child: Icon(
                    Icons.image_not_supported,
                    size: 48,
                    color: Colors.grey[500],
                  ),
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
                    _badge('#$id', Colors.orange),
                    const SizedBox(width: 6),
                    _badge(_labelType(p['type'] as String?), Colors.blue),
                    const Spacer(),
                    if ((p['source'] ?? '').toString().isNotEmpty)
                      Text(
                        '${p['source']}',
                        style: GoogleFonts.poppins(
                            fontSize: 10,
                            color:
                                isDark ? Colors.grey[400] : Colors.grey[600]),
                      ),
                  ],
                ),
                const SizedBox(height: 8),

                // ── Description complète ──
                Text(
                  '${p['description'] ?? ''}',
                  style: GoogleFonts.poppins(
                    fontSize: 13,
                    color: isDark ? Colors.grey[300] : Colors.grey[800],
                  ),
                ),
                const SizedBox(height: 8),

                // ── Infos utilisateur ──
                if (user != null) ...[
                  _userInfoRow(
                      FontAwesomeIcons.user,
                      '${user['pseudo'] ?? ''} ${user['nom'] ?? ''}',
                      isDark),
                  const SizedBox(height: 2),
                  _userInfoRow(FontAwesomeIcons.envelope,
                      '${user['mail'] ?? ''}', isDark),
                  const SizedBox(height: 2),
                  _userInfoRow(
                      FontAwesomeIcons.phone, '${user['tel'] ?? ''}', isDark),
                  const SizedBox(height: 4),
                ],

                // ── Date ──
                Text(
                  '${(langUserPhone == "fr") ? "Soumis le" : "Submitted on"} ${p['createdAt'] ?? ''}',
                  style: GoogleFonts.poppins(
                      fontSize: 10,
                      color: isDark ? Colors.grey[600] : Colors.grey[400]),
                ),
                const SizedBox(height: 12),

                // ── Boutons ──
                if (isProcessing)
                  const Center(child: CircularProgressIndicator(color: primaryColor))
                else
                  Row(
                    children: [
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: () => _accepter(id),
                          icon: const FaIcon(FontAwesomeIcons.check, size: 14),
                          label: Text(
                            (langUserPhone == "fr") ? 'Accepter' : 'Accept',
                            style: GoogleFonts.poppins(
                                fontWeight: FontWeight.bold),
                          ),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green,
                            foregroundColor: Colors.white,
                            shape: const StadiumBorder(),
                            padding:
                                const EdgeInsets.symmetric(vertical: 12),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: () => _showRefuserDialog(id),
                          icon:
                              const FaIcon(FontAwesomeIcons.xmark, size: 14),
                          label: Text(
                            (langUserPhone == "fr") ? 'Refuser' : 'Reject',
                            style: GoogleFonts.poppins(
                                fontWeight: FontWeight.bold),
                          ),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.red,
                            foregroundColor: Colors.white,
                            shape: const StadiumBorder(),
                            padding:
                                const EdgeInsets.symmetric(vertical: 12),
                          ),
                        ),
                      ),
                    ],
                  ),
              ],
            ),
          ),
        ],
      ),
    );
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
            fontSize: 10,
            fontWeight: FontWeight.bold,
            color: color[800]),
      ),
    );
  }

  Widget _userInfoRow(IconData icon, String text, bool isDark) {
    return Row(
      children: [
        FaIcon(icon, size: 11, color: Colors.grey),
        const SizedBox(width: 6),
        Expanded(
          child: Text(
            text,
            style: GoogleFonts.poppins(
                fontSize: 11,
                color: isDark ? Colors.grey[400] : Colors.grey[600]),
          ),
        ),
      ],
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
          (langUserPhone == "fr") ? 'Promotions en attente' : 'Pending Promotions',
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
          : _promotions.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const FaIcon(FontAwesomeIcons.circleCheck,
                          size: 52, color: Colors.green),
                      const SizedBox(height: 16),
                      Text(
                        (langUserPhone == "fr") ? 'Aucune promotion en attente' : 'No pending promotions',
                        style: GoogleFonts.poppins(
                            fontSize: 16,
                            color: isDark
                                ? Colors.grey[400]
                                : Colors.grey[600]),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _fetchPromos,
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    itemCount: _promotions.length,
                    itemBuilder: (_, i) => _buildCard(
                        _promotions[i] as Map<String, dynamic>, isDark),
                  ),
                ),
    );
  }
}
