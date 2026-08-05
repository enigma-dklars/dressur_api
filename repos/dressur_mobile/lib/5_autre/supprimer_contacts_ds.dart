import 'package:animate_do/animate_do.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/noti.dart';
import 'package:dressur/components/ds_deletion_service.dart';

class SupprimerContactsDSPage extends StatefulWidget {
  const SupprimerContactsDSPage({Key? key}) : super(key: key);

  @override
  State<SupprimerContactsDSPage> createState() =>
      _SupprimerContactsDSPageState();
}

class _SupprimerContactsDSPageState extends State<SupprimerContactsDSPage> {
  // Singleton partagé, survit à la navigation
  final DSDeletionService _service = DSDeletionService();

  @override
  void initState() {
    super.initState();
    // Écoute les changements du service pour reconstruire la page
    _service.addListener(_onServiceChanged);
  }

  @override
  void dispose() {
    _service.removeListener(_onServiceChanged);
    super.dispose();
  }

  void _onServiceChanged() {
    if (mounted) setState(() {});
  }

  Future<void> _lancer() async {
    final String? erreur = await _service.start();
    if (erreur != null && mounted) {
      warningNoti(
        (langUserPhone == "fr") ? "Attention !" : "Warning!",
        erreur,
        context,
      );
    }
  }

