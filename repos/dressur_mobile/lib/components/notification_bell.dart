import 'dart:convert';
import 'dart:math';

import 'package:dressur/1_reception/liste_notification.dart';
import 'package:dressur/components/constant.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// Icône de cloche avec badge du nombre de notifications non vues.
///
/// À utiliser dans les `actions` de l'AppBar des pages principales
/// (Réception, Services, Actu, Préférences, Paramètres) pour garder un
/// comportement identique partout : le badge se met à jour au chargement de
/// la page et se remet à zéro dès que l'utilisateur ouvre la liste des
/// notifications.
class NotificationBellAction extends StatefulWidget {
  const NotificationBellAction({Key? key}) : super(key: key);

  @override
  State<NotificationBellAction> createState() =>
      _NotificationBellActionState();
}

class _NotificationBellActionState extends State<NotificationBellAction> {
  int _notifCount = 0; // nombre de notifs non vues
  int _totalNotifs = 0; // total actuel renvoyé par l'API

  static const _prefKey = 'notif_last_seen_total';

  @override
  void initState() {
    super.initState();
    _loadNotifCount();
  }

  // ── Récupère le total de notifs et calcule le badge ──────────────────────
  Future<void> _loadNotifCount() async {
    try {
      final response = await http.post(
        Uri.parse('$generalRouteForApi/getNotifications'),
        body: {'uid': '$uidUser'},
      ).timeout(const Duration(seconds: 10));

      final body = jsonDecode(response.body) as Map<String, dynamic>;
      if (body['error'] == true) return;

      final List<dynamic> raw = body['notifications'] ?? [];
      final total = raw.length;

      final prefs = await SharedPreferences.getInstance();
      final lastSeen = prefs.getInt(_prefKey) ?? 0;
      final unseen = max(0, total - lastSeen);

      if (!mounted) return;
      setState(() {
        _totalNotifs = total;
        _notifCount = unseen;
      });
    } catch (_) {}
  }

  // ── Marque toutes les notifs comme vues ──────────────────────────────────
  Future<void> _markAsSeen() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setInt(_prefKey, _totalNotifs);
    } catch (_) {}
    if (!mounted) return;
    setState(() => _notifCount = 0);
  }

  // ── Ouvre la page notifs puis remet le badge à 0 ─────────────────────────
  void _openNotifications() {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => ListeNotification()),
    ).then((_) => _markAsSeen());
  }

  @override
  Widget build(BuildContext context) {
    return IconButton(
      onPressed: _openNotifications,
      icon: Stack(
        clipBehavior: Clip.none,
        children: [
          const FaIcon(
            FontAwesomeIcons.solidBell,
            size: 20,
            color: Colors.white,
          ),
          if (_notifCount > 0)
            Positioned(
              top: -5,
              right: -6,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 3),
                constraints:
                    const BoxConstraints(minWidth: 16, minHeight: 16),
                decoration: const BoxDecoration(
                  color: Colors.red,
                  shape: BoxShape.circle,
                ),
                alignment: Alignment.center,
                child: Text(
                  _notifCount > 9 ? '9+' : '$_notifCount',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 9,
                    fontWeight: FontWeight.bold,
                    height: 1,
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
            ),
        ],
      ),
    );
  }
}
