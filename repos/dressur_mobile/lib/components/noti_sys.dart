import 'package:dressur/components/constant.dart';
import 'package:dressur/main.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:timezone/timezone.dart' as tz;

int id = 0;

// ── IDs réservés ──────────────────────────────────────────────────────────────
const int _boostReminderNotifId    = 99;
const int _boostReminder48hNotifId = 100;
const int _promoReminderNotifId    = 101;
const int _promoReminder48hNotifId = 102;
const int _dsDeletionNotifId       = 200;

// ── Notifications immédiates ───────────────────────────────────────────────────

Future<void> showNotification(title, body) async {
  const AndroidNotificationDetails androidNotificationDetails =
      AndroidNotificationDetails('your channel id', 'your channel name',
          channelDescription: 'your channel description',
          importance: Importance.max,
          priority: Priority.high,
          ticker: 'ticker');
  const NotificationDetails notificationDetails =
      NotificationDetails(android: androidNotificationDetails);
  await flutterLocalNotificationsPlugin
      .show(id++, title, body, notificationDetails, payload: 'item x');
}

Future<void> showNotificationTimeOutAfter(
    String title, String body, int timeOut) async {
  AndroidNotificationDetails androidNotificationDetails =
      AndroidNotificationDetails(
    'your channel id',
    'your channel name',
    channelDescription: 'your channel description',
    importance: Importance.max,
    priority: Priority.high,
    ticker: 'ticker',
    timeoutAfter: timeOut,
  );
  NotificationDetails notificationDetails =
      NotificationDetails(android: androidNotificationDetails);
  await flutterLocalNotificationsPlugin.show(
    id++,
    title,
    body,
    notificationDetails,
    payload: 'item x',
  );
}

// ── Rappels Boost Contact ──────────────────────────────────────────────────────

/// Planifie deux notifications de rappel après l'inscription :
///   - 24h : rappel doux si pas de boost
///   - 48h : rappel urgent si toujours pas de boost
Future<void> scheduleBoostReminderNotification() async {
  await flutterLocalNotificationsPlugin.cancel(_boostReminderNotifId);
  await flutterLocalNotificationsPlugin.cancel(_boostReminder48hNotifId);

  final bool isFr = langUserPhone == 'fr';

  const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
    'boost_reminder_channel',
    'Rappel Boost Contact',
    channelDescription:
        'Rappels pour effectuer votre premier boost contact après inscription',
    importance: Importance.max,
    priority: Priority.high,
    ticker: 'Dressur',
  );
  const NotificationDetails notifDetails =
      NotificationDetails(android: androidDetails);

  // 24h
  await flutterLocalNotificationsPlugin.zonedSchedule(
    _boostReminderNotifId,
    isFr ? 'Rappel Dressur 🚀' : 'Dressur Reminder 🚀',
    isFr
        ? "Tu n'as pas encore boosté ton contact ! 10 personnes attendent de te voir."
        : "You haven't boosted your contact yet! 10 people are waiting to see you.",
    tz.TZDateTime.now(tz.local).add(const Duration(hours: 24)),
    notifDetails,
    androidScheduleMode: AndroidScheduleMode.exactAllowWhileIdle,
    uiLocalNotificationDateInterpretation:
        UILocalNotificationDateInterpretation.absoluteTime,
    payload: 'boost_reminder_24h',
  );

  // 48h
  await flutterLocalNotificationsPlugin.zonedSchedule(
    _boostReminder48hNotifId,
    isFr ? '⏰ Tu passes à côté !' : '⏰ You\'re missing out!',
    isFr
        ? "Ça fait 2 jours ! Des dizaines de personnes attendent de te découvrir. Lance ton premier boost maintenant 👇"
        : "It's been 2 days! Dozens of people are waiting to discover you. Start your first boost now 👇",
    tz.TZDateTime.now(tz.local).add(const Duration(hours: 48)),
    notifDetails,
    androidScheduleMode: AndroidScheduleMode.exactAllowWhileIdle,
    uiLocalNotificationDateInterpretation:
        UILocalNotificationDateInterpretation.absoluteTime,
    payload: 'boost_reminder_48h',
  );
}

