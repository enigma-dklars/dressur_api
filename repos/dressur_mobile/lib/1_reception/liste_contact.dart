// ignore_for_file: unnecessary_null_comparison, prefer_const_constructors

import 'dart:convert';
import 'package:dressur/5_autre/autre_profil.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/1_reception/synchronisation_avance.dart';
import 'package:dressur/5_autre/support_assistance.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;

class ContactDS {
  final String id;
  final String pseudo;
  final String nom;
  final String mail;
  final String pays;
  final String tel;
  final String apropos;
  final String tiktok;
  final String instagram;
  final String facebook;
  final String youtube;

  const ContactDS({
    required this.id,
    required this.pseudo,
    required this.nom,
    required this.mail,
    required this.pays,
    required this.tel,
    required this.apropos,
    required this.tiktok,
    required this.instagram,
    required this.facebook,
    required this.youtube,
  });
}

// ── Couleurs d'avatar ────────────────────────────────────────────────────────
const List<Color> _kAvatarColors = [
  Color(0xFF1565C0),
  Color(0xFF2E7D32),
  Color(0xFF6A1B9A),
  Color(0xFFC62828),
  Color(0xFF00838F),
  Color(0xFFE65100),
  Color(0xFF4527A0),
  Color(0xFF00695C),
  Color(0xFF558B2F),
  Color(0xFF283593),
  Color(0xFF880E4F),
  Color(0xFF37474F),
];

Color _avatarColor(ContactDS c) {
  final String key = (c.nom.isNotEmpty ? c.nom : c.pseudo).toLowerCase();
  int hash = 0;
  for (final int code in key.codeUnits) {
    hash = (hash * 31 + code) & 0x7FFFFFFF;
  }
  return _kAvatarColors[hash % _kAvatarColors.length];
}

String _initials(ContactDS c) {
  final String name = (c.nom.isNotEmpty ? c.nom : c.pseudo).trim();
  final List<String> parts =
      name.split(" ").where((p) => p.isNotEmpty).toList();
  if (parts.isEmpty) return "?";
  if (parts.length == 1) return parts[0][0].toUpperCase();
  return (parts[0][0] + parts[1][0]).toUpperCase();
}

String _displayName(ContactDS c) {
  final bool hasNom = c.nom.isNotEmpty;
  final bool hasPseudo = c.pseudo.isNotEmpty;
  if (hasNom && hasPseudo) return "${c.nom} - ${c.pseudo}";
  if (hasNom) return c.nom;
  if (hasPseudo) return c.pseudo;
  return "";
}

// ── Carte contact — StatelessWidget pour que Flutter puisse la recycler ──────

class _ContactCard extends StatelessWidget {
  const _ContactCard({required this.contact});

  final ContactDS contact;

  @override
  Widget build(BuildContext context) {
    final Color avatarColor = _avatarColor(contact);
    final String initials = _initials(contact);
    final String name = _displayName(contact);

    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 8),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(13)),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10.0, vertical: 8.0),
        child: Column(
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 22,
                  backgroundColor: avatarColor,
                  child: Text(
                    initials,
                    style: GoogleFonts.poppins(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    name,
                    style: GoogleFonts.poppins(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                IconButton(
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                  icon: FaIcon(FontAwesomeIcons.circleInfo,
                      color: Colors.grey[400], size: 18),
                  onPressed: () {
                    uidAutreUser = contact.id;
                    addUserOnAutreProfilPage = "non";
                    Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => AutreProfilPage()));
                  },
                ),
              ],
            ),
            Divider(height: 12, thickness: 0.5),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _ActionBtn(
                    icon: FontAwesomeIcons.phone,
                    onTap: () => launchPhoneCall(contact.tel)),
                _ActionBtn(
                    icon: FontAwesomeIcons.solidMessage,
                    onTap: () => launchSMS(contact.tel)),
                _ActionBtn(
                    icon: FontAwesomeIcons.solidEnvelope,
                    onTap: () => launchEmail(contact.mail)),
                _ActionBtn(
                    icon: FontAwesomeIcons.whatsapp,
                    onTap: () => launchWhatsApp(contact.tel)),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _ActionBtn extends StatelessWidget {
  const _ActionBtn({required this.icon, required this.onTap});

  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return IconButton(
      onPressed: onTap,
      icon: FaIcon(icon, color: primaryColor, size: 18),
      splashRadius: 20,
      padding: const EdgeInsets.symmetric(vertical: 4),
      constraints: const BoxConstraints(),
    );
  }
}

// ── Page principale ──────────────────────────────────────────────────────────

class ContactPage extends StatefulWidget {
  @override
  State<ContactPage> createState() => _ContactPageState();
}

class _ContactPageState extends State<ContactPage> {
  bool _isLoading = true;
  List<ContactDS> _contacts = [];
  List<ContactDS> _filteredContacts = [];
  final TextEditingController _searchController = TextEditingController();
  final ScrollController _scrollController = ScrollController();

