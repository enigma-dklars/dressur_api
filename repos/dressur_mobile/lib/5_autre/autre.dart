// ignore_for_file: use_build_context_synchronously, prefer_const_constructors

import 'package:dressur/5_autre/liste_tuto.dart';
import 'package:dressur/5_autre/suggestions.dart';
import 'package:dressur/5_autre/supprimer_contacts_ds.dart';
import 'package:dressur/components/sociaux.dart';
import 'package:flutter/material.dart';
import 'package:dressur/8_admin/admin.dart';
import 'package:dressur/5_autre/a_propos_ds.dart';
import 'package:dressur/5_autre/delete_compte_user.dart';
import 'package:dressur/5_autre/modifier_mot_de_passe.dart';
import 'package:dressur/5_autre/espace_partenaire.dart';
import 'package:dressur/5_autre/utiliser_code_partenaire.dart';
import 'package:dressur/5_autre/profil_user.dart';
import 'package:dressur/4_preference/preference.dart';
import 'package:dressur/5_autre/signaler_user.dart';
import 'package:dressur/components/notification_bell.dart';
import 'package:dressur/7_demarage/presentation_ds.dart';
import 'package:dressur/5_autre/support_assistance.dart';
import 'package:dressur/6_assistant/assistant_page.dart';
import 'package:art_sweetalert/art_sweetalert.dart';
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/sql_helper.dart';
import 'package:dressur/main.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'dart:math';
import 'package:dressur/1_reception/liste_contact.dart';
import 'package:dressur/1_reception/synchronisation_avance.dart';
import 'package:dressur/1_reception/recompense_dashboard.dart';
import 'package:dressur/1_reception/recompense_start.dart';
import 'package:dressur/5_autre/vendeur_adhesion.dart';
import 'package:dressur/5_autre/vendeur_recharge.dart';
import 'package:dressur/1_reception/liste_notification.dart';

class SettingPage extends StatefulWidget {
  SettingPage({Key? key}) : super(key: key);

  @override
  State<SettingPage> createState() => _SettingPageState();
}

