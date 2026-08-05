import 'package:flutter/foundation.dart';
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/noti_sys.dart';

class DSDeletionService extends ChangeNotifier {
  // ── Singleton ─────────────────────────────────────────────────────────────
  static final DSDeletionService _instance = DSDeletionService._internal();
  factory DSDeletionService() => _instance;
  DSDeletionService._internal();

  // ── État ──────────────────────────────────────────────────────────────────
  bool isRunning = false;
  bool isCompleted = false;
  bool isCancelled = false;
  int current = 0;
  int total = 0;
  int totalSupprime = 0;
  String statusText = "";
  String? errorText;

  bool _cancelRequested = false;

  double get progress => total > 0 ? current / total : 0.0;
  bool get hasResult => isCompleted || errorText != null || isCancelled;

  /// Vrai dès que l'utilisateur a demandé l'arrêt mais que la boucle
  /// n'a pas encore fini son itération courante.
  bool get isCancelPending => _cancelRequested && isRunning;

  // ── Actions ───────────────────────────────────────────────────────────────

  /// Demande l'interruption du traitement en cours.
  /// Déclenche une notification immédiate à l'UI sans attendre la fin de
  /// l'itération courante.
  void cancel() {
    if (_cancelRequested) return; // déjà demandé
    _cancelRequested = true;
    statusText = langUserPhone == 'fr'
        ? "Annulation en cours, veuillez patienter…"
        : "Cancellation in progress, please wait…";
    notifyListeners(); // feedback immédiat → le bouton se grise
  }

  /// Réinitialise l'état pour permettre une nouvelle suppression.
  void reset() {
    isRunning = false;
    isCompleted = false;
    isCancelled = false;
    _cancelRequested = false;
    current = 0;
    total = 0;
    totalSupprime = 0;
    statusText = "";
    errorText = null;
    notifyListeners();
  }

  /// Lance la suppression des contacts DS.
  Future<String?> start() async {
    if (isRunning) return null;

    final bool isFr = langUserPhone == 'fr';

    // Permission contacts
    PermissionStatus contactPerm = await Permission.contacts.status;
    if (contactPerm != PermissionStatus.granted) {
      contactPerm = await Permission.contacts.request();
    }
    if (contactPerm != PermissionStatus.granted) {
      return isFr
          ? "Veuillez autoriser Dressur à accéder à vos contacts."
          : "Please allow Dressur to access your contacts.";
    }

    // Permission notifications (Android 13+)
    await Permission.notification.request();

    // Démarrage
    isRunning = true;
    isCompleted = false;
    isCancelled = false;
    _cancelRequested = false;
    current = 0;
    total = 0;
    totalSupprime = 0;
    errorText = null;
    statusText =
        isFr ? "Recherche des contacts DS…" : "Looking for DS contacts…";
    notifyListeners();

    try {
      final List<Contact> tousLesContacts =
          await FlutterContacts.getContacts(withProperties: true, withAccounts: true);

      if (_cancelRequested) { _applyCancel(isFr); return null; }

      final List<Contact> dsContacts = tousLesContacts
          .where((c) => c.displayName.contains('#DS'))
          .toList();

      if (dsContacts.isEmpty) {
        await cancelDSDeletionNotification();
        isRunning = false;
        isCompleted = true;
        totalSupprime = 0;
        statusText =
            isFr ? "Aucun contact DS trouvé." : "No DS contacts found.";
        notifyListeners();
        return null;
      }

      total = dsContacts.length;
      statusText = isFr
          ? "$total contact(s) DS détecté(s). Suppression en cours…"
          : "$total DS contact(s) detected. Deletion in progress…";
      notifyListeners();

      await showDSDeletionProgress(0, total);

      for (final contact in dsContacts) {
        if (_cancelRequested) break;

        await contact.delete();

        if (_cancelRequested) break; // vérification post-delete

        current++;
        await showDSDeletionProgress(current, total);
        statusText = isFr
            ? "Suppression… ($current / $total)"
            : "Deleting… ($current / $total)";
        notifyListeners();
      }

      if (_cancelRequested) {
        _applyCancel(isFr);
        return null;
      }

      // ── Terminé ───────────────────────────────────────────────────────────
      await showDSDeletionComplete(current);
      isRunning = false;
      isCompleted = true;
      totalSupprime = current;
      statusText = isFr
          ? "$current contact(s) DS supprimé(s) avec succès."
          : "$current DS contact(s) successfully deleted.";
      notifyListeners();
    } catch (e) {
      await cancelDSDeletionNotification();
      isRunning = false;
      errorText =
          isFr ? "Une erreur est survenue : $e" : "An error occurred: $e";
      notifyListeners();
    }

    return null;
  }

  void _applyCancel(bool isFr) {
    cancelDSDeletionNotification();
    isRunning = false;
    isCancelled = true;
    statusText = isFr
        ? "Suppression interrompue. $current / $total contact(s) traité(s)."
        : "Deletion interrupted. $current / $total contact(s) processed.";
    notifyListeners();
  }
}