/// Annule les rappels boost dès que l'utilisateur effectue son premier boost.
Future<void> cancelBoostReminderNotification() async {
  await flutterLocalNotificationsPlugin.cancel(_boostReminderNotifId);
  await flutterLocalNotificationsPlugin.cancel(_boostReminder48hNotifId);
}

// ── Rappels Promo Affaire ──────────────────────────────────────────────────────

/// Planifie deux notifications de rappel pour inciter à créer une première promo :
///   - 24h : rappel doux
///   - 48h : rappel plus incitatif
Future<void> schedulePromoReminderNotification() async {
  await flutterLocalNotificationsPlugin.cancel(_promoReminderNotifId);
  await flutterLocalNotificationsPlugin.cancel(_promoReminder48hNotifId);

  final bool isFr = langUserPhone == 'fr';

  const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
    'promo_reminder_channel',
    'Rappel Promotion',
    channelDescription:
        'Rappels pour créer votre première promotion après inscription',
    importance: Importance.max,
    priority: Priority.high,
    ticker: 'Dressur',
  );
  const NotificationDetails notifDetails =
      NotificationDetails(android: androidDetails);

  // 24h
  await flutterLocalNotificationsPlugin.zonedSchedule(
    _promoReminderNotifId,
    isFr ? '📣 Ta business n\'est pas encore visible !' : '📣 Your business isn\'t visible yet!',
    isFr
        ? "Tu n'as pas encore créé de promotion ! Des milliers d'utilisateurs pourraient découvrir ta business dès aujourd'hui."
        : "You haven't created a promotion yet! Thousands of users could discover your business today.",
    tz.TZDateTime.now(tz.local).add(const Duration(hours: 24)),
    notifDetails,
    androidScheduleMode: AndroidScheduleMode.exactAllowWhileIdle,
    uiLocalNotificationDateInterpretation:
        UILocalNotificationDateInterpretation.absoluteTime,
    payload: 'promo_reminder_24h',
  );

  // 48h
  await flutterLocalNotificationsPlugin.zonedSchedule(
    _promoReminder48hNotifId,
    isFr ? '🔥 2 jours sans promo, c\'est trop long !' : '🔥 2 days without a promo is too long!',
    isFr
        ? "Des milliers d'utilisateurs ne te voient pas encore. Crée ta première promotion maintenant et booste ta visibilité 👇"
        : "Thousands of users still can't see you. Create your first promotion now and boost your visibility 👇",
    tz.TZDateTime.now(tz.local).add(const Duration(hours: 48)),
    notifDetails,
    androidScheduleMode: AndroidScheduleMode.exactAllowWhileIdle,
    uiLocalNotificationDateInterpretation:
        UILocalNotificationDateInterpretation.absoluteTime,
    payload: 'promo_reminder_48h',
  );
}

/// Annule les rappels promo dès que l'utilisateur crée sa première promotion.
Future<void> cancelPromoReminderNotification() async {
  await flutterLocalNotificationsPlugin.cancel(_promoReminderNotifId);
  await flutterLocalNotificationsPlugin.cancel(_promoReminder48hNotifId);
}

// ── Suppression contacts DS ────────────────────────────────────────────────────

/// Affiche (ou met à jour) une notification avec barre de progression pendant
/// la suppression des contacts DS. L'ID est fixe pour écraser la précédente.
Future<void> showDSDeletionProgress(int current, int total) async {
  final bool isFr = langUserPhone == 'fr';
  final AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
    'ds_deletion_channel',
    isFr ? 'Suppression contacts DS' : 'DS contact deletion',
    channelDescription:
        isFr ? 'Progression de la suppression des contacts DS' : 'DS contact deletion progress',
    importance: Importance.low,
    priority: Priority.low,
    showProgress: true,
    maxProgress: total,
    progress: current,
    ongoing: true,
    autoCancel: false,
    onlyAlertOnce: true,
    playSound: false,
    enableVibration: false,
  );
  final NotificationDetails notifDetails =
      NotificationDetails(android: androidDetails);

  await flutterLocalNotificationsPlugin.show(
    _dsDeletionNotifId,
    isFr ? '🧹 Suppression en cours…' : '🧹 Deletion in progress…',
    isFr
        ? '$current / $total contact(s) DS supprimé(s)'
        : '$current / $total DS contact(s) deleted',
    notifDetails,
  );
}

