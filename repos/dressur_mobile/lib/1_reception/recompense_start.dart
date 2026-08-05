// ignore_for_file: prefer_const_constructors, prefer_const_literals_to_create_immutables

import 'package:dressur/1_reception/recompense_dashboard.dart';
import 'package:dressur/components/noti.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;

class ProgrammeRecompensePage extends StatefulWidget {
  final dynamic optionPage;

  const ProgrammeRecompensePage({super.key, required this.optionPage});

  @override
  State<ProgrammeRecompensePage> createState() =>
      _ProgrammeRecompensePageState();
}

class _ProgrammeRecompensePageState extends State<ProgrammeRecompensePage> {
  var data;
  bool _desactive = false;

  // Conditions d'accès au programme
  bool _conditionsLoaded = false;
  bool _condInscrit7j = false;
  bool _condMail = false;
  bool _condWhatsapp = false;
  bool _condCommandes = false;
  int _nbrCommandes = 0;
  bool get _toutesConditions =>
      _condInscrit7j && _condMail && _condWhatsapp && _condCommandes;

  @override
  void initState() {
    super.initState();
    if (!isInscritProgrammeRecompense) {
      _fetchConditions();
    }
  }

  Future<void> _fetchConditions() async {
    try {
      var request = http.MultipartRequest(
          'POST',
          Uri.parse('$generalRouteForApi/getConditionsProgrammeRecompense'));
      request.fields.addAll({'uid': uidUser});

      http.StreamedResponse response = await request.send();
      if (response.statusCode == 200) {
        var data1 = await response.stream.bytesToString();
        var decoded = convert.jsonDecode(data1);
        if (decoded['error'] == false) {
          final c = decoded['conditions'];
          if (mounted) {
            setState(() {
              _condInscrit7j  = c['inscritDepuis7Jours'] == true;
              _condMail       = c['mailConfirme'] == true;
              _condWhatsapp   = c['whatsappConfirme'] == true;
              _condCommandes  = c['cinqCommandes'] == true;
              _nbrCommandes   = c['nbrCommandes'] ?? 0;
              _conditionsLoaded = true;
            });
          }
        }
      }
    } catch (e) {
      // Silencieux — le bouton reste désactivé
    }
  }

