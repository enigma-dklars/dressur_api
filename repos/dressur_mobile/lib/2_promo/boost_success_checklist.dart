// ignore_for_file: use_build_context_synchronously
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/components/constant.dart';

class BoostSuccessChecklistPage extends StatefulWidget {
  final String typeBoost;
  final int nbMax;

  const BoostSuccessChecklistPage({
    super.key,
    required this.typeBoost,
    required this.nbMax,
  });

  @override
  State<BoostSuccessChecklistPage> createState() =>
      _BoostSuccessChecklistPageState();
}

class _BoostSuccessChecklistPageState
    extends State<BoostSuccessChecklistPage> {
  final List<bool> _checked = [false, false, false, false];

  bool get _allChecked => _checked.every((c) => c);

  List<String> _buildItems() {
    final bool isFr = langUserPhone == 'fr';
    final String item4 = addPageActu
        ? (isFr
            ? "Sur l'onglet Actu, appuyez sur 'Enregistrer les contacts' dès que vous en voyez de disponibles"
            : "On the News tab, tap 'Save contacts' as soon as you see some available")
        : (isFr
            ? "Allez dans Boîte de Réception → Contacts pour retrouver vos contacts obtenus"
            : "Go to Inbox → Contacts to find your received contacts");

    if (widget.typeBoost == 'quota') {
      return isFr
          ? [
              "Ouvrez l'application au moins 4 fois par jour pour ne manquer aucun contact disponible",
              "Surveillez votre progression dans Services → Voir la liste. Quand vos ${widget.nbMax} contacts sont reçus, votre boost s'arrête automatiquement",
              "Dès que votre quota est atteint, revenez faire un nouveau boost pour continuer à recevoir des contacts",
              item4,
            ]
          : [
              "Open the app at least 4 times a day to not miss any available contact",
              "Monitor your progress in Services → View list. When your ${widget.nbMax} contacts are received, your boost stops automatically",
              "Once your quota is reached, come back to start a new boost to keep receiving contacts",
              item4,
            ];
    } else {
      return isFr
          ? [
              "Ouvrez l'application au moins 4 fois par jour pour ne manquer aucun contact disponible",
              "Surveillez la date de fin de votre boost dans Services → Voir la liste",
              "Dès que votre boost expire, revenez immédiatement faire un nouveau boost pour continuer à recevoir des contacts",
              item4,
            ]
          : [
              "Open the app at least 4 times a day to not miss any available contact",
              "Monitor your boost end date in Services → View list",
              "As soon as your boost expires, come back immediately to start a new boost to keep receiving contacts",
              item4,
            ];
    }
  }

  @override
  Widget build(BuildContext context) {
    final bool isFr = langUserPhone == 'fr';
    final items = _buildItems();

    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              const SizedBox(height: 12),
              // ── Icône succès animée ──────────────────────────────────
              AnimatedContainer(
                duration: const Duration(milliseconds: 600),
                width: 84,
                height: 84,
                decoration: BoxDecoration(
                  color: const Color(0xFF4CAF50).withOpacity(0.12),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.check_circle_rounded,
                  color: Color(0xFF4CAF50),
                  size: 52,
                ),
              ),
              const SizedBox(height: 20),
              // ── Titre ────────────────────────────────────────────────
              Text(
                isFr
                    ? "Boost activé ! Voici ce que vous devez faire"
                    : "Boost activated! Here's what to do",
                style: GoogleFonts.poppins(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: const Color(0xFF1A1A1A),
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              Text(
                isFr
                    ? "Cochez chaque point pour continuer"
                    : "Check each point to continue",
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  color: Colors.grey[600],
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 28),
              // ── Checklist ────────────────────────────────────────────
              ...List.generate(4, (i) => _buildCheckItem(i, items[i])),
              const SizedBox(height: 32),
              // ── Bouton ───────────────────────────────────────────────
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _allChecked
                        ? const Color(0xFF4CAF50)
                        : Colors.grey[300],
                    foregroundColor:
                        _allChecked ? Colors.white : Colors.grey[500],
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    elevation: _allChecked ? 2 : 0,
                  ),
                  onPressed: _allChecked
                      ? () => Navigator.popUntil(
                          context, (route) => route.isFirst)
                      : null,
                  child: Text(
                    isFr ? "J'ai compris, continuer" : "Got it, continue",
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.w600,
                      fontSize: 16,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCheckItem(int index, String text) {
    final bool checked = _checked[index];
    return GestureDetector(
      onTap: () => setState(() => _checked[index] = !_checked[index]),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 280),
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: checked ? const Color(0xFFE8F5E9) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: checked
                ? const Color(0xFF4CAF50)
                : Colors.grey[200]!,
            width: 1.5,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.04),
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            AnimatedSwitcher(
              duration: const Duration(milliseconds: 220),
              child: Icon(
                checked
                    ? Icons.check_box_rounded
                    : Icons.check_box_outline_blank_rounded,
                key: ValueKey(checked),
                color: checked
                    ? const Color(0xFF4CAF50)
                    : Colors.grey[400],
                size: 24,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                text,
                style: GoogleFonts.poppins(
                  fontSize: 14,
                  color: checked
                      ? const Color(0xFF2E7D32)
                      : Colors.grey[800],
                  fontWeight:
                      checked ? FontWeight.w600 : FontWeight.w400,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
