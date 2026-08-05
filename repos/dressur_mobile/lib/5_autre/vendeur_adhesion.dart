// ignore_for_file: use_build_context_synchronously
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/noti.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:select_form_field/select_form_field.dart';

class VendeurAdhesionPage extends StatefulWidget {
  @override
  State<VendeurAdhesionPage> createState() => _VendeurAdhesionPageState();
}

class _VendeurAdhesionPageState extends State<VendeurAdhesionPage> {
  bool _desactive = false;
  bool _loadingMethods = false;
  dynamic _valueMethodePaiement;
  final TextEditingController _montantRechargeController = TextEditingController(text: '500');
  final TextEditingController _telController = TextEditingController(text: tel);
  String? _erreurMontant;

  @override
  void initState() {
    super.initState();
    if (listeMethodePaiements.isEmpty) {
      _chargerMethodesPaiement();
    }
  }

  Future<void> _chargerMethodesPaiement() async {
    setState(() => _loadingMethods = true);
    try {
      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/listeFormuleBoost'));
      request.fields.addAll({'typeBoost': 'date'});
      http.StreamedResponse response = await request.send();
      if (response.statusCode == 200) {
        var data1 = await response.stream.bytesToString();
        var data = convert.jsonDecode(data1);
        if (data["error"] == false) {
          setState(() {
            listeMethodePaiements = (data["listeMethodePaiements"] as List<dynamic>)
                .map((item) => item as Map<String, dynamic>)
                .toList();
          });
        }
      }
    } catch (_) {
      // Garde le sélecteur vide en cas d'erreur réseau
    } finally {
      if (mounted) setState(() => _loadingMethods = false);
    }
  }

