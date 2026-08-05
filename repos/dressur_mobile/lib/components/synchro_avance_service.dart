import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:http/http.dart' as http;
import 'package:permission_handler/permission_handler.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/sql_helper.dart';
import 'package:dressur/components/noti_sys.dart';

class SynchroAvanceService extends ChangeNotifier {
  // ── Singleton ─────────────────────────────────────────────────────────────
  static final SynchroAvanceService _instance =
      SynchroAvanceService._internal();
  factory SynchroAvanceService() => _instance;
  SynchroAvanceService._internal();

  // ── État ──────────────────────────────────────────────────────────────────
  bool isRunning = false;
  bool isCompleted = false;
  bool isCancelled = false;
  double progress = 0.0;
  int nbCreated = 0;
  int nbUpdated = 0;
  int nbMerged = 0;
  bool etendreAuxNonDS = false;
  String statusText = "";
  String? errorText;

  bool _cancelRequested = false;

  bool get hasResult => isCompleted || errorText != null || isCancelled;

  /// Vrai dès que l'utilisateur a demandé l'arrêt mais que la boucle
  /// n'a pas encore fini son itération courante.
  bool get isCancelPending => _cancelRequested && isRunning;

  // ── Annulation ────────────────────────────────────────────────────────────

  void cancel() {
    if (_cancelRequested) return;
    _cancelRequested = true;
    statusText = langUserPhone == 'fr'
        ? "Annulation en cours, veuillez patienter…"
        : "Cancellation in progress, please wait…";
    notifyListeners();
  }

  // ── Réinitialisation ──────────────────────────────────────────────────────
  void reset() {
    isRunning = false;
    isCompleted = false;
    isCancelled = false;
    _cancelRequested = false;
    progress = 0.0;
    nbCreated = 0;
    nbUpdated = 0;
    nbMerged = 0;
    statusText = "";
    errorText = null;
    notifyListeners();
  }

  // ── Helpers internes ──────────────────────────────────────────────────────
  String _normalizeNumber(String tel) {
    final String n = tel.replaceAll(" ", "").replaceAll("-", "");
    if (n.startsWith("+22901") && n.length > 6) {
      return "+229${n.substring(6)}";
    }
    return n;
  }

  Set<String> _computeDuplicatesToDelete(List<Contact> contacts) {
    final Map<String, List<Contact>> grouped = {};
    for (final c in contacts) {
      for (final p in c.phones) {
        final String norm = _normalizeNumber(p.number);
        grouped.putIfAbsent(norm, () => []);
        if (!grouped[norm]!.any((x) => x.id == c.id)) {
          grouped[norm]!.add(c);
        }
      }
    }
    final Set<String> toDelete = {};
    for (final group in grouped.values) {
      if (group.length > 1) {
        group.sort((a, b) => b.phones.length.compareTo(a.phones.length));
        for (int i = 1; i < group.length; i++) {
          toDelete.add(group[i].id);
        }
      }
    }
    return toDelete;
  }

  Contact? _findDSContactByTel(List<Contact> dsContacts, String tel) {
    final String normTel = _normalizeNumber(tel);
    for (final c in dsContacts) {
      for (final p in c.phones) {
        if (_normalizeNumber(p.number) == normTel) return c;
      }
    }
    return null;
  }

  void _applyCancel(bool isFr) {
    cancelSynchroAvanceNotification();
    isRunning = false;
    isCancelled = true;
    statusText = isFr
        ? "Synchronisation interrompue (${(progress * 100).round()} % effectué)."
        : "Synchronization interrupted (${(progress * 100).round()} % done).";
    textChargementEvolution = statusText;
    notifyListeners();
  }

