// ignore_for_file: prefer_const_constructors, use_build_context_synchronously, deprecated_member_use, sort_child_properties_last

import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:path_provider/path_provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:dressur/1_reception/liste_contact.dart';
import 'package:dressur/2_promo/liste_boost_contact.dart';
import 'package:dressur/2_promo/new_boost_contact.dart';
import 'package:dressur/6_assistant/assistant_page.dart';
import 'package:dressur/5_autre/profil_user.dart';
import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter_linkify/flutter_linkify.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/5_autre/confirme_mail_user.dart';
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/notification_bell.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:dressur/components/sociaux.dart';
import 'package:dressur/components/sql_helper.dart';
import 'package:dressur/5_autre/support_assistance.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:dressur/8_admin/admin.dart';

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
  late Animation<double> _glowAnimation;
  late Animation<Color?> _colorAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2000),
    )..repeat(reverse: true);

    _scaleAnimation = Tween<double>(begin: 1.0, end: 1.12).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
    );

    _glowAnimation = Tween<double>(begin: 5.0, end: 18.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
    );

    // Animation de couleur entre la couleur primaire et une variante plus vive (dorée/orange)
    _colorAnimation = ColorTween(
      begin: primaryColor,
      end: Colors
          .red, // Vous pouvez changer pour Colors.amber ou une autre couleur vive
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
              padding: const EdgeInsets.all(5),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: LinearGradient(
                  colors: [
                    currentColor.withOpacity(0.95),
                    currentColor.withOpacity(0.7),
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                border: Border.all(
                  color: Colors.white.withOpacity(0.9),
                  width: 1,
                ),
                boxShadow: [
                  // Lueur colorée animée
                  BoxShadow(
                    color: currentColor.withOpacity(0.6),
                    blurRadius: _glowAnimation.value,
                    spreadRadius: _glowAnimation.value / 3,
                  ),
                  BoxShadow(
                    color: Colors.black.withOpacity(0.3),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: const FaIcon(
                FontAwesomeIcons.star,
                color: Colors.white,
                size: 20,
              ),
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

class StoryModel {
  final int id;
  final String user;
  final String description;
  final String url;
  final List<String> images;
  final List<String> videos;

  StoryModel({
    required this.id,
    required this.user,
    required this.description,
    required this.url,
    required this.images,
    required this.videos,
  });

  factory StoryModel.fromJson(Map<String, dynamic> json) {
    return StoryModel(
      id: json['id'] ?? 0,
      user: json['user'] ?? '',
      description: json['description'] ?? '',
      url: json['url'] ?? '',
      images: List<String>.from(json['images'] ?? []),
      videos: List<String>.from(json['videos'] ?? []),
    );
  }
}

class ActuPage extends StatefulWidget {
  ActuPage({Key? key}) : super(key: key);

  @override
  State<ActuPage> createState() => _ActuPageState();
}

class _ActuPageState extends State<ActuPage>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  final ScrollController _scrollController = ScrollController();

  final ValueNotifier<bool> _showFabText = ValueNotifier(true);
  bool _loading = false;
  List<Map<String, dynamic>> historiqueContactsAdd = [];
  final Set<int> _pendingWatchIds = {};
  Timer? _batchWatchTimer;

  bool havePublicites = false;
  bool rechercheEnCours = true;
  late Future<List<Advertisement>> _futureAdvertisements;
  bool _firstLoad = true;
  List<StoryModel> _stories = [];

  // ── Bandeau statut boost ───────────────────────────────────────────────────
  String? _boostTypeActif;
  int _boostNbObtenus = 0;
  int _boostNbMax = 0;
  bool _bandeauDismissed = false;
  int _bandeauDismissedContactCount = -1;

  @override
  void initState() {
    super.initState();
    _futureAdvertisements = fetchAdvertisements();
    _loadStoriesWithCache();
    _loadPromoWithCache();
    _scrollController.addListener(_scrollListener);
  }

  @override
  void dispose() {
    _scrollController.removeListener(_scrollListener);
    _scrollController.dispose();
    _showFabText.dispose();
    _batchWatchTimer?.cancel();
    super.dispose();
  }

  void showWarningDialog(BuildContext context) {
    final int duree = isNouvelUtilisateur ? 10 : 5;
    showModalBottomSheet(
      context: context,
      isDismissible: false,
      enableDrag: false,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _WarningBottomSheet(totalSeconds: duree),
    );
  }

  void _scrollListener() {
    if (_scrollController.position.userScrollDirection ==
        ScrollDirection.reverse) {
      _showFabText.value = false;
    } else if (_scrollController.position.atEdge &&
        _scrollController.position.pixels == 0) {
      _showFabText.value = true;
    }
  }

  Future<void> _refreshData() async {
    setState(() {
      actualise(true);
    });
  }

  Future<File> _promoCacheFile() async {
    final dir = await getApplicationDocumentsDirectory();
    return File('${dir.path}/promos_cache.json');
  }

  Future<void> _savePromoCache(dynamic promosJson) async {
    if (promosJson is! String || promosJson.isEmpty) return;
    try {
      final file = await _promoCacheFile();
      await file.writeAsString(jsonEncode({
        'ts': DateTime.now().millisecondsSinceEpoch,
        'data': jsonDecode(promosJson),
      }));
    } catch (_) {}
  }

  Future<void> _loadPromoWithCache() async {
    if (lesPublicites is String && (lesPublicites as String).isNotEmpty) {
      _savePromoCache(lesPublicites);
      return;
    }
    try {
      final file = await _promoCacheFile();
      if (await file.exists()) {
        final raw = await file.readAsString();
        final cacheData = jsonDecode(raw) as Map<String, dynamic>;
        final ts = cacheData['ts'] as int? ?? 0;
        final age = DateTime.now().millisecondsSinceEpoch - ts;
        if (cacheData['data'] != null) {
          final cachedPromos = jsonEncode(cacheData['data']);
          if (mounted) {
            setState(() {
              lesPublicites = cachedPromos;
              _futureAdvertisements = fetchAdvertisements();
            });
          }
          if (age < 30 * 60 * 1000) return;
        }
      }
    } catch (_) {}
    _refreshPromosBackground();
  }

  Future<void> _refreshPromosBackground() async {
    try {
      final request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/getUserInfo'));
      request.fields.addAll({
        'uid': uidUser,
      });
      final response = await request.send();
      if (response.statusCode == 200) {
        final data1 = await response.stream.bytesToString();
        final data = jsonDecode(data1) as Map<String, dynamic>;
        if (data['error'] == false) {
          final newPromos = (data['user']['lesPublicites'] as String?) ?? '';
          if (mounted) {
            setState(() {
              initUserInformations(data['user']);
              if (newPromos.isNotEmpty) {
                lesPublicites = newPromos;
                _futureAdvertisements = fetchAdvertisements();
              }
            });
            if (newPromos.isNotEmpty) await _savePromoCache(newPromos);
          }
        }
      }
    } catch (_) {}
  }

  void actualise(affMessage) async {
    if (affMessage == true) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
        content: Text(
          (langUserPhone == "fr")
              ? 'Actualisation en cours…'
              : 'Update in progress…',
          style: GoogleFonts.poppins(
            color: Colors.white,
          ),
        ),
      ));
    }
    setState(() {
      _loading = true;
    });

    var request = http.MultipartRequest(
        'POST', Uri.parse('$generalRouteForApi/getUserInfo'));
    request.fields.addAll({
      'uid': uidUser,
    });

    try {
      http.StreamedResponse response = await request.send();

      if (response.statusCode == 200) {
        var data1 = await response.stream.bytesToString();
        if (!mounted) return;
        var data = convert.jsonDecode(data1);
        if (data["error"] == false) {
          setState(() {
            initUserInformations(data['user']);
            lesPublicites = data['user']["lesPublicites"];
            print(jsonDecode(lesPublicites).length);
            _futureAdvertisements = fetchAdvertisements();
            _loading = false;
            // ── Infos boost actif (retournées par l'API si disponibles) ──
            _boostTypeActif = data['user']['boostTypeActif']?.toString();
            // Utiliser int.tryParse pour éviter un TypeError si l'API renvoie
            // un String au lieu d'un int (ex: "5" au lieu de 5).
            _boostNbObtenus = int.tryParse(
                    data['user']['boostNbObtenus']?.toString() ?? '') ??
                0;
            _boostNbMax = int.tryParse(
                    data['user']['boostNbMax']?.toString() ?? '') ??
                0;
            // Réafficher le bandeau si de nouveaux contacts sont disponibles
            if (nombreContactDispo > 0 &&
                nombreContactDispo != _bandeauDismissedContactCount) {
              _bandeauDismissed = false;
            }
          });
          _savePromoCache(lesPublicites);
        } else {
          setState(() {
            _loading = false;
          });
        }
      } else {
        setState(() {
          _loading = false;
        });
      }
      if (!mounted) return;
      if (affMessage == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: Colors.green,
            behavior: SnackBarBehavior.floating,
            content: Text(
              (langUserPhone == "fr")
                  ? 'Actualisation terminée.'
                  : 'Refresh complete.',
              style: GoogleFonts.poppins(
                color: Colors.white,
              ),
            ),
          ),
        );
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<File> _storiesCacheFile() async {
    final dir = await getApplicationDocumentsDirectory();
    return File('${dir.path}/stories_cache.json');
  }

  Future<void> _loadStoriesWithCache() async {
    // 1. Affichage immédiat depuis le cache local (0 ms de délai)
    try {
      final file = await _storiesCacheFile();
      if (await file.exists()) {
        final raw = await file.readAsString();
        final cached = jsonDecode(raw) as List<dynamic>;
        final list = cached.map((e) => StoryModel.fromJson(e)).toList();
        if (mounted && list.isNotEmpty) {
          setState(() => _stories = list);
        }
      }
    } catch (_) {}

    // 2. Rafraîchissement réseau en arrière-plan
    _fetchStories();
  }

  Future<void> _fetchStories() async {
    try {
      final response = await http.get(
        Uri.parse('$generalRouteForApi/getActiveStories'),
      );
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['error'] == false) {
          final rawList = data['stories'] as List;
          final list = rawList.map((e) => StoryModel.fromJson(e)).toList();
          if (mounted) setState(() => _stories = list);
          // Sauvegarde du cache pour le prochain lancement
          try {
            final file = await _storiesCacheFile();
            await file.writeAsString(jsonEncode(rawList));
          } catch (_) {}
        }
      }
    } catch (_) {}
  }

  Future<List<Advertisement>> fetchAdvertisements() async {
    if (nbrAffichageAvertissement == 0 && !admin) {
      Future.delayed(const Duration(milliseconds: 2500), () {
        if (mounted) showWarningDialog(context);
      });
    }

    setState(() {
      nbrAffichageAvertissement++;
    });

    if (lesPublicites.toString().isNotEmpty) {
      havePublicites = true;
      final jsonData = jsonDecode(lesPublicites) as List<dynamic>;
      final advertisements = jsonData.map((data) {
        return Advertisement(
          uidUser: data['uidUser'],
          id: data['id'],
          imageName: data['image'],
          image: generalRouteForPromotionImage + data['image'],
          description: data['description'],
          whatsappNumber: data['whatsappNumber'],
          pseudoAnnonceur: data['pseudoAnnonceur'],
          nombreDeVues: data['nombreDeVues'],
          nombreImpression: data['nombreImpression'],
          typePromotionAffaire: data['typePromotionAffaire'],
          annotherInfo: data['annotherInfo'] != null
              ? jsonEncode(data['annotherInfo'])
              : "",
          inProgrammeRecompense: data['inProgrammeRecompense'] == 1,
        );
      }).toList();
      // Mélanger l'ordre des éléments
      advertisements.shuffle();
      return advertisements;
    } else {
      havePublicites = false;
      return []; // Retourne une liste vide si aucune annonce n'est disponible
    }
  }

  void setPromotionToWatch(Advertisement advertisement) {
    if (advertisement.uidUser == uidUser) return;
    _pendingWatchIds.add(advertisement.id);
    _batchWatchTimer?.cancel();
    _batchWatchTimer = Timer(const Duration(seconds: 3), _flushWatchBatch);
  }

  Future<void> _flushWatchBatch() async {
    if (_pendingWatchIds.isEmpty) return;
    final ids = List<int>.from(_pendingWatchIds);
    _pendingWatchIds.clear();
    try {
      await http.post(
        Uri.parse('$generalRouteForApi/setMultiplePromotionsToWatch/$uidUser'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'ids': ids}),
      );
    } catch (_) {}
  }

  void _showMessagePasPermiAdd(message, context) async {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(20.0),
          topRight: Radius.circular(20.0),
        ),
      ),
      elevation: 5,
      isScrollControlled: true,
      builder: (_) => Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Colors.red,
              Colors.redAccent,
            ],
          ),
        ),
        padding: EdgeInsets.only(
          top: 0,
          left: 0,
          right: 0,
          // this will prevent the soft keyboard from covering the text fields
          bottom: MediaQuery.of(context).viewInsets.bottom + 0,
        ),
        child: Container(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
          child: Text(
            message,
            style: GoogleFonts.poppins(
              fontSize: 14,
              color: Colors.white,
            ),
            textAlign: TextAlign.justify,
          ),
        ),
      ),
    );
  }

  void addTousLesContacts() async {
    setState(() {
      _loading = true;
    });
    var request = http.MultipartRequest(
        'POST',
        Uri.parse(
            '$generalRouteForApi/addTousUserContact/$uidUser/${langUserPhone.toString()}'));
    request.fields.addAll({
      'uid': uidUser,
    });

    http.StreamedResponse response = await request.send();

    if (response.statusCode == 200) {
      var data1 = await response.stream.bytesToString();
      var data = jsonDecode(data1);
      if (data["error"] == true) {
        if (data["permissionAdd"] == false) {
          setState(() {
            permissionAdd = false;
            messageErreurPermissionAdd = data["messageErreurPermissionAdd"];
          });
          await saveContactsAddsIfExiste(data);
          // tu a deja depasser
          _showMessagePasPermiAdd(messageErreurPermissionAdd, context);
        }
      } else if (data["error"] == false) {
        await saveContactsAddsIfExiste(data);
      } else {
        // erreur server heinnn
      }

      setState(() {
        _loading = false;
      });
    } else {
      setState(() {
        _loading = false;
      });
    }
  }

  Future<void> saveContactsAddsIfExiste(data) async {
    int nombreAddNow = 0;
    if ((data["contactsAdd"]).length >= 1) {
      for (var contactAdd in data["contactsAdd"]) {
        if ((await SQLHelper.getOneNumsTelUser(contactAdd['tel'])).isEmpty) {
          final String tel = (contactAdd["tel"] as String);
          final String telSansPlus = tel.replaceAll("+", "");
          final List<Phone> phonesList = [Phone(tel)];
          if (tel.startsWith("+229") && !tel.startsWith("+22901")) {
            final String afterCode = tel.substring(4);
            phonesList.add(Phone("+22901$afterCode"));
          }
          final String nom = (contactAdd["nom"] ?? "").toString().trim();
          final String pseudo = contactAdd["pseudo"] as String;
          final List<String> nameParts =
              [nom, pseudo, telSansPlus].where((s) => s.isNotEmpty).toList();
          final newContact = Contact()
            ..name.first = "${nameParts.join(" - ")} #DS"
            ..phones = phonesList;
          await newContact.insert();
          await insertNumTelUserIntoDataBase(contactAdd["tel"]);
        }
      }
      setState(() {
        nombreAddNow = (data["contactsAdd"]).length;
        nombreContactDispo = nombreContactDispo - nombreAddNow;
      });
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        backgroundColor: Colors.green,
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 15),
        content: Text(
          (langUserPhone == "fr")
              ? 'ADD de $nombreAddNow contact(s) avec succès.'
              : 'ADD of $nombreAddNow contacts successfully.',
          style: GoogleFonts.poppins(
            color: Colors.white,
          ),
        ),
      ));
    }
  }

  void _showPasDeContactAdd(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(25)),
      ),
      backgroundColor: isDark ? Color(0xFF1E1E1E) : Colors.white,
      builder: (_) => Container(
        padding: EdgeInsets.only(
          top: 12,
          left: 20,
          right: 20,
          bottom: MediaQuery.of(context).viewInsets.bottom + 30,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // --- Poignée de glissement ---
            Container(
              width: 40,
              height: 5,
              decoration: BoxDecoration(
                color: Colors.grey[300],
                borderRadius: BorderRadius.circular(10),
              ),
            ),
            SizedBox(height: 25),

            // --- EN-TÊTE : LE CONSTAT ---
            FaIcon(FontAwesomeIcons.magnifyingGlass,
                color: primaryColor, size: 50),
            SizedBox(height: 16),
            Text(
              (langUserPhone == "fr")
                  ? "Aucun contact disponible"
                  : "No Contacts Available",
              textAlign: TextAlign.center,
              style: GoogleFonts.poppins(
                fontSize: 22,
                fontWeight: FontWeight.bold,
              ),
            ),
            SizedBox(height: 10),
            Text(
              (langUserPhone == "fr")
                  ? "Pour le moment, aucun utilisateur correspondant à vos préférences n'a fait de boost."
                  : "Currently, no users matching your preferences have boosted.",
              textAlign: TextAlign.center,
              style: GoogleFonts.poppins(fontSize: 15, color: Colors.grey[600]),
            ),
            SizedBox(height: 30),

            // --- TITRE DES SOLUTIONS ---
            Text(
              (langUserPhone == "fr")
                  ? "Que faire maintenant ?"
                  : "What to do now?",
              style: GoogleFonts.poppins(
                  fontSize: 18, fontWeight: FontWeight.w600),
            ),
            SizedBox(height: 20),

            // --- CARTE D'ACTION 1 : FAIRE UN BOOST ---
            _buildActionCard(
              context: context,
              icon: FontAwesomeIcons.rocket,
              title:
                  (langUserPhone == "fr") ? "Faire un Boost" : "Make a Boost",
              subtitle: (langUserPhone == "fr")
                  ? "Soyez proactif ! Obtenez de nombreux contacts instantanément."
                  : "Be proactive! Get many contacts instantly.",
              buttonText: (langUserPhone == "fr")
                  ? "Démarrer un Boost"
                  : "Start a Boost",
              buttonColor: primaryColor,
              onTap: () {
                Navigator.pop(context); // Ferme la modale avant de naviguer
                Navigator.push(
                  context,
                  MaterialPageRoute(
                      builder: (context) => NewBoostContactPage()),
                );
              },
            ),
            SizedBox(height: 15),

            // --- CARTE D'ACTION 2 : PARTAGER ---
          ],
        ),
      ),
    );
  }

