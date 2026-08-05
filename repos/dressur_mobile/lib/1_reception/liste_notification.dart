// ignore_for_file: unnecessary_null_comparison, prefer_const_constructors

import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/5_autre/support_assistance.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'package:url_launcher/url_launcher.dart';

// ── Modèle ────────────────────────────────────────────────────────────────────
class NotificationDS {
  final int id;
  final String text;
  final DateTime createdAt;

  const NotificationDS({
    required this.id,
    required this.text,
    required this.createdAt,
  });

  factory NotificationDS.fromJson(Map<String, dynamic> json) {
    DateTime dt;
    try {
      dt = DateTime.parse(json['createdAt'] as String? ?? '');
    } catch (_) {
      dt = DateTime.now();
    }
    return NotificationDS(
      id: json['id'] as int,
      text: json['text'] as String? ?? '',
      createdAt: dt,
    );
  }
}

// ── Types d'éléments de liste ─────────────────────────────────────────────────
abstract class _ListItem {}

class _WhatsAppBanner extends _ListItem {}

class _DateHeader extends _ListItem {
  final String label;
  _DateHeader(this.label);
}

class _NotifItem extends _ListItem {
  final NotificationDS notif;
  final String timeLabel;
  final bool isLastBeforeHeader; // pas de margin bottom si vrai
  _NotifItem(this.notif, this.timeLabel, {this.isLastBeforeHeader = false});
}

// ── Helpers de formatage ──────────────────────────────────────────────────────
String _dayKey(DateTime dt) =>
    '${dt.year}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')}';

String _headerLabel(DateTime dt) {
  final now = DateTime.now();
  final today = DateTime(now.year, now.month, now.day);
  final d = DateTime(dt.year, dt.month, dt.day);
  final diff = today.difference(d).inDays;

  if (diff == 0) return (langUserPhone == 'fr') ? "Aujourd'hui" : "Today";
  if (diff == 1) return (langUserPhone == 'fr') ? "Hier" : "Yesterday";

  const monthsFr = [
    '',
    'Janvier',
    'Février',
    'Mars',
    'Avril',
    'Mai',
    'Juin',
    'Juillet',
    'Août',
    'Septembre',
    'Octobre',
    'Novembre',
    'Décembre'
  ];
  const monthsEn = [
    '',
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December'
  ];
  final months = (langUserPhone == 'fr') ? monthsFr : monthsEn;

  if (dt.year == now.year) return '${dt.day} ${months[dt.month]}';
  return '${dt.day} ${months[dt.month]} ${dt.year}';
}

String _timeLabel(DateTime dt) {
  final h = dt.hour.toString().padLeft(2, '0');
  final m = dt.minute.toString().padLeft(2, '0');
  return '$h:$m';
}

// ── Regroupement ──────────────────────────────────────────────────────────────
List<_ListItem> _buildItems(List<NotificationDS> notifications) {
  // Bannière WhatsApp en premier
  final items = <_ListItem>[_WhatsAppBanner()];

  String? currentKey;
  for (int i = 0; i < notifications.length; i++) {
    final n = notifications[i];
    final key = _dayKey(n.createdAt);
    if (key != currentKey) {
      items.add(_DateHeader(_headerLabel(n.createdAt)));
      currentKey = key;
    }
    // Détermine si c'est le dernier avant un nouveau groupe
    final nextIsNewDay = i + 1 < notifications.length &&
        _dayKey(notifications[i + 1].createdAt) != key;
    items.add(_NotifItem(n, _timeLabel(n.createdAt),
        isLastBeforeHeader: nextIsNewDay));
  }
  return items;
}

// ── Widget principal ──────────────────────────────────────────────────────────
class ListeNotification extends StatefulWidget {
  @override
  State<ListeNotification> createState() => _ListeNotificationState();
}

class _ListeNotificationState extends State<ListeNotification> {
  final ScrollController _scrollController = ScrollController();