  Future<void> _confirmerArret() async {
    final bool isFr = langUserPhone == 'fr';
    final bool? confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          isFr ? 'Arrêter la suppression ?' : 'Stop the deletion?',
          style: GoogleFonts.poppins(fontWeight: FontWeight.w600),
        ),
        content: Text(
          isFr
              ? 'Le traitement sera interrompu. Les contacts déjà supprimés ne seront pas restaurés.'
              : 'The operation will be stopped. Already deleted contacts will not be restored.',
          style: GoogleFonts.poppins(fontSize: 13),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isFr ? 'Continuer' : 'Continue'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(
              isFr ? 'Arrêter' : 'Stop',
              style: const TextStyle(color: Colors.red),
            ),
          ),
        ],
      ),
    );
    if (confirm == true) _service.cancel();
  }

  Widget _buildWarningRow(String text) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        FaIcon(FontAwesomeIcons.triangleExclamation,
            size: 13, color: Colors.red[600]),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: GoogleFonts.poppins(
                fontSize: 13,
                color: Colors.red[700],
                fontWeight: FontWeight.w500),
          ),
        ),
      ],
    );
  }

  Widget _buildInfoRow(String text) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        FaIcon(FontAwesomeIcons.circleCheck, size: 13, color: Colors.blue[600]),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: GoogleFonts.poppins(fontSize: 12, color: Colors.grey[700]),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final bool isFr = langUserPhone == 'fr';
    final bool enCour = _service.isRunning;
    final bool termine = _service.isCompleted;
    final bool erreur = _service.errorText != null;
    final bool annule = _service.isCancelled;
    final bool cancelPending = _service.isCancelPending;
    final double progress = _service.progress;
    final String statusText = _service.statusText;

    return Scaffold(
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          isFr ? "Suppression des contacts DS" : "Delete DS Contacts",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
        leading: IconButton(
          // Toujours actif : la suppression continue en arrière-plan
          onPressed: () => Navigator.pop(context),
          icon: const FaIcon(FontAwesomeIcons.chevronLeft, color: Colors.white),
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 20.0, vertical: 30.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const Spacer(),

            // ── ICÔNE ────────────────────────────────────────────────────
            FadeIn(
              duration: const Duration(milliseconds: 500),
              child: FaIcon(
                erreur
                    ? FontAwesomeIcons.circleXmark
                    : termine
                        ? FontAwesomeIcons.circleCheck
                        : FontAwesomeIcons.broom,
                size: 80,
                color: erreur
                    ? Colors.red[600]
                    : termine
                        ? Colors.green
                        : Colors.orange[700],
              ),
            ),
            const SizedBox(height: 20),

            // ── TITRE ─────────────────────────────────────────────────────
            FadeInUp(
              from: 20,
              duration: const Duration(milliseconds: 500),
              delay: const Duration(milliseconds: 200),
              child: Text(
                erreur
                    ? (isFr ? "Erreur" : "Error")
                    : termine
                        ? (isFr ? "Suppression terminée" : "Deletion completed")
                        : (isFr
                            ? "Suppression des contacts DS"
                            : "Delete DS Contacts"),
                textAlign: TextAlign.center,
                style: GoogleFonts.poppins(
                    fontSize: 26, fontWeight: FontWeight.bold),
              ),
            ),
            const SizedBox(height: 15),

            // ── RÉSUMÉ TERMINÉ ────────────────────────────────────────────
            if (termine) ...[
              FadeInUp(
                from: 20,
                duration: const Duration(milliseconds: 400),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.green.withOpacity(0.07),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.green.withOpacity(0.3)),
                  ),
                  child: Text(
                    statusText,
                    textAlign: TextAlign.center,
                    style: GoogleFonts.poppins(
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                      color: Colors.green[700],
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              FadeInUp(
                from: 20,
                duration: const Duration(milliseconds: 400),
                delay: const Duration(milliseconds: 100),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () {
                      _service.reset();
                    },
                    icon: const FaIcon(FontAwesomeIcons.arrowsRotate),
                    label: Text(isFr
                        ? "Nouvelle suppression"
                        : "New deletion"),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.orange[700],
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      textStyle: GoogleFonts.poppins(
                          fontSize: 15, fontWeight: FontWeight.bold),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 8),
              FadeInUp(
                from: 20,
                duration: const Duration(milliseconds: 400),
                delay: const Duration(milliseconds: 150),
                child: SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: () => Navigator.pop(context),
                    icon: const FaIcon(FontAwesomeIcons.chevronLeft),
                    label: Text(isFr ? "Retour" : "Go back"),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: primaryColor,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      textStyle: GoogleFonts.poppins(
                          fontSize: 15, fontWeight: FontWeight.bold),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
              ),
            ],

            // ── ERREUR ───────────────────────────────────────────────────
            // ── ANNULÉ ───────────────────────────────────────────────────
            if (annule && !enCour) ...[
              FadeInUp(
                from: 20,
                duration: const Duration(milliseconds: 400),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.orange.withOpacity(0.07),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.orange.withOpacity(0.3)),
                  ),
                  child: Text(
                    statusText,
                    textAlign: TextAlign.center,
                    style: GoogleFonts.poppins(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: Colors.orange[800],
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              FadeInUp(
                from: 20,
                duration: const Duration(milliseconds: 400),
                delay: const Duration(milliseconds: 100),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () => _service.reset(),
                    icon: const FaIcon(FontAwesomeIcons.arrowsRotate),
                    label: Text(
                        isFr ? 'Relancer la suppression' : 'Restart deletion'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.orange[700],
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      textStyle: GoogleFonts.poppins(
                          fontSize: 15, fontWeight: FontWeight.bold),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 8),
              FadeInUp(
                from: 20,
                duration: const Duration(milliseconds: 400),
                delay: const Duration(milliseconds: 150),
                child: SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: () => Navigator.pop(context),
                    icon: const FaIcon(FontAwesomeIcons.chevronLeft),
                    label: Text(isFr ? 'Retour' : 'Go back'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: primaryColor,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      textStyle: GoogleFonts.poppins(
                          fontSize: 15, fontWeight: FontWeight.bold),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
              ),
            ],

            if (erreur && !termine && !annule) ...[
              FadeInUp(
                from: 20,
                duration: const Duration(milliseconds: 400),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.red.withOpacity(0.06),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.red.withOpacity(0.3)),
                  ),
                  child: Text(
                    _service.errorText!,
                    textAlign: TextAlign.center,
                    style: GoogleFonts.poppins(
                        fontSize: 13,
                        color: Colors.red[700],
                        fontWeight: FontWeight.w500),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () => _service.reset(),
                  icon: const FaIcon(FontAwesomeIcons.arrowsRotate),
                  label: Text(isFr ? "Réessayer" : "Try again"),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.red[600],
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    textStyle: GoogleFonts.poppins(
                        fontSize: 15, fontWeight: FontWeight.bold),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ),
            ],

            // ── VUE INITIALE / EN COURS ───────────────────────────────────
            if (!termine && !erreur && !annule) ...[

              // Bannière "en cours", rappel navigation possible
              if (enCour) ...[
                FadeIn(
                  duration: const Duration(milliseconds: 300),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(
                      color: Colors.blue.withOpacity(0.06),
                      borderRadius: BorderRadius.circular(12),
                      border:
                          Border.all(color: Colors.blue.withOpacity(0.2)),
                    ),
                    child: Row(
                      children: [
                        FaIcon(FontAwesomeIcons.bell,
                            size: 13, color: Colors.blue[600]),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            isFr
                                ? "Suppression en cours, vous pouvez naviguer, la progression est visible ici et dans vos notifications."
                                : "Deletion in progress, you can navigate freely, progress is shown here and in your notifications.",
                            style: GoogleFonts.poppins(
                                fontSize: 12, color: Colors.blue[700]),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 14),
              ],

              // Avertissements (masqués pendant la suppression)
              if (!enCour) ...[
                FadeInUp(
                  from: 20,
                  duration: const Duration(milliseconds: 500),
                  delay: const Duration(milliseconds: 400),
                  child: Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.red.withOpacity(0.05),
                      borderRadius: BorderRadius.circular(12),
                      border:
                          Border.all(color: Colors.red.withOpacity(0.2)),
                    ),
                    child: Column(
                      children: [
                        _buildWarningRow(
                          isFr
                              ? "Cette action est irréversible."
                              : "This action is irreversible.",
                        ),
                        const SizedBox(height: 10),
                        _buildWarningRow(
                          isFr
                              ? "Ne fermez pas complètement l'application."
                              : "Do not fully close the application.",
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Info box
                FadeInUp(
                  from: 20,
                  duration: const Duration(milliseconds: 500),
                  delay: const Duration(milliseconds: 500),
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Colors.blue.withOpacity(0.05),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                          color: Colors.blue.withOpacity(0.15)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            FaIcon(FontAwesomeIcons.circleInfo,
                                size: 14, color: Colors.blue[600]),
                            const SizedBox(width: 8),
                            Text(
                              isFr
                                  ? "Ce que va faire cette opération :"
                                  : "What this operation will do:",
                              style: GoogleFonts.poppins(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: Colors.blue[700],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        _buildInfoRow(
                          isFr
                              ? "Détecter tous les contacts dont le nom contient « #DS »"
                              : "Detect all contacts whose name contains \"#DS\"",
                        ),
                        const SizedBox(height: 4),
                        _buildInfoRow(
                          isFr
                              ? "Les supprimer définitivement de votre répertoire téléphonique"
                              : "Permanently delete them from your phone contacts",
                        ),
                        const SizedBox(height: 4),
                        _buildInfoRow(
                          isFr
                              ? "Vos contacts personnels ne seront pas affectés"
                              : "Your personal contacts will not be affected",
                        ),
                        const SizedBox(height: 4),
                        _buildInfoRow(
                          isFr
                              ? "La progression sera affichée ici et dans vos notifications"
                              : "Progress will be shown here and in your notifications",
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
              ],

              // Bouton démarrer
              FadeInUp(
                from: 20,
                duration: const Duration(milliseconds: 500),
                delay: const Duration(milliseconds: 600),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: enCour ? null : _lancer,
                    icon: enCour
                        ? const SizedBox.shrink()
                        : const FaIcon(FontAwesomeIcons.broom),
                    label: enCour
                        ? const SizedBox(
                            height: 24,
                            width: 24,
                            child: CircularProgressIndicator(
                                color: Colors.white, strokeWidth: 2))
                        : Text(isFr
                            ? "Démarrer la suppression"
                            : "Start deletion"),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.orange[700],
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      textStyle: GoogleFonts.poppins(
                          fontSize: 16, fontWeight: FontWeight.bold),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
              ),

              // ── BOUTON D'ARRÊT (visible uniquement pendant le traitement) ─
              if (enCour) ...[
                const SizedBox(height: 10),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: cancelPending ? null : _confirmerArret,
                    icon: cancelPending
                        ? const SizedBox(
                            height: 14,
                            width: 14,
                            child: CircularProgressIndicator(
                                strokeWidth: 2, color: Colors.red))
                        : const FaIcon(FontAwesomeIcons.stop, size: 14),
                    label: Text(cancelPending
                        ? (isFr ? 'Annulation en cours…' : 'Cancelling…')
                        : (isFr ? 'Arrêter le processus' : 'Stop the process')),
                    style: OutlinedButton.styleFrom(
                      foregroundColor:
                          cancelPending ? Colors.red[300] : Colors.red[600],
                      side: BorderSide(
                          color: cancelPending
                              ? Colors.red.shade200
                              : Colors.red.shade300),
                      padding: const EdgeInsets.symmetric(vertical: 13),
                      textStyle: GoogleFonts.poppins(
                          fontSize: 14, fontWeight: FontWeight.w600),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
              ],
            ],

            const Spacer(),

            // ── BARRE DE PROGRESSION (visible pendant la suppression) ─────
            if (enCour)
              FadeIn(
                duration: const Duration(milliseconds: 300),
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 20.0),
                  child: Column(
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(10),
                        child: LinearProgressIndicator(
                          value: progress,
                          minHeight: 8,
                          backgroundColor: Colors.grey[200],
                          color: Colors.orange[700],
                        ),
                      ),
                      const SizedBox(height: 10),
                      Text(
                        statusText,
                        style: GoogleFonts.poppins(
                            fontSize: 14, color: Colors.grey[600]),
                        textAlign: TextAlign.center,
                      ),
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
