import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/5_autre/support_assistance.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;

class Boost {
  final String id;
  final String typeBoost;
  final String modeBoostFormule;
  final String statutFormule;
  final String nomFormule;
  final String prixFormule;
  final String dateDebutFormule;
  final String? dateExp;
  final int nbContactsObtenus;
  final int? nbContactsMax;

  Boost({
    required this.id,
    required this.typeBoost,
    required this.modeBoostFormule,
    required this.statutFormule,
    required this.nomFormule,
    required this.prixFormule,
    required this.dateDebutFormule,
    this.dateExp,
    required this.nbContactsObtenus,
    this.nbContactsMax,
  });
}

class ListeBoostContactPage extends StatefulWidget {
  @override
  State<ListeBoostContactPage> createState() => _ListeBoostContactPageState();
}

class _ListeBoostContactPageState extends State<ListeBoostContactPage> {
  bool _loading = false;
  List<Boost> _boosts = [];

  Future<void> fetchBoosts() async {
    setState(() {
      _loading = true;
    });
    try {
      final url = Uri.parse('$generalRouteForApi/listBoost/$uidUser/fr');
      final response = await http.get(url);

      if (response.statusCode == 200) {
        final jsonData = convert.jsonDecode(response.body) as List<dynamic>;
        final boosts = jsonData.map((data) {
          return Boost(
            id: data['id'],
            typeBoost: data['typeBoost'] ?? 'date',
            modeBoostFormule: data['modeBoostFormule'],
            statutFormule: data['statutFormule'],
            nomFormule: data['nomFormule'],
            prixFormule: data['prixFormule'],
            dateDebutFormule: data['dateDebutFormule'],
            dateExp: data['dateExp'],
            nbContactsObtenus: data['nbContactsObtenus'] ?? 0,
            nbContactsMax: data['nbContactsMax'],
          );
        }).toList();

        setState(() {
          _boosts = boosts;
        });
      } else {
        _showErrorDialog(
            (langUserPhone == "fr")
                ? 'Impossible de récupérer les boosts. Code d\'erreur : ${response.statusCode}'
                : 'Failed to retrieve boosts. Error code: ${response.statusCode}');
      }
    } catch (e) {
      _showErrorDialog(
          (langUserPhone == "fr")
              ? 'Une erreur est survenue lors du chargement des boosts. Veuillez réessayer.'
              : 'An error occurred while fetching boosts. Please try again.');
    } finally {
      setState(() {
        _loading = false;
      });
    }
  }

  void _showErrorDialog(String message) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: Text((langUserPhone == "fr") ? 'Erreur' : 'Error'),
          content: Text(message),
          actions: <Widget>[
            TextButton(
              child: Text('OK'),
              onPressed: () {
                Navigator.of(context).pop();
              },
            ),
          ],
        );
      },
    );
  }

  @override
  void initState() {
    super.initState();
    fetchBoosts();
  }

  Widget _buildBoostCard(Boost boost) {
    final bool isQuota = boost.typeBoost == 'quota';
    final bool isFr = langUserPhone == "fr";
    final int obtenus = boost.nbContactsObtenus;
    final int max = boost.nbContactsMax ?? 1;
    final double progress = isQuota ? (obtenus / max).clamp(0.0, 1.0) : 0.0;
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      child: Padding(
        padding: const EdgeInsets.all(15.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // --- Badges : mode | type | statut ---
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    _buildStatusLabel(boost.modeBoostFormule, isMode: true),
                    const SizedBox(width: 6),
                    _buildTypeLabel(isQuota, isFr),
                  ],
                ),
                _buildStatusLabel(boost.statutFormule),
              ],
            ),
            const SizedBox(height: 8),
            // --- Nom + Prix ---
            Text(
              "${boost.nomFormule} (${boost.prixFormule})",
              style: GoogleFonts.poppins(
                fontWeight: FontWeight.w600,
                fontSize: 16,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 8),
            // --- Infos selon le type ---
            if (isQuota) ...[
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    isFr ? "Contacts reçus" : "Contacts received",
                    style: GoogleFonts.poppins(
                        fontSize: 12, color: Colors.grey[600]),
                  ),
                  Text(
                    "$obtenus / $max",
                    style: GoogleFonts.poppins(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: primaryColor,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              ClipRRect(
                borderRadius: BorderRadius.circular(4),
                child: LinearProgressIndicator(
                  value: progress,
                  minHeight: 8,
                  backgroundColor: Colors.grey[200],
                  color: progress >= 1.0 ? Colors.green : primaryColor,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                boost.dateDebutFormule,
                style:
                    GoogleFonts.poppins(fontSize: 11, color: Colors.grey[500]),
              ),
            ] else ...[
              Text(
                boost.dateDebutFormule,
                style: GoogleFonts.poppins(fontSize: 12),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildTypeLabel(bool isQuota, bool isFr) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 1),
      decoration: BoxDecoration(
        color: isQuota ? Colors.deepPurple : Colors.blueGrey,
        borderRadius: BorderRadius.circular(10.0),
      ),
      child: Text(
        isQuota
            ? (isFr ? "Par contacts" : "By contacts")
            : (isFr ? "Par durée" : "By duration"),
        style: GoogleFonts.poppins(
          fontSize: 11,
          color: Colors.white,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }

  Widget _buildStatusLabel(String status, {bool isMode = false}) {
    Color backgroundColor;
    if (isMode) {
      backgroundColor =
          (status == "Gratuit" || status == "Free") ? Colors.green : Colors.red;
    } else {
      backgroundColor = (status == "Completed" || status == "Terminé")
          ? Colors.green
          : (status == "In progress" || status == "En cours")
              ? Colors.orange
              : Colors.red;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 1),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(10.0),
      ),
      child: Text(
        status,
        style: GoogleFonts.poppins(
          fontSize: 14,
          color: Colors.white,
          fontWeight: FontWeight.w800,
        ),
        textAlign: TextAlign.end,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          (langUserPhone == "fr")
              ? "Liste Boost Contact"
              : "Boost List Contact",
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
                  fetchBoosts();
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
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: primaryColor))
          : _boosts.isEmpty
              ? Center(
                  child: Text(
                    (langUserPhone == "fr")
                        ? "Aucun boost contact trouvé."
                        : "No contact boost found.",
                    style: GoogleFonts.poppins(fontSize: 16),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: fetchBoosts,
                  color: primaryColor,
                  child: ListView.builder(
                    physics: const BouncingScrollPhysics(
                        parent: AlwaysScrollableScrollPhysics()),
                    cacheExtent: 500,
                    padding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    itemCount: _boosts.length,
                    itemBuilder: (BuildContext context, int index) {
                      return RepaintBoundary(
                          child: _buildBoostCard(_boosts[index]));
                    },
                  ),
                ),
    );
  }
}