// --- WIDGET HELPER POUR LES CARTES D'ACTION ---
  Widget _buildActionCard({
    required BuildContext context,
    required IconData icon,
    required String title,
    required String subtitle,
    required String buttonText,
    required Color buttonColor,
    required VoidCallback onTap,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      padding: EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? Colors.grey[850] : Colors.white,
        borderRadius: BorderRadius.circular(15),
        border:
            Border.all(color: isDark ? Colors.grey[800]! : Colors.grey[200]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              FaIcon(icon, color: buttonColor, size: 24),
              SizedBox(width: 12),
              Text(title,
                  style: GoogleFonts.poppins(
                      fontSize: 16, fontWeight: FontWeight.bold)),
            ],
          ),
          SizedBox(height: 8),
          Text(
            subtitle,
            style: GoogleFonts.poppins(color: Colors.grey[600], fontSize: 14),
          ),
          SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: onTap,
              child: Text(buttonText,
                  style: GoogleFonts.poppins(fontWeight: FontWeight.w600)),
              style: ElevatedButton.styleFrom(
                backgroundColor: buttonColor,
                foregroundColor: Colors.white,
                padding: EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ),
        ],
      ),
    );
  } // ---------------------------------------------------------------------------

  // BOTTOM MODAL D'INFORMATION
  // ---------------------------------------------------------------------------
  void _showRewardInfo(BuildContext context) {
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
                (langUserPhone == "fr") ? "Gain estimé" : "Estimated Gain",
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

  // ── Bandeau statut global ─────────────────────────────────────────────────
  Widget? _buildBandeau(BuildContext context) {
    final bool isFr = langUserPhone == 'fr';

    // Si l'utilisateur a fermé le bandeau pour cette session, ne pas afficher
    if (_bandeauDismissed) return null;

    Color bgColor;
    String text;
    String btnLabel;
    VoidCallback onBtnTap;
    Widget? secondBtn;

    if (nombreContactDispo > 0) {
      // ─ CAS 1 : contacts disponibles ──────────────────────────────────────
      bgColor = const Color(0xFF4CAF50);
      text = isFr
          ? '📥 ${nombreContactDispo} contact(s) à enregistrer'
          : '📥 ${nombreContactDispo} contact(s) to save';
      btnLabel = isFr ? 'Enregistrer' : 'Save';
      onBtnTap = () => addTousLesContacts();
    } else if (boostEnCours) {
      // ─ CAS 2 : boost actif ───────────────────────────────────────────────
      bgColor = const Color(0xFF2196F3);
      if (_boostTypeActif == 'quota' && _boostNbMax > 0) {
        text = isFr
            ? '🔵 Boost actif — $_boostNbObtenus/$_boostNbMax contacts reçus'
            : '🔵 Active boost — $_boostNbObtenus/$_boostNbMax contacts received';
      } else {
        text = isFr ? '🔵 Boost Contact actif' : '🔵 Active Boost Contact';
      }
      btnLabel = isFr ? 'Voir' : 'View';
      onBtnTap = () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => ListeBoostContactPage()),
          );
    } else if (!boostEnCours && (_boostNbMax > 0 || _boostNbObtenus > 0)) {
      // ─ CAS 3 : boost expiré (l'API a renvoyé des données de boost mais
      //   boostEnCours est false → quota atteint ou durée écoulée).
      //   On n'utilise plus nombreContacts > 0 pour éviter d'afficher le
      //   bandeau à tout utilisateur ayant déjà eu des contacts sans avoir
      //   de boost actif ou récemment expiré.
      bgColor = const Color(0xFFFF9800);
      text = isFr
          ? '🔴 Votre boost est terminé — Relancez pour continuer'
          : '🔴 Your boost ended — Restart to keep receiving contacts';
      btnLabel = isFr ? 'Relancer' : 'Restart';
      onBtnTap = () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => NewBoostContactPage()),
          );
    } else {
      return null;
    }

    return Container(
      width: double.infinity,
      color: bgColor,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      child: Row(
        children: [
          Expanded(
            child: Text(
              text,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 13,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          const SizedBox(width: 8),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.white,
              foregroundColor: bgColor,
              padding:
                  const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
              elevation: 0,
            ),
            onPressed: onBtnTap,
            child: Text(
              btnLabel,
              style: const TextStyle(
                fontWeight: FontWeight.w600,
                fontSize: 12,
              ),
            ),
          ),
          const SizedBox(width: 6),
          GestureDetector(
            onTap: () => setState(() {
              _bandeauDismissed = true;
              _bandeauDismissedContactCount = nombreContactDispo;
            }),
            child: const Icon(Icons.close, color: Colors.white, size: 18),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    if (_firstLoad) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        // actualise(false);
      });
      setState(() {
        _firstLoad = false;
      });
    }
    return Scaffold(
      appBar: AppBar(
        automaticallyImplyLeading: false,
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          (langUserPhone == "fr") ? "Actu" : "News",
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
                onTap: () {
                  _loading ? '' : actualise(true);
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
              PopupMenuItem(
                value: 3,
                child: Row(
                  children: [
                    Text(
                      (langUserPhone == "fr") ? "Assistant IA" : "AI Assistant",
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
              } else if (value == 3) {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                      builder: (context) => const AssistantPage()),
                );
              }
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // ── Bandeau statut global collant ─────────────────────────────────
          if (addPageActu)
            Builder(builder: (ctx) {
              final bandeau = _buildBandeau(ctx);
              return bandeau ?? const SizedBox.shrink();
            }),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _refreshData,
              child: ListView(
                controller: _scrollController,
                children: [
                  const SizedBox(height: 15),
                  // ── Strip de Stories ──────────────────────────────
                  if (_stories.isNotEmpty)
                    _StoryStrip(
                      stories: _stories,
                      routeStoryImage: generalRouteForStoryImage,
                    ),

                  // Affiche la carte de mise à jour si nécessaire
                  if (int.parse(versionApp.toString().replaceAll(".", "")) <
                      int.parse(
                          myDressurVersion.toString().replaceAll(".", ""))) ...[
                    _buildUpdateCard(
                      context: context,
                      onUpdate: () async {
                        final Uri _url = Uri.parse(dressurUrlPlaystore);
                        if (!await launchUrl(_url,
                            mode: LaunchMode.externalApplication)) {
                          throw 'Could not launch $_url';
                        }
                      },
                    ),
                  ],

                  _buildActionChecklist(
                    context: context,
                    showTel: !telIsVerified,
                    showMail: !mailIsVerified,
                    showProfile: (nom.toString().replaceAll(' ', '').isEmpty ||
                        nom == null),
                    showBoost: (addPageActu &&
                        nombreContactDispo <= 100 &&
                        boostEnCours == false &&
                        telIsVerified),
                  ),
                  const SizedBox(height: 10),

                  if (addPageActu && nombreContactDispo > 0) ...[
                    const SizedBox(height: 5),
                    _buildAvailableContactsCard(
                      context: context,
                      contactCount: nombreContactDispo,
                      isLoading: _loading,
                      onRefresh: () => actualise(true),
                      onSaveAll: () {
                        if (!telIsVerified) {
                          showConfNumeroWhatsapp(context);
                        }
                        // else if (!boostEnCours) {
                        //   showDialog(
                        //     context: context,
                        //     builder: (context) => AlertDialog(
                        //       title: Text(
                        //         (langUserPhone == "fr")
                        //             ? 'Boost Contact requis'
                        //             : 'Boost Contact required',
                        //       ),
                        //       content: Text(
                        //         (langUserPhone == "fr")
                        //             ? 'Vous devez avoir un Boost Contact en cours pour pouvoir ajouter des contacts. Voulez-vous en démarrer un maintenant ?'
                        //             : 'You must have an active Boost Contact to add contacts. Would you like to start one now?',
                        //       ),
                        //       actions: [
                        //         TextButton(
                        //           onPressed: () => Navigator.pop(context),
                        //           child: Text(
                        //             (langUserPhone == "fr")
                        //                 ? 'Annuler'
                        //                 : 'Cancel',
                        //           ),
                        //         ),
                        //         TextButton(
                        //           onPressed: () {
                        //             Navigator.pop(context);
                        //             Navigator.push(
                        //               context,
                        //               MaterialPageRoute(
                        //                   builder: (context) =>
                        //                       NewBoostContactPage()),
                        //             );
                        //           },
                        //           child: Text(
                        //             (langUserPhone == "fr")
                        //                 ? 'Démarrer un Boost'
                        //                 : 'Start a Boost',
                        //           ),
                        //         ),
                        //       ],
                        //     ),
                        //   );
                        // }
                        else {
                          addTousLesContacts();
                        }
                      },
                      onGoToContacts: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                              builder: (context) => ContactPage()),
                        );
                      },
                    ),
                    const SizedBox(height: 10),
                  ],
                  if (havePublicites == true)
                    FutureBuilder<List<Advertisement>>(
                      future: _futureAdvertisements,
                      builder: (context, snapshot) {
                        if (snapshot.connectionState ==
                            ConnectionState.waiting) {
                          return const Center(
                            child:
                                CircularProgressIndicator(color: primaryColor),
                          );
                        } else if (snapshot.hasError) {
                          return Center(
                            child: Text((langUserPhone == "fr")
                                ? 'Erreur: ${snapshot.error}'
                                : 'Error: ${snapshot.error}'),
                          );
                        } else if (!snapshot.hasData ||
                            snapshot.data!.isEmpty) {
                          return const Center(
                            child: null,
                          );
                        } else {
                          return ListView.builder(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: snapshot.data!.length,
                            itemBuilder: (context, index) {
                              Advertisement advertisement =
                                  snapshot.data![index];
                              return Container(
                                margin: const EdgeInsets.only(
                                    left: 7, top: 0, right: 7, bottom: 0),
                                child: Column(
                                  children: [
                                    Card(
                                      child: Column(
                                        children: [
                                          GestureDetector(
                                            onTap: () {
                                              setPromotionToWatch(
                                                  advertisement);
                                              Navigator.push(
                                                context,
                                                MaterialPageRoute(
                                                  builder: (context) =>
                                                      AdvertisementDetailPage(
                                                    advertisement:
                                                        advertisement,
                                                  ),
                                                ),
                                              );
                                            },
                                            child: Column(
                                              children: [
                                                Stack(
                                                  children: [
                                                    ClipRRect(
                                                      borderRadius:
                                                          BorderRadius.circular(
                                                              10),
                                                      child: CachedNetworkImage(
                                                        imageUrl:
                                                            advertisement.image,
                                                        placeholder: (context,
                                                                url) =>
                                                            Image.asset(
                                                                'images/placeholder.png'),
                                                        errorWidget: (context,
                                                                url, error) =>
                                                            Image.asset(
                                                                'images/error_image.png'),
                                                        fit: BoxFit.cover,
                                                      ),
                                                    ),

                                                    // BADGE ÉLIGIBILITÉ (Affiche si inProgrammeRecompense est vrai)
                                                    if (advertisement
                                                        .inProgrammeRecompense)
                                                      Positioned(
                                                        top: 10,
                                                        right: 10,
                                                        child:
                                                            AnimatedRewardBadge(
                                                          onTap: () =>
                                                              _showRewardInfo(
                                                                  context),
                                                        ),
                                                      ),
                                                  ],
                                                ),
                                                const SizedBox(height: 10),
                                                Padding(
                                                  padding:
                                                      const EdgeInsets.fromLTRB(
                                                          12, 0, 12, 5),
                                                  child: Column(
                                                    children: [
                                                      Text(
                                                        advertisement
                                                            .description
                                                            .replaceAll(
                                                                '\n', ' '),
                                                        maxLines: 4,
                                                        overflow: TextOverflow
                                                            .ellipsis,
                                                        style:
                                                            GoogleFonts.poppins(
                                                                fontSize: 15,
                                                                fontWeight:
                                                                    FontWeight
                                                                        .w400),
                                                      ),
                                                    ],
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                          // les icons
                                          _buildActionFooter(
                                            context: context,
                                            impressionCount:
                                                advertisement.nombreImpression,
                                            viewCount:
                                                advertisement.nombreDeVues,
                                            onShare: () {
                                              sharePromotion(
                                                context,
                                                advertisement.image,
                                                advertisement.imageName,
                                                advertisement.description,
                                              );
                                            },
                                          ),
                                        ],
                                      ),
                                    ),
                                    const SizedBox(height: 5),
                                  ],
                                ),
                              );
                            },
                          );
                        }
                      },
                    ),
                  const SizedBox(height: 5),
                  SociauxPage(),
                  const SizedBox(height: 10),
                  // essai notification
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionChecklist({
    required BuildContext context,
    required bool showTel,
    required bool showMail,
    required bool showProfile,
    required bool showBoost,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    // Liste des actions à afficher
    final List<Widget> actions = [];

    if (showTel) {
      actions.add(_buildActionItem(
        context,
        icon: FontAwesomeIcons.mobileScreen,
        text: (langUserPhone == "fr")
            ? "Confirmer le numéro WhatsApp ($tel)"
            : "Confirm WhatsApp Number ($tel)",
        onTap: () => showWhatsappConfirmation(context),
      ));
    }
    if (showMail) {
      actions.add(_buildActionItem(
        context,
        icon: FontAwesomeIcons.solidEnvelope,
        text: (langUserPhone == "fr")
            ? "Confirmer l'adresse e-mail"
            : "Confirm E-mail Address",
        onTap: () => Navigator.push(context,
            MaterialPageRoute(builder: (context) => CodeMailConfirmePage())),
      ));
    }
    if (showProfile) {
      actions.add(_buildActionItem(
        context,
        icon: FontAwesomeIcons.user,
        text: (langUserPhone == "fr")
            ? "Compléter votre profil"
            : "Complete Your Profile",
        onTap: () => Navigator.push(
            context, MaterialPageRoute(builder: (context) => ProfilPage())),
      ));
    }
    if (showBoost) {
      actions.add(_buildActionItem(
        context,
        icon: FontAwesomeIcons.rocket,
        text: (langUserPhone == "fr")
            ? "Activer le Boost Contact"
            : "Activate Boost Contact",
        onTap: () => _showPasDeContactAdd(context),
      ));
    }

    // Ne rien afficher si la liste est vide
    if (actions.isEmpty) return SizedBox.shrink();

    return Column(
      children: [
        const SizedBox(height: 15),
        Container(
          margin: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: isDark ? Color(0xFF1E1E1E) : Colors.white,
            borderRadius: BorderRadius.circular(15),
            border: Border.all(
                color: isDark ? Colors.grey[800]! : Colors.grey[200]!),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                (langUserPhone == "fr")
                    ? "Pour commencer, vous devez :"
                    : "To get started, you need to:",
                style: GoogleFonts.poppins(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: isDark ? Colors.white : Colors.black87,
                ),
              ),
              SizedBox(height: 10),
              // Affiche les actions avec des séparateurs
              ...List.generate(actions.length, (index) {
                return Column(
                  children: [
                    if (index > 0)
                      Divider(
                          color: isDark ? Colors.grey[800] : Colors.grey[200],
                          height: 1),
                    actions[index],
                  ],
                );
              }),
            ],
          ),
        )
      ],
    );
  }

// Helper pour chaque ligne de la checklist
  Widget _buildActionItem(BuildContext context,
      {required IconData icon,
      required String text,
      required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 12.0),
        child: Row(
          children: [
            FaIcon(icon, color: Colors.redAccent, size: 24),
            SizedBox(width: 16),
            Expanded(
              child: Text(
                text,
                style: GoogleFonts.poppins(
                    fontSize: 15, fontWeight: FontWeight.w500),
              ),
            ),
            FaIcon(FontAwesomeIcons.chevronRight,
                size: 16, color: Colors.grey[400]),
          ],
        ),
      ),
    );
  }

// Helper pour la carte de mise à jour
  Widget _buildUpdateCard(
      {required BuildContext context, required VoidCallback onUpdate}) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [Colors.red, Colors.redAccent.shade700],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.red.withOpacity(0.3),
            blurRadius: 12,
            offset: Offset(0, 5),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              FaIcon(FontAwesomeIcons.arrowDown, color: Colors.white, size: 30),
              SizedBox(width: 12),
              Text(
                (langUserPhone == "fr")
                    ? "Mise à jour requise"
                    : "Update Required",
                style: GoogleFonts.poppins(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          SizedBox(height: 12),
          Text(
            (langUserPhone == "fr")
                ? "Une nouvelle version est disponible. Mettez à jour pour bénéficier des dernières fonctionnalités et améliorations de sécurité."
                : "A new version is available. Update now to get the latest features and security improvements.",
            style: GoogleFonts.poppins(
              color: Colors.white.withOpacity(0.9),
              fontSize: 14,
              height: 1.5,
            ),
          ),
          SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: onUpdate,
              icon: FaIcon(FontAwesomeIcons.download, color: Colors.red),
              label: Text(
                (langUserPhone == "fr")
                    ? "Mettre à jour maintenant"
                    : "Update Now",
                style: GoogleFonts.poppins(
                    fontWeight: FontWeight.bold, color: Colors.red),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.white,
                padding: EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAvailableContactsCard({
    required BuildContext context,
    required int contactCount,
    required bool isLoading,
    required VoidCallback onRefresh,
    required VoidCallback onSaveAll,
    required VoidCallback onGoToContacts,
  }) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [primaryColor, primaryColor.withOpacity(0.2)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(15),
        boxShadow: [
          BoxShadow(
            color: primaryColor.withOpacity(0.3),
            blurRadius: 15,
            offset: Offset(0, 5),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                (langUserPhone == "fr")
                    ? "Contacts Disponibles"
                    : "Available Contacts",
                style: GoogleFonts.poppins(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              IconButton(
                onPressed: isLoading ? null : onRefresh,
                icon: FaIcon(FontAwesomeIcons.arrowsRotate,
                    color: Colors.white.withOpacity(0.8)),
                tooltip: (langUserPhone == "fr") ? "Actualiser" : "Refresh",
              ),
            ],
          ),

          Text(
            contactCount.toString(),
            style: GoogleFonts.poppins(
                color: Colors.white,
                fontSize: 64,
                fontWeight: FontWeight.bold,
                shadows: [
                  Shadow(blurRadius: 10, color: Colors.black.withOpacity(0.2))
                ]),
          ),
          Text(
            (langUserPhone == "fr")
                ? "nouveau(x) contact(s) à enregistrer"
                : "new contact(s) to save",
            style: GoogleFonts.poppins(
              color: Colors.white.withOpacity(0.9),
              fontSize: 14,
              fontWeight: FontWeight.w500,
            ),
          ),
          SizedBox(height: 10),

          // --- BOUTONS D'ACTION ---
          Row(
            children: [
              // --- BOUTON PRINCIPAL "ENREGISTRER TOUS" ---
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: isLoading ? null : onSaveAll,
                  icon: isLoading
                      ? Container(
                          // Spinner de chargement
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.5,
                            color: primaryColor,
                          ),
                        )
                      : FaIcon(FontAwesomeIcons.download, color: primaryColor),
                  label: Text(
                    isLoading
                        ? ((langUserPhone == "fr") ? "Patientez..." : "Wait...")
                        : ((langUserPhone == "fr")
                            ? "Enregistrer Tous"
                            : "Save All"),
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.bold,
                      color: isLoading ? Colors.grey[600] : primaryColor,
                    ),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.white,
                    padding: EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ),
              SizedBox(width: 12),

              // --- BOUTON SECONDAIRE "MES CONTACTS" ---
              Expanded(
                child: OutlinedButton(
                  onPressed: onGoToContacts,
                  style: OutlinedButton.styleFrom(
                    side: BorderSide(color: Colors.white.withOpacity(0.6)),
                    foregroundColor: Colors.white,
                    padding: EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                  child: Text(
                    (langUserPhone == "fr") ? "Mes Contacts" : "My Contacts",
                    style: GoogleFonts.poppins(fontWeight: FontWeight.w600),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildActionFooter({
    required BuildContext context,
    required String impressionCount,
    required String viewCount,
    required VoidCallback onShare,
  }) {
    return Container(
      // Ajout d'une bordure supérieure pour séparer visuellement le footer du contenu

      padding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          // --- PARTIE STATISTIQUES (à gauche) ---
          Row(
            children: [
              _buildStatItem(
                icon: FontAwesomeIcons.eye,
                value: impressionCount.toString(),
                label: (langUserPhone == "fr") ? "Impressions" : "Impressions",
              ),
              SizedBox(width: 15),
              _buildStatItem(
                icon: FontAwesomeIcons.handPointer,
                value: viewCount.toString(),
                label: (langUserPhone == "fr") ? "Vues" : "Views",
              ),
            ],
          ),

          // --- PARTIE ACTION "PARTAGER" (à droite) ---
          ElevatedButton.icon(
            onPressed: onShare,
            icon: FaIcon(FontAwesomeIcons.shareNodes, size: 18),
            label: Text(
              (langUserPhone == "fr") ? "Partager" : "Share",
              style: GoogleFonts.poppins(fontWeight: FontWeight.w600),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: primaryColor.withOpacity(0.1),
              foregroundColor: primaryColor,
              elevation: 0, // Pas d'ombre pour un look plat et moderne
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(10),
              ),
              padding: EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            ),
          ),
        ],
      ),
    );
  }

// Helper pour afficher un item de statistique (icône + valeur)
  Widget _buildStatItem(
      {required IconData icon, required String value, required String label}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        FaIcon(icon, color: Colors.grey[600], size: 20),
        SizedBox(width: 8),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              value,
              style: GoogleFonts.poppins(
                fontWeight: FontWeight.bold,
                fontSize: 18,
              ),
            ),
            Text(
              label,
              style: GoogleFonts.poppins(
                color: Colors.grey[600],
                fontSize: 9,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class AdvertisementDetailPage extends StatelessWidget {
  final Advertisement advertisement;

  AdvertisementDetailPage({required this.advertisement});

  Future<void> _launchURL(String url) async {
    final Uri uri = Uri.parse(url);
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      print('Could not launch $url');
    }
  }

  void openWhatsAppChat() async {
    await launchUrl(
      Uri.parse('https://wa.me/${advertisement.whatsappNumber}'),
      mode: LaunchMode.externalApplication,
    );
  }

  void _showRewardInfo(BuildContext context) {
    showModalBottomSheet(
      context: context,
      shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(25))),
      builder: (context) => Container(
        padding: EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            FaIcon(FontAwesomeIcons.star, color: primaryColor, size: 40),
            SizedBox(height: 10),
            Text(
                (langUserPhone == "fr")
                    ? "Promotion Éligible"
                    : "Eligible Promotion",
                style: GoogleFonts.poppins(
                    fontWeight: FontWeight.bold, fontSize: 18)),
            SizedBox(height: 10),
            Text((langUserPhone == "fr")
                ? "Partagez cette promotion pour gagner des récompenses !"
                : "Share this promotion to earn rewards!"),
            SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final Map<String, dynamic> infoMap = (advertisement.annotherInfo != "")
        ? jsonDecode(advertisement.annotherInfo)
        : {};

    return Scaffold(
      appBar: AppBar(
        backgroundColor: primaryColor,
        foregroundColor: Colors.white,
        title: Text(
          (langUserPhone == "fr")
              ? 'Détails de la promotion'
              : 'Promotion Details',
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
      ),
      body: SingleChildScrollView(
        physics: BouncingScrollPhysics(),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // --- AFFICHE DE LA PROMOTION (AFFICHAGE COMPLET) ---
            Stack(
              children: [
                CachedNetworkImage(
                  imageUrl: advertisement.image,
                  placeholder: (context, url) => AspectRatio(
                    aspectRatio:
                        16 / 9, // Un ratio commun pour les placeholders
                    child: Container(color: Colors.grey[200]),
                  ),
                  errorWidget: (context, url, error) => AspectRatio(
                    aspectRatio: 16 / 9,
                    child: Container(
                        color: Colors.grey[200],
                        child: FaIcon(FontAwesomeIcons.circleExclamation)),
                  ),
                  // `fit: BoxFit.contain` pourrait aussi être une option si vous voulez des bandes noires
                  // mais `fit: BoxFit.cover` avec `width: double.infinity` est souvent le meilleur compromis.
                  width: double.infinity,
                  fit: BoxFit
                      .fitWidth, // Affiche l'image en pleine largeur, hauteur ajustée
                ),
                if (advertisement.inProgrammeRecompense)
                  Positioned(
                    top: 10,
                    right: 10,
                    child: AnimatedRewardBadge(
                        onTap: () => _showRewardInfo(context)),
                  ),
              ],
            ),

            // --- CONTENU TEXTUEL ---
            Padding(
              padding: const EdgeInsets.all(15.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // --- TITRE ---

                  SizedBox(height: 15),

                  // --- BARRE DE STATS ET CONTACT ---
                  _buildContactBar(context),
                  SizedBox(height: 25),

                  // --- DESCRIPTION ---
                  _buildSectionTitle(
                      icon: FontAwesomeIcons.solidFileLines,
                      title: (langUserPhone == "fr")
                          ? "Description"
                          : "Description"),
                  SelectableLinkify(
                    onOpen: (link) => _launchURL(link.url),
                    text: advertisement.description,
                    style: GoogleFonts.poppins(fontSize: 14, height: 1.6),
                    // --- STYLE DES LIENS SANS SOULIGNEMENT ---
                    linkStyle: GoogleFonts.poppins(
                      color: Colors.blue,
                      fontWeight: FontWeight.w500,
                      decoration: TextDecoration.none, // Enlève le soulignement
                    ),
                  ),
                  SizedBox(height: 30),

                  // --- INFORMATIONS SUPPLÉMENTAIRES ---
                  if (infoMap.isNotEmpty) ...[
                    _buildSectionTitle(
                        icon: FontAwesomeIcons.circleInfo,
                        title: (langUserPhone == "fr")
                            ? "Informations supplémentaires"
                            : "Additional Information"),
                    ...infoMap.entries.map((entry) {
                      final key =
                          StringExtension(entry.key.replaceAll('_', ' '))
                              .capitalize();
                      final value = entry.value;
                      return _buildInfoRow(key, value);
                    }).toList(),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // --- WIDGETS HELPERS ---

  Widget _buildContactBar(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 0, vertical: 0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // Stats
          Row(
            children: [
              FaIcon(FontAwesomeIcons.eye, color: Colors.grey[600], size: 20),
              SizedBox(width: 4),
              Text(advertisement.nombreImpression.toString(),
                  style: GoogleFonts.poppins(
                      fontSize: 18, fontWeight: FontWeight.w600)),
              SizedBox(width: 12),
              FaIcon(FontAwesomeIcons.handPointer,
                  color: Colors.grey[600], size: 20),
              SizedBox(width: 4),
              Text(advertisement.nombreDeVues.toString(),
                  style: GoogleFonts.poppins(
                      fontSize: 18, fontWeight: FontWeight.w600)),
            ],
          ),
          // Bouton de contact
          ElevatedButton.icon(
            onPressed: openWhatsAppChat,
            icon: FaIcon(FontAwesomeIcons.whatsapp, size: 18),
            label: Text(
              (langUserPhone == "fr") ? "Contacter" : "Contact",
              style: GoogleFonts.poppins(fontWeight: FontWeight.bold),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: Color.fromRGBO(37, 211, 102, 0.5),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10)),
              padding: EdgeInsets.symmetric(horizontal: 18, vertical: 8),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle({required IconData icon, required String title}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10.0),
      child: Row(
        children: [
          FaIcon(icon, color: primaryColor, size: 22),
          SizedBox(width: 10),
          Text(title,
              style: GoogleFonts.poppins(
                  fontSize: 18, fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }

  Widget _buildInfoRow(String key, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            key,
            style: GoogleFonts.poppins(
                fontSize: 14,
                color: Colors.grey[600],
                fontWeight: FontWeight.w500),
          ),
          SizedBox(height: 4),
          SelectableLinkify(
            onOpen: (link) => _launchURL(link.url),
            text: value,
            style:
                GoogleFonts.poppins(fontSize: 16, fontWeight: FontWeight.w500),
            linkStyle: GoogleFonts.poppins(
                color: Colors.blue, decoration: TextDecoration.none),
          ),
          Divider(height: 12),
        ],
      ),
    );
  }
}

// ════════════════════════════════════════════════════
// STORY STRIP + VIEWER
// ════════════════════════════════════════════════════

class _StoryStrip extends StatelessWidget {
  final List<StoryModel> stories;
  final String routeStoryImage;
  const _StoryStrip({required this.stories, required this.routeStoryImage});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 185,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 10),
        itemCount: stories.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (context, i) {
          final story = stories[i];
          final imageUrl = story.images.isNotEmpty
              ? '$routeStoryImage${story.images[0]}'
              : null;
          return GestureDetector(
            onTap: () => _openViewer(context, i),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: Stack(
                children: [
                  // Image de fond
                  Container(
                    width: 108,
                    height: 180,
                    color: Colors.black87,
                    child: imageUrl != null
                        ? CachedNetworkImage(
                            imageUrl: imageUrl,
                            fit: BoxFit.cover,
                            width: 108,
                            height: 180,
                            placeholder: (_, __) =>
                                Container(color: Colors.grey[900]),
                            errorWidget: (_, __, ___) => const Icon(
                                Icons.broken_image,
                                color: Colors.white54),
                          )
                        : const Center(
                            child: Icon(Icons.play_circle_fill,
                                color: Colors.white70, size: 36)),
                  ),
                  // Dégradé bas
                  Positioned(
                    bottom: 0,
                    left: 0,
                    right: 0,
                    child: Container(
                      height: 56,
                      decoration: const BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.bottomCenter,
                          end: Alignment.topCenter,
                          colors: [Colors.black87, Colors.transparent],
                        ),
                      ),
                    ),
                  ),
                  // Nom de l'utilisateur
                  Positioned(
                    bottom: 6,
                    left: 0,
                    right: 0,
                    child: Text(
                      story.user.length > 12
                          ? story.user.substring(0, 12)
                          : story.user,
                      textAlign: TextAlign.center,
                      style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  // Bordure bleue
                  Positioned.fill(
                    child: Container(
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: primaryColor, width: 2),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  void _openViewer(BuildContext context, int index) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => _StoryViewer(
          stories: stories,
          initialIndex: index,
          routeStoryImage: routeStoryImage,
        ),
      ),
    );
  }
}

class _StoryViewer extends StatefulWidget {
  final List<StoryModel> stories;
  final int initialIndex;
  final String routeStoryImage;
  const _StoryViewer(
      {required this.stories,
      required this.initialIndex,
      required this.routeStoryImage});

  @override
  State<_StoryViewer> createState() => _StoryViewerState();
}

class _StoryViewerState extends State<_StoryViewer>
    with SingleTickerProviderStateMixin {
  late int _storyIdx;
  int _mediaIdx = 0;
  Timer? _timer;
  late AnimationController _progressController;
  static const _duration = Duration(seconds: 60);
  bool _expanded = false;

  // Swipe
  double _dragStartX = 0;

  @override
  void initState() {
    super.initState();
    _storyIdx = widget.initialIndex;
    _progressController = AnimationController(vsync: this, duration: _duration);
    _startTimer();
  }

  @override
  void dispose() {
    _timer?.cancel();
    _progressController.dispose();
    super.dispose();
  }

  List<String> get _currentMedia {
    final s = widget.stories[_storyIdx];
    return [
      ...s.images.map((img) => '${widget.routeStoryImage}$img'),
      ...s.videos,
    ];
  }

  void _startTimer() {
    _timer?.cancel();
    _expanded = false;
    _progressController.reset();
    _progressController.forward();
    _timer = Timer(_duration, _nextMedia);
  }

  void _pauseTimer() {
    _timer?.cancel();
    _progressController.stop();
  }

  void _nextMedia() {
    final media = _currentMedia;
    if (_mediaIdx < media.length - 1) {
      setState(() => _mediaIdx++);
      _startTimer();
    } else if (_storyIdx < widget.stories.length - 1) {
      setState(() {
        _storyIdx++;
        _mediaIdx = 0;
      });
      _startTimer();
    } else {
      Navigator.pop(context);
    }
  }

  void _prevMedia() {
    if (_mediaIdx > 0) {
      setState(() => _mediaIdx--);
      _startTimer();
    } else if (_storyIdx > 0) {
      setState(() {
        _storyIdx--;
        _mediaIdx = 0;
      });
      _startTimer();
    }
  }

  @override
  Widget build(BuildContext context) {
    final story = widget.stories[_storyIdx];
    final media = _currentMedia;
    final current = media.isNotEmpty ? media[_mediaIdx] : null;
    final isImage = current != null &&
        (current.contains('/story/') ||
            current.endsWith('.jpg') ||
            current.endsWith('.jpeg') ||
            current.endsWith('.png') ||
            current.endsWith('.webp'));

    return Scaffold(
      backgroundColor: Colors.black,
      body: GestureDetector(
        onHorizontalDragStart: (d) => _dragStartX = d.globalPosition.dx,
        onHorizontalDragEnd: (d) {
          final dx = d.globalPosition.dx - _dragStartX;
          if (dx.abs() > 40) {
            if (dx < 0)
              _nextMedia();
            else
              _prevMedia();
          }
        },
        child: Stack(
          fit: StackFit.expand,
          children: [
            // ── Média principal ──
            if (current != null)
              Center(
                child: isImage
                    ? CachedNetworkImage(
                        imageUrl: current,
                        fit: BoxFit.contain,
                        placeholder: (_, __) => const CircularProgressIndicator(
                            color: Colors.white),
                        errorWidget: (_, __, ___) => const Icon(
                            Icons.broken_image,
                            color: Colors.white54,
                            size: 60),
                      )
                    : const Icon(Icons.play_circle_fill,
                        color: Colors.white70, size: 80),
              ),

            // ── Barres de progression ──
            Positioned(
              top: MediaQuery.of(context).padding.top + 6,
              left: 8,
              right: 8,
              child: Row(
                children: List.generate(media.length, (i) {
                  return Expanded(
                    child: Container(
                      height: 3,
                      margin: const EdgeInsets.symmetric(horizontal: 2),
                      decoration: BoxDecoration(
                        color: Colors.white30,
                        borderRadius: BorderRadius.circular(2),
                      ),
                      child: i < _mediaIdx
                          ? Container(
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(2),
                              ),
                            )
                          : i == _mediaIdx
                              ? AnimatedBuilder(
                                  animation: _progressController,
                                  builder: (_, __) => FractionallySizedBox(
                                    alignment: Alignment.centerLeft,
                                    widthFactor: _progressController.value,
                                    child: Container(
                                      decoration: BoxDecoration(
                                        color: Colors.white,
                                        borderRadius: BorderRadius.circular(2),
                                      ),
                                    ),
                                  ),
                                )
                              : const SizedBox(),
                    ),
                  );
                }),
              ),
            ),

            // ── Header (nom + fermer) ──
            Positioned(
              top: MediaQuery.of(context).padding.top + 16,
              left: 12,
              right: 12,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    story.user,
                    style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontWeight: FontWeight.w600,
                        fontSize: 14),
                  ),
                  GestureDetector(
                    onTap: () => Navigator.pop(context),
                    child:
                        const Icon(Icons.close, color: Colors.white, size: 26),
                  ),
                ],
              ),
            ),

            // ── Boutons prev/next ──
            Positioned(
              left: 4,
              top: 0,
              bottom: 0,
              child: Center(
                child: IconButton(
                  icon: const Icon(Icons.chevron_left,
                      color: Colors.white70, size: 36),
                  onPressed: _prevMedia,
                ),
              ),
            ),
            Positioned(
              right: 4,
              top: 0,
              bottom: 0,
              child: Center(
                child: IconButton(
                  icon: const Icon(Icons.chevron_right,
                      color: Colors.white70, size: 36),
                  onPressed: _nextMedia,
                ),
              ),
            ),

            // ── Description + URL (bas) ──
            if (story.description.isNotEmpty || story.url.isNotEmpty)
              Positioned(
                bottom: 0,
                left: 0,
                right: 0,
                child: Container(
                  padding: const EdgeInsets.fromLTRB(14, 12, 14, 28),
                  decoration: const BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.bottomCenter,
                      end: Alignment.topCenter,
                      colors: [Colors.black87, Colors.transparent],
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Bouton lien
                      if (story.url.isNotEmpty)
                        GestureDetector(
                          onTap: () async {
                            final uri = Uri.tryParse(story.url);
                            if (uri != null) {
                              await launchUrl(uri,
                                  mode: LaunchMode.externalApplication);
                            }
                          },
                          child: Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            padding: const EdgeInsets.symmetric(
                                horizontal: 16, vertical: 8),
                            decoration: BoxDecoration(
                              color: const Color(0xFF25D366),
                              borderRadius: BorderRadius.circular(24),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.link,
                                    color: Colors.white, size: 16),
                                const SizedBox(width: 6),
                                Text(
                                  (langUserPhone == "fr")
                                      ? 'Voir le lien'
                                      : 'View link',
                                  style: GoogleFonts.poppins(
                                      color: Colors.white,
                                      fontSize: 13,
                                      fontWeight: FontWeight.w600),
                                ),
                              ],
                            ),
                          ),
                        ),
                      // Description
                      if (story.description.isNotEmpty) ...[
                        Text(
                          story.description,
                          maxLines: _expanded ? null : 2,
                          overflow: _expanded
                              ? TextOverflow.visible
                              : TextOverflow.ellipsis,
                          style: GoogleFonts.poppins(
                              color: Colors.white, fontSize: 13, height: 1.5),
                        ),
                        if (!_expanded &&
                            (story.description.split('\n').length > 2 ||
                                story.description.length > 120))
                          GestureDetector(
                            onTap: () {
                              _pauseTimer();
                              setState(() => _expanded = true);
                            },
                            child: Text(
                              (langUserPhone == "fr")
                                  ? 'Lire la suite'
                                  : 'Read more',
                              style: GoogleFonts.poppins(
                                  color: Colors.lightBlueAccent,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600),
                            ),
                          ),
                        if (_expanded)
                          GestureDetector(
                            onTap: () {
                              setState(() => _expanded = false);
                            },
                            child: Text(
                              (langUserPhone == "fr") ? 'Réduire' : 'Show less',
                              style: GoogleFonts.poppins(
                                  color: Colors.lightBlueAccent,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600),
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
    );
  }
}

// ─── Bottom sheet avertissement ───────────────────────────────────────────────

class _WarningBottomSheet extends StatefulWidget {
  final int totalSeconds;
  const _WarningBottomSheet({required this.totalSeconds});

  @override
  State<_WarningBottomSheet> createState() => _WarningBottomSheetState();
}

class _WarningBottomSheetState extends State<_WarningBottomSheet>
    with SingleTickerProviderStateMixin {
  late int _remaining;
  Timer? _timer;
  late AnimationController _progressCtrl;

  @override
  void initState() {
    super.initState();
    _remaining = widget.totalSeconds;
    _progressCtrl = AnimationController(
      vsync: this,
      duration: Duration(seconds: widget.totalSeconds),
      value: 1.0,
    );

    // Attendre que le bottom sheet ait fini de monter avant de démarrer
    Future.delayed(const Duration(milliseconds: 700), () {
      if (!mounted) return;
      _progressCtrl.animateTo(0.0, curve: Curves.linear);
      _timer = Timer.periodic(const Duration(seconds: 1), (t) {
        if (!mounted) {
          t.cancel();
          return;
        }
        if (_remaining <= 1) {
          t.cancel();
          setState(() => _remaining = 0);
        } else {
          setState(() => _remaining--);
        }
      });
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _progressCtrl.dispose();
    super.dispose();
  }

  Widget _buildItem(IconData icon, String text) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 38,
          height: 38,
          decoration: BoxDecoration(
            color: Colors.red[50],
            borderRadius: BorderRadius.circular(10),
          ),
          child: Center(
            child: FaIcon(icon, size: 16, color: Colors.red[700]),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.only(top: 7),
            child: Text(
              text,
              style: GoogleFonts.poppins(
                fontSize: 13,
                color: Colors.grey[700],
                height: 1.5,
              ),
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final bool canClose = _remaining == 0;
    final bool isFr = langUserPhone == "fr";

    return WillPopScope(
      onWillPop: () async => false,
      child: Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        padding: EdgeInsets.fromLTRB(
          24,
          12,
          24,
          MediaQuery.of(context).padding.bottom + 28,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Barre de poignée
            Container(
              width: 44,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey[300],
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: 24),

            // Icône centrale
            Container(
              width: 76,
              height: 76,
              decoration: BoxDecoration(
                color: Colors.red[50],
                shape: BoxShape.circle,
              ),
              child: Center(
                child: FaIcon(
                  FontAwesomeIcons.triangleExclamation,
                  color: Colors.red[700],
                  size: 34,
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Titre
            Text(
              isFr ? "Avertissement important" : "Important Warning",
              style: GoogleFonts.poppins(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Colors.red[700],
              ),
            ),
            const SizedBox(height: 6),
            Text(
              isFr
                  ? "Lis attentivement avant de continuer"
                  : "Read carefully before continuing",
              style: GoogleFonts.poppins(
                fontSize: 13,
                color: Colors.grey[500],
              ),
            ),

            const SizedBox(height: 24),

            // Points d'avertissement
            _buildItem(
              FontAwesomeIcons.userShield,
              isFr
                  ? "Dressur ne peut garantir la fiabilité ou la moralité des utilisateurs."
                  : "Dressur cannot guarantee the reliability or integrity of users.",
            ),
            const SizedBox(height: 14),
            _buildItem(
              FontAwesomeIcons.moneyBillWave,
              isFr
                  ? "Ne jamais envoyer d'argent pour un service sans être certain de ce que vous recevrez en retour."
                  : "Never send money for a service without being certain of what you will receive.",
            ),
            const SizedBox(height: 14),
            _buildItem(
              FontAwesomeIcons.scaleBalanced,
              isFr
                  ? "Dressur décline toute responsabilité en cas d'arnaque ou de perte financière causée par un utilisateur."
                  : "Dressur disclaims all liability for scams or financial losses caused by another user.",
            ),

            const SizedBox(height: 24),

            // Barre de progression
            AnimatedBuilder(
              animation: _progressCtrl,
              builder: (_, __) => Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(6),
                    child: LinearProgressIndicator(
                      value: _progressCtrl.value,
                      backgroundColor: Colors.grey[200],
                      valueColor: AlwaysStoppedAnimation<Color>(
                        canClose ? Colors.green : Colors.red,
                      ),
                      minHeight: 6,
                    ),
                  ),
                  if (!canClose) ...[
                    const SizedBox(height: 6),
                    Text(
                      isFr
                          ? "Disponible dans $_remaining s"
                          : "Available in $_remaining s",
                      style: GoogleFonts.poppins(
                        fontSize: 11,
                        color: Colors.grey[400],
                      ),
                    ),
                  ],
                ],
              ),
            ),

            const SizedBox(height: 16),

            // Bouton
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: canClose ? () => Navigator.of(context).pop() : null,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.red[700],
                  disabledBackgroundColor: Colors.red[100],
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 15),
                  elevation: 0,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
                child: Text(
                  canClose
                      ? (isFr ? "J'ai compris ✓" : "I understand ✓")
                      : (isFr
                          ? "J'ai compris ($_remaining)"
                          : "I understand ($_remaining)"),
                  style: GoogleFonts.poppins(
                    fontWeight: FontWeight.w600,
                    fontSize: 15,
                    color: canClose ? Colors.white : Colors.red[300],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

extension StringExtension on String {
  String capitalize() {
    if (this.isEmpty) return "";
    return "${this[0].toUpperCase()}${this.substring(1)}";
  }
}
