// ignore_for_file: use_build_context_synchronously
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/noti.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:select_form_field/select_form_field.dart';

class VendeurRechargePage extends StatefulWidget {
  @override
  State<VendeurRechargePage> createState() => _VendeurRechargePageState();
}

class _VendeurRechargePageState extends State<VendeurRechargePage> {
  bool _desactive = false;
  bool _loadingMethods = false;
  dynamic _valueMethodePaiement;
  final TextEditingController _montantController = TextEditingController();
  late final TextEditingController _telController =
      TextEditingController(text: tel);
  String? _erreurMontant;
  String? _erreurTel;

  @override
  void initState() {
    super.initState();
    if (listeMethodePaiements.isEmpty) {
      _chargerMethodesPaiement();
    }
  }

  @override
  void dispose() {
    _montantController.dispose();
    _telController.dispose();
    super.dispose();
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
            listeMethodePaiements =
                (data["listeMethodePaiements"] as List<dynamic>)
                    .map((item) => item as Map<String, dynamic>)
                    .toList();
            if (listeMethodePaiements.isNotEmpty) {
              _valueMethodePaiement = listeMethodePaiements[0]['value'];
            }
          });
        }
      }
    } catch (_) {
      // Garde le sélecteur vide en cas d'erreur réseau
    } finally {
      if (mounted) setState(() => _loadingMethods = false);
    }
  }

  Future<void> _recharger() async {
    // Validation côté client
    final int montant = int.tryParse(_montantController.text.trim()) ?? 0;
    if (montant < 500) {
      setState(() {
        _erreurMontant = langUserPhone != "fr"
            ? "Minimum amount is 500 FCFA"
            : "Le montant minimum est de 500 FCFA";
      });
      return;
    }
    setState(() => _erreurMontant = null);

    final String telSaisi = _telController.text.trim();
    if (telSaisi.isEmpty) {
      setState(() {
        _erreurTel = langUserPhone != "fr"
            ? "Please enter your phone number (international format)"
            : "Veuillez saisir votre numéro (format international)";
      });
      return;
    }
    setState(() => _erreurTel = null);

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

    setState(() => _desactive = true);

    try {
      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/vendeur/recharge'));
      request.fields.addAll({
        'uid': uidUser,
        'methodePaiementId': _valueMethodePaiement.toString(),
        'montant': montant.toString(),
        'tel': telSaisi,
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
                ? "Balance topped up by $montant FCFA."
                : "Solde rechargé de $montant FCFA.",
            context,
          );
          setState(() {
            soldeProgrammeRecompense =
                (soldeProgrammeRecompense ?? 0) + montant;
            _desactive = false;
          });
          Navigator.pop(context);
        } else if (data["url"] != null && data["url"] != "none") {
          launchPaiement(data["url"]);
          setState(() => _desactive = false);
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

    return Scaffold(
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          isFr ? "Recharger mon solde" : "Top up balance",
          style: GoogleFonts.poppins(
              color: Colors.white, fontWeight: FontWeight.w400, fontSize: 18),
        ),
        leading: IconButton(
          onPressed: () => Navigator.pop(context),
          icon: const FaIcon(FontAwesomeIcons.chevronLeft, color: Colors.white),
        ),
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: 10),
              // Solde actuel
              Container(
                padding:
                    const EdgeInsets.symmetric(vertical: 18, horizontal: 16),
                decoration: BoxDecoration(
                  color: primaryColor.withOpacity(0.08),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: primaryColor.withOpacity(0.2)),
                ),
                child: Row(
                  children: [
                    FaIcon(FontAwesomeIcons.wallet,
                        color: primaryColor, size: 22),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            isFr ? "Solde actuel" : "Current balance",
                            style: GoogleFonts.poppins(
                                fontSize: 12, color: Colors.grey[500]),
                          ),
                          Text(
                            "${soldeProgrammeRecompense ?? 0} FCFA",
                            style: GoogleFonts.poppins(
                                fontSize: 20,
                                fontWeight: FontWeight.w700,
                                color: primaryColor),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              // Champ montant
              Text(
                isFr ? "Montant à recharger" : "Amount to top up",
                style: GoogleFonts.poppins(
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                    color: Colors.grey[600]),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: _montantController,
                keyboardType: TextInputType.number,
                onChanged: (_) {
                  if (_erreurMontant != null) {
                    setState(() => _erreurMontant = null);
                  }
                },
                decoration: InputDecoration(
                  hintText: isFr ? "Minimum 500 FCFA" : "Minimum 500 FCFA",
                  hintStyle: GoogleFonts.poppins(color: Colors.grey[400]),
                  border: const OutlineInputBorder(),
                  errorText: _erreurMontant,
                  suffixText: "FCFA",
                ),
                style: GoogleFonts.poppins(fontSize: 15),
              ),
              const SizedBox(height: 20),
              // Champ téléphone de paiement
              Text(
                isFr
                    ? "Indicatif + Numéro du paiement"
                    : "Dial code + Payment number",
                style: GoogleFonts.poppins(
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                    color: Colors.grey[600]),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: _telController,
                keyboardType: TextInputType.phone,
                onChanged: (_) {
                  if (_erreurTel != null) {
                    setState(() => _erreurTel = null);
                  }
                },
                decoration: InputDecoration(
                  hintText: isFr ? "Ex: +22890000000" : "e.g. +22890000000",
                  hintStyle: GoogleFonts.poppins(color: Colors.grey[400]),
                  border: const OutlineInputBorder(),
                  errorText: _erreurTel,
                  prefixIcon: const Icon(Icons.phone),
                ),
                style: GoogleFonts.poppins(fontSize: 15),
              ),
              const SizedBox(height: 20),
              // Sélecteur méthode de paiement
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
              const SizedBox(height: 24),
              // Bouton recharger
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
                    shape: const StadiumBorder(),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                  onPressed: _desactive ? null : _recharger,
                  child: Text(
                    _desactive
                        ? (isFr ? "Patientez..." : "Wait...")
                        : (isFr ? "Recharger" : "Top up"),
                    style: GoogleFonts.poppins(
                        color: Colors.white, fontWeight: FontWeight.w600),
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
    );
  }
}
