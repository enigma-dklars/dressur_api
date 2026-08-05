// ignore_for_file: use_build_context_synchronously, prefer_const_constructors
import 'dart:convert';
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/noti.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;

class UtiliserCodePartenairePage extends StatefulWidget {
  @override
  State<UtiliserCodePartenairePage> createState() =>
      _UtiliserCodePartenairePageState();
}

class _UtiliserCodePartenairePageState
    extends State<UtiliserCodePartenairePage> {
  final TextEditingController _codeController = TextEditingController();
  bool _loading = false;

  // --- Conditions vérifiées localement ---
  bool get _nomRenseigne => nom != null && nom.toString().trim().isNotEmpty;
  bool get _whatsappConfirme => telIsVerified;
  bool get _emailConfirme => mailIsVerified;
  bool get _inscritDepuis24h {
    try {
      if (createdAt == null) return false;
      final dateInscription = DateTime.parse(createdAt.toString());
      return DateTime.now().difference(dateInscription).inHours < 24;
    } catch (_) {
      return false;
    }
  }

  bool get _toutesConditionsRemplies =>
      _nomRenseigne && _whatsappConfirme && _emailConfirme && _inscritDepuis24h;

  Future<void> _utiliserCode() async {
    final code = _codeController.text.trim().toUpperCase();
    if (code.isEmpty) {
      dangerNoti(
        langUserPhone == "fr" ? "Attention !" : "Attention!",
        langUserPhone == "fr"
            ? "Veuillez saisir un Code Partenaire."
            : "Please enter a Partner Code.",
        context,
      );
      return;
    }
    setState(() => _loading = true);
    try {
      final response = await http.post(
        Uri.parse('$generalRouteForApi/utiliserCodePartenaire'),
        body: {
          'uid': '$uidUser',
          'codePartenaire': code,
        },
      ).timeout(const Duration(seconds: 15));
      final body = jsonDecode(response.body) as Map<String, dynamic>;
      if (!mounted) return;
      if (body['error'] == false) {
        setState(() => aUnPartenaire = true);
        successNoti(
          langUserPhone == "fr" ? "Succès !" : "Success!",
          body['message'] ??
              (langUserPhone == "fr"
                  ? "Vous êtes maintenant accompagné par un partenaire."
                  : "You are now partnered."),
          context,
        );
        if (mounted) Navigator.pop(context);
      } else {
        dangerNoti(
          body['titre'] ?? (langUserPhone == "fr" ? "Erreur !" : "Error!"),
          body['message'] ??
              (langUserPhone == "fr"
                  ? "Une erreur est survenue."
                  : "An error occurred."),
          context,
        );
      }
    } catch (_) {
      if (!mounted) return;
      dangerNoti(
        langUserPhone == "fr" ? "Erreur !" : "Error!",
        langUserPhone == "fr"
            ? "Impossible de contacter le serveur. Vérifiez votre connexion."
            : "Unable to reach the server. Check your connection.",
        context,
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    _codeController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Scaffold(
      backgroundColor: isDark ? Color(0xFF121212) : Color(0xFFF8F9FA),
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        leading: IconButton(
          onPressed: () => Navigator.pop(context),
          icon: const FaIcon(FontAwesomeIcons.chevronLeft, color: Colors.white),
        ),
        title: Text(
          langUserPhone == "fr" ? "Code Partenaire" : "Partner Code",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
      ),
      body: SingleChildScrollView(
        physics: BouncingScrollPhysics(),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // --- EN-TÊTE ---
            Center(
              child: Column(
                children: [
                  FaIcon(FontAwesomeIcons.handshake,
                      color: primaryColor, size: 60),
                  SizedBox(height: 16),
                  Text(
                    langUserPhone == "fr"
                        ? "Utiliser un Code Partenaire"
                        : "Use a Partner Code",
                    style: GoogleFonts.poppins(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  SizedBox(height: 8),
                  Text(
                    langUserPhone == "fr"
                        ? "Entrez le code partagé par votre partenaire pour rejoindre son réseau."
                        : "Enter the code shared by your partner to join their network.",
                    style: GoogleFonts.poppins(
                        fontSize: 14, color: Colors.grey[600]),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
            SizedBox(height: 32),
            // --- SECTION CONDITIONS ---
            Text(
              langUserPhone == "fr"
                  ? "CONDITIONS REQUISES"
                  : "REQUIRED CONDITIONS",
              style: GoogleFonts.poppins(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                color: Colors.grey[500],
                letterSpacing: 0.8,
              ),
            ),
            SizedBox(height: 10),
            Container(
              decoration: BoxDecoration(
                color: isDark ? Color(0xFF1E1E1E) : Colors.white,
                borderRadius: BorderRadius.circular(15),
              ),
              child: Column(
                children: [
                  _buildConditionRow(
                    icon: FontAwesomeIcons.user,
                    label: langUserPhone == "fr"
                        ? "Nom et prénom renseignés"
                        : "Name filled in",
                    fulfilled: _nomRenseigne,
                  ),
                  Divider(height: 1, indent: 20, endIndent: 20),
                  _buildConditionRow(
                    icon: FontAwesomeIcons.whatsapp,
                    label: langUserPhone == "fr"
                        ? "WhatsApp confirmé"
                        : "WhatsApp confirmed",
                    fulfilled: _whatsappConfirme,
                  ),
                  Divider(height: 1, indent: 20, endIndent: 20),
                  _buildConditionRow(
                    icon: FontAwesomeIcons.envelope,
                    label: langUserPhone == "fr"
                        ? "Adresse e-mail confirmée"
                        : "Email address confirmed",
                    fulfilled: _emailConfirme,
                  ),
                  Divider(height: 1, indent: 20, endIndent: 20),
                  _buildConditionRow(
                    icon: FontAwesomeIcons.clock,
                    label: langUserPhone == "fr"
                        ? "Inscrit depuis moins de 24h"
                        : "Registered less than 24h ago",
                    fulfilled: _inscritDepuis24h,
                  ),
                ],
              ),
            ),
            SizedBox(height: 28),
            // --- CHAMP SAISIE CODE ---
            Text(
              langUserPhone == "fr" ? "VOTRE CODE" : "YOUR CODE",
              style: GoogleFonts.poppins(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                color: Colors.grey[500],
                letterSpacing: 0.8,
              ),
            ),
            SizedBox(height: 10),
            TextField(
              controller: _codeController,
              enabled: _toutesConditionsRemplies && !_loading,
              textCapitalization: TextCapitalization.characters,
              style: GoogleFonts.poppins(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                letterSpacing: 4,
              ),
              maxLength: 8,
              decoration: InputDecoration(
                hintText: "EX: A3KT9BM2",
                hintStyle: GoogleFonts.poppins(
                  color: Colors.grey[400],
                  letterSpacing: 4,
                  fontWeight: FontWeight.w400,
                ),
                filled: true,
                fillColor: isDark ? Color(0xFF1E1E1E) : Colors.white,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide(color: Colors.grey[300]!, width: 1),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide(color: primaryColor, width: 2),
                ),
                disabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide(color: Colors.grey[200]!, width: 1),
                ),
                counterText: "",
                prefixIcon: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 14),
                  child: Align(
                    alignment: Alignment.center,
                    widthFactor: 1.0,
                    heightFactor: 1.0,
                    child: FaIcon(FontAwesomeIcons.key,
                        color: _toutesConditionsRemplies
                            ? primaryColor
                            : Colors.grey[400],
                        size: 20),
                  ),
                ),
              ),
            ),
            SizedBox(height: 8),
            // --- MESSAGE SI CONDITIONS NON REMPLIES ---
            if (!_toutesConditionsRemplies)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text(
                  langUserPhone == "fr"
                      ? "Remplissez toutes les conditions ci-dessus pour activer la saisie du code."
                      : "Complete all conditions above to enable code entry.",
                  style: GoogleFonts.poppins(
                      fontSize: 12, color: Colors.orange[700]),
                ),
              ),
            SizedBox(height: 32),
            // --- BOUTON VALIDER ---
            SizedBox(
              width: double.infinity,
              height: 52,
              child: ElevatedButton(
                onPressed: (_toutesConditionsRemplies && !_loading)
                    ? _utiliserCode
                    : null,
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  disabledBackgroundColor: Colors.grey[300],
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  elevation: 0,
                ),
                child: _loading
                    ? SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(
                            color: Colors.white, strokeWidth: 2.5),
                      )
                    : Text(
                        langUserPhone == "fr"
                            ? "Utiliser ce code"
                            : "Use this code",
                        style: GoogleFonts.poppins(
                          color: Colors.white,
                          fontWeight: FontWeight.w600,
                          fontSize: 16,
                        ),
                      ),
              ),
            ),
            SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildConditionRow({
    required IconData icon,
    required String label,
    required bool fulfilled,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      child: Row(
        children: [
          FaIcon(icon,
              color: fulfilled ? Colors.green : Colors.grey[400], size: 18),
          SizedBox(width: 14),
          Expanded(
            child: Text(
              label,
              style: GoogleFonts.poppins(
                  fontSize: 14, fontWeight: FontWeight.w500),
            ),
          ),
          FaIcon(
            fulfilled
                ? FontAwesomeIcons.circleCheck
                : FontAwesomeIcons.circleXmark,
            color: fulfilled ? Colors.green : Colors.red[300],
            size: 18,
          ),
        ],
      ),
    );
  }
}