/// Remplace la notification de progression par une notification de succès
/// une fois la suppression terminée.
Future<void> showDSDeletionComplete(int totalSupprime) async {
  final bool isFr = langUserPhone == 'fr';
  await flutterLocalNotificationsPlugin.cancel(_dsDeletionNotifId);

  final AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
    'ds_deletion_channel',
    isFr ? 'Suppression contacts DS' : 'DS contact deletion',
    channelDescription: isFr
        ? 'Progression de la suppression des contacts DS'
        : 'DS contact deletion progress',
    importance: Importance.defaultImportance,
    priority: Priority.defaultPriority,
    autoCancel: true,
  );
  final NotificationDetails notifDetails =
      NotificationDetails(android: androidDetails);

  await flutterLocalNotificationsPlugin.show(
    _dsDeletionNotifId,
    isFr ? '✅ Suppression terminée' : '✅ Deletion complete',
    isFr
        ? '$totalSupprime contact(s) DS supprimé(s) avec succès.'
        : '$totalSupprime DS contact(s) successfully deleted.',
    notifDetails,
  );
}

/// Annule la notification de suppression DS (si l'utilisateur annule ou en cas d'erreur).
Future<void> cancelDSDeletionNotification() async {
  await flutterLocalNotificationsPlugin.cancel(_dsDeletionNotifId);
}

// ── Synchronisation avancée ────────────────────────────────────────────────────

const int _synchroAvanceNotifId = 201;

/// Affiche (ou met à jour) une notification avec barre de progression pendant
/// la synchronisation avancée. L'ID est fixe pour écraser la précédente.
Future<void> showSynchroAvanceProgress(
    double progress, String statusText) async {
  final bool isFr = langUserPhone == 'fr';
  final int progressInt = (progress * 100).round().clamp(0, 100);

  final AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
    'synchro_avance_channel',
    isFr ? 'Synchronisation avancée' : 'Advanced synchronization',
    channelDescription: isFr
        ? 'Progression de la synchronisation avancée des contacts'
        : 'Advanced contact synchronization progress',
    importance: Importance.low,
    priority: Priority.low,
    showProgress: true,
    maxProgress: 100,
    progress: progressInt,
    ongoing: true,
    autoCancel: false,
    onlyAlertOnce: true,
    playSound: false,
    enableVibration: false,
  );
  final NotificationDetails notifDetails =
      NotificationDetails(android: androidDetails);

  await flutterLocalNotificationsPlugin.show(
    _synchroAvanceNotifId,
    isFr ? '🔄 Synchronisation en cours…' : '🔄 Synchronization in progress…',
    statusText,
    notifDetails,
  );
}

/// Remplace la notification de progression par une notification de succès
/// une fois la synchronisation terminée.
Future<void> showSynchroAvanceComplete(
    int nbCreated, int nbUpdated, int nbMerged) async {
  final bool isFr = langUserPhone == 'fr';
  await flutterLocalNotificationsPlugin.cancel(_synchroAvanceNotifId);

  final AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
    'synchro_avance_channel',
    isFr ? 'Synchronisation avancée' : 'Advanced synchronization',
    channelDescription: isFr
        ? 'Progression de la synchronisation avancée des contacts'
        : 'Advanced contact synchronization progress',
    importance: Importance.defaultImportance,
    priority: Priority.defaultPriority,
    autoCancel: true,
  );
  final NotificationDetails notifDetails =
      NotificationDetails(android: androidDetails);

  final String body = isFr
      ? '$nbCreated créé(s) · $nbUpdated mis à jour · $nbMerged doublon(s) fusionné(s)'
      : '$nbCreated created · $nbUpdated updated · $nbMerged duplicate(s) merged';

  await flutterLocalNotificationsPlugin.show(
    _synchroAvanceNotifId,
    isFr ? '✅ Synchronisation terminée' : '✅ Synchronization complete',
    body,
    notifDetails,
  );
}

/// Annule la notification de synchronisation avancée.
Future<void> cancelSynchroAvanceNotification() async {
  await flutterLocalNotificationsPlugin.cancel(_synchroAvanceNotifId);
}
