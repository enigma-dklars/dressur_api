import 'package:animate_do/animate_do.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/noti.dart';
import 'package:dressur/components/synchro_avance_service.dart';

class SynchroAvance extends StatelessWidget {
  const SynchroAvance({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: Colors.white,
      body: _SynchroAvancePage(),
    );
  }
}

class _SynchroAvancePage extends StatefulWidget {
  const _SynchroAvancePage({Key? key}) : super(key: key);

  @override
  State<_SynchroAvancePage> createState() => _SynchroAvancePageState();
}

class _SynchroAvancePageState extends State<_SynchroAvancePage> {
  // Singleton partagé, survit à la navigation
  final SynchroAvanceService _service = SynchroAvanceService();

  // Option locale (modifiable uniquement quand la synchro est inactive)
  bool _etendreAuxNonDS = false;

  @override
  void initState() {
    super.initState();
    _service.addListener(_onServiceChanged);
    _initPermissionsAndContact();
  }

  @override
  void dispose() {
    _service.removeListener(_onServiceChanged);
    super.dispose();
  }

  void _onServiceChanged() {
    if (mounted) setState(() {});
  }

  /// Demande la permission contacts et insère le contact Dressur si accordée.
  Future<void> _initPermissionsAndContact() async {
    PermissionStatus permission = await Permission.contacts.status;
    if (permission != PermissionStatus.granted) {
      permission = await Permission.contacts.request();
    }
    if (permission == PermissionStatus.granted) {
      insertDressurContact();
    } else if (permission != PermissionStatus.granted) {
      if (!mounted) return;
      warningNoti(
        "Attention !",
        langUserPhone != "fr"
            ? "Please allow Dressur to automatically save contacts to your phone.\nThis authorization is necessary to take full advantage of our features."
            : "Veuillez autoriser Dressur a enregistrer automatiquement les contacts dans votre téléphone.\nCette autorisation est nécéssaire pour profiter pleinement de nos fonctionnalités.",
        context,
      );
    }
  }

  Future<void> _lancer() async {
    final String? erreur = await _service.start(_etendreAuxNonDS);
    if (erreur != null && mounted) {
      warningNoti("Attention !", erreur, context);
    }
  }

