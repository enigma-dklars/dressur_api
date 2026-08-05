// ignore_for_file: use_build_context_synchronously

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/4_preference/choix_pays.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

const _accountsChannel = MethodChannel('com.dressur.ds/accounts');

class PreferencePage extends StatefulWidget {
  PreferencePage({Key? key}) : super(key: key);

  @override
  State<PreferencePage> createState() => _PreferencePageState();
}

class _PreferencePageState extends State<PreferencePage>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  var data;
  bool _addPageActuLocal = addPageActu;
  bool _updatingAddPageActu = false;

  String? _selectedAccountName;

  Future<List<Map<String, String?>>> _fetchAccountsList() async {
    final defaultEntry = {'name': null, 'type': null, 'label': langUserPhone == 'fr' ? 'Téléphone (local)' : 'Phone (local)'};
    try {
      final granted = await FlutterContacts.requestPermission();
      if (!granted) return [defaultEntry];

      // Interroge Android AccountManager directement via MethodChannel pour
      // lister TOUS les comptes du téléphone (Google, Exchange, etc.),
      // indépendamment des contacts synchronisés.
      final raw = await _accountsChannel.invokeMethod<List>('getDeviceAccounts');
      final seen = <String>{};
      final accounts = <Map<String, String?>>[defaultEntry];
      for (final item in raw ?? []) {
        final name = item['name'] as String? ?? '';
        final type = item['type'] as String? ?? '';
        final key = '${name}__${type}';
        if (name.isNotEmpty && !seen.contains(key)) {
          seen.add(key);
          final label = name.contains('@') ? name : '$name (${type.split('.').last})';
          accounts.add({'name': name, 'type': type, 'label': label});
        }
      }
      return accounts;
    } catch (e) {
      debugPrint('[Accounts] erreur: $e');
      return [defaultEntry];
    }
  }

  Future<void> _saveAccountPreference(String? name, String? type) async {
    final prefs = await SharedPreferences.getInstance();
    if (name == null) {
      await prefs.remove('selectedContactAccountName');
      await prefs.remove('selectedContactAccountType');
    } else {
      await prefs.setString('selectedContactAccountName', name);
      if (type != null) await prefs.setString('selectedContactAccountType', type);
    }
    setState(() {
      _selectedAccountName = name;
    });
    selectedContactAccountName = name;
    selectedContactAccountType = type;
  }

  @override
  void dispose() {
    super.dispose();
  }

  Future<void> _updateAddPageActu(bool value) async {
    setState(() {
      _updatingAddPageActu = true;
      _addPageActuLocal = value;
      addPageActu = value;
    });

    final valueParam = value ? '1' : '0';
    await http.get(Uri.parse(
        '$generalRouteForApi/updateAddPageActu/$uidUser/$valueParam'));

    setState(() {
      _updatingAddPageActu = false;
    });
  }

  @override
  void initState() {
    super.initState();
    _selectedAccountName = selectedContactAccountName;
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return Scaffold(
        appBar: AppBar(
          elevation: 0,
          backgroundColor: primaryColor,
          leading: IconButton(
            onPressed: () => Navigator.pop(context),
            icon: const FaIcon(FontAwesomeIcons.chevronLeft, color: Colors.white),
          ),
          title: Text(
            (langUserPhone == "fr") ? "Préférences" : "Preferences",
            style: GoogleFonts.poppins(
              color: Colors.white,
              fontWeight: FontWeight.w400,
              fontSize: 18,
            ),
          ),
        ),
        body: SingleChildScrollView(
          child: Container(
            child: Column(
              children: [
                const SizedBox(height: 10),
                _buildAddPageActuCard(context: context),
                const SizedBox(height: 10),
                _buildCountryPreferenceCard(
                  context: context,
                  selectedCountriesText: preferencePaysText.toString(),
                  onEdit: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (context) => ChoixDesPays()),
                    ).then((_) => setState(() {}));
                  },
                ),
                const SizedBox(height: 10),
                _buildContactStorageCard(context: context),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
    );
  }

  Widget _buildAddPageActuCard({required BuildContext context}) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
      child: Ink(
        decoration: BoxDecoration(
          color: isDark ? Color(0xFF1E1E1E) : Colors.white,
          borderRadius: BorderRadius.circular(15),
          border: Border.all(
              color: isDark ? Colors.grey[800]! : Colors.grey[200]!, width: 1),
          boxShadow: [
            BoxShadow(
              color: isDark
                  ? Colors.black.withOpacity(0.2)
                  : Colors.grey.withOpacity(0.1),
              blurRadius: 10,
              offset: Offset(0, 4),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  FaIcon(FontAwesomeIcons.addressCard,
                      color: primaryColor, size: 28),
                  SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      (langUserPhone == "fr")
                          ? "Contacts Dressur"
                          : "Dressur contacts",
                      style: GoogleFonts.poppins(
                        color: isDark ? Colors.white : Colors.black87,
                        fontWeight: FontWeight.w600,
                        fontSize: 18,
                      ),
                    ),
                  ),
                  _updatingAddPageActu
                      ? SizedBox(
                          width: 24,
                          height: 24,
                          child: CircularProgressIndicator(
                              strokeWidth: 2.5, color: primaryColor),
                        )
                      : Switch(
                          trackOutlineColor: MaterialStateColor.resolveWith(
                              (states) => primaryColor),
                          activeColor: Colors.green,
                          activeTrackColor: primaryColor,
                          inactiveThumbColor: Colors.white,
                          inactiveTrackColor: Colors.grey,
                          value: _addPageActuLocal,
                          onChanged: _updateAddPageActu,
                        ),
                ],
              ),
              SizedBox(height: 15),
              Container(
                padding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                decoration: BoxDecoration(
                  color: primaryColor.withOpacity(0.05),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  (langUserPhone == "fr")
                      ? "Affiche le nombre de contacts Dressur disponibles dans votre fil Actu, ainsi qu'un rappel pour booster vos contacts. Active également le bouton « Enregistrer » pour sauvegarder vos contacts Dressur directement depuis l'Actu."
                      : "Shows the number of available Dressur contacts in your feed, along with a reminder to boost your contacts. Also enables the \"Save\" button to save your Dressur contacts directly from the Feed.",
                  style: GoogleFonts.poppins(
                    color: isDark ? Colors.grey[300] : Colors.grey[700],
                    fontSize: 13,
                    height: 1.5,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCountryPreferenceCard({
    required BuildContext context,
    required String selectedCountriesText,
    required VoidCallback onEdit,
  }) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
      // Utilisation d'un `Ink` pour l'effet de surbrillance sur toute la carte
      child: Ink(
        decoration: BoxDecoration(
          color: isDark ? Color(0xFF1E1E1E) : Colors.white,
          borderRadius: BorderRadius.circular(15),
          border: Border.all(
              color: isDark ? Colors.grey[800]! : Colors.grey[200]!, width: 1),
          boxShadow: [
            BoxShadow(
              color: isDark
                  ? Colors.black.withOpacity(0.2)
                  : Colors.grey.withOpacity(0.1),
              blurRadius: 10,
              offset: Offset(0, 4),
            ),
          ],
        ),
        // Utilisation d'un `InkWell` pour rendre toute la carte cliquable
        child: InkWell(
          onTap: onEdit,
          borderRadius: BorderRadius.circular(20),
          splashColor: primaryColor.withOpacity(0.1),
          highlightColor: primaryColor.withOpacity(0.05),
          child: Padding(
            padding: const EdgeInsets.all(20.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // --- EN-TÊTE ---
                Row(
                  children: [
                    FaIcon(FontAwesomeIcons.globe,
                        color: primaryColor, size: 28),
                    SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        (langUserPhone == "fr")
                            ? "Pays ciblés"
                            : "Target countries",
                        style: GoogleFonts.poppins(
                          color: isDark ? Colors.white : Colors.black87,
                          fontWeight: FontWeight.w600,
                          fontSize: 18,
                        ),
                      ),
                    ),
                    ElevatedButton.icon(
                      onPressed: onEdit,
                      icon: FaIcon(FontAwesomeIcons.pen, size: 14),
                      label: Text(
                        (langUserPhone == "fr") ? 'Modifier' : 'Edit',
                        style: GoogleFonts.poppins(fontWeight: FontWeight.w600, fontSize: 13),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: primaryColor,
                        foregroundColor: Colors.white,
                        padding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                    ),
                  ],
                ),
                SizedBox(height: 15),

                // --- EXPLICATION IMPORTANTE (le "NB") ---
                Container(
                  padding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    color: primaryColor.withOpacity(0.05),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    (langUserPhone == "fr")
                        ? "Ce choix détermine les contacts proposés, la cible de vos promotions affaires et programmes de récompenses ainsi que l’actualité affichée."
                        : "This choice determines the contacts offered, the target of your business promotions and reward programs, as well as the news displayed.",
                    style: GoogleFonts.poppins(
                      color: isDark ? Colors.grey[300] : Colors.grey[700],
                      fontSize: 13,
                      height: 1.5,
                    ),
                  ),
                ),
                SizedBox(height: 20),

                // --- AFFICHAGE DES PAYS SÉLECTIONNÉS ---
                Builder(builder: (_) {
                  final String raw = selectedCountriesText.trim();
                  final bool isEmpty = raw.isEmpty ||
                      raw == "Aucun Choix" ||
                      raw == "No Choice";

                  final String sublabel;
                  final String value;

                  if (isEmpty) {
                    sublabel = langUserPhone == "fr"
                        ? "Sélection actuelle :"
                        : "Current selection:";
                    value = langUserPhone == "fr"
                        ? "Aucun pays sélectionné"
                        : "No country selected";
                  } else {
                    final List<String> pays =
                        raw.split(', ').where((s) => s.isNotEmpty).toList();
                    if (pays.length <= 3) {
                      sublabel = langUserPhone == "fr"
                          ? "Pays sélectionnés :"
                          : "Selected countries:";
                      value = pays.join(', ');
                    } else {
                      sublabel = langUserPhone == "fr"
                          ? "Nombre de pays sélectionnés :"
                          : "Number of selected countries:";
                      value = langUserPhone == "fr"
                          ? "${pays.length} pays"
                          : "${pays.length} countries";
                    }
                  }

                  return RichText(
                    text: TextSpan(
                      children: [
                        TextSpan(
                          text: '$sublabel ',
                          style: GoogleFonts.poppins(
                            color: isDark ? Colors.grey[400] : Colors.grey[600],
                            fontSize: 14,
                            fontWeight: FontWeight.w400,
                          ),
                        ),
                        TextSpan(
                          text: value,
                          style: GoogleFonts.poppins(
                            color: isDark ? Colors.white : Colors.black,
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  );
                }),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildContactStorageCard({required BuildContext context}) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
      child: Ink(
        decoration: BoxDecoration(
          color: isDark ? Color(0xFF1E1E1E) : Colors.white,
          borderRadius: BorderRadius.circular(15),
          border: Border.all(
              color: isDark ? Colors.grey[800]! : Colors.grey[200]!, width: 1),
          boxShadow: [
            BoxShadow(
              color: isDark
                  ? Colors.black.withOpacity(0.2)
                  : Colors.grey.withOpacity(0.1),
              blurRadius: 10,
              offset: Offset(0, 4),
            ),
          ],
        ),
        child: InkWell(
          onTap: () {
            showModalBottomSheet(
              context: context,
              isScrollControlled: true,
              builder: (ctx) => DraggableScrollableSheet(
                initialChildSize: 0.5,
                minChildSize: 0.3,
                maxChildSize: 0.85,
                expand: false,
                builder: (ctx, scrollController) =>
                    FutureBuilder<List<Map<String, String?>>>(
                  future: _fetchAccountsList(),
                  builder: (ctx, snap) {
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Padding(
                          padding: const EdgeInsets.fromLTRB(16, 20, 16, 12),
                          child: Text(
                            langUserPhone == 'fr'
                                ? 'Choisir le compte de sauvegarde'
                                : 'Choose storage account',
                            style: GoogleFonts.poppins(
                                fontWeight: FontWeight.bold, fontSize: 16),
                          ),
                        ),
                        Divider(height: 1),
                        if (!snap.hasData)
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 48),
                            child: Center(
                              child: Column(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  CircularProgressIndicator(color: primaryColor),
                                  SizedBox(height: 20),
                                  Text(
                                    langUserPhone == 'fr'
                                        ? 'Chargement en cours…'
                                        : 'Loading…',
                                    style: GoogleFonts.poppins(
                                        fontSize: 13, color: Colors.grey),
                                  ),
                                ],
                              ),
                            ),
                          )
                        else
                          Expanded(
                            child: ListView(
                              controller: scrollController,
                              children: [
                                ...snap.data!.map((acc) => RadioListTile<String?>(
                                      value: acc['name'],
                                      groupValue: _selectedAccountName,
                                      title: Text(acc['label'] ?? '',
                                          style: GoogleFonts.poppins()),
                                      onChanged: (val) {
                                        Navigator.pop(ctx);
                                        _saveAccountPreference(val, acc['type']);
                                      },
                                    )),
                                SizedBox(height: 12),
                              ],
                            ),
                          ),
                      ],
                    );
                  },
                ),
              ),
            );
          },
          borderRadius: BorderRadius.circular(15),
          splashColor: primaryColor.withOpacity(0.1),
          highlightColor: primaryColor.withOpacity(0.05),
          child: Padding(
            padding: const EdgeInsets.all(20.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    FaIcon(FontAwesomeIcons.addressBook,
                        color: primaryColor, size: 28),
                    SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        langUserPhone == 'fr'
                            ? 'Sauvegarde des contacts'
                            : 'Contact storage',
                        style: GoogleFonts.poppins(
                          color: isDark ? Colors.white : Colors.black87,
                          fontWeight: FontWeight.w600,
                          fontSize: 18,
                        ),
                      ),
                    ),
                    Icon(Icons.chevron_right,
                        color: isDark ? Colors.grey[400] : Colors.grey[600]),
                  ],
                ),
                SizedBox(height: 15),
                Container(
                  padding:
                      EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    color: primaryColor.withOpacity(0.05),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    langUserPhone == 'fr'
                        ? 'Choisissez sur quel compte vos contacts Dressur seront enregistrés (Google, Samsung, téléphone local…). Ce réglage s\'applique à toutes les synchronisations.\n\nCompte actuel : ${_selectedAccountName ?? "Téléphone (local)"}'
                        : 'Choose which account your Dressur contacts will be saved to (Google, Samsung, local phone…). This setting applies to all synchronizations.\n\nCurrent account: ${_selectedAccountName ?? "Phone (local)"}',
                    style: GoogleFonts.poppins(
                      color: isDark ? Colors.grey[300] : Colors.grey[700],
                      fontSize: 13,
                      height: 1.5,
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