class _SettingPageState extends State<SettingPage>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  // ── Notification badge ────────────────────────────────────────────────────
  int _notifCount = 0;
  int _totalNotifs = 0;
  static const _notifPrefKey = 'notif_last_seen_total';

  @override
  void initState() {
    super.initState();
    _loadNotifCount();
  }

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
      final lastSeen = prefs.getInt(_notifPrefKey) ?? 0;
      final unseen = max(0, total - lastSeen);

      if (!mounted) return;
      setState(() {
        _totalNotifs = total;
        _notifCount = unseen;
      });
    } catch (_) {}
  }

  Future<void> _markNotifAsSeen() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setInt(_notifPrefKey, _totalNotifs);
    } catch (_) {}
    if (!mounted) return;
    setState(() => _notifCount = 0);
  }

  void _openNotifications(BuildContext context) {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => ListeNotification()),
    ).then((_) => _markNotifAsSeen());
  }

  Future<void> _setTheme(ThemeMode mode) async {
    MyApp.themeNotifier.value = mode;
    final prefs = await SharedPreferences.getInstance();
    final key = mode == ThemeMode.light
        ? 'light'
        : mode == ThemeMode.dark
            ? 'dark'
            : 'system';
    await prefs.setString('themeMode', key);
    setState(() {});
  }

  Future<void> _setLang(String lang) async {
    setState(() {
      langUserPhone = lang;
    });
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$generalRouteForApi/updateUserLang'),
      );
      request.fields['uid'] = uidUser ?? '';
      request.fields['lang'] = lang;
      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);
      if (response.statusCode == 200) {
        // if (body.contains('"error":false')) {
        //   setState(() {
        //     langUserPhone = lang;
        //   });
        // }
      }
    } catch (_) {}
  }

  void _handleLogout() async {
    ArtDialogResponse response = await ArtSweetAlert.show(
        barrierDismissible: false,
        context: context,
        artDialogArgs: ArtDialogArgs(
            title: (langUserPhone == "fr") ? "Déconnexion ?" : "Sign out?",
            text: (langUserPhone == "fr")
                ? "Voulez-vous vraiment vous déconnecter ?"
                : "Do you really want to sign out?",
            confirmButtonText: (langUserPhone == "fr") ? "Oui" : "Yes",
            denyButtonText: (langUserPhone == "fr") ? "Non" : "No",
            type: ArtSweetAlertType.question));

    if (response.isTapConfirmButton) {
      SQLHelper.viderLaBaseDeDonneeLocal();
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (context) => WelcomePage()),
        (Route<dynamic> route) => false,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Scaffold(
      backgroundColor: isDark ? Color(0xFF121212) : Color(0xFFF8F9FA),
      appBar: AppBar(
        automaticallyImplyLeading: false,
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          (langUserPhone == "fr") ? "Paramètres" : "Settings",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
        actions: [
          const NotificationBellAction(),
          const Padding(
            padding: EdgeInsets.fromLTRB(0, 10, 0, 10),
            child: VerticalDivider(
              width: 0,
              color: Colors.white,
              thickness: 1,
            ),
          ),
          PopupMenuButton<int>(
            itemBuilder: (context) => [
              PopupMenuItem(
                value: 1,
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
              if (value == 1) {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (context) => SupportPage()),
                );
              }
            },
          ),
        ],
      ),
      body: SingleChildScrollView(
        physics: BouncingScrollPhysics(),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // --- SECTION ADMINISTRATION (admins uniquement) ---
            if (admin) ...[
              _buildSectionTitle((langUserPhone == "fr") ? "Administration" : "Administration"),
              _buildMenuContainer(isDark, [
                _buildMenuRow(
                    FontAwesomeIcons.userShield,
                    (langUserPhone == "fr")
                        ? "Panneau Administrateur"
                        : "Admin Panel",
                    () => Navigator.push(
                        context,
                        MaterialPageRoute(
                            builder: (context) => AdministrationPage()))),
              ]),
            ],

            // --- CARTE SOLDE ---
            const SizedBox(height: 4),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 10),
              child: _buildSoldeCard(context),
            ),
            const SizedBox(height: 10),

            // --- SECTION MON COMPTE ---
            _buildSectionTitle(
                (langUserPhone == "fr") ? "Mon Compte" : "My Account"),
            _buildMenuContainer(isDark, [
              // -- Identité & sécurité --
              _buildMenuRow(
                  FontAwesomeIcons.user,
                  (langUserPhone == "fr") ? "Profil" : "Profile",
                  () => Navigator.push(context,
                      MaterialPageRoute(builder: (context) => ProfilPage()))),
              _buildMenuRow(
                  FontAwesomeIcons.lock,
                  (langUserPhone == "fr")
                      ? "Modifier le mot de passe"
                      : "Change Password",
                  () => Navigator.push(
                      context,
                      MaterialPageRoute(
                          builder: (context) => ModifierMdpPage()))),
              // -- Données principales --
              _buildMenuRow(
                  FontAwesomeIcons.solidAddressBook,
                  (langUserPhone == "fr") ? "Contacts" : "Contacts",
                  () => Navigator.push(
                      context,
                      MaterialPageRoute(builder: (context) => ContactPage()))),
              _buildMenuRow(
                  FontAwesomeIcons.arrowsRotate,
                  (langUserPhone == "fr")
                      ? "Synchronisation avancée"
                      : "Advanced Sync",
                  () => Navigator.push(
                      context,
                      MaterialPageRoute(
                          builder: (context) => const SynchroAvance()))),
              // -- Développer son compte --
              _buildMenuRow(
                  FontAwesomeIcons.trophy,
                  (langUserPhone == "fr") ? "Espace Récompense" : "Reward Space",
                  () => Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => isInscritProgrammeRecompense
                          ? ProgrammeRecompenseDashboard()
                          : ProgrammeRecompensePage(optionPage: false),
                    ),
                  )),
              if (!isVendeur)
                _buildMenuRow(
                    FontAwesomeIcons.store,
                    (langUserPhone == "fr")
                        ? "Devenir Vendeur"
                        : "Become a Vendor",
                    () => Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => VendeurAdhesionPage()),
                    ).then((_) => setState(() {}))),
              // -- Partenariat --
              if (!aUnPartenaire)
                _buildMenuRow(
                    FontAwesomeIcons.handshake,
                    (langUserPhone == "fr")
                        ? "Code Partenaire"
                        : "Partner Code",
                    () => Navigator.push(
                        context,
                        MaterialPageRoute(
                            builder: (context) => UtiliserCodePartenairePage()))),
              _buildMenuRow(
                  FontAwesomeIcons.star,
                  (langUserPhone == "fr") ? "Espace Partenaire" : "Partner Space",
                  () => Navigator.push(
                      context,
                      MaterialPageRoute(
                          builder: (context) => const EspacePartenairePage()))),
              // -- Personnalisation --
              _buildMenuRow(
                  FontAwesomeIcons.heart,
                  (langUserPhone == "fr") ? "Préférences" : "Preferences",
                  () => Navigator.push(
                      context,
                      MaterialPageRoute(
                          builder: (context) => PreferencePage()))),
            ]),

            // --- SECTION ASSISTANCE & FEEDBACK ---
            _buildSectionTitle((langUserPhone == "fr")
                ? "Assistance & Avis"
                : "Support & Feedback"),
            _buildMenuContainer(isDark, [
              _buildMenuRow(
                  FontAwesomeIcons.graduationCap,
                  (langUserPhone == "fr") ? "Tutoriels" : "Tutorials",
                  () => Navigator.push(context,
                      MaterialPageRoute(builder: (context) => const ListeTuto()))),
              _buildMenuRow(
                  FontAwesomeIcons.comments,
                  (langUserPhone == "fr")
                      ? "Assistant IA"
                      : "AI Assistant",
                  () => Navigator.push(context,
                      MaterialPageRoute(builder: (context) => const AssistantPage()))),
              _buildMenuRow(
                  FontAwesomeIcons.headset,
                  (langUserPhone == "fr")
                      ? "Support Technique"
                      : "Technical Support",
                  () => Navigator.push(context,
                      MaterialPageRoute(builder: (context) => SupportPage()))),
              _buildMenuRow(
                  FontAwesomeIcons.lightbulb,
                  (langUserPhone == "fr") ? "Suggestions" : "Suggestions",
                  () => Navigator.push(
                      context,
                      MaterialPageRoute(
                          builder: (context) => SuggestionsPage()))),
              _buildMenuRow(
                  FontAwesomeIcons.triangleExclamation,
                  (langUserPhone == "fr")
                      ? "Signaler un utilisateur"
                      : "Report a User",
                  () => Navigator.push(context,
                      MaterialPageRoute(builder: (context) => SignalerPage()))),
            ]),

            // --- SECTION ACTIONS AVANCÉES ---
            _buildSectionTitle((langUserPhone == "fr")
                ? "Actions Avancées"
                : "Advanced Actions"),
            _buildMenuContainer(isDark, [
              _buildMenuRow(
                FontAwesomeIcons.broom,
                (langUserPhone == "fr")
                    ? "Supprimer les contacts DS"
                    : "Delete DS Contacts",
                () => Navigator.push(
                    context,
                    MaterialPageRoute(
                        builder: (context) => const SupprimerContactsDSPage())),
                color: Colors.orange[700],
              ),
              _buildMenuRow(
                FontAwesomeIcons.trash,
                (langUserPhone == "fr")
                    ? "Supprimer mon compte"
                    : "Delete My Account",
                () => Navigator.push(
                    context,
                    MaterialPageRoute(
                        builder: (context) => DeletecomptePage())),
                color: Colors.red,
              ),
            ]),

            // --- SECTION APPLICATION ---
            _buildSectionTitle((langUserPhone == "fr") ? "Application" : "Application"),
            _buildMenuContainer(isDark, [
              _buildLanguageSelector(isDark),
              _buildThemeSelector(isDark),
              _buildMenuRow(
                  FontAwesomeIcons.circleInfo,
                  (langUserPhone == "fr") ? "À Propos" : "About Us",
                  () => Navigator.push(context,
                      MaterialPageRoute(builder: (context) => AproposPage()))),
              _buildMenuRow(
                  FontAwesomeIcons.rightFromBracket,
                  (langUserPhone == "fr") ? "Se déconnecter" : "Sign Out",
                  _handleLogout,
                  showChevron: false),
            ]),

            // --- SECTION ABONNEMENT & PARTAGE ---
            _buildSectionTitle((langUserPhone == "fr")
                ? "Abonnement et Partage"
                : "Subscription & Sharing"),
            SociauxPage(),

            SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  // --- WIDGETS HELPERS POUR UN DESIGN PROPRE ET RÉUTILISABLE ---

  Widget _buildLanguageSelector(bool isDark) {
    final options = [
      (
        'fr',
        FontAwesomeIcons.f,
        'Français',
      ),
      (
        'en',
        FontAwesomeIcons.e,
        'English',
      ),
    ];

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              FaIcon(FontAwesomeIcons.language, color: primaryColor, size: 18),
              SizedBox(width: 16),
              Text(
                (langUserPhone == "fr") ? "Langue" : "Language",
                style: GoogleFonts.poppins(
                  fontSize: 15,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          SizedBox(height: 12),
          Container(
            margin: EdgeInsets.only(left: 34),
            decoration: BoxDecoration(
              color: isDark ? Color(0xFF2C2C2C) : Color(0xFFF0F0F0),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              children: options.map((opt) {
                final isSelected = langUserPhone == opt.$1;
                return Expanded(
                  child: GestureDetector(
                    onTap: () => _setLang(opt.$1),
                    child: AnimatedContainer(
                      duration: Duration(milliseconds: 200),
                      padding: EdgeInsets.symmetric(vertical: 9),
                      decoration: BoxDecoration(
                        color: isSelected ? primaryColor : Colors.transparent,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          FaIcon(
                            opt.$2,
                            size: 14,
                            color: isSelected
                                ? Colors.white
                                : (isDark
                                    ? Colors.grey[400]
                                    : Colors.grey[600]),
                          ),
                          SizedBox(height: 4),
                          Text(
                            opt.$3,
                            style: GoogleFonts.poppins(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: isSelected
                                  ? Colors.white
                                  : (isDark
                                      ? Colors.grey[400]
                                      : Colors.grey[600]),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildThemeSelector(bool isDark) {
    final currentMode = MyApp.themeNotifier.value;

    final options = [
      (
        ThemeMode.system,
        FontAwesomeIcons.circleHalfStroke,
        (langUserPhone == "fr") ? "Système" : "System",
      ),
      (
        ThemeMode.light,
        FontAwesomeIcons.sun,
        (langUserPhone == "fr") ? "Clair" : "Light",
      ),
      (
        ThemeMode.dark,
        FontAwesomeIcons.moon,
        (langUserPhone == "fr") ? "Sombre" : "Dark",
      ),
    ];

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              FaIcon(FontAwesomeIcons.palette, color: primaryColor, size: 18),
              SizedBox(width: 16),
              Text(
                (langUserPhone == "fr") ? "Thème" : "Theme",
                style: GoogleFonts.poppins(
                  fontSize: 15,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          SizedBox(height: 12),
          Container(
            margin: EdgeInsets.only(left: 34),
            decoration: BoxDecoration(
              color: isDark ? Color(0xFF2C2C2C) : Color(0xFFF0F0F0),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              children: options.map((opt) {
                final isSelected = currentMode == opt.$1;
                return Expanded(
                  child: GestureDetector(
                    onTap: () => _setTheme(opt.$1),
                    child: AnimatedContainer(
                      duration: Duration(milliseconds: 200),
                      padding: EdgeInsets.symmetric(vertical: 9),
                      decoration: BoxDecoration(
                        color: isSelected ? primaryColor : Colors.transparent,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          FaIcon(
                            opt.$2,
                            size: 14,
                            color: isSelected
                                ? Colors.white
                                : (isDark
                                    ? Colors.grey[400]
                                    : Colors.grey[600]),
                          ),
                          SizedBox(height: 4),
                          Text(
                            opt.$3,
                            style: GoogleFonts.poppins(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: isSelected
                                  ? Colors.white
                                  : (isDark
                                      ? Colors.grey[400]
                                      : Colors.grey[600]),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  // ── Carte Solde Dressur ───────────────────────────────────────────────────
  Widget _buildSoldeCard(BuildContext context) {
    final bool isFr = langUserPhone == "fr";
    final bool isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 14),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E1E1E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDark ? Colors.grey[800]! : Colors.grey[200]!,
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: isDark
                ? Colors.black.withOpacity(0.15)
                : Colors.grey.withOpacity(0.08),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              FaIcon(FontAwesomeIcons.wallet, color: primaryColor, size: 14),
              const SizedBox(width: 7),
              Text(
                isFr ? "Solde Dressur" : "Dressur Balance",
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  color: isDark ? Colors.grey[400] : Colors.grey[600],
                ),
              ),
              const Spacer(),
              if (isVendeur)
                GestureDetector(
                  onTap: () => Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => VendeurRechargePage()),
                  ).then((_) => setState(() {})),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 12, vertical: 5),
                    decoration: BoxDecoration(
                      color: primaryColor,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      isFr ? "Recharger" : "Top up",
                      style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            "${soldeProgrammeRecompense ?? 0} FCFA",
            style: GoogleFonts.poppins(
              fontSize: 28,
              fontWeight: FontWeight.w700,
              color: isDark ? Colors.white : Colors.black87,
            ),
          ),
          const SizedBox(height: 14),
          Divider(
            height: 1,
            color: isDark ? Colors.grey[800] : Colors.grey[200],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              _buildStatusChip(
                icon: FontAwesomeIcons.store,
                label: isFr ? "Vendeur" : "Vendor",
                active: isVendeur,
              ),
              const SizedBox(width: 8),
              _buildStatusChip(
                icon: FontAwesomeIcons.trophy,
                label: isFr ? "Récompenses" : "Rewards",
                active: isInscritProgrammeRecompense,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatusChip({
    required IconData icon,
    required String label,
    required bool active,
  }) {
    final Color color = active ? Colors.green : Colors.grey;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withOpacity(0.35)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          FaIcon(icon, size: 11, color: color),
          const SizedBox(width: 5),
          Text(
            label,
            style: GoogleFonts.poppins(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  // ── Item de navigation style carte (section Mon Espace) ───────────────────
  Widget _buildNavigationItem({
    required BuildContext context,
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
    int badge = 0,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 10),
        padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 12),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1E1E1E) : Colors.white,
          borderRadius: BorderRadius.circular(15),
          border: Border.all(
              color: isDark ? Colors.grey[800]! : Colors.grey[200]!, width: 1),
          boxShadow: [
            BoxShadow(
              color: isDark
                  ? Colors.black.withOpacity(0.15)
                  : Colors.grey.withOpacity(0.08),
              blurRadius: 8,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Row(
          children: [
            Stack(
              clipBehavior: Clip.none,
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: primaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: FaIcon(icon, color: primaryColor, size: 26),
                ),
                if (badge > 0)
                  Positioned(
                    top: -4,
                    right: -4,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 3),
                      constraints:
                          const BoxConstraints(minWidth: 18, minHeight: 18),
                      decoration: const BoxDecoration(
                        color: Colors.red,
                        shape: BoxShape.circle,
                      ),
                      alignment: Alignment.center,
                      child: Text(
                        badge > 9 ? '9+' : '$badge',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          height: 1,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: GoogleFonts.poppins(
                      color: isDark ? Colors.white : Colors.black87,
                      fontWeight: FontWeight.w600,
                      fontSize: 17,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    subtitle,
                    style: GoogleFonts.poppins(
                      color: isDark ? Colors.grey[400] : Colors.grey[600],
                      fontSize: 13,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            FaIcon(
              FontAwesomeIcons.chevronRight,
              color: isDark ? Colors.grey[600] : Colors.grey[400],
              size: 18,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 10),
      child: Text(
        title.toUpperCase(),
        style: GoogleFonts.poppins(
          fontSize: 12,
          fontWeight: FontWeight.bold,
          color: Colors.grey[500],
          letterSpacing: 0.8,
        ),
      ),
    );
  }

  Widget _buildMenuContainer(bool isDark, List<Widget> children) {
    return Container(
      margin: EdgeInsets.symmetric(horizontal: 10),
      decoration: BoxDecoration(
        color: isDark ? Color(0xFF1E1E1E) : Colors.white,
        borderRadius: BorderRadius.circular(15),
      ),
      child: Column(children: children),
    );
  }

  Widget _buildMenuRow(IconData icon, String text, VoidCallback? onTap,
      {Color? color, bool showChevron = true}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(15),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        child: Row(
          children: [
            FaIcon(icon, color: color ?? primaryColor, size: 18),
            SizedBox(width: 16),
            Expanded(
                child: Text(text,
                    style: GoogleFonts.poppins(
                        fontSize: 15,
                        fontWeight: FontWeight.w500,
                        color: color))),
            if (showChevron)
              FaIcon(FontAwesomeIcons.chevronRight,
                  size: 16, color: Colors.grey[400]),
          ],
        ),
      ),
    );
  }
}
