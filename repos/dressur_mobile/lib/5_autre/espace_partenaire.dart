// ignore_for_file: use_build_context_synchronously
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:url_launcher/url_launcher.dart';
import 'package:dressur/components/constant.dart';

class EspacePartenairePage extends StatefulWidget {
  const EspacePartenairePage({Key? key}) : super(key: key);
  @override
  State<EspacePartenairePage> createState() => _EspacePartenairePageState();
}

class _EspacePartenairePageState extends State<EspacePartenairePage> {
  bool _loading = true;
  List<dynamic> _accompagnes = [];

  @override
  void initState() {
    super.initState();
    if (estPartenaire) _chargerAccompagnes();
    else setState(() => _loading = false);
  }

  Future<void> _chargerAccompagnes() async {
    try {
      final response = await http.get(
        Uri.parse('$generalRouteForApi/accompagnesPartenaire'),
        headers: {'uid': uidUser ?? ''},
      );
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['success'] == true) {
          setState(() { _accompagnes = body['accompagnes'] ?? []; });
        }
      }
    } catch (_) {}
    setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bool toutesConditions = condNom && condTel && condMail && condAnciennete && condCumul;
    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF121212) : const Color(0xFFF8F9FA),
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        leading: IconButton(
          onPressed: () => Navigator.pop(context),
          icon: const FaIcon(FontAwesomeIcons.chevronLeft, color: Colors.white),
        ),
        title: Text(
          'Espace Partenaire',
          style: GoogleFonts.poppins(color: Colors.white, fontWeight: FontWeight.w500, fontSize: 18),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              physics: const BouncingScrollPhysics(),
              padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // ── VUE PARTENAIRE ACTIF ───────────────────────────────────
                  if (estPartenaire) ...[
                    _buildCard(isDark, child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(children: [
                          FaIcon(FontAwesomeIcons.star, color: Colors.amber[600], size: 20),
                          const SizedBox(width: 8),
                          Text('Votre Espace Partenaire',
                              style: GoogleFonts.poppins(fontWeight: FontWeight.bold, fontSize: 16)),
                        ]),
                        const SizedBox(height: 16),
                        Text('Votre Code Partenaire',
                            style: GoogleFonts.poppins(
                                fontSize: 11, fontWeight: FontWeight.bold,
                                color: Colors.grey[500], letterSpacing: 0.8)),
                        const SizedBox(height: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                          decoration: BoxDecoration(
                            color: isDark ? const Color(0xFF2A2A2A) : const Color(0xFFF0F4FF),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(children: [
                            Expanded(
                              child: Text(
                                monCodePartenaire ?? '—',
                                style: GoogleFonts.sourceCodePro(
                                    fontSize: 22, fontWeight: FontWeight.bold,
                                    letterSpacing: 4),
                              ),
                            ),
                            GestureDetector(
                              onTap: () {
                                Clipboard.setData(ClipboardData(text: monCodePartenaire ?? ''));
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text('Code copié !',
                                        style: GoogleFonts.poppins()),
                                    backgroundColor: Colors.green[700],
                                    duration: const Duration(seconds: 2),
                                  ),
                                );
                              },
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                decoration: BoxDecoration(
                                  color: primaryColor,
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Row(children: [
                                  const FaIcon(FontAwesomeIcons.copy, color: Colors.white, size: 13),
                                  const SizedBox(width: 6),
                                  Text('Copier',
                                      style: GoogleFonts.poppins(
                                          color: Colors.white, fontSize: 13, fontWeight: FontWeight.w600)),
                                ]),
                              ),
                            ),
                          ]),
                        ),
                        const SizedBox(height: 6),
                        Text('Ce code change automatiquement après chaque utilisation.',
                            style: GoogleFonts.poppins(fontSize: 11, color: Colors.grey[500])),
                      ],
                    )),
                    const SizedBox(height: 12),
                    // ── Liste des accompagnés ──────────────────────────────
                    _buildCard(isDark, child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(children: [
                          FaIcon(FontAwesomeIcons.users, color: primaryColor, size: 16),
                          const SizedBox(width: 8),
                          Text('Mes Accompagnés',
                              style: GoogleFonts.poppins(fontWeight: FontWeight.bold, fontSize: 15)),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: primaryColor,
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text('${_accompagnes.length}',
                                style: GoogleFonts.poppins(color: Colors.white, fontSize: 12,
                                    fontWeight: FontWeight.bold)),
                          ),
                        ]),
                        const SizedBox(height: 12),
                        if (_accompagnes.isEmpty)
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            child: Center(
                              child: Text('Aucun accompagné pour l\'instant.\nPartagez votre code !',
                                  textAlign: TextAlign.center,
                                  style: GoogleFonts.poppins(color: Colors.grey[500], fontSize: 13)),
                            ),
                          )
                        else
                          ..._accompagnes.map((acc) => _buildAccompagneCard(isDark, acc)).toList(),
                      ],
                    )),
                    const SizedBox(height: 12),
                    Padding(
                      padding: const EdgeInsets.only(bottom: 4),
                      child: Text('▼  Présentation de l\'Espace Partenaire',
                          style: GoogleFonts.poppins(fontSize: 13, color: Colors.grey[500])),
                    ),
                    const SizedBox(height: 4),
                  ],
                  // ── PRÉSENTATION (toujours visible) ────────────────────────
                  _buildCard(isDark, child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(children: [
                        FaIcon(FontAwesomeIcons.handshake, color: primaryColor, size: 18),
                        const SizedBox(width: 8),
                        Expanded(child: Text('Qu\'est-ce que l\'Espace Partenaire ?',
                            style: GoogleFonts.poppins(fontWeight: FontWeight.bold, fontSize: 15))),
                      ]),
                      const SizedBox(height: 10),
                      Text(
                        'Le Partenaire Dressur est un utilisateur confirmé qui maîtrise la plateforme et s\'engage à accompagner, aider et expliquer Dressur aux personnes qui utilisent son code d\'affiliation. C\'est un rôle actif, pas seulement un statut.',
                        style: GoogleFonts.poppins(fontSize: 13, height: 1.6, color: Colors.grey[700]),
                      ),
                    ],
                  )),
                  const SizedBox(height: 12),
                  _buildCard(isDark, child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(children: [
                        FaIcon(FontAwesomeIcons.gift, color: Colors.green[600], size: 18),
                        const SizedBox(width: 8),
                        Text('Vos Avantages', style: GoogleFonts.poppins(fontWeight: FontWeight.bold, fontSize: 15)),
                      ]),
                      const SizedBox(height: 10),
                      _buildAvantage('2% de commission sur chaque transaction payante de vos accompagnés, crédités sur votre solde Dressur'),
                      _buildAvantage('Le solde est utilisable sur tous les services payants (boosts, promos…)'),
                      _buildAvantage('Tableau de bord dédié avec la liste de vos accompagnés'),
                    ],
                  )),
                  const SizedBox(height: 12),
                  // ── Conditions ─────────────────────────────────────────────
                  _buildCard(isDark, child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(children: [
                        FaIcon(FontAwesomeIcons.listCheck, color: Colors.orange[600], size: 18),
                        const SizedBox(width: 8),
                        Text('Conditions pour devenir Partenaire',
                            style: GoogleFonts.poppins(fontWeight: FontWeight.bold, fontSize: 15)),
                      ]),
                      const SizedBox(height: 12),
                      _buildCondition(isDark, condNom, 'Nom complet renseigné dans le profil'),
                      _buildCondition(isDark, condTel, 'Numéro WhatsApp confirmé'),
                      _buildCondition(isDark, condMail, 'Adresse e-mail confirmée'),
                      _buildCondition(isDark, condAnciennete,
                          'Inscrit depuis au moins 7 jours ($joursInscrit j / 7 j)'),
                      _buildCondition(isDark, condCumul,
                          'Au moins 2 000 FCFA cumulés en services payants ($cumulFcfa FCFA / 2 000 FCFA)'),
                      _buildConditionInfo(isDark, 'Entretien de validation avec l\'équipe Dressur sur WhatsApp (dernière étape)'),
                      if (!estPartenaire) ...[
                        const SizedBox(height: 16),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: toutesConditions
                                ? () async {
                                    final message = 'Bonjour, j\'aimerais passer l\'entretien pour devenir Partenaire Dressur. Mon pseudo est : $pseudo';
                                    final uri = Uri.parse(
                                        'https://wa.me/22964044294?text=${Uri.encodeComponent(message)}');
                                    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        SnackBar(content: Text('Impossible d\'ouvrir WhatsApp',
                                            style: GoogleFonts.poppins())));
                                    }
                                  }
                                : null,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.green[700],
                              disabledBackgroundColor: Colors.grey[400],
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12)),
                            ),
                            icon: const FaIcon(FontAwesomeIcons.whatsapp, color: Colors.white, size: 18),
                            label: Text('Demander l\'entretien WhatsApp',
                                style: GoogleFonts.poppins(
                                    color: Colors.white, fontWeight: FontWeight.w600, fontSize: 15)),
                          ),
                        ),
                        if (!toutesConditions)
                          Padding(
                            padding: const EdgeInsets.only(top: 8),
                            child: Text(
                              'Complétez toutes les conditions ci-dessus pour débloquer l\'entretien.',
                              textAlign: TextAlign.center,
                              style: GoogleFonts.poppins(fontSize: 12, color: Colors.red[400]),
                            ),
                          ),
                      ],
                    ],
                  )),
                  const SizedBox(height: 30),
                ],
              ),
            ),
    );
  }

  Widget _buildCard(bool isDark, {required Widget child}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E1E1E) : Colors.white,
        borderRadius: BorderRadius.circular(15),
        border: Border.all(color: isDark ? Colors.grey[800]! : Colors.grey[200]!),
      ),
      child: child,
    );
  }

  Widget _buildCondition(bool isDark, bool ok, String label) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          FaIcon(
            ok ? FontAwesomeIcons.circleCheck : FontAwesomeIcons.circleXmark,
            color: ok ? Colors.green[600] : Colors.red[400],
            size: 16,
          ),
          const SizedBox(width: 10),
          Expanded(child: Text(label, style: GoogleFonts.poppins(fontSize: 13))),
        ],
      ),
    );
  }

  Widget _buildConditionInfo(bool isDark, String label) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          FaIcon(FontAwesomeIcons.shield, color: Colors.orange[600], size: 16),
          const SizedBox(width: 10),
          Expanded(child: Text(label, style: GoogleFonts.poppins(fontSize: 13))),
        ],
      ),
    );
  }

  Widget _buildAvantage(String text) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          FaIcon(FontAwesomeIcons.circleCheck, color: Colors.green[500], size: 14),
          const SizedBox(width: 8),
          Expanded(child: Text(text, style: GoogleFonts.poppins(fontSize: 13, height: 1.5))),
        ],
      ),
    );
  }

  Widget _buildAccompagneCard(bool isDark, Map<String, dynamic> acc) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF2A2A2A) : const Color(0xFFF8F9FA),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: isDark ? Colors.grey[800]! : Colors.grey[200]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Text(acc['nom'] ?? '—',
                style: GoogleFonts.poppins(fontWeight: FontWeight.w600, fontSize: 14)),
            const SizedBox(width: 8),
            Text('@${acc['pseudo'] ?? '—'}',
                style: GoogleFonts.poppins(fontSize: 12, color: Colors.grey[500])),
          ]),
          const SizedBox(height: 6),
          _buildAccInfo(FontAwesomeIcons.whatsapp, acc['tel'] ?? '—', Colors.green[600]!),
          const SizedBox(height: 3),
          _buildAccInfo(FontAwesomeIcons.envelope, acc['mail'] ?? '—', Colors.grey[600]!),
          const SizedBox(height: 3),
          _buildAccInfo(FontAwesomeIcons.calendarDay, 'Affilié le ${acc['dateAffiliation'] ?? '—'}',
              Colors.grey[500]!),
        ],
      ),
    );
  }

  Widget _buildAccInfo(IconData icon, String text, Color color) {
    return Row(children: [
      FaIcon(icon, size: 12, color: color),
      const SizedBox(width: 6),
      Expanded(child: Text(text, style: GoogleFonts.poppins(fontSize: 12, color: Colors.grey[600]))),
    ]);
  }
}