  Future<void> _payerAdhesion() async {
    bool isConnected = await isConnectedToInternet();
    if (!mounted) return;
    if (!isConnected) {
      dangerNoti(
        langUserPhone != "fr" ? "Mistake!" : "Erreur!",
        langUserPhone != "fr"
            ? "You are not connected to the internet."
            : "Vous n'êtes pas connecté à internet.",
        context,
      );
      return;
    }

    if (_valueMethodePaiement == null) {
      dangerNoti(
        langUserPhone != "fr" ? "Attention!" : "Attention !",
        langUserPhone != "fr"
            ? "Please choose a payment method."
            : "Veuillez choisir une méthode de paiement.",
        context,
      );
      return;
    }

    // Validation montant recharge (obligatoire, min 500 FCFA)
    final int montantRecharge = int.tryParse(_montantRechargeController.text.trim()) ?? 0;
    if (montantRecharge < 500) {
      setState(() => _erreurMontant = langUserPhone != "fr"
          ? "An initial recharge of at least 500 FCFA is required"
          : "Une recharge initiale d'au moins 500 FCFA est obligatoire");
      return;
    }
    setState(() => _erreurMontant = null);

    setState(() => _desactive = true);

    try {
      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/vendeur/adhesion'));
      request.fields.addAll({
        'uid': uidUser,
        'methodePaiementId': _valueMethodePaiement.toString(),
        'montantRecharge': montantRecharge.toString(),
        'tel': _telController.text.trim(),
      });
      http.StreamedResponse response = await request.send();
      if (response.statusCode == 200) {
        var data1 = await response.stream.bytesToString();
        if (!mounted) return;
        var data = convert.jsonDecode(data1);
        if (data["error"] == true) {
          dangerNoti(data["titre"], data["message"], context);
          setState(() => _desactive = false);
        } else if (data["direct"] == true) {
          successNoti(
            langUserPhone != "fr" ? "Success" : "Succès",
            langUserPhone != "fr"
                ? "Payment confirmed. You are now a vendor!"
                : "Paiement confirmé. Vous êtes maintenant vendeur !",
            context,
          );
          setState(() {
            isVendeur = true;
            if (montantRecharge > 0) {
              soldeProgrammeRecompense = (soldeProgrammeRecompense) + montantRecharge;
            }
            _desactive = false;
          });
          Navigator.pop(context);
        } else if (data["url"] != null && data["url"] != "none") {
          launchPaiement(data["url"]);
          setState(() {
            isVendeur = true;
            _desactive = false;
          });
          Navigator.pop(context);
        } else {
          setState(() => _desactive = false);
        }
      } else {
        setState(() => _desactive = false);
      }
    } catch (e) {
      if (!mounted) return;
      dangerNoti(
        langUserPhone != "fr" ? "Error!" : "Erreur!",
        langUserPhone != "fr"
            ? "A network error occurred. Please try again."
            : "Une erreur réseau s'est produite. Veuillez réessayer.",
        context,
      );
      setState(() => _desactive = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bool isFr = langUserPhone == "fr";
    final bool isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          isFr ? "Devenir Vendeur" : "Become a Vendor",
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
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [

            // ── Header ──────────────────────────────────────────────────────
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
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
                  const FaIcon(FontAwesomeIcons.store, size: 48, color: Colors.white),
                  const SizedBox(height: 16),
                  Text(
                    isFr
                        ? "Devenez Vendeur Dressur"
                        : "Become a Dressur Vendor",
                    style: GoogleFonts.poppins(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    isFr
                        ? "Accédez à des avantages exclusifs et développez votre activité avec Dressur."
                        : "Access exclusive benefits and grow your business with Dressur.",
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

                  // ── Présentation ─────────────────────────────────────────
                  _sectionTitle(context, isFr ? "Présentation" : "Overview"),
                  _paragraph(
                    context,
                    isDark,
                    isFr
                        ? "Le statut Vendeur Dressur est réservé aux utilisateurs qui souhaitent bénéficier d'avantages commerciaux sur la plateforme. Il s'obtient via un paiement unique d'adhésion et donne accès à des fonctionnalités et tarifs privilégiés."
                        : "The Dressur Vendor status is reserved for users who want to benefit from commercial advantages on the platform. It is obtained through a one-time membership payment and gives access to privileged features and rates.",
                  ),

                  _divider(isDark),

                  // ── Avantages ────────────────────────────────────────────
                  _sectionTitle(context, isFr ? "Avantages vendeur" : "Vendor benefits"),
                  _bulletItem(context, FontAwesomeIcons.tag, isDark,
                    isFr
                        ? "10% de réduction sur toutes les promotions réseau sociaux"
                        : "10% discount on all social network promotions",
                  ),
                  _bulletItem(context, FontAwesomeIcons.wallet, isDark,
                    isFr
                        ? "Solde rechargeable utilisable pour vos achats sur Dressur"
                        : "Rechargeable balance usable for your purchases on Dressur",
                  ),
                  _bulletItem(context, FontAwesomeIcons.headset, isDark,
                    isFr
                        ? "Support prioritaire réservé aux vendeurs"
                        : "Priority support reserved for vendors",
                  ),

                  _divider(isDark),

                  // ── Comment ça marche ────────────────────────────────────
                  _sectionTitle(context, isFr ? "Comment ça marche" : "How it works"),
                  _stepItem(context, "1",
                    isFr
                        ? "Choisissez votre méthode de paiement (Mobile Money ou carte)."
                        : "Choose your payment method (Mobile Money or card).",
                  ),
                  _stepItem(context, "2",
                    isFr
                        ? "Payez les frais d'adhésion de 2 000 FCFA (paiement unique)."
                        : "Pay the 2,000 FCFA membership fee (one-time payment).",
                  ),
                  _stepItem(context, "3",
                    isFr
                        ? "Effectuez une recharge initiale de votre solde (minimum 500 FCFA)."
                        : "Make an initial top-up of your balance (minimum 500 FCFA).",
                  ),
                  _stepItem(context, "4",
                    isFr
                        ? "Votre statut vendeur est activé immédiatement après confirmation du paiement."
                        : "Your vendor status is activated immediately after payment confirmation.",
                  ),

                  _divider(isDark),

                  // ── Frais d'adhésion ─────────────────────────────────────
                  _sectionTitle(context, isFr ? "Frais d'adhésion" : "Membership fee"),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
                    decoration: BoxDecoration(
                      color: primaryColor.withOpacity(0.08),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: primaryColor.withOpacity(0.2)),
                    ),
                    child: Column(
                      children: [
                        Text(
                          "2 000 FCFA",
                          style: GoogleFonts.poppins(
                              fontSize: 32,
                              fontWeight: FontWeight.w700,
                              color: primaryColor),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 4),
                        Text(
                          isFr ? "Paiement unique, non répétable." : "One-time payment, non-repeatable.",
                          style: GoogleFonts.poppins(
                              fontSize: 12, color: Colors.grey[500]),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),

                  _divider(isDark),

                  // ── Formulaire de paiement ───────────────────────────────
                  _sectionTitle(context, isFr ? "Paiement" : "Payment"),

                  // ── Méthode de paiement ──────────────────────────────────
                  Text(
                    isFr ? "Méthode de paiement" : "Payment method",
                    style: GoogleFonts.poppins(
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                        color: Colors.grey[600]),
                  ),
                  const SizedBox(height: 8),
                  _loadingMethods
                      ? const Center(
                          child: CircularProgressIndicator(color: primaryColor))
                      : SelectFormField(
                          decoration: InputDecoration(
                            labelText: isFr
                                ? 'Moyen de paiement mobile ou par carte'
                                : 'Mobile or card payment method',
                            border: const OutlineInputBorder(),
                          ),
                          type: SelectFormFieldType.dropdown,
                          initialValue: _valueMethodePaiement?.toString(),
                          labelText: isFr
                              ? 'Moyen de paiement mobile ou par carte'
                              : 'Mobile or card payment method',
                          items: listeMethodePaiements,
                          onChanged: (val) =>
                              setState(() => _valueMethodePaiement = val),
                          onSaved: (val) {},
                        ),

                  const SizedBox(height: 20),

                  // ── Numéro de téléphone ───────────────────────────────────
                  Text(
                    isFr
                        ? "Indicatif + Numéro du paiement"
                        : "Country code + Payment number",
                    style: GoogleFonts.poppins(
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                        color: Colors.grey[600]),
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _telController,
                    keyboardType: TextInputType.phone,
                    decoration: InputDecoration(
                      labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
                      labelText: isFr
                          ? 'Indicatif + Numéro du paiement'
                          : 'Country code + Payment number',
                      border: const OutlineInputBorder(),
                    ),
                  ),

                  const SizedBox(height: 20),

                  // ── Recharge initiale ────────────────────────────────────
                  Text(
                    isFr
                        ? "Recharge initiale (obligatoire, min 500 FCFA)"
                        : "Initial recharge (required, min 500 FCFA)",
                    style: GoogleFonts.poppins(
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                        color: Colors.grey[600]),
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _montantRechargeController,
                    keyboardType: TextInputType.number,
                    onChanged: (_) {
                      if (_erreurMontant != null)
                        setState(() => _erreurMontant = null);
                    },
                    decoration: InputDecoration(
                      hintText: isFr ? "Ex : 5000" : "e.g. 5000",
                      border: const OutlineInputBorder(),
                      suffixText: "FCFA",
                      errorText: _erreurMontant,
                    ),
                  ),

                  const SizedBox(height: 24),

                  // ── Bouton payer ─────────────────────────────────────────
                  ValueListenableBuilder<TextEditingValue>(
                    valueListenable: _montantRechargeController,
                    builder: (context, value, _) {
                      final int recharge =
                          int.tryParse(value.text.trim()) ?? 0;
                      final int total = 2000 + (recharge > 0 ? recharge : 0);
                      final String totalStr =
                          total.toString().replaceAllMapped(
                        RegExp(r'(\d)(?=(\d{3})+$)'),
                        (m) => '${m[1]} ',
                      );
                      return SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: primaryColor,
                            shape: const StadiumBorder(),
                            padding: const EdgeInsets.symmetric(vertical: 14),
                          ),
                          onPressed: _desactive ? null : _payerAdhesion,
                          child: Text(
                            _desactive
                                ? (isFr ? "Patientez..." : "Wait...")
                                : (isFr
                                    ? "Payer $totalStr FCFA"
                                    : "Pay $totalStr FCFA"),
                            style: GoogleFonts.poppins(
                                color: Colors.white,
                                fontWeight: FontWeight.w600),
                          ),
                        ),
                      );
                    },
                  ),

                  const SizedBox(height: 24),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ── Helpers présentation ────────────────────────────────────────────────────

  Widget _sectionTitle(BuildContext context, String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Text(
        title,
        style: GoogleFonts.poppins(
          fontSize: 16,
          fontWeight: FontWeight.bold,
          color: Theme.of(context).textTheme.bodyLarge?.color,
        ),
      ),
    );
  }

  Widget _paragraph(BuildContext context, bool isDark, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Text(
        text,
        style: GoogleFonts.poppins(
          fontSize: 13,
          color: isDark ? Colors.grey[400] : Colors.grey[700],
          height: 1.5,
        ),
      ),
    );
  }

  Widget _bulletItem(
      BuildContext context, IconData icon, bool isDark, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 2),
            child: FaIcon(icon, size: 13, color: primaryColor),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: GoogleFonts.poppins(
                fontSize: 13,
                color: isDark ? Colors.grey[400] : Colors.grey[700],
                height: 1.4,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _stepItem(BuildContext context, String number, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 11,
            backgroundColor: primaryColor,
            child: Text(
              number,
              style: GoogleFonts.poppins(
                  color: Colors.white,
                  fontSize: 11,
                  fontWeight: FontWeight.bold),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(top: 2),
              child: Text(
                text,
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  color: Theme.of(context)
                      .textTheme
                      .bodyMedium
                      ?.color
                      ?.withOpacity(0.8),
                  height: 1.4,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _divider(bool isDark) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 16),
      child: Divider(
        color: isDark ? Colors.grey[800] : Colors.grey[200],
        height: 1,
      ),
    );
  }
}