  Future<void> _confirmerArret() async {
    final bool isFr = langUserPhone == 'fr';
    final bool? confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          isFr ? 'Arrêter la synchronisation ?' : 'Stop the synchronization?',
          style: GoogleFonts.poppins(fontWeight: FontWeight.w600),
        ),
        content: Text(
          isFr
              ? 'La synchronisation en cours sera interrompue.'
              : 'The ongoing synchronization will be interrupted.',
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

  // ── Helpers UI ─────────────────────────────────────────────────────────────

  Widget _buildWarningRow(String text) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        FaIcon(FontAwesomeIcons.triangleExclamation,
            color: Colors.red[700], size: 16),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            text,
            style: GoogleFonts.poppins(
                color: Colors.red[800], fontWeight: FontWeight.w500),
          ),
        ),
      ],
    );
  }

  Widget _buildInfoRow(String text) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(top: 2.0),
          child: FaIcon(FontAwesomeIcons.circleCheck,
              size: 13, color: Colors.blue[400]),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style:
                GoogleFonts.poppins(fontSize: 12, color: Colors.blue[800]),
          ),
        ),
      ],
    );
  }

  Widget _buildSummaryRow(IconData icon, int count, String label) {
    return Row(
      children: [
        FaIcon(icon, size: 14, color: Colors.green[600]),
        const SizedBox(width: 10),
        Text(
          '$count $label',
          style:
              GoogleFonts.poppins(fontSize: 13, color: Colors.green[800]),
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

    return Scaffold(
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          isFr ? "Synchronisation avancée" : "Advanced synchronization",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
        leading: IconButton(
          onPressed: () => Navigator.pop(context),
          icon: const FaIcon(FontAwesomeIcons.chevronLeft,
              color: Colors.white),
        ),
      ),
      body: SingleChildScrollView(
        padding:
            const EdgeInsets.symmetric(horizontal: 20.0, vertical: 30.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            // ── ICÔNE ──────────────────────────────────────────────────────
            FadeIn(
              duration: const Duration(milliseconds: 500),
              child: FaIcon(FontAwesomeIcons.lock,
                  size: 72, color: primaryColor),
            ),
            const SizedBox(height: 16),

            // ── TITRE ──────────────────────────────────────────────────────
            FadeInUp(
              from: 20,
              duration: const Duration(milliseconds: 500),
              delay: const Duration(milliseconds: 200),
              child: Text(
                isFr
                    ? "Synchronisation Avancée"
                    : "Advanced Synchronization",
                textAlign: TextAlign.center,
                style: GoogleFonts.poppins(
                    fontSize: 24, fontWeight: FontWeight.bold),
              ),
            ),
            const SizedBox(height: 20),

            // ── BANNIÈRE "EN COURS" — rappel navigation possible ───────────
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
                              ? "Synchronisation en cours, vous pouvez naviguer librement, la progression est visible ici et dans vos notifications."
                              : "Synchronization in progress, you can navigate freely, progress is shown here and in your notifications.",
                          style: GoogleFonts.poppins(
                              fontSize: 12, color: Colors.blue[700]),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],

            // ── VUE INITIALE (pas en cours, pas terminé, pas d'erreur) ─────
            if (!enCour && !termine && !erreur && !annule) ...[

              // Avertissements
              FadeInUp(
                from: 20,
                duration: const Duration(milliseconds: 500),
                delay: const Duration(milliseconds: 300),
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
                            ? "L'opération peut être longue selon le nombre de contacts."
                            : "The operation may take a while depending on your contacts.",
                      ),
                      const SizedBox(height: 10),
                      _buildWarningRow(
                        isFr
                            ? "Vous pouvez quitter la page, la synchro continuera en arrière-plan."
                            : "You can leave this page, the sync will continue in background.",
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 14),

              // Option étendue
              FadeInUp(
                from: 20,
                duration: const Duration(milliseconds: 500),
                delay: const Duration(milliseconds: 400),
                child: Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 16, vertical: 12),
                  decoration: BoxDecoration(
                    color: _etendreAuxNonDS
                        ? primaryColor.withOpacity(0.07)
                        : Colors.grey.withOpacity(0.07),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: _etendreAuxNonDS
                          ? primaryColor.withOpacity(0.3)
                          : Colors.grey.withOpacity(0.2),
                    ),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isFr
                                  ? "Nettoyer aussi mes contacts personnels"
                                  : "Also clean my personal contacts",
                              style: GoogleFonts.poppins(
                                fontWeight: FontWeight.w600,
                                fontSize: 14,
                                color: _etendreAuxNonDS
                                    ? primaryColor
                                    : Colors.grey[700],
                              ),
                            ),
                            const SizedBox(height: 3),
                            Text(
                              isFr
                                  ? "Supprimer aussi les doublons dans vos contacts personnels (hors Dressur)."
                                  : "Also remove duplicates in personal contacts (non-Dressur).",
                              style: GoogleFonts.poppins(
                                fontSize: 12,
                                color: Colors.grey[500],
                              ),
                            ),
                          ],
                        ),
                      ),
                      Switch(
                        value: _etendreAuxNonDS,
                        onChanged: (val) =>
                            setState(() => _etendreAuxNonDS = val),
                        activeColor: primaryColor,
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 12),

              // Ce que va faire la synchro
              FadeInUp(
                from: 20,
                duration: const Duration(milliseconds: 500),
                delay: const Duration(milliseconds: 480),
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
                                ? "Ce que va faire la synchronisation :"
                                : "What the synchronization will do:",
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
                            ? _etendreAuxNonDS
                                ? "Fusionner les doublons parmi TOUS vos contacts (Dressur + personnels)"
                                : "Fusionner les doublons parmi vos contacts Dressur"
                            : _etendreAuxNonDS
                                ? "Merge duplicates among ALL your contacts (Dressur + personal)"
                                : "Merge duplicates among your Dressur contacts",
                      ),
                      const SizedBox(height: 4),
                      _buildInfoRow(
                        isFr
                            ? "Créer les nouveaux contacts Dressur manquants dans votre téléphone"
                            : "Create missing new Dressur contacts on your phone",
                      ),
                      const SizedBox(height: 4),
                      _buildInfoRow(
                        isFr
                            ? "Mettre à jour le nom des contacts Dressur déjà enregistrés"
                            : "Update the name of already saved Dressur contacts",
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // Bouton démarrer
              FadeInUp(
                from: 20,
                duration: const Duration(milliseconds: 500),
                delay: const Duration(milliseconds: 560),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: _lancer,
                    icon: const FaIcon(FontAwesomeIcons.solidCirclePlay),
                    label: Text(
                      isFr
                          ? "Démarrer la Synchronisation"
                          : "Start Synchronization",
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: primaryColor,
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
            ],

            // ── VUE EN COURS ───────────────────────────────────────────────
            if (enCour) ...[
              ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: LinearProgressIndicator(
                  value: _service.progress,
                  minHeight: 10,
                  backgroundColor: Colors.grey[200],
                  color: primaryColor,
                ),
              ),
              const SizedBox(height: 12),
              Text(
                _service.statusText,
                style: GoogleFonts.poppins(
                    fontSize: 13, color: Colors.grey[600]),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 20),
              // Bouton désactivé pendant la synchro
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: null,
                  icon: const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(
                        strokeWidth: 2, color: Colors.white),
                  ),
                  label: Text(
                    isFr
                        ? "Synchronisation en cours…"
                        : "Synchronization in progress…",
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    textStyle: GoogleFonts.poppins(
                        fontSize: 15, fontWeight: FontWeight.bold),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ),

              // ── BOUTON D'ARRÊT ─────────────────────────────────────────────
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

            // ── VUE TERMINÉE ───────────────────────────────────────────────
            if (termine && !enCour) ...[
              FadeInUp(
                duration: const Duration(milliseconds: 400),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.green[50],
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                        color: Colors.green.withOpacity(0.3)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          FaIcon(FontAwesomeIcons.circleCheck,
                              color: Colors.green[700], size: 16),
                          const SizedBox(width: 8),
                          Text(
                            isFr
                                ? "Résumé de la synchronisation"
                                : "Sync Summary",
                            style: GoogleFonts.poppins(
                              fontWeight: FontWeight.w600,
                              fontSize: 14,
                              color: Colors.green[800],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      _buildSummaryRow(
                        FontAwesomeIcons.userPlus,
                        _service.nbCreated,
                        isFr ? "contact(s) créé(s)" : "contact(s) created",
                      ),
                      const SizedBox(height: 6),
                      _buildSummaryRow(
                        FontAwesomeIcons.penToSquare,
                        _service.nbUpdated,
                        isFr
                            ? "contact(s) mis à jour"
                            : "contact(s) updated",
                      ),
                      const SizedBox(height: 6),
                      _buildSummaryRow(
                        FontAwesomeIcons.codeMerge,
                        _service.nbMerged,
                        isFr
                            ? "doublon(s) fusionné(s)"
                            : "duplicate(s) merged",
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // Bouton relancer
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () => _service.reset(),
                  icon: const FaIcon(FontAwesomeIcons.arrowRotateLeft,
                      size: 16),
                  label: Text(
                    isFr
                        ? "Relancer une synchronisation"
                        : "Run synchronization again",
                  ),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: primaryColor,
                    side: BorderSide(color: primaryColor),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    textStyle: GoogleFonts.poppins(
                        fontSize: 14, fontWeight: FontWeight.w600),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ),
            ],

            // ── VUE ANNULÉE ────────────────────────────────────────────────
            if (annule && !enCour) ...[
              FadeInUp(
                duration: const Duration(milliseconds: 400),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.orange.withOpacity(0.07),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.orange.withOpacity(0.3)),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      FaIcon(FontAwesomeIcons.circleStop,
                          color: Colors.orange[700], size: 16),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          _service.statusText,
                          style: GoogleFonts.poppins(
                              fontSize: 13,
                              color: Colors.orange[800],
                              fontWeight: FontWeight.w500),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () => _service.reset(),
                  icon: const FaIcon(FontAwesomeIcons.arrowsRotate),
                  label: Text(isFr
                      ? "Recommencer la synchronisation"
                      : "Run synchronization again"),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    textStyle: GoogleFonts.poppins(
                        fontSize: 15, fontWeight: FontWeight.bold),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ),
              const SizedBox(height: 8),
              SizedBox(
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
            ],

            // ── VUE ERREUR ─────────────────────────────────────────────────
            if (erreur && !enCour) ...[
              FadeInUp(
                duration: const Duration(milliseconds: 400),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.red[50],
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                        color: Colors.red.withOpacity(0.3)),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      FaIcon(FontAwesomeIcons.circleExclamation,
                          color: Colors.red[700], size: 16),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          _service.errorText ?? "",
                          style: GoogleFonts.poppins(
                              fontSize: 13, color: Colors.red[800]),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // Bouton réessayer
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () {
                    _service.reset();
                  },
                  icon: const FaIcon(FontAwesomeIcons.arrowRotateLeft,
                      size: 16),
                  label: Text(
                    isFr ? "Réessayer" : "Try again",
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
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
          ],
        ),
      ),
    );
  }
}
