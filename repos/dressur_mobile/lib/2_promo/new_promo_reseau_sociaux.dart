// ignore_for_file: unnecessary_null_comparison, non_constant_identifier_names, prefer_final_fields, unused_field, use_build_context_synchronously
import 'dart:ui';

import 'package:dressur/components/noti_sys.dart';
import 'package:flutter/material.dart';
import 'package:flutter/widgets.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:dressur/components/noti.dart';
import 'package:select_form_field/select_form_field.dart';
import 'package:dressur/components/info_service_bottom_sheet.dart';

class PromotionReseauSociauxFormPage extends StatefulWidget {
  @override
  State<PromotionReseauSociauxFormPage> createState() =>
      _PromotionReseauSociauxFormPageState();
}

class _PromotionReseauSociauxFormPageState
    extends State<PromotionReseauSociauxFormPage> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _showInfoModal();
    });
  }

  void _showInfoModal({int countdown = 2}) {
    showServiceInfoModal(
      context,
      countdownSeconds: countdown,
      titleFr: "Informations Promotion Réseaux Sociaux",
      titleEn: "Social Network Promotion Information",
      items: const [
        ServiceInfoItem(
          icon: FontAwesomeIcons.sliders,
          textFr: "Vos préférences sur Dressur ne s'appliquent pas aux promotions sur les réseaux sociaux.",
          textEn: "Your Dressur preferences do not apply to social media promotions.",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.shareNodes,
          textFr: "Les comptes qui interagiront avec votre promotion seront sélectionnés directement par le réseau social.",
          textEn: "The accounts that will interact with your promotion will be selected directly by the social network.",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.thumbsUp,
          textFr: "Ce service peut vous aider à obtenir des votes, likes, commentaires et partages.",
          textEn: "This service can help you get votes, likes, comments and shares.",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.userGroup,
          textFr: "Ce service vous permet d'attirer davantage l'attention sur vos réseaux sociaux.",
          textEn: "This service allows you to attract more attention on your social media.",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.fileLines,
          textFr: "Lisez correctement la description du service réseau auquel vous voulez souscrire.",
          textEn: "Read carefully the description of the network service you want to subscribe to.",
        ),
      ],
    );
  }

  Widget _buildInfoButton() {
    final bool isFr = langUserPhone == "fr";
    return OutlinedButton.icon(
      onPressed: () => _showInfoModal(countdown: 0),
      icon: const FaIcon(FontAwesomeIcons.circleInfo, size: 14),
      label: Text(
        isFr ? "Voir les informations sur le service" : "View service information",
        style: GoogleFonts.poppins(fontSize: 13, fontWeight: FontWeight.w500),
      ),
      style: OutlinedButton.styleFrom(
        foregroundColor: primaryColor,
        side: BorderSide(color: primaryColor.withOpacity(0.5)),
        minimumSize: const Size(double.infinity, 48),
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        title: Text(
          (langUserPhone == "fr")
              ? "Nouvelle Promotion Réseau Sociaux"
              : "New Social Network Promotion",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
        leading: IconButton(
          onPressed: () {
            Navigator.pop(context);
          },
          icon: const FaIcon(
            FontAwesomeIcons.chevronLeft,
            color: Colors.white,
          ),
        ),
      ),
      body: SingleChildScrollView(
        child: Column(
          children: [
            const SizedBox(height: 10),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 10),
              child: _buildInfoButton(),
            ),
            const SizedBox(height: 16),
            Container(
              margin: const EdgeInsets.only(bottom: 10, left: 15, right: 15),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Text(
                    (langUserPhone == "fr")
                        ? "Promotion Réseau Sociaux"
                        : "Social Network Promotion",
                    style: GoogleFonts.poppins(
                      fontSize: 20,
                      fontWeight: FontWeight.w600,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),

                  // Formulaire
                  RegisterForm3(),

                  const SizedBox(height: 10),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class RegisterForm3 extends StatefulWidget {
  @override
  State<RegisterForm3> createState() => _RegisterForm3State();
}

class _RegisterForm3State extends State<RegisterForm3> {
  final ScrollController _scrollController = ScrollController();
  String? selectedSocialNetwork = "";
  var nombreAdresseMailForm = 0;
  int? initialService;
  bool _loading_liste_formule = false;
  bool _desactive2 = false;
  bool _desactive3 = false;
  bool _isValidLink = true;
  dynamic data;
  List<dynamic> listSocialNetworks = [];
  List<Map<String, dynamic>> listServices = [];
  dynamic valueMethodePaiement = "mtn";
  String? titreFormuleFils;
  dynamic idFormulePromoReseau = 0;
  var _message = "";

  String titre = "";
  int prix = 0;
  int id = 0;
  int qte = 0;
  int qteMin = 0;
  int qteMax = 0;
  String description = (langUserPhone == "fr")
      ? "Veuillez choisir un réseau social puis un service."
      : "Please choose a social network then a service.";

  final descriptionController = TextEditingController();
  final linkController = TextEditingController();
  final quantityController = TextEditingController(text: "0");
  final priceController = TextEditingController();
  final telController = TextEditingController(text: tel);

  void onChangeService(val) async {
    for (var service in listServices) {
      if ("$val" == "${service['id']}") {
        setState(() {
          idFormulePromoReseau = service['id'];
          id = service['id'];
          titre = service['titre'];
          prix = service['prix'];
          qte = service['qte'];
          qteMin = service['qteMin'];
          qteMax = service['qteMax'];
          description = service['description'];

          if (quantityController.text == "0" ||
              int.parse(quantityController.text) < qteMin) {
            quantityController.text = qteMin.toString();
          }
        });
      }
    }
    calculerPrixTotal();
  }

  void calculerPrixTotal() {
    _message = "";
    if (qte != 0) {
      int qteDemander = int.tryParse(quantityController.text) ?? 0;
      if (qteDemander >= qteMin && qteDemander <= qteMax) {
        int prixQteDemander = ((prix * qteDemander) / qte).round();
        quantityController.text = "$qteDemander";
        priceController.text = "$prixQteDemander";
        if (prixQteDemander < 100) {
          _message = (langUserPhone == "fr")
              ? "Le prix minimum pour une transaction est de 100 FCFA."
              : "The minimum price for a transaction is 100 FCFA.";
        }
      } else {
        _message = (langUserPhone == "fr")
            ? "La quantité doit être comprise entre $qteMin et $qteMax"
            : "The quantity must be between $qteMin and $qteMax";
      }
    } else {
      _message = (langUserPhone == "fr")
          ? "Veuillez choisir un réseau social puis un service."
          : "Please choose a social network then a service.";
    }
  }

  void listeFormPromoReseau() async {
    bool isConnected = await isConnectedToInternet();
    if (isConnected) {
      setState(() {
        _loading_liste_formule = true;
      });

      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/listeFormulePromoReseau'));
      request.fields.addAll({
        
      });

      http.StreamedResponse response = await request.send();

      if (response.statusCode == 200) {
        var data1 = await response.stream.bytesToString();
        var data = convert.jsonDecode(data1);
        if (data["error"] == false) {
          listSocialNetworks = data["listeFormulePromoReseau"];
          listeMethodePaiements =
              (data["listeMethodePaiements"] as List<dynamic>)
                  .map((item) => item as Map<String, dynamic>)
                  .toList();
          setState(() {
            _loading_liste_formule = false;
          });
        }
      }
    } else {
      if (langUserPhone != "fr") {
        dangerNoti(
            "Mistake!", "You are not connected to the internet.", context);
      } else {
        dangerNoti("Erreur!", "Vous n'êtes pas connecté à internet.", context);
      }
      setState(() {
        _loading_liste_formule = false;
      });
    }
  }

  void newPromoReseau() async {
    if (_message != "") {
      dangerNoti((langUserPhone == "fr") ? "Attention !!!" : "Attention !!!",
          _message, context);
    } else {
      setState(() {
        _desactive3 = true;
      });

      bool isConnected = await isConnectedToInternet();
      if (isConnected) {
        var request = http.MultipartRequest(
            'POST', Uri.parse('$generalRouteForApi/newPromoReseau'));
        request.fields.addAll({
          
          'uid': uidUser,
          'idFormulePromoReseau': idFormulePromoReseau.toString(),
          'qteDemander': quantityController.text,
          'prixQteDemander': priceController.text,
          'lien': linkController.text,
          'valueMethodePaiement': valueMethodePaiement,
          'tel': telController.text,
        });

        http.StreamedResponse response = await request.send();

        if (response.statusCode == 200) {
          var data1 = await response.stream.bytesToString();
          var data = convert.jsonDecode(data1);
          if (data["error"] == false) {
            cancelPromoReminderNotification();
            setState(() {
              _desactive3 = false;
            });
            if (data["solde_used"] == true) {
              successNoti(
                  (langUserPhone == "fr") ? "Succès" : "Success",
                  data["message"] ?? ((langUserPhone == "fr") ? "Solde débité. Promotion Réseau enregistrée." : "Balance debited. Social media promotion registered."),
                  context);
            } else if (data["direct"] == true) {
              dangerNoti(
                  (langUserPhone == "fr") ? "Attention !!!" : "Attention !!!",
                  (langUserPhone == "fr")
                      ? "Après confirmation du paiement, veuillez consulter la liste de vos promotions réseaux sociaux."
                      : "After payment confirmation, please view the list of your social media promotions.",
                  context);
            } else {
              launchPaiement(data["url"]);
              Navigator.pop(context);
              showNotification(
                  (langUserPhone == "fr")
                      ? "Paiement en cours !"
                      : "Payment in progress !",
                  (langUserPhone == "fr")
                      ? "Après confirmation du paiement, veuillez consulter la liste de vos promotions réseaux sociaux."
                      : "After payment confirmation, please view the list of your social media promotions.");
            }
          } else {
            dangerNoti(data["titre"], data["message"], context);
            setState(() {
              _desactive3 = false;
            });
          }
        } else {
          dangerNoti("ERROR", "ERROR", context);
          setState(() {
            _desactive3 = false;
          });
        }
      } else {
        if (langUserPhone != "fr") {
          dangerNoti(
              "Mistake!", "You are not connected to the internet.", context);
        } else {
          dangerNoti(
              "Erreur!", "Vous n'ètes pas connecté a internet.", context);
        }
        setState(() {
          _desactive3 = false;
        });
      }
    }
  }

  onChangeMethodePaiement(val) async {
    setState(() {
      valueMethodePaiement = val;
    });
  }

  @override
  void initState() {
    super.initState();

    // S'assurer que le ScrollController est attaché après le rendu initial
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        setState(() {
          listeFormPromoReseau();
        }); // Rafraîchir pour s'assurer que tout est prêt
      }
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      child: Column(
        children: [
          _loading_liste_formule
              ? const Center(
                  child: CircularProgressIndicator(color: primaryColor),
                )
              : listSocialNetworks.isEmpty
                  ? Center(
                      child: Text(
                        (langUserPhone == "fr")
                            ? "Erreur lors du chargement des formules de promotion réseau sociaux. Veuillez Contacter l'assistance Dressur."
                            : "Error loading social network promotion formulas. Please Contact Dressur Support.",
                        style: GoogleFonts.poppins(fontSize: 16),
                      ),
                    )
                  : SizedBox(
                      height: 60,
                      child: Scrollbar(
                        controller: _scrollController,
                        thumbVisibility: true,
                        thickness: 5,
                        radius: const Radius.circular(5),
                        child: SingleChildScrollView(
                          controller:
                              _scrollController, // Attach the ScrollController here
                          scrollDirection: Axis.horizontal,
                          child: Row(
                            spacing: 10,
                            children: List.generate(
                              listSocialNetworks.length,
                              (index) => ActionChip(
                                label: Row(
                                  children: [
                                    FaIcon(
                                      selectedSocialNetwork ==
                                              listSocialNetworks[index]['titre']
                                          ? FontAwesomeIcons.solidCircleCheck
                                          : FontAwesomeIcons.circle,
                                      size: 15,
                                    ),
                                    const SizedBox(width: 10),
                                    Text(
                                      listSocialNetworks[index]['titre'],
                                      style: GoogleFonts.poppins(
                                        fontSize: 15,
                                        fontWeight: FontWeight.w400,
                                      ),
                                    ),
                                  ],
                                ),
                                onPressed: () {
                                  setState(() {
                                    idFormulePromoReseau = 0;
                                    description = (langUserPhone == "fr")
                                        ? "Veuillez choisir un réseau social puis un service."
                                        : "Please choose a social network then a service.";
                                    _message = description;
                                    selectedSocialNetwork =
                                        listSocialNetworks[index]['titre'];
                                    listServices = (listSocialNetworks[index]
                                                ['lesFormulesFils']
                                            as List<dynamic>)
                                        .map((item) =>
                                            item as Map<String, dynamic>)
                                        .toList();
                                    initialService = listServices.isNotEmpty
                                        ? listServices.first['id']
                                        : null;
                                  });
                                },
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
          const SizedBox(height: 20),
          SelectFormField(
            decoration: InputDecoration(
              labelText: 'Services $selectedSocialNetwork',
              border: const OutlineInputBorder(),
            ),
            type: SelectFormFieldType.dropdown,
            initialValue: "1",
            labelText: 'Services $selectedSocialNetwork',
            items: listServices,
            onChanged: (val) => onChangeService(val),
            onSaved: (val) => print(val),
          ),
          const SizedBox(height: 10),
          Card(
            child: Container(
                width: MediaQuery.of(context).size.width * 1,
                padding: const EdgeInsets.fromLTRB(10, 10, 10, 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      description,
                      style: GoogleFonts.poppins(
                        fontWeight: FontWeight.w400,
                        fontSize: 16,
                      ),
                    ),
                  ],
                )),
          ),
          const SizedBox(height: 15),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              SizedBox(
                width: MediaQuery.of(context).size.width * 0.35,
                child: TextField(
                  controller: quantityController,
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    labelText: (langUserPhone == "fr") ? "Quantité" : "Quantity",
                    helperText: "Min : $qteMin - Max : $qteMax",
                    border: const OutlineInputBorder(),
                  ),
                  onChanged: (val) => calculerPrixTotal(),
                ),
              ),
              SizedBox(
                width: MediaQuery.of(context).size.width * 0.55,
                child: TextField(
                  controller: priceController,
                  maxLines: 3,
                  minLines: 1,
                  keyboardType:
                      const TextInputType.numberWithOptions(decimal: true),
                  decoration: InputDecoration(
                    labelText: (langUserPhone == "fr") ? "Prix" : "Price",
                    helperText: "FCFA",
                    border: const OutlineInputBorder(),
                    // Définir la couleur du texte lorsque le champ est désactivé
                    labelStyle: GoogleFonts.poppins(color: primaryColor),
                  ),
                  style: GoogleFonts.poppins(color: primaryColor),
                  enabled: false,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          TextField(
            controller: linkController,
            decoration: InputDecoration(
              labelText: (langUserPhone == "fr") ? "Lien" : "Link",
              border: const OutlineInputBorder(),
              errorText: _isValidLink
                  ? null
                  : (langUserPhone == "fr") ? "Veuillez saisir un lien valide." : "Please enter a valid link.",
            ),
            onChanged: (text) {
              setState(() {
                // Basic URL validation using a regular expression
                final RegExp urlRegex = RegExp(r"^(?:(?:https?|ftp)://)?\S+$",
                    caseSensitive: false);
                _isValidLink = urlRegex.hasMatch(text);
              });
            },
          ),
          const SizedBox(height: 15),
          SelectFormField(
            decoration: InputDecoration(
              labelText: (langUserPhone == "fr") ? 'Moyen de paiement mobile ou par carte' : 'Mobile or card payment method',
              border: const OutlineInputBorder(),
            ),
            type: SelectFormFieldType.dropdown,
            initialValue: 'mtn',
            labelText: (langUserPhone == "fr") ? 'Moyen de paiement mobile ou par carte' : 'Mobile or card payment method',
            items: listeMethodePaiements,
            onChanged: (val) => onChangeMethodePaiement(val),
            onSaved: (val) => print(val),
          ),
          const SizedBox(height: 15),
          TextField(
            controller: telController,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr") ? 'Indicatif + Numéro du paiement' : 'Country code + Payment number',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 15),
          SizedBox(
            width: MediaQuery.of(context).size.width * 0.95,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                shape: const StadiumBorder(),
                padding: const EdgeInsets.symmetric(
                  vertical: 13,
                ),
              ),
              child: Text(
                _desactive3
                    ? (langUserPhone == "fr")
                        ? "Patientez..."
                        : "Wait..."
                    : (langUserPhone == "fr")
                        ? "Payer et Démarrer"
                        : "Pay and Get Started",
                style: GoogleFonts.poppins(
                  color: Colors.white,
                ),
              ),
              onPressed: () {
                _desactive3 ? null : newPromoReseau();
              },
            ),
          ),
          const SizedBox(height: 15),
        ],
      ),
    );
  }
}
