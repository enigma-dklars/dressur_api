// ignore_for_file: prefer_const_constructors

import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/5_autre/support_assistance.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'package:url_launcher/url_launcher.dart';

// ── Modèle ───────────────────────────────────────────────────────────────────
class TutoDS {
  final int id;
  final String titre;
  final String description;
  final String? url;

  const TutoDS({
    required this.id,
    required this.titre,
    required this.description,
    this.url,
  });

  factory TutoDS.fromJson(Map<String, dynamic> json) {
    return TutoDS(
      id: json['id'] as int,
      titre: json['titre'] as String? ?? '',
      description: json['description'] as String? ?? '',
      url: json['url'] as String?,
    );
  }

  bool get hasUrl => url != null && url!.trim().isNotEmpty;

  bool get isYoutube {
    final u = url?.toLowerCase() ?? '';
    return u.contains('youtube') || u.contains('youtu.be');
  }
}

// ── Page principale ───────────────────────────────────────────────────────────
class ListeTuto extends StatefulWidget {
  const ListeTuto({Key? key}) : super(key: key);

  @override
  State<ListeTuto> createState() => _ListeTutoState();
}

class _ListeTutoState extends State<ListeTuto> {
  List<TutoDS> _tutos = [];
  bool _loading = true;
  bool _hasError = false;

  @override
  void initState() {
    super.initState();
    _fetchTutos();
  }

  Future<void> _fetchTutos() async {
    if (!mounted) return;
    setState(() {
      _loading = true;
      _hasError = false;
    });
    try {
      final url = Uri.parse('$generalRouteForApi/getTutos');
      final response = await http
          .post(url, body: {'uid': '$uidUser'}).timeout(const Duration(seconds: 15));
      if (!mounted) return;
      final Map<String, dynamic> body = jsonDecode(response.body);
      if (body['error'] == false) {
        final List<dynamic> raw = body['tutos'] ?? [];
        setState(() {
          _tutos = raw.map((e) => TutoDS.fromJson(e)).toList();
          _loading = false;
        });
      } else {
        setState(() { _hasError = true; _loading = false; });
      }
    } catch (_) {
      if (!mounted) return;
      setState(() { _hasError = true; _loading = false; });
    }
  }