  void addToRecompenseProgramme() async {
    bool isConnected = await isConnectedToInternet();
    if (isConnected) {
      setState(() {
        _desactive = true;
      });

      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/addToRecompenseProgramme'));
      request.fields.addAll({
        'uid': uidUser,
      });

      http.StreamedResponse response = await request.send();

      if (response.statusCode == 200) {
        var data1 = await response.stream.bytesToString();
        data = convert.jsonDecode(data1);
        if (data["error"] == true) {
          dangerNoti(data["titre"], data["message"], context);
          setState(() {
            _desactive = false;
          });
        } else {
          setState(() {
            _desactive = false;
            isInscritProgrammeRecompense = true;
          });
        }
      } else {
        setState(() {
          _desactive = false;
        });
        if (langUserPhone != "fr") {
          dangerNoti("Mistake!",
              "We encountered a problem, contact the administrators.", context);
        } else {
          dangerNoti(
              "Erreur!",
              "Nous avons rencontré un problème, contacter les administrateurs.",
              context);
        }
      }
    } else {
      setState(() {
        _desactive = false;
      });
      if (langUserPhone != "fr") {
        dangerNoti(
            "Mistake!", "You are not connected to the internet.", context);
      } else {
        dangerNoti("Erreur!", "Vous n'ètes pas connecté a internet.", context);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final bool isFr = langUserPhone == "fr";

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: primaryColor,
        elevation: 0,
        title: Text(
          isFr ? "Programme des récompenses" : "Rewards Program",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
        leading: IconButton(
          icon: FaIcon(FontAwesomeIcons.chevronLeft,
              color: Colors.white, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: widget.optionPage
          ? _pagePresentationProgramme(context)
          : isInscritProgrammeRecompense
              ? _pageInformationApresInscription(context)
              : _pagePresentationProgramme(context),
    );
  }

  // ---------------------------------------------------------------------------
  // PAGE DE PRÉSENTATION EXHAUSTIVE
  // ---------------------------------------------------------------------------
  Widget _pagePresentationProgramme(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final bool isFr = langUserPhone == "fr";

    return SingleChildScrollView(
      physics: BouncingScrollPhysics(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Container(
            width: double.infinity,
            padding: EdgeInsets.all(24),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [primaryColor, primaryColor.withOpacity(0.8)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                FaIcon(FontAwesomeIcons.star, size: 48, color: Colors.white),
                SizedBox(height: 16),
                Text(
                  isFr
                      ? "Gagnez des récompenses avec Dressur"
                      : "Earn rewards with Dressur",
                  style: GoogleFonts.poppins(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                SizedBox(height: 8),
                Text(
                  isFr
                      ? "Partagez des promotions sur votre statut WhatsApp et soyez récompensé."
                      : "Share promotions on your WhatsApp status and get rewarded.",
                  style: GoogleFonts.poppins(
                    fontSize: 14,
                    color: Colors.white.withOpacity(0.9),
                  ),
                ),
              ],
            ),
          ),

          Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // 1. Présentation
                _sectionTitle(
                    context, isFr ? "Présentation" : "Presentation"),
                _paragraph(
                  context,
                  isFr
                      ? "Le Programme des récompenses Dressur est une opportunité de gain occasionnelle destinée aux utilisateurs qui partagent des promotions d'affaires (produits et services) directement depuis l'application Dressur sur WhatsApp Statut."
                      : "The Dressur Rewards Program is an occasional earning opportunity for users who share business promotions (products and services) directly from the Dressur app on WhatsApp Status.",
                ),
                _paragraph(
                  context,
                  isFr
                      ? "Ce programme permet aux annonceurs de faire connaître leurs offres et aux utilisateurs de recevoir une récompense après validation des preuves et selon le quota disponible."
                      : "This program allows advertisers to promote their offers and users to receive a reward after proof validation and according to the available quota.",
                ),

                _divider(context),

                // 2. Réseau concerné
                _sectionTitle(context,
                    isFr ? "Réseau concerné" : "Supported network"),
                _bulletItem(
                  context,
                  FontAwesomeIcons.solidCircleCheck,
                  isFr
                      ? "WhatsApp – Statut uniquement"
                      : "WhatsApp – Status only",
                  Colors.green,
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.xmark,
                  isFr
                      ? "Autres réseaux non pris en charge"
                      : "Other networks not supported",
                  Colors.red,
                ),

                _divider(context),

                // 3. Principe de fonctionnement
                _sectionTitle(context,
                    isFr ? "Principe de fonctionnement" : "How it works"),
                _stepItem(context, "1",
                    isFr
                        ? "Identifiez une promotion éligible au programme."
                        : "Identify a promotion eligible for the program."),
                _stepItem(context, "2",
                    isFr
                        ? "Partagez la promotion directement depuis l'application Dressur vers votre statut WhatsApp."
                        : "Share the promotion directly from the Dressur app to your WhatsApp status."),
                _stepItem(context, "3",
                    isFr
                        ? "Le contenu partagé (image + texte) est officiel et validé par Dressur."
                        : "The shared content (image + text) is official and validated by Dressur."),
                _stepItem(context, "4",
                    isFr
                        ? "Aucune modification n'est autorisée (image, texte, description)."
                        : "No modifications are allowed (image, text, description)."),
                _stepItem(context, "5",
                    isFr
                        ? "Après 20 heures, vous pouvez soumettre vos preuves."
                        : "After 20 hours, you can submit your proofs."),
                _stepItem(context, "6",
                    isFr
                        ? "Les preuves sont analysées."
                        : "The proofs are reviewed."),
                _stepItem(context, "7",
                    isFr
                        ? "Si elles sont validées et que le quota n'est pas atteint, votre solde est crédité."
                        : "If validated and the quota is not reached, your balance is credited."),

                _divider(context),

                // 4. Niveaux de vues et récompenses
                _sectionTitle(
                  context,
                  isFr
                      ? "Niveaux de vues et récompenses"
                      : "View levels and rewards",
                ),
                _paragraph(
                  context,
                  isFr
                      ? "Gagnez plus d'argent en atteignant des paliers de vues plus élevés sur votre statut."
                      : "Earn more money by reaching higher view thresholds on your status.",
                ),
                _rewardRow(context, "250 vues", "100", Colors.green),
                _rewardRow(context, "500 vues", "200", Colors.blue),
                _rewardRow(context, "1 000 vues", "500", Colors.purple),
                _rewardRow(context, "2 000 vues", "1 000", Colors.orange),
                _rewardRow(context, "4 000 vues", "2 500", Colors.red),
                _infoText(
                  context,
                  isFr
                      ? "👉 Vous êtes rémunéré au niveau correspondant au plus haut seuil atteint."
                      : "👉 You are paid at the level corresponding to the highest threshold reached.",
                ),

                _divider(context),

                // 5. Règles essentielles
                _sectionTitle(context,
                    isFr ? "Règles essentielles" : "Essential rules"),
                _bulletItem(
                  context,
                  FontAwesomeIcons.circleInfo,
                  isFr
                      ? "Le partage doit être effectué uniquement depuis Dressur."
                      : "Sharing must only be done from Dressur.",
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.stopwatch,
                  isFr
                      ? "Le statut doit rester visible au moins 20 heures."
                      : "The status must remain visible for at least 20 hours.",
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.slash,
                  isFr
                      ? "Toute modification du contenu annule automatiquement la récompense."
                      : "Any modification of the content automatically cancels the reward.",
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.solidCircleCheck,
                  isFr
                      ? "Les preuves doivent être authentiques, complètes et personnelles."
                      : "Proofs must be authentic, complete and personal.",
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.users,
                  isFr
                      ? "Seuls les premiers utilisateurs ayant fourni des preuves valides sont récompensés."
                      : "Only the first users to provide valid proofs are rewarded.",
                ),

                SizedBox(height: 8),
                _infoText(
                  context,
                  isFr
                      ? "👉 Cliquer sur « Participer » vaut acceptation totale de ces règles."
                      : "👉 Clicking \"Participate\" constitutes full acceptance of these rules.",
                ),

                _divider(context),

                // 6. Délais importants
                _sectionTitle(context,
                    isFr ? "Délais importants" : "Important deadlines"),
                _bulletItem(
                  context,
                  FontAwesomeIcons.hourglassHalf,
                  isFr
                      ? "Délai minimum : 20 heures après le partage"
                      : "Minimum deadline: 20 hours after sharing",
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.hourglass,
                  isFr
                      ? "Délai maximum : avant 24 heures (expiration WhatsApp)"
                      : "Maximum deadline: before 24 hours (WhatsApp expiration)",
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.arrowsRotate,
                  isFr
                      ? "En cas de preuve incomplète, une reprise peut être demandée dans la limite du délai"
                      : "In case of incomplete proof, a resubmission may be requested within the deadline",
                ),

                _divider(context),

                // 7. Preuves à fournir
                _sectionTitle(
                  context,
                  isFr
                      ? "Preuves à fournir (obligatoires)"
                      : "Required proofs (mandatory)",
                ),
                _paragraph(
                  context,
                  isFr
                      ? "Pour être éligible, vous devez fournir les trois preuves suivantes :"
                      : "To be eligible, you must provide the following three proofs:",
                ),
                SizedBox(height: 15),
                _proofCard(
                  context,
                  "1",
                  isFr
                      ? "Capture – Liste des statuts"
                      : "Screenshot – Status list",
                  isFr
                      ? "• Affiche la liste des statuts WhatsApp\n• Le statut de la promotion doit être visible"
                      : "• Shows the WhatsApp status list\n• The promotion status must be visible",
                ),
                _proofCard(
                  context,
                  "2",
                  isFr ? "Capture – Statut ouvert" : "Screenshot – Open status",
                  isFr
                      ? "• Image complète\n• Texte descriptif complet\n• Nombre de vues, date et heure visibles"
                      : "• Full image\n• Complete descriptive text\n• Number of views, date and time visible",
                ),
                _proofCard(
                  context,
                  "3",
                  isFr ? "Vidéo – Preuve principale" : "Video – Main proof",
                  isFr
                      ? "La vidéo doit montrer : \n• l'ouverture de WhatsApp, \n• la liste des statuts, \n• l'ouverture du statut, \n• la lecture de la description, \n• le défilement des vues, \n• la date/heure et les paramètres WhatsApp (numéro)."
                      : "The video must show: \n• opening WhatsApp, \n• the status list, \n• opening the status, \n• reading the description, \n• scrolling through views, \n• the date/time and WhatsApp settings (number).",
                ),
                SizedBox(height: 10),
                _warningBox(
                  context,
                  isFr
                      ? "Toute vidéo incomplète, modifiée ou incohérente sera rejetée."
                      : "Any incomplete, modified or inconsistent video will be rejected.",
                ),

                _divider(context),

                // 8. Validation et quota
                _sectionTitle(context,
                    isFr ? "Validation et quota" : "Validation and quota"),
                _bulletItem(
                  context,
                  FontAwesomeIcons.chartSimple,
                  isFr
                      ? "Les preuves sont analysées par le système et/ou l'équipe Dressur."
                      : "Proofs are reviewed by the system and/or the Dressur team.",
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.clipboardCheck,
                  isFr
                      ? "La validation dépend de la conformité et du quota disponible."
                      : "Validation depends on compliance and the available quota.",
                ),

                _infoText(
                  context,
                  isFr
                      ? "📌 Atteindre un seuil de vues ne garantit pas automatiquement le paiement."
                      : "📌 Reaching a view threshold does not automatically guarantee payment.",
                ),

                _divider(context),

                // 9. Récompense et solde
                _sectionTitle(context,
                    isFr ? "Récompense et solde" : "Reward and balance"),
                _bulletItem(
                  context,
                  FontAwesomeIcons.wallet,
                  isFr
                      ? "Ajoutée à votre solde Programme des récompenses"
                      : "Added to your Rewards Program balance",
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.clockRotateLeft,
                  isFr
                      ? "Enregistrée dans votre historique avec la promotion"
                      : "Recorded in your history with the promotion",
                ),

                _divider(context),

                // 10. Utilisation du solde
                _sectionTitle(context,
                    isFr ? "Utilisation de votre solde" : "Using your balance"),
                _bulletItem(
                  context,
                  FontAwesomeIcons.moneyBillTrendUp,
                  isFr
                      ? "Votre solde est utilisé automatiquement pour payer vos services sur Dressur."
                      : "Your balance is automatically used to pay for your services on Dressur.",
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.shield,
                  isFr
                      ? "Soumis aux contrôles de sécurité habituels."
                      : "Subject to standard security checks.",
                ),

                _divider(context),

                // 11. Politique anti-fraude
                _sectionTitle(context,
                    isFr ? "Politique anti-fraude" : "Anti-fraud policy"),
                _paragraph(
                  context,
                  isFr
                      ? "Toute tentative de fraude entraîne :"
                      : "Any attempt at fraud results in:",
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.slash,
                  isFr
                      ? "Annulation immédiate des gains"
                      : "Immediate cancellation of earnings",
                  Colors.red,
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.userSlash,
                  isFr
                      ? "Suspension ou suppression du compte"
                      : "Account suspension or deletion",
                  Colors.red,
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.gavel,
                  isFr
                      ? "Interdiction définitive de participation"
                      : "Permanent ban from participation",
                  Colors.red,
                ),

                _paragraph(
                  context,
                  isFr
                      ? "Dressur se réserve le droit de refuser toute preuve jugée douteuse."
                      : "Dressur reserves the right to reject any proof deemed suspicious.",
                ),

                _divider(context),

                // 12. Utilisation automatique du solde
                _sectionTitle(
                  context,
                  isFr
                      ? "Utilisation automatique du solde"
                      : "Automatic balance usage",
                ),

                _paragraph(
                  context,
                  isFr
                      ? "Les gains du Programme des récompenses s'accumulent dans votre solde Dressur. Ils ne sont pas retirables en argent, mais sont utilisés automatiquement comme crédit plateforme."
                      : "Rewards Program earnings accumulate in your Dressur balance. They cannot be withdrawn as cash, but are automatically used as platform credit.",
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.arrowsRotate,
                  isFr
                      ? "Si votre solde est suffisant, il est débité automatiquement lors de votre prochain achat de service sur Dressur."
                      : "If your balance is sufficient, it is automatically deducted on your next service purchase on Dressur.",
                ),
                _bulletItem(
                  context,
                  FontAwesomeIcons.cartShopping,
                  isFr
                      ? "Services concernés : Boost Contact, Promotion Affaire, Promotion Réseaux Sociaux."
                      : "Applicable services: Boost Contact, Business Promotion, Social Media Promotion.",
                ),

                _infoText(
                  context,
                  isFr
                      ? "📌 Si votre solde couvre entièrement le montant d'un service, aucun paiement supplémentaire ne vous sera demandé."
                      : "📌 If your balance fully covers the cost of a service, no additional payment will be required.",
                ),

                _divider(context),

                // 14. Message important
                _sectionTitle(
                    context, isFr ? "Message important" : "Important message"),
                _paragraph(
                  context,
                  isFr
                      ? "Le Programme des récompenses est une opportunité de gain complémentaire, non un revenu garanti. Il vise également à aider les utilisateurs à faire connaître des promotions utiles au-delà de la plateforme."
                      : "The Rewards Program is a supplementary earning opportunity, not a guaranteed income. It also aims to help users promote useful offers beyond the platform.",
                ),

                _divider(context),

                // 15. Action finale
                _sectionTitle(context, isFr ? "Action" : "Action"),
                _paragraph(
                  context,
                  isFr
                      ? "En cliquant sur « Participer », vous confirmez avoir compris le fonctionnement, accepter les règles et être prêt à fournir les preuves demandées."
                      : "By clicking \"Participate\", you confirm that you have understood how it works, accept the rules and are ready to provide the required proofs.",
                ),

                SizedBox(height: 20),
                if (isInscritProgrammeRecompense == false) ...[
                  // --- Checklist des conditions d'accès ---
                  Container(
                    width: double.infinity,
                    padding: EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      color: isDark
                          ? Colors.white.withOpacity(0.05)
                          : Colors.grey[50],
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: _conditionsLoaded
                            ? (_toutesConditions
                                ? Colors.green.withOpacity(0.4)
                                : Colors.orange.withOpacity(0.4))
                            : theme.dividerColor.withOpacity(0.15),
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isFr
                              ? "Conditions d'accès au programme"
                              : "Program access conditions",
                          style: GoogleFonts.poppins(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: theme.textTheme.bodyLarge?.color,
                          ),
                        ),
                        SizedBox(height: 12),
                        _conditionRow(
                          context,
                          isFr
                              ? "Inscrit depuis au moins 7 jours"
                              : "Registered for at least 7 days",
                          _conditionsLoaded ? _condInscrit7j : null,
                        ),
                        _conditionRow(
                          context,
                          isFr
                              ? "Adresse e-mail confirmée"
                              : "Email address confirmed",
                          _conditionsLoaded ? _condMail : null,
                        ),
                        _conditionRow(
                          context,
                          isFr
                              ? "Numéro WhatsApp confirmé"
                              : "WhatsApp number confirmed",
                          _conditionsLoaded ? _condWhatsapp : null,
                        ),
                        _conditionRow(
                          context,
                          isFr
                              ? "Au moins 5 commandes payantes ($_nbrCommandes/5)"
                              : "At least 5 paid orders ($_nbrCommandes/5)",
                          _conditionsLoaded ? _condCommandes : null,
                        ),
                      ],
                    ),
                  ),
                  SizedBox(height: 16),
                  // --- Bouton Participer ---
                  SizedBox(
                    width: double.infinity,
                    height: 55,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: (_conditionsLoaded && _toutesConditions)
                            ? primaryColor
                            : Colors.grey[400],
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(15),
                        ),
                      ),
                      onPressed: () {
                        if (_desactive) return;
                        if (!_conditionsLoaded || !_toutesConditions) return;
                        addToRecompenseProgramme();
                      },
                      child: _desactive
                          ? SizedBox(
                              width: 30,
                              height: 30,
                              child: CircularProgressIndicator.adaptive(
                                strokeWidth: 2.5,
                                valueColor:
                                    AlwaysStoppedAnimation(Colors.white),
                              ),
                            )
                          : Text(
                              isFr
                                  ? "Participer au programme"
                                  : "Join the program",
                              style: GoogleFonts.poppins(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.white,
                              ),
                            ),
                    ),
                  ),
                  if (_conditionsLoaded && !_toutesConditions) ...[
                    SizedBox(height: 10),
                    Text(
                      isFr
                          ? "Complétez toutes les conditions pour débloquer le programme."
                          : "Complete all conditions to unlock the program.",
                      textAlign: TextAlign.center,
                      style: GoogleFonts.poppins(
                        fontSize: 12,
                        color: Colors.orange[700],
                      ),
                    ),
                  ],
                ] else ...[
                  Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      SizedBox(height: 10),

                      Container(
                        padding: EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: Colors.green.withOpacity(0.1),
                          shape: BoxShape.circle,
                        ),
                        child: FaIcon(
                          FontAwesomeIcons.solidCircleCheck,
                          size: 80,
                          color: Colors.green,
                        ),
                      ),

                      SizedBox(height: 24),

                      Text(
                        isFr
                            ? "Vous participez déjà au programme"
                            : "You are already in the program",
                        textAlign: TextAlign.center,
                        style: GoogleFonts.poppins(
                          fontSize: 22,
                          fontWeight: FontWeight.bold,
                          color: theme.textTheme.bodyLarge?.color,
                        ),
                      ),

                      SizedBox(height: 12),

                      Text(
                        isFr
                            ? "Votre compte est actif. Vous pouvez commencer à partager des promotions pour gagner des récompenses."
                            : "Your account is active. You can start sharing promotions to earn rewards.",
                        textAlign: TextAlign.center,
                        style: GoogleFonts.poppins(
                          fontSize: 15,
                          color: isDark ? Colors.grey[400] : Colors.grey[600],
                        ),
                      ),

                      SizedBox(height: 40),

                      Container(
                        width: double.infinity,
                        padding: EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: isDark
                              ? Colors.white.withOpacity(0.05)
                              : Colors.grey[50],
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(
                            color: theme.dividerColor.withOpacity(0.1),
                          ),
                        ),
                        child: Column(
                          children: [
                            Text(
                              isFr
                                  ? "Besoin de quitter le programme ?"
                                  : "Need to leave the program?",
                              textAlign: TextAlign.center,
                              style: GoogleFonts.poppins(
                                fontSize: 15,
                                fontWeight: FontWeight.w600,
                              ),
                            ),

                            SizedBox(height: 10),

                            Text(
                              isFr
                                  ? "Pour vous désinscrire du programme des récompenses, veuillez contacter notre équipe d'assistance."
                                  : "To unsubscribe from the rewards program, please contact our support team.",
                              textAlign: TextAlign.center,
                              style: GoogleFonts.poppins(
                                fontSize: 13,
                                color: isDark
                                    ? Colors.grey[400]
                                    : Colors.grey[600],
                              ),
                            ),

                            SizedBox(height: 20),

                            InkWell(
                              onTap: () {
                                // TODO: ouvrir WhatsApp / support
                              },
                              borderRadius: BorderRadius.circular(12),
                              child: Container(
                                padding: EdgeInsets.symmetric(
                                  horizontal: 20,
                                  vertical: 12,
                                ),
                                decoration: BoxDecoration(
                                  color: Color(0xFF25D366).withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(
                                    color: Color(0xFF25D366).withOpacity(0.3),
                                  ),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    FaIcon(
                                      FontAwesomeIcons.solidComment,
                                      color: Color(0xFF25D366),
                                      size: 20,
                                    ),
                                    SizedBox(width: 10),
                                    Text(
                                      isFr
                                          ? "Contacter l'assistance"
                                          : "Contact support",
                                      style: GoogleFonts.poppins(
                                        color: Color(0xFF25D366),
                                        fontWeight: FontWeight.bold,
                                        fontSize: 14,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],

                SizedBox(height: 40),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // PAGE APRÈS INSCRIPTION
  // ---------------------------------------------------------------------------
  Widget _pageInformationApresInscription(BuildContext context) {
    final theme = Theme.of(context);
    final bool isFr = langUserPhone == "fr";
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(30),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          FaIcon(FontAwesomeIcons.solidCircleCheck,
              size: 80, color: Colors.green),
          SizedBox(height: 24),
          Text(
            isFr ? "Inscription confirmée" : "Registration confirmed",
            style: GoogleFonts.poppins(
              fontSize: 22,
              fontWeight: FontWeight.bold,
              color: theme.textTheme.bodyLarge?.color,
            ),
          ),
          SizedBox(height: 12),
          Text(
            isFr
                ? "Vous pouvez maintenant commencer à promouvoir des offres et soumettre vos preuves."
                : "You can now start promoting offers and submitting your proofs.",
            textAlign: TextAlign.center,
            style: GoogleFonts.poppins(fontSize: 14),
          ),
          SizedBox(height: 40),
          SizedBox(
            width: double.infinity,
            height: 55,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(15)),
              ),
              onPressed: () {
                Navigator.pushReplacement(
                  context,
                  MaterialPageRoute(
                    builder: (_) => ProgrammeRecompenseDashboard(),
                  ),
                );
              },
              child: Text(
                isFr ? "Commencer" : "Get started",
                style: GoogleFonts.poppins(
                    color: Colors.white, fontWeight: FontWeight.w600),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // COMPOSANTS DE DESIGN
  // ---------------------------------------------------------------------------

  /// [met] = true → vert ✅, false → rouge ❌, null → gris (chargement)
  Widget _conditionRow(BuildContext context, String label, bool? met) {
    final Color iconColor = met == null
        ? Colors.grey
        : (met ? Colors.green : Colors.red);
    final IconData icon = met == null
        ? Icons.radio_button_unchecked
        : (met ? Icons.check_circle : Icons.cancel);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        children: [
          Icon(icon, size: 20, color: iconColor),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              label,
              style: GoogleFonts.poppins(
                fontSize: 13,
                color: Theme.of(context).textTheme.bodyMedium?.color,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionTitle(BuildContext context, String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12, top: 8),
      child: Text(
        title,
        style: GoogleFonts.poppins(
          fontSize: 18,
          fontWeight: FontWeight.bold,
          color: primaryColor,
        ),
      ),
    );
  }

  Widget _paragraph(BuildContext context, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Text(
        text,
        style: GoogleFonts.poppins(
          fontSize: 14,
          height: 1.5,
          color: Theme.of(context).textTheme.bodyMedium?.color,
        ),
      ),
    );
  }

  Widget _bulletItem(BuildContext context, IconData icon, String text,
      [Color? iconColor]) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          FaIcon(icon,
              size: 18, color: iconColor ?? primaryColor.withOpacity(0.7)),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: GoogleFonts.poppins(fontSize: 14),
            ),
          ),
        ],
      ),
    );
  }

  Widget _stepItem(BuildContext context, String number, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 12,
            backgroundColor: primaryColor,
            child: Text(number,
                style: GoogleFonts.poppins(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.bold)),
          ),
          SizedBox(width: 12),
          Expanded(
            child: Text(text, style: GoogleFonts.poppins(fontSize: 14)),
          ),
        ],
      ),
    );
  }

  Widget _rewardRow(
      BuildContext context, String views, String amount, Color color) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      margin: EdgeInsets.only(bottom: 10),
      padding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(15),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              FaIcon(FontAwesomeIcons.eye, color: color, size: 20),
              SizedBox(width: 12),
              Text(
                views,
                style: GoogleFonts.poppins(
                  fontWeight: FontWeight.w600,
                  fontSize: 15,
                  color: isDark ? Colors.white : Colors.black87,
                ),
              ),
            ],
          ),
          Container(
            padding: EdgeInsets.symmetric(horizontal: 12, vertical: 4),
            decoration: BoxDecoration(
              color: color,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Text(
              "$amount FCFA",
              style: GoogleFonts.poppins(
                fontWeight: FontWeight.bold,
                fontSize: 14,
                color: Colors.white,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoText(BuildContext context, String text) {
    return Text(
      text,
      style: GoogleFonts.poppins(
        fontSize: 13,
        fontWeight: FontWeight.w500,
        fontStyle: FontStyle.italic,
        color: Theme.of(context).textTheme.bodySmall?.color,
      ),
    );
  }

  Widget _proofCard(
      BuildContext context, String number, String title, String content) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      width: double.infinity,
      margin: EdgeInsets.only(bottom: 12),
      padding: EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: isDark ? Colors.white.withOpacity(0.05) : Colors.green[50],
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 12,
                backgroundColor: primaryColor,
                child: Text(number,
                    style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.bold)),
              ),
              SizedBox(width: 10),
              Expanded(
                child: Text(
                  title,
                  style: GoogleFonts.poppins(
                      fontWeight: FontWeight.bold, fontSize: 14),
                ),
              ),
            ],
          ),
          SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.only(left: 30),
            child: Text(content,
                style: GoogleFonts.poppins(fontSize: 13, height: 1.4)),
          ),
        ],
      ),
    );
  }

  Widget _warningBox(BuildContext context, String text) {
    return Container(
      padding: EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.red.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.red.withOpacity(0.3)),
      ),
      child: Row(
        children: [
          FaIcon(FontAwesomeIcons.triangleExclamation,
              color: Colors.red, size: 20),
          SizedBox(width: 10),
          Expanded(
            child: Text(text,
                style: GoogleFonts.poppins(
                    fontSize: 12,
                    color: Colors.red[700],
                    fontWeight: FontWeight.w500)),
          ),
        ],
      ),
    );
  }

  Widget _divider(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 15),
      child: Divider(
          thickness: 1, color: Theme.of(context).dividerColor.withOpacity(0.5)),
    );
  }
}