  // ── Démarrage ─────────────────────────────────────────────────────────────
  Future<String?> start(bool etendreOption) async {
    if (isRunning) return null;

    final bool isFr = langUserPhone == 'fr';

    PermissionStatus contactPerm = await Permission.contacts.status;
    if (contactPerm != PermissionStatus.granted) {
      contactPerm = await Permission.contacts.request();
    }
    if (contactPerm != PermissionStatus.granted) {
      return isFr
          ? "Veuillez autoriser Dressur à accéder à vos contacts."
          : "Please allow Dressur to access your contacts.";
    }

    await Permission.notification.request();

    isRunning = true;
    isCompleted = false;
    isCancelled = false;
    _cancelRequested = false;
    progress = 0.0;
    nbCreated = 0;
    nbUpdated = 0;
    nbMerged = 0;
    errorText = null;
    etendreAuxNonDS = etendreOption;
    statusText = isFr
        ? "Recherche de vos contacts DS..."
        : "Finding your DS contacts...";
    textChargementEvolution = statusText;
    notifyListeners();

    try {
      final url = Uri.parse('$generalRouteForApi/listContactDS/$uidUser/fr');
      final response = await http.get(url);

      if (_cancelRequested) {
        _applyCancel(isFr);
        return null;
      }

      if (response.statusCode != 200) {
        throw Exception("Erreur API: ${response.statusCode}");
      }

      final jsonData = jsonDecode(response.body) as List<dynamic>;

      if (jsonData.isEmpty) {
        await cancelSynchroAvanceNotification();
        isRunning = false;
        isCompleted = true;
        statusText = isFr
            ? "Vous n'avez aucun contact DS, la synchronisation n'est pas nécessaire."
            : "You don't have any DS contacts, synchronization is not needed.";
        textChargementEvolution = statusText;
        notifyListeners();
        return null;
      }

      final int totalDSContacts = jsonData.length;

      // ── ÉTAPE 1 : Fusion des doublons ────────────────────────────────────
      if (_cancelRequested) {
        _applyCancel(isFr);
        return null;
      }

      statusText = isFr
          ? "Détection des doublons${etendreAuxNonDS ? '' : ' DS'}..."
          : "Detecting${etendreAuxNonDS ? '' : ' DS'} duplicates...";
      textChargementEvolution = statusText;
      notifyListeners();

      List<Contact> allContacts = await FlutterContacts.getContacts(
          withProperties: true, withAccounts: true);

      if (_cancelRequested) {
        _applyCancel(isFr);
        return null;
      }

      final List<Contact> dsContacts =
          allContacts.where((c) => c.name.first.endsWith("#DS")).toList();

      final Set<String> dsToDeleteIds = _computeDuplicatesToDelete(dsContacts);
      final Set<String> nonDSToDeleteIds = etendreAuxNonDS
          ? _computeDuplicatesToDelete(
              allContacts.where((c) => !c.name.first.endsWith("#DS")).toList())
          : {};
      final List<String> toDeleteList =
          {...dsToDeleteIds, ...nonDSToDeleteIds}.toList();

      int deletedCount = 0;
      for (int i = 0; i < toDeleteList.length; i++) {
        if (_cancelRequested) {
          _applyCancel(isFr);
          return null;
        }

        final Contact? toDelete = allContacts
            .cast<Contact?>()
            .firstWhere((x) => x?.id == toDeleteList[i], orElse: () => null);
        if (toDelete != null) {
          await FlutterContacts.deleteContacts([toDelete]);
          if (_cancelRequested) {
            _applyCancel(isFr);
            return null;
          }
          deletedCount++;
        }
        final double p =
            (i + 1) / (toDeleteList.isNotEmpty ? toDeleteList.length : 1) * 0.3;
        progress = p;
        final bool isDS = dsToDeleteIds.contains(toDeleteList[i]);
        statusText = isFr
            ? "Fusion ${isDS ? 'DS' : 'contacts personnels'}... ($deletedCount supprimé(s))"
            : "Merging ${isDS ? 'DS' : 'personal contacts'}... ($deletedCount removed)";
        textChargementEvolution = statusText;
        await showSynchroAvanceProgress(progress, statusText);
        notifyListeners();
      }

      progress = 0.3;
      nbMerged = deletedCount;
      if (toDeleteList.isEmpty) {
        statusText = isFr ? "Aucun doublon détecté." : "No duplicates found.";
        textChargementEvolution = statusText;
      }
      notifyListeners();

      // ── ÉTAPE 2 : Analyse contacts locaux ────────────────────────────────
      if (_cancelRequested) {
        _applyCancel(isFr);
        return null;
      }

      statusText = isFr
          ? "Analyse de vos contacts locaux..."
          : "Analyzing your local contacts...";
      textChargementEvolution = statusText;
      await showSynchroAvanceProgress(progress, statusText);
      notifyListeners();

      await SQLHelper.viderLaBaseDeDonneeLocalTelUser();
      allContacts = await FlutterContacts.getContacts(
          withProperties: true, withAccounts: true);

      if (_cancelRequested) {
        _applyCancel(isFr);
        return null;
      }

      final int totalLocalContacts = allContacts.length;

      for (int i = 0; i < totalLocalContacts; i++) {
        if (_cancelRequested) {
          _applyCancel(isFr);
          return null;
        }

        final contact = allContacts[i];
        for (final phone in contact.phones) {
          final String numberTel =
              phone.number.replaceAll(" ", "").replaceAll("-", "");
          if ((await SQLHelper.getOneNumsTelUser(numberTel)).isEmpty) {
            await insertNumTelUserIntoDataBase(numberTel);
          }
        }
        progress = 0.3 + (i + 1) / totalLocalContacts * 0.3;
        statusText = isFr
            ? "Analyse des contacts locaux... (${i + 1} / $totalLocalContacts)"
            : "Analyzing local contacts... (${i + 1} / $totalLocalContacts)";
        textChargementEvolution = statusText;
        await showSynchroAvanceProgress(progress, statusText);
        notifyListeners();
      }

      // ── ÉTAPE 3 : Synchronisation DS ─────────────────────────────────────
      if (_cancelRequested) {
        _applyCancel(isFr);
        return null;
      }

      statusText = isFr
          ? "Synchronisation des contacts DS..."
          : "Synchronizing DS contacts...";
      textChargementEvolution = statusText;
      await showSynchroAvanceProgress(progress, statusText);
      notifyListeners();

      final List<Contact> freshDSContacts =
          allContacts.where((c) => c.name.first.endsWith("#DS")).toList();

      for (int i = 0; i < totalDSContacts; i++) {
        if (_cancelRequested) {
          _applyCancel(isFr);
          return null;
        }

        final contactData = jsonData[i];
        final String tel = (contactData["tel"] as String);
        final String telSansPlus = tel.replaceAll("+", "");
        final String nom = (contactData["nom"] ?? "").toString().trim();
        final String pseudo = (contactData["pseudo"] ?? "").toString().trim();
        final List<String> nameParts =
            [nom, pseudo, telSansPlus].where((s) => s.isNotEmpty).toList();
        final String expectedName = "${nameParts.join(" - ")} #DS";

        final List<Phone> phonesList = [Phone(tel)];
        if (tel.startsWith("+229") && !tel.startsWith("+22901")) {
          final String afterCode = tel.substring(4);
          phonesList.add(Phone("+22901$afterCode"));
        }

        if ((await SQLHelper.getOneNumsTelUser(tel)).isEmpty) {
          final newContact = Contact()
            ..name.first = expectedName
            ..phones = phonesList
            ..accounts = (selectedContactAccountName != null &&
                    selectedContactAccountType != null)
                ? [
                    Account('', selectedContactAccountType!,
                        selectedContactAccountName!, [])
                  ]
                : [];
          await newContact.insert().timeout(
                const Duration(seconds: 15),
                onTimeout: () => Contact(),
              );
          if (_cancelRequested) {
            _applyCancel(isFr);
            return null;
          }
          await insertNumTelUserIntoDataBase(tel);
          nbCreated++;
        } else {
          final Contact? existing = _findDSContactByTel(freshDSContacts, tel);
          if (existing != null && existing.name.first != expectedName) {
            final Contact? fullContact = await FlutterContacts.getContact(
              existing.id,
              withProperties: true,
              withAccounts: true,
            );
            if (fullContact != null) {
              fullContact.name.first = expectedName;
              fullContact.phones = phonesList;
              if (selectedContactAccountName != null &&
                  selectedContactAccountType != null) {
                fullContact.accounts = [
                  Account('', selectedContactAccountType!,
                      selectedContactAccountName!, [])
                ];
              }
              await fullContact.update().timeout(
                    const Duration(seconds: 15),
                    onTimeout: () => Contact(),
                  );
              if (_cancelRequested) {
                _applyCancel(isFr);
                return null;
              }
              nbUpdated++;
            }
          }
        }

        progress = 0.6 + (i + 1) / totalDSContacts * 0.4;
        statusText = isFr
            ? "Synchronisation DS... (${i + 1} / $totalDSContacts)"
            : "DS sync... (${i + 1} / $totalDSContacts)";
        textChargementEvolution = statusText;
        await showSynchroAvanceProgress(progress, statusText);
        notifyListeners();
      }

      // ── TERMINÉ ───────────────────────────────────────────────────────────
      await showSynchroAvanceComplete(nbCreated, nbUpdated, nbMerged);
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(
          'lastSynchroAvanceDate', DateTime.now().toIso8601String());
      isRunning = false;
      isCompleted = true;
      progress = 1.0;
      statusText = isFr
          ? "Synchronisation terminée avec succès !"
          : "Synchronization completed successfully!";
      textChargementEvolution = statusText;
      notifyListeners();
    } catch (e) {
      await cancelSynchroAvanceNotification();
      isRunning = false;
      errorText =
          isFr ? "Une erreur est survenue : $e" : "An error occurred: $e";
      notifyListeners();
    }

    return null;
  }
}