  Future<void> _openUrl(String rawUrl) async {
    final uri = Uri.tryParse(rawUrl.trim());
    if (uri == null) return;
    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      try {
        await launchUrl(uri, mode: LaunchMode.inAppBrowserView);
      } catch (_) {}
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF121212) : const Color(0xFFF5F6FA),
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          (langUserPhone == 'fr') ? "Tutoriels" : "Tutorials",
          style: GoogleFonts.poppins(
              color: Colors.white, fontWeight: FontWeight.w400, fontSize: 18),
        ),
        leading: IconButton(
          onPressed: () => Navigator.pop(context),
          icon: const FaIcon(FontAwesomeIcons.chevronLeft, color: Colors.white),
        ),
        actions: [
          PopupMenuButton<int>(
            itemBuilder: (context) => [
              PopupMenuItem(
                value: 1,
                child: Text(
                  (langUserPhone == "fr") ? "Actualiser" : "Refresh",
                  style: GoogleFonts.poppins(
                      color: Colors.white, fontWeight: FontWeight.w600),
                ),
              ),
              PopupMenuItem(
                value: 2,
                child: Text(
                  (langUserPhone == "fr") ? "Aide" : "Help",
                  style: GoogleFonts.poppins(
                      color: Colors.white, fontWeight: FontWeight.w600),
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
                _fetchTutos();
              } else if (value == 2) {
                Navigator.push(context,
                    MaterialPageRoute(builder: (_) => SupportPage()));
              }
            },
          ),
        ],
      ),
      body: _buildBody(isDark),
    );
  }

  Widget _buildBody(bool isDark) {
    if (_loading) {
      return Center(child: CircularProgressIndicator(color: primaryColor));
    }

    if (_hasError) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.red.withOpacity(0.08),
                  shape: BoxShape.circle,
                ),
                child: FaIcon(FontAwesomeIcons.circleExclamation,
                    color: Colors.red[300], size: 36),
              ),
              const SizedBox(height: 16),
              Text(
                (langUserPhone == 'fr')
                    ? "Impossible de charger les tutoriels."
                    : "Unable to load tutorials.",
                style: GoogleFonts.poppins(
                    color: Colors.grey[500], fontSize: 14),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 20),
              ElevatedButton.icon(
                onPressed: _fetchTutos,
                icon: const FaIcon(FontAwesomeIcons.arrowsRotate,
                    size: 13, color: Colors.white),
                label: Text(
                  (langUserPhone == 'fr') ? "Réessayer" : "Retry",
                  style: GoogleFonts.poppins(
                      color: Colors.white, fontWeight: FontWeight.w600),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  padding:
                      const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ],
          ),
        ),
      );
    }

    if (_tutos.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: primaryColor.withOpacity(0.07),
                shape: BoxShape.circle,
              ),
              child: FaIcon(FontAwesomeIcons.graduationCap,
                  color: primaryColor.withOpacity(0.4), size: 40),
            ),
            const SizedBox(height: 16),
            Text(
              (langUserPhone == 'fr')
                  ? "Aucun tutoriel disponible"
                  : "No tutorials available",
              style: GoogleFonts.poppins(
                  fontSize: 15,
                  fontWeight: FontWeight.w500,
                  color: Colors.grey[500]),
            ),
            const SizedBox(height: 6),
            Text(
              (langUserPhone == 'fr')
                  ? "Revenez plus tard"
                  : "Check back later",
              style: GoogleFonts.poppins(
                  fontSize: 13, color: Colors.grey[400]),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      color: primaryColor,
      onRefresh: _fetchTutos,
      child: CustomScrollView(
        slivers: [
          // ── Bannière de résumé ─────────────────────────────────────────
          SliverToBoxAdapter(
            child: _buildHeader(isDark),
          ),
          // ── Liste ──────────────────────────────────────────────────────
          SliverPadding(
            padding:
                const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
            sliver: SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) => _TutoCard(
                  tuto: _tutos[index],
                  index: index,
                  isDark: isDark,
                  onOpenUrl: _openUrl,
                ),
                childCount: _tutos.length,
              ),
            ),
          ),
          const SliverToBoxAdapter(child: SizedBox(height: 20)),
        ],
      ),
    );
  }

  Widget _buildHeader(bool isDark) {
    return Container(
      margin: const EdgeInsets.fromLTRB(14, 16, 14, 10),
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [primaryColor, primaryColor.withOpacity(0.78)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: primaryColor.withOpacity(0.30),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.15),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const FaIcon(FontAwesomeIcons.graduationCap,
                color: Colors.white, size: 26),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  (langUserPhone == 'fr') ? "Tutoriels Dressur" : "Dressur Tutorials",
                  style: GoogleFonts.poppins(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  '${_tutos.length} ${(langUserPhone == 'fr') ? (_tutos.length > 1 ? "tutoriels disponibles" : "tutoriel disponible") : (_tutos.length > 1 ? "tutorials available" : "tutorial available")}',
                  style: GoogleFonts.poppins(
                    color: Colors.white70,
                    fontSize: 13,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ── Carte tutoriel ────────────────────────────────────────────────────────────
class _TutoCard extends StatelessWidget {
  final TutoDS tuto;
  final int index;
  final bool isDark;
  final Future<void> Function(String) onOpenUrl;

  const _TutoCard({
    required this.tuto,
    required this.index,
    required this.isDark,
    required this.onOpenUrl,
  });

  Color get _accentColor => tuto.isYoutube ? const Color(0xFFFF0000) : primaryColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E1E1E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDark ? Colors.grey[800]! : Colors.grey[100]!,
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: isDark
                ? Colors.black.withOpacity(0.18)
                : Colors.grey.withOpacity(0.09),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // ── Barre colorée gauche ──────────────────────────────────
              Container(width: 5, color: _accentColor),
              // ── Contenu ───────────────────────────────────────────────
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // ── Numéro + titre ────────────────────────────────
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          Container(
                            width: 28,
                            height: 28,
                            alignment: Alignment.center,
                            decoration: BoxDecoration(
                              color: _accentColor.withOpacity(0.12),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              '${index + 1}',
                              style: GoogleFonts.poppins(
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                                color: _accentColor,
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              tuto.titre,
                              style: GoogleFonts.poppins(
                                fontSize: 15,
                                fontWeight: FontWeight.w600,
                                color: isDark ? Colors.white : Colors.black87,
                                height: 1.3,
                              ),
                            ),
                          ),
                          if (tuto.isYoutube)
                            Padding(
                              padding: const EdgeInsets.only(left: 8),
                              child: FaIcon(FontAwesomeIcons.youtube,
                                  color: Colors.red, size: 18),
                            ),
                        ],
                      ),
                      // ── Description ───────────────────────────────────
                      if (tuto.description.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        Text(
                          tuto.description,
                          style: GoogleFonts.poppins(
                            fontSize: 13,
                            color:
                                isDark ? Colors.grey[400] : Colors.grey[600],
                            height: 1.55,
                          ),
                        ),
                      ],
                      // ── Bouton ────────────────────────────────────────
                      if (tuto.hasUrl) ...[
                        const SizedBox(height: 14),
                        GestureDetector(
                          onTap: () => onOpenUrl(tuto.url!),
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 14, vertical: 10),
                            decoration: BoxDecoration(
                              color: _accentColor,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                FaIcon(
                                  tuto.isYoutube
                                      ? FontAwesomeIcons.youtube
                                      : FontAwesomeIcons.arrowUpRightFromSquare,
                                  size: 13,
                                  color: Colors.white,
                                ),
                                const SizedBox(width: 8),
                                Text(
                                  tuto.isYoutube
                                      ? (langUserPhone == 'fr')
                                          ? "Voir sur YouTube"
                                          : "Watch on YouTube"
                                      : (langUserPhone == 'fr')
                                          ? "Voir le tutoriel"
                                          : "Open tutorial",
                                  style: GoogleFonts.poppins(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w600,
                                    color: Colors.white,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