  Future<void> fetchContactDSs() async {
    setState(() {
      _isLoading = true;
      nombreContacts = 0;
    });
    final url = Uri.parse('$generalRouteForApi/listContactDS/$uidUser/fr');

    final response = await http.get(url);

    if (response.statusCode == 200) {
      final jsonData = jsonDecode(response.body) as List<dynamic>;

      final contacts = jsonData.map((data) {
        return ContactDS(
          id: data['id'],
          pseudo: data['pseudo'],
          nom: data['nom'],
          mail: data['mail'],
          pays: data['pays'],
          tel: data['tel'],
          apropos: data['apropos'],
          tiktok: data['tiktok'],
          instagram: data['instagram'],
          facebook: data['facebook'],
          youtube: data['youtube'],
        );
      }).toList();

      if (mounted) {
        setState(() {
          _isLoading = false;
          _contacts = contacts;
          nombreContacts = contacts.length;
          _filteredContacts = List.from(_contacts);
        });
      }
    } else {
      if (mounted) {
        showDialog(
          context: context,
          builder: (BuildContext context) {
            return AlertDialog(
              title: Text((langUserPhone == "fr") ? 'Erreur' : 'Error'),
              content: (langUserPhone == "fr")
                  ? Text(
                      'Échec de récupération des contacts. Code d\'erreur: ${response.statusCode}')
                  : Text(
                      'Failed to retrieve contacts. Error code: ${response.statusCode}'),
              actions: <Widget>[
                TextButton(
                  child: const Text('OK'),
                  onPressed: () => Navigator.of(context).pop(),
                ),
              ],
            );
          },
        );
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  void initState() {
    super.initState();
    fetchContactDSs();
    _searchController.addListener(_filterContacts);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _filterContacts() {
    final String query = _searchController.text.toLowerCase();
    setState(() {
      _filteredContacts = _contacts.where((contact) {
        return contact.nom.toLowerCase().contains(query) ||
            contact.pseudo.toLowerCase().contains(query) ||
            contact.tel.toLowerCase().contains(query);
      }).toList();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          "Contacts ( $nombreContacts )",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
        leading: IconButton(
          onPressed: () => Navigator.pop(context),
          icon: const FaIcon(
            FontAwesomeIcons.chevronLeft,
            color: Colors.white,
          ),
        ),
        actions: [
          PopupMenuButton<int>(
            itemBuilder: (context) => [
              PopupMenuItem(
                value: 1,
                onTap: () {
                  if (!_isLoading) fetchContactDSs();
                },
                child: Text(
                  (langUserPhone == "fr") ? "Actualiser" : "Refresh",
                  style: GoogleFonts.poppins(
                      color: Colors.white, fontWeight: FontWeight.w600),
                ),
              ),
              PopupMenuItem(
                value: 3,
                child: Text(
                  (langUserPhone == "fr")
                      ? "Synchronisation avancée"
                      : "Advanced synchronization",
                  style: GoogleFonts.poppins(
                      color: Colors.white, fontWeight: FontWeight.w600),
                ),
              ),
              PopupMenuItem(
                value: 4,
                child: Text(
                  (langUserPhone == "fr") ? "Aide" : "Help",
                  style: GoogleFonts.poppins(
                      color: Colors.white, fontWeight: FontWeight.w600),
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
              if (value == 3) {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const SynchroAvance()),
                );
              }
              if (value == 4) {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => SupportPage()),
                );
              }
            },
          ),
        ],
      ),
      body: Column(
        children: [
          _buildSearchBar(),
          Expanded(
            child: _isLoading
                ? const Center(
                    child: CircularProgressIndicator(color: primaryColor))
                : _filteredContacts.isEmpty
                    ? _buildEmptyState()
                    : _buildContactList(),
          ),
        ],
      ),
    );
  }

  Widget _buildSearchBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(10, 10, 10, 6),
      child: TextField(
        controller: _searchController,
        style: GoogleFonts.poppins(),
        decoration: InputDecoration(
          hintText: (langUserPhone == "fr")
              ? "Rechercher par nom, pseudo..."
              : "Search by name, pseudo...",
          hintStyle: GoogleFonts.poppins(color: Colors.grey[500]),
          prefixIcon: Padding(
            padding: const EdgeInsets.only(left: 20, right: 13, top: 14),
            child: FaIcon(
              FontAwesomeIcons.magnifyingGlass,
              color: Colors.grey[500],
              size: 18,
            ),
          ),
          filled: true,
          fillColor: Theme.of(context).brightness == Brightness.dark
              ? Colors.grey[850]
              : Colors.white,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(15),
            borderSide: BorderSide.none,
          ),
          contentPadding: const EdgeInsets.symmetric(vertical: 12),
        ),
      ),
    );
  }

  Widget _buildContactList() {
    return RefreshIndicator(
      onRefresh: fetchContactDSs,
      color: primaryColor,
      child: ListView.builder(
        controller: _scrollController,
        // Pré-rendu des items hors écran pour un scroll sans accroc
        cacheExtent: 500,
        // Bouncing natif + RefreshIndicator toujours actif
        physics: const BouncingScrollPhysics(
          parent: AlwaysScrollableScrollPhysics(),
        ),
        // Ferme le clavier automatiquement au scroll
        keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        itemCount: _filteredContacts.length,
        itemBuilder: (context, index) {
          final ContactDS contact = _filteredContacts[index];
          // RepaintBoundary isole chaque carte : une carte qui change ne
          // force pas le re-rendu des autres.
          return RepaintBoundary(
            child: _ContactCard(contact: contact),
          );
        },
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          FaIcon(FontAwesomeIcons.magnifyingGlass,
              size: 60, color: Colors.grey[400]),
          const SizedBox(height: 15),
          Text(
            _searchController.text.isEmpty
                ? ((langUserPhone == "fr")
                    ? "Votre liste de contacts est vide"
                    : "Your contact list is empty")
                : ((langUserPhone == "fr")
                    ? "Aucun contact trouvé"
                    : "No contact found"),
            style: GoogleFonts.poppins(fontSize: 16, color: Colors.grey[600]),
          ),
        ],
      ),
    );
  }
}