  List<NotificationDS> _notifications = [];
  bool _loading = true;
  bool _hasError = false;

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchNotifications() async {
    if (!mounted) return;
    setState(() {
      _loading = true;
      _hasError = false;
    });

    try {
      final url = Uri.parse('$generalRouteForApi/getNotifications');
      final response = await http.post(
        url,
        body: {'uid': '$uidUser'},
      ).timeout(const Duration(seconds: 15));

      if (!mounted) return;
      final Map<String, dynamic> body = jsonDecode(response.body);

      if (body['error'] == false) {
        final List<dynamic> raw = body['notifications'] ?? [];
        setState(() {
          _notifications = raw.map((e) => NotificationDS.fromJson(e)).toList();
          _loading = false;
        });
      } else {
        setState(() {
          _hasError = true;
          _loading = false;
        });
      }
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _hasError = true;
        _loading = false;
      });
    }
  }

  Future<void> _openWhatsApp() async {
    final uri = Uri.tryParse(chaineWhatsApp);
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
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          "Notifications",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
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
                _fetchNotifications();
              } else if (value == 2) {
                Navigator.push(
                    context, MaterialPageRoute(builder: (_) => SupportPage()));
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
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            FaIcon(FontAwesomeIcons.circleExclamation,
                color: Colors.grey[400], size: 40),
            const SizedBox(height: 14),
            Text(
              (langUserPhone == 'fr')
                  ? "Impossible de charger les notifications."
                  : "Unable to load notifications.",
              style: GoogleFonts.poppins(color: Colors.grey[500], fontSize: 14),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            TextButton.icon(
              onPressed: _fetchNotifications,
              icon: const FaIcon(FontAwesomeIcons.arrowsRotate,
                  size: 14, color: Colors.white),
              label: Text(
                (langUserPhone == 'fr') ? "Réessayer" : "Retry",
                style: GoogleFonts.poppins(color: Colors.white),
              ),
              style: TextButton.styleFrom(backgroundColor: primaryColor),
            ),
          ],
        ),
      );
    }

    if (_notifications.isEmpty) {
      return ListView(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        children: [
          _WhatsAppBannerWidget(onTap: _openWhatsApp, isDark: isDark),
          const SizedBox(height: 40),
          Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                FaIcon(FontAwesomeIcons.solidBell,
                    color: Colors.grey[300], size: 48),
                const SizedBox(height: 14),
                Text(
                  (langUserPhone == "fr")
                      ? "Aucune notification reçue"
                      : "No notifications received",
                  style: GoogleFonts.poppins(
                      fontSize: 15, color: Colors.grey[500]),
                ),
              ],
            ),
          ),
        ],
      );
    }

    final items = _buildItems(_notifications);

    return RefreshIndicator(
      color: primaryColor,
      onRefresh: _fetchNotifications,
      child: ListView.builder(
        controller: _scrollController,
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        itemCount: items.length,
        itemBuilder: (context, index) {
          final item = items[index];
          if (item is _WhatsAppBanner) {
            return _WhatsAppBannerWidget(onTap: _openWhatsApp, isDark: isDark);
          }
          if (item is _DateHeader) {
            return _DateSeparator(
              label: item.label,
              isDark: isDark,
              isFirst: index == 1, // index 0 = bannière WA
            );
          }
          if (item is _NotifItem) {
            return _NotificationCard(
              notif: item.notif,
              isDark: isDark,
              timeLabel: item.timeLabel,
              isLastBeforeHeader: item.isLastBeforeHeader,
            );
          }
          return const SizedBox.shrink();
        },
      ),
    );
  }
}

// ── Bannière chaîne WhatsApp ───────────────────────────────────────────────────
class _WhatsAppBannerWidget extends StatelessWidget {
  final VoidCallback onTap;
  final bool isDark;

  const _WhatsAppBannerWidget({required this.onTap, required this.isDark});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF128C7E), Color(0xFF25D366)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF25D366).withOpacity(0.30),
            blurRadius: 14,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.18),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const FaIcon(FontAwesomeIcons.whatsapp,
                      color: Colors.white, size: 26),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        (langUserPhone == 'fr')
                            ? "Chaîne WhatsApp"
                            : "WhatsApp Channel",
                        style: GoogleFonts.poppins(
                          color: Colors.white,
                          fontWeight: FontWeight.w700,
                          fontSize: 14,
                        ),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        (langUserPhone == 'fr')
                            ? "Offres flash, promos immédiates et actus en temps réel"
                            : "Flash deals, instant promos & real-time updates",
                        style: GoogleFonts.poppins(
                          color: Colors.white.withOpacity(0.88),
                          fontSize: 12,
                          height: 1.4,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    (langUserPhone == 'fr') ? "Suivre" : "Follow",
                    style: GoogleFonts.poppins(
                      color: const Color(0xFF128C7E),
                      fontWeight: FontWeight.w700,
                      fontSize: 12,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// ── Séparateur de date ─────────────────────────────────────────────────────────
class _DateSeparator extends StatelessWidget {
  final String label;
  final bool isDark;
  final bool isFirst;

  const _DateSeparator(
      {required this.label, required this.isDark, this.isFirst = false});

  @override
  Widget build(BuildContext context) {
    return Padding(
      // Pas de margin top si c'est le premier séparateur (juste après la bannière)
      padding: EdgeInsets.only(top: isFirst ? 10 : 18, bottom: 8),
      child: Row(
        children: [
          Expanded(
            child: Divider(
              color: isDark ? Colors.grey[700] : Colors.grey[300],
              thickness: 1,
              endIndent: 10,
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
            decoration: BoxDecoration(
              color: primaryColor.withOpacity(0.1),
              borderRadius: BorderRadius.circular(20),
              border:
                  Border.all(color: primaryColor.withOpacity(0.25), width: 1),
            ),
            child: Text(
              label,
              style: GoogleFonts.poppins(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: primaryColor,
              ),
            ),
          ),
          Expanded(
            child: Divider(
              color: isDark ? Colors.grey[700] : Colors.grey[300],
              thickness: 1,
              indent: 10,
            ),
          ),
        ],
      ),
    );
  }
}

// ── Carte notification ─────────────────────────────────────────────────────────
class _NotificationCard extends StatelessWidget {
  final NotificationDS notif;
  final bool isDark;
  final String timeLabel;
  final bool isLastBeforeHeader;

  const _NotificationCard({
    required this.notif,
    required this.isDark,
    required this.timeLabel,
    this.isLastBeforeHeader = false,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      // Supprime le margin bottom si la prochaine entrée est un séparateur de jour
      margin: EdgeInsets.only(bottom: isLastBeforeHeader ? 0 : 8),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E1E1E) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isDark ? Colors.grey[800]! : Colors.grey[200]!,
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: isDark
                ? Colors.black.withOpacity(0.12)
                : Colors.grey.withOpacity(0.07),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(9),
            decoration: BoxDecoration(
              color: primaryColor.withOpacity(0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: FaIcon(FontAwesomeIcons.solidBell,
                color: primaryColor, size: 18),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  notif.text,
                  style: GoogleFonts.poppins(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    color: isDark ? Colors.white : Colors.black87,
                    height: 1.45,
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  timeLabel,
                  style: GoogleFonts.poppins(
                    fontSize: 11,
                    color: isDark ? Colors.grey[500] : Colors.grey[400],
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
