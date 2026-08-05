// ignore_for_file: use_build_context_synchronously
import 'package:dressur/2_promo/boost_success_checklist.dart';
import 'package:dressur/2_promo/liste_boost_contact.dart';
import 'package:dressur/components/noti_sys.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:select_form_field/select_form_field.dart';
import 'package:dressur/components/noti.dart';
import 'package:dressur/components/info_service_bottom_sheet.dart';
class NewBoostContactPage extends StatefulWidget {
  @override
  State<NewBoostContactPage> createState() => _NewBoostContactPageState();
}
class _NewBoostContactPageState extends State<NewBoostContactPage> {
  bool _isPaid = false;
  String _typeBoost = 'date';
  int _freeNbrJour = 5;
  int _freeNbContactsMax = 20;
  bool _loadingFreeInfo = false;
  @override
  void initState() {
    super.initState();
    _fetchFreeBoostInfo();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _showInfoModal();
    });
  }

  void _showInfoModal({int countdown = 2}) {
    showServiceInfoModal(
      context,
      countdownSeconds: countdown,
      titleFr: "Informations Boost Contact",
      titleEn: "Boost Contact Information",
      items: const [
        ServiceInfoItem(
          icon: FontAwesomeIcons.clock,
          textFr: "Par Durée : votre numéro est visible X jours dans vos pays préférés.",
          textEn: "By Duration: your number is visible for X days in your preferred countries.",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.users,
          textFr: "Par Quota : vous recevez un nombre précis de contacts ; le boost se termine automatiquement.",
          textEn: "By Quota: you receive a set number of contacts; the boost ends automatically.",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.trophy,
          textFr: "Les Boosts Payants sont beaucoup plus mis en avant !",
          textEn: "Paid Boosts are much more prominently featured!",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.calendarDays,
          textFr: "Vous pouvez programmer plusieurs Boosts Payants.",
          textEn: "You can schedule several Paid Boosts.",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.clockRotateLeft,
          textFr: "Votre Boost devient Inactif si votre dernière connexion remonte à plus de 48H.",
          textEn: "Your Boost becomes inactive if your last login was more than 48 hours ago.",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.arrowsRotate,
          textFr: "Après un Boost Gratuit, faites au moins un Boost Payant avant d'en refaire un Gratuit.",
          textEn: "After a Free Boost, complete at least one Paid Boost before getting another Free Boost.",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.rightToBracket,
          textFr: "Connectez-vous chaque jour pour récupérer les contacts obtenus.",
          textEn: "Log in every day to retrieve the contacts obtained.",
        ),
      ],
    );
  }
  Future<void> _fetchFreeBoostInfo() async {
    setState(() => _loadingFreeInfo = true);
    try {
      final response = await http.get(
        Uri.parse('$generalRouteForApi/freeBoostInfo'),
      );
      if (response.statusCode == 200) {
        final data = convert.jsonDecode(response.body);
        if (data['error'] == false) {
          setState(() {
            _freeNbrJour        = data['date']['nbrJour']          ?? 1;
            _freeNbContactsMax  = data['quota']['nbContactsMax']   ?? 15;
          });
        }
      }
    } catch (_) {
      // Garde les valeurs par défaut en cas d'erreur réseau
    } finally {
      setState(() => _loadingFreeInfo = false);
    }
  }
  @override
  Widget build(BuildContext context) {
    final bool isFr = langUserPhone == "fr";
    return Scaffold(
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          isFr ? "Nouveau Boost Contact" : "New Boost Contact",
          style: GoogleFonts.poppins(
              color: Colors.white, fontWeight: FontWeight.w400, fontSize: 18),
        ),
        leading: IconButton(
          onPressed: () => Navigator.pop(context),
          icon: const FaIcon(FontAwesomeIcons.chevronLeft, color: Colors.white),
        ),
      ),
      body: SingleChildScrollView(
        child: Column(
          children: [
            const SizedBox(height: 10),
            _buildInfoButton(isFr),
            const SizedBox(height: 16),
            Text(
              isFr ? "Nouveau Boost Contact" : "New Boost Contact",
              style: GoogleFonts.poppins(
                fontSize: 20,
                fontWeight: FontWeight.w600,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 15),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    isFr ? "Type de boost" : "Boost type",
                    style: GoogleFonts.poppins(
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                        color: Colors.grey[600]),
                  ),
                  const SizedBox(height: 8),
                  _buildTypeSelector(isFr),
                  const SizedBox(height: 20),
                  Text(
                    isFr ? "Mode de boost" : "Boost mode",
                    style: GoogleFonts.poppins(
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                        color: Colors.grey[600]),
                  ),
                  const SizedBox(height: 8),
                  _buildModeSelector(isFr),
                  const SizedBox(height: 14),
                  _buildDescription(isFr),
                  const SizedBox(height: 16),
                  _isPaid
                      ? RegisterForm2(
                          key: ValueKey(_typeBoost), typeBoost: _typeBoost)
                      : RegisterForm(typeBoost: _typeBoost, nbMax: _freeNbContactsMax),
                  const SizedBox(height: 24),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
  Widget _buildInfoButton(bool isFr) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 10),
      child: OutlinedButton.icon(
        onPressed: () => _showInfoModal(countdown: 0),
        icon: const FaIcon(FontAwesomeIcons.circleInfo, size: 14),
        label: Text(
          isFr
              ? "Voir les informations sur le service"
              : "View service information",
          style: GoogleFonts.poppins(fontSize: 13, fontWeight: FontWeight.w500),
        ),
        style: OutlinedButton.styleFrom(
          foregroundColor: primaryColor,
          side: BorderSide(color: primaryColor.withOpacity(0.5)),
          minimumSize: const Size(double.infinity, 48),
          padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      ),
    );
  }
  Widget _buildTypeSelector(bool isFr) {
    return Row(
      children: [
        Expanded(
          child: GestureDetector(
            onTap: () => setState(() => _typeBoost = 'date'),
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 14),
              decoration: BoxDecoration(
                color: _typeBoost == 'date' ? primaryColor : Colors.grey[100],
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(12),
                  bottomLeft: Radius.circular(12),
                ),
                border: Border.all(
                    color: _typeBoost == 'date'
                        ? primaryColor
                        : Colors.grey[300]!),
              ),
              child: Column(
                children: [
                  FaIcon(FontAwesomeIcons.clock,
                      size: 18,
                      color: _typeBoost == 'date'
                          ? Colors.white
                          : Colors.grey[500]),
                  const SizedBox(height: 4),
                  Text(
                    isFr ? "Par Durée" : "By Duration",
                    textAlign: TextAlign.center,
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                      color: _typeBoost == 'date'
                          ? Colors.white
                          : Colors.grey[600],
                    ),
                  ),
                  Text(
                    isFr ? "Limité en jours" : "Day-limited",
                    textAlign: TextAlign.center,
                    style: GoogleFonts.poppins(
                        fontSize: 10,
                        color: _typeBoost == 'date'
                            ? Colors.white70
                            : Colors.grey[400]),
                  ),
                ],
              ),
            ),
          ),
        ),
        Expanded(
          child: GestureDetector(
            onTap: () => setState(() => _typeBoost = 'quota'),
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 14),
              decoration: BoxDecoration(
                color: _typeBoost == 'quota' ? primaryColor : Colors.grey[100],
                borderRadius: const BorderRadius.only(
                  topRight: Radius.circular(12),
                  bottomRight: Radius.circular(12),
                ),
                border: Border.all(
                    color: _typeBoost == 'quota'
                        ? primaryColor
                        : Colors.grey[300]!),
              ),
              child: Column(
                children: [
                  FaIcon(FontAwesomeIcons.users,
                      size: 18,
                      color: _typeBoost == 'quota'
                          ? Colors.white
                          : Colors.grey[500]),
                  const SizedBox(height: 4),
                  Text(
                    isFr ? "Par Quota" : "By Quota",
                    textAlign: TextAlign.center,
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                      color: _typeBoost == 'quota'
                          ? Colors.white
                          : Colors.grey[600],
                    ),
                  ),
                  Text(
                    isFr ? "Nombre précis" : "Set number",
                    textAlign: TextAlign.center,
                    style: GoogleFonts.poppins(
                        fontSize: 10,
                        color: _typeBoost == 'quota'
                            ? Colors.white70
                            : Colors.grey[400]),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
  Widget _buildModeSelector(bool isFr) {
    return Row(
      children: [
        Expanded(
          child: GestureDetector(
            onTap: () => setState(() => _isPaid = false),
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 14),
              decoration: BoxDecoration(
                color: !_isPaid ? Colors.green : Colors.grey[100],
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(12),
                  bottomLeft: Radius.circular(12),
                ),
                border: Border.all(
                    color: !_isPaid ? Colors.green : Colors.grey[300]!),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  FaIcon(FontAwesomeIcons.gift,
                      size: 16,
                      color: !_isPaid ? Colors.white : Colors.grey[500]),
                  const SizedBox(width: 6),
                  Text(
                    isFr ? "Gratuit" : "Free",
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.w700,
                      fontSize: 15,
                      color: !_isPaid ? Colors.white : Colors.grey[600],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
        Expanded(
          child: GestureDetector(
            onTap: () => setState(() => _isPaid = true),
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 14),
              decoration: BoxDecoration(
                color: _isPaid ? Colors.red : Colors.grey[100],
                borderRadius: const BorderRadius.only(
                  topRight: Radius.circular(12),
                  bottomRight: Radius.circular(12),
                ),
                border: Border.all(
                    color: _isPaid ? Colors.red : Colors.grey[300]!),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  FaIcon(FontAwesomeIcons.creditCard,
                      size: 16,
                      color: _isPaid ? Colors.white : Colors.grey[500]),
                  const SizedBox(width: 6),
                  Text(
                    isFr ? "Payant" : "Paid",
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.w700,
                      fontSize: 15,
                      color: _isPaid ? Colors.white : Colors.grey[600],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
  Widget _buildDescription(bool isFr) {
    if (_loadingFreeInfo && !_isPaid) {
      return const Center(
        child: SizedBox(
          height: 20,
          width: 20,
          child: CircularProgressIndicator(strokeWidth: 2, color: primaryColor),
        ),
      );
    }
    String text;
    if (_typeBoost == 'quota') {
      text = _isPaid
          ? (isFr
              ? "Choisissez une formule : vous recevrez un nombre précis de contacts. Le boost se termine automatiquement dès que le quota est atteint."
              : "Choose a plan: you will receive a set number of contacts. The boost ends automatically once the quota is reached.")
          : (isFr
              ? "Boost Gratuit limité à $_freeNbContactsMax contacts. Le boost se termine automatiquement dès que les $_freeNbContactsMax contacts sont obtenus."
              : "Free Boost limited to $_freeNbContactsMax contacts. The boost ends automatically once the $_freeNbContactsMax contacts are obtained.");
    } else {
      text = _isPaid
          ? (isFr
              ? "Choisissez une formule : votre numéro sera visible dans vos pays préférés pendant la durée choisie."
              : "Choose a plan: your number will be visible in your preferred countries for the chosen duration.")
          : (isFr
              ? "Boost Gratuit de $_freeNbrJour jour(s) : votre numéro sera visible dans vos pays préférés pendant $_freeNbrJour jour(s)."
              : "Free $_freeNbrJour-day Boost: your number will be visible in your preferred countries for $_freeNbrJour day(s).");
    }
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Text(
        text,
        style: GoogleFonts.poppins(fontSize: 13, color: Colors.grey[700]),
        textAlign: TextAlign.center,
      ),
    );
  }
}
// ─────────────────────────────────────────────────────────────────────────────
// BOOST GRATUIT
// ─────────────────────────────────────────────────────────────────────────────
class RegisterForm extends StatefulWidget {
  final String typeBoost;
  final int nbMax;
  const RegisterForm({super.key, required this.typeBoost, required this.nbMax});
  @override
  State<RegisterForm> createState() => _RegisterFormState();
}
class _RegisterFormState extends State<RegisterForm> {
  bool _desactive = false;
  void newBoost() async {
    if (telIsVerified == true) {
      bool isConnected = await isConnectedToInternet();
      if (isConnected) {
        setState(() => _desactive = true);
        var request = http.MultipartRequest(
            'POST', Uri.parse('$generalRouteForApi/newBoost'));
        request.fields.addAll({
          'uid': uidUser,
          
          'typeBoost': widget.typeBoost,
        });
        http.StreamedResponse response = await request.send();
        if (response.statusCode == 200) {
          var data1 = await response.stream.bytesToString();
          var data = convert.jsonDecode(data1);
          if (data["error"] == false) {
            cancelBoostReminderNotification();
            setState(() => _desactive = false);
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(
                builder: (context) => BoostSuccessChecklistPage(
                  typeBoost: widget.typeBoost,
                  nbMax: widget.nbMax,
                ),
              ),
            );
          } else {
            dangerNoti(data["titre"], data["message"], context);
            setState(() => _desactive = false);
          }
        }
      } else {
        dangerNoti(
            langUserPhone != "fr" ? "Mistake!" : "Erreur!",
            langUserPhone != "fr"
                ? "You are not connected to the internet."
                : "Vous n'êtes pas connecté à internet.",
            context);
        setState(() => _desactive = false);
      }
    } else {
      dangerNoti(
          langUserPhone != "fr" ? "Access denied!" : "Accès Refusé !",
          langUserPhone != "fr"
              ? "Please confirm your WhatsApp number first."
              : "Veuillez d'abord confirmer votre numéro WhatsApp.",
          context);
    }
  }
  @override
  Widget build(BuildContext context) {
    final bool isFr = langUserPhone == "fr";
    return Column(
      children: [
        const SizedBox(height: 10),
        SizedBox(
          width: MediaQuery.of(context).size.width * 0.95,
          child: ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.green,
              shape: const StadiumBorder(),
              padding: const EdgeInsets.symmetric(vertical: 14),
            ),
            child: Text(
              _desactive
                  ? (isFr ? "Patientez..." : "Wait...")
                  : (isFr ? "Demander un Boost Gratuit" : "Request a Free Boost"),
              style: GoogleFonts.poppins(
                  color: Colors.white, fontWeight: FontWeight.w600),
            ),
            onPressed: () {
              if (!telIsVerified) {
                showConfNumeroWhatsapp(context);
              } else {
                _desactive ? null : newBoost();
              }
            },
          ),
        ),
      ],
    );
  }
}
// ─────────────────────────────────────────────────────────────────────────────
// BOOST PAYANT
// ─────────────────────────────────────────────────────────────────────────────
class RegisterForm2 extends StatefulWidget {
  final String typeBoost;
  const RegisterForm2({super.key, required this.typeBoost});
  @override
  State<RegisterForm2> createState() => _RegisterForm2State();
}
class _RegisterForm2State extends State<RegisterForm2> {
  bool _desactive2 = false;
  bool loading_formule_payant = false;
  var _message = "";
  dynamic idFormulBoost = 1;
  dynamic valueMethodePaiement = "mtn";
  final telController = TextEditingController(text: tel);
  List<Map<String, dynamic>> listeDesFormules = [];
  int value = 0;
  var label = "";
  int prix = 0;
  int jours = 0;
  int? nbContactsMax;

  @override
  void dispose() {
    telController.dispose();
    super.dispose();
  }

  void listeFormuleBoost() async {
    bool isConnected = await isConnectedToInternet();
    if (isConnected) {
      setState(() {
        _desactive2 = true;
        loading_formule_payant = true;
      });
      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/listeFormuleBoost'));
      request.fields.addAll({'typeBoost': widget.typeBoost});
      http.StreamedResponse response = await request.send();
      if (response.statusCode == 200) {
        var data1 = await response.stream.bytesToString();
        if (!mounted) return;
        var data = convert.jsonDecode(data1);
        if (data["error"] == false) {
          setState(() {
            _desactive2 = false;
            loading_formule_payant = false;
            listeDesFormules = (data["listeFormulBoost"] as List<dynamic>)
                .map((item) => item as Map<String, dynamic>)
                .toList();
            listeMethodePaiements =
                (data["listeMethodePaiements"] as List<dynamic>)
                    .map((item) => item as Map<String, dynamic>)
                    .toList();
            _message = (langUserPhone == "fr")
                ? "Veuillez choisir une formule."
                : "Please choose a plan.";
          });
        }
      }
    } else {
      dangerNoti(
          langUserPhone != "fr" ? "Mistake!" : "Erreur!",
          langUserPhone != "fr"
              ? "You are not connected to the internet."
              : "Vous n'êtes pas connecté à internet.",
          context);
      setState(() {
        _desactive2 = false;
        loading_formule_payant = false;
      });
    }
  }
  onChangeFormulBoost(val) async {
    for (var service in listeDesFormules) {
      if ("$val" == "${service['value']}") {
        setState(() {
          value = service['value'];
          label = service['label'];
          prix = service['prix'];
          jours = service['jours'];
          nbContactsMax = service['nbContactsMax'];
        });
      }
    }
    setState(() {
      idFormulBoost = val;
      if (widget.typeBoost == 'quota') {
        _message = (langUserPhone == "fr")
            ? "Cette formule vous offre un boost de $nbContactsMax contact(s) pour $prix FCFA."
            : "This plan offers a boost of $nbContactsMax contact(s) for $prix FCFA.";
      } else {
        _message = (langUserPhone == "fr")
            ? "Cette formule vous offre un boost de $jours jour(s) pour $prix FCFA."
            : "This formula offers you a boost of $jours day(s) for $prix FCFA.";
      }
    });
  }
  onChangeMethodePaiement(val) async {
    setState(() => valueMethodePaiement = val);
  }
  void newBoostPayant() async {
    if (telIsVerified == true) {
      bool isConnected = await isConnectedToInternet();
      if (isConnected) {
        setState(() => _desactive2 = true);
        var request = http.MultipartRequest(
            'POST', Uri.parse('$generalRouteForApi/newBoostPayant'));
        request.fields.addAll({
          'uid': uidUser,
          
          'idFormulBoost': idFormulBoost.toString(),
          'valueMethodePaiement': valueMethodePaiement,
          'tel': telController.text,
          'typeBoost': widget.typeBoost,
        });
        http.StreamedResponse response = await request.send();
        if (response.statusCode == 200) {
          var data1 = await response.stream.bytesToString();
          if (!mounted) return;
          var data = convert.jsonDecode(data1);
          if (data["error"] == false) {
            setState(() => _desactive2 = false);
            if (data["solde_used"] == true) {
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(
                  builder: (context) => BoostSuccessChecklistPage(
                    typeBoost: widget.typeBoost,
                    nbMax: nbContactsMax ?? 0,
                  ),
                ),
              );
            } else if (data["direct"] == true) {
              dangerNoti(
                  (langUserPhone == "fr") ? "Attention !!!" : "Attention !!!",
                  (langUserPhone == "fr")
                      ? "Après confirmation du paiement, veuillez consulter la liste de vos boosts."
                      : "After payment confirmation, please view the list of your boosts.",
                  context);
            } else {
              launchPaiement(data["url"]);
              Navigator.pop(context);
              showNotification(
                  (langUserPhone == "fr")
                      ? "Paiement en cours !"
                      : "Payment in progress !",
                  (langUserPhone == "fr")
                      ? "Après confirmation du paiement, veuillez consulter la liste de vos boosts."
                      : "After payment confirmation, please view the list of your boosts.");
            }
          } else {
            dangerNoti(data["titre"], data["message"], context);
            setState(() => _desactive2 = false);
          }
        }
      } else {
        dangerNoti(
            langUserPhone != "fr" ? "Mistake!" : "Erreur!",
            langUserPhone != "fr"
                ? "You are not connected to the internet."
                : "Vous n'êtes pas connecté à internet.",
            context);
        setState(() => _desactive2 = false);
      }
    } else {
      dangerNoti(
          langUserPhone != "fr" ? "Access denied!" : "Accès Refusé !",
          langUserPhone != "fr"
              ? "Please confirm your WhatsApp number first."
              : "Veuillez d'abord confirmer votre numéro WhatsApp.",
          context);
      setState(() => _desactive2 = false);
    }
  }

  @override
  void initState() {
    super.initState();
    listeFormuleBoost();
  }
  @override
  Widget build(BuildContext context) {
    final bool isFr = langUserPhone == "fr";
    return Column(
      children: [
        loading_formule_payant
            ? const Center(
                child: CircularProgressIndicator(color: primaryColor))
            : SelectFormField(
                decoration: InputDecoration(
                  labelText: isFr
                      ? 'Formules de Boost Payant'
                      : 'Paid Boost Plans',
                  border: const OutlineInputBorder(),
                ),
                type: SelectFormFieldType.dropdown,
                initialValue: '0',
                labelText: isFr ? 'Formules de Boost Payant' : 'Paid Boost Plans',
                items: listeDesFormules,
                onChanged: (val) => onChangeFormulBoost(val),
                onSaved: (val) => print(val),
              ),
        const SizedBox(height: 10),
        Text(
          _message,
          style: GoogleFonts.poppins(fontSize: 14),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 10),
        SelectFormField(
          decoration: InputDecoration(
            labelText: isFr
                ? 'Moyen de paiement mobile ou par carte'
                : 'Mobile or card payment method',
            border: const OutlineInputBorder(),
          ),
          type: SelectFormFieldType.dropdown,
          initialValue: 'mtn',
          labelText: isFr
              ? 'Moyen de paiement mobile ou par carte'
              : 'Mobile or card payment method',
          items: listeMethodePaiements,
          onChanged: (val) => onChangeMethodePaiement(val),
          onSaved: (val) => print(val),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: telController,
          decoration: InputDecoration(
            labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
            labelText: isFr
                ? 'Indicatif + Numéro du paiement'
                : 'Country code + Payment number',
            border: const OutlineInputBorder(),
          ),
        ),
        const SizedBox(height: 14),
        SizedBox(
          width: MediaQuery.of(context).size.width * 0.95,
          child: ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: primaryColor,
              shape: const StadiumBorder(),
              padding: const EdgeInsets.symmetric(vertical: 14),
            ),
            child: Text(
              _desactive2
                  ? (isFr ? "Patientez..." : "Wait...")
                  : (isFr ? "Payer et Booster" : "Pay and Boost"),
              style: GoogleFonts.poppins(
                  color: Colors.white, fontWeight: FontWeight.w600),
            ),
            onPressed: () {
              if (!telIsVerified) {
                showConfNumeroWhatsapp(context);
              } else {
                _desactive2 ? null : newBoostPayant();
              }
            },
          ),
        ),
        const SizedBox(height: 10),
      ],
    );
  }
}
