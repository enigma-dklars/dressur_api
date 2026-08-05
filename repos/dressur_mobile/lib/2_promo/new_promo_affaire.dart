// ignore_for_file: use_build_context_synchronously, non_constant_identifier_names, prefer_typing_uninitialized_variables

import 'dart:convert';
import 'dart:convert' as convert;
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:http/http.dart' as http;
import 'dart:io';
import 'dart:async';
import 'package:dressur/components/padding_and_divider.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:path_provider/path_provider.dart';
import 'package:image/image.dart' as img;
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/noti.dart';
import 'package:dressur/components/noti_sys.dart';
import 'package:select_form_field/select_form_field.dart';
import 'package:dressur/components/info_service_bottom_sheet.dart';

class PromotionFormPage extends StatefulWidget {
  @override
  _PromotionFormPageState createState() => _PromotionFormPageState();
}

class _PromotionFormPageState extends State<PromotionFormPage> {
  dynamic type_promo_affaire = "produit_service";
  onChangeTypePromoAffaire(val) async {
    setState(() {
      type_promo_affaire = val;
    });
  }

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
      titleFr: "Informations Promotion Affaire",
      titleEn: "Business Promotion Information",
      items: const [
        ServiceInfoItem(
          icon: FontAwesomeIcons.magnifyingGlass,
          textFr:
              "Après envoi, votre promotion sera analysée par les administrateurs de Dressur.",
          textEn:
              "After submission, your promotion will be reviewed by Dressur administrators.",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.eye,
          textFr:
              "Si acceptée, elle sera visible par des milliers d'utilisateurs correspondant à vos préférences pays.",
          textEn:
              "If accepted, it will be visible to thousands of users matching your country preferences.",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.penToSquare,
          textFr: "Si rejetée, vous aurez la possibilité de la modifier.",
          textEn: "If rejected, you will have the option to modify it.",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.whatsapp,
          textFr:
              "Les utilisateurs intéressés vous contacteront sur votre numéro WhatsApp.",
          textEn: "Interested users will contact you on your WhatsApp number.",
        ),
        ServiceInfoItem(
          icon: FontAwesomeIcons.gift,
          textFr:
              "Les Promotions Affaires (Offre d'emploi et Demande d'emploi) sont gratuites.",
          textEn:
              "Business Promotions (Job Offer and Job Application) are free.",
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
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          (langUserPhone == "fr")
              ? 'Nouvelle Promotion Affaire'
              : 'New Business Promotion',
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
        backgroundColor: primaryColor,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.only(left: 10, top: 5, right: 10, bottom: 5),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const SizedBox(height: 10),
            _buildInfoButton(),
            const SizedBox(height: 16),
            Text(
              (langUserPhone == "fr")
                  ? "Nouvelle Promotion Affaire"
                  : "New Business Promotion",
              style: GoogleFonts.poppins(
                fontSize: 20,
                fontWeight: FontWeight.w600,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            SelectFormField(
              decoration: InputDecoration(
                labelText: (langUserPhone == "fr")
                    ? 'Type Promotion Affaire'
                    : 'Business Promotion Type',
                border: const OutlineInputBorder(),
              ),
              type: SelectFormFieldType.dropdown,
              initialValue: 'produit_service',
              labelText: (langUserPhone == "fr")
                  ? 'Type Promotion Affaire'
                  : 'Business Promotion Type',
              items: listeTypePromoAffaire(),
              onChanged: (val) => onChangeTypePromoAffaire(val),
              onSaved: (val) => print(val),
            ),
            const SizedBox(height: 5),
            DressurDivider(),
            const SizedBox(height: 5),
            if (type_promo_affaire == "produit_service") ...[
              ProduitsServices()
            ],
            if (type_promo_affaire == "dmd_emploi") ...[DemandesEmploi()],
            if (type_promo_affaire == "offre_emploi") ...[OffresEmploi()],
            const SizedBox(height: 15),
          ],
        ),
      ),
    );
  }
}

class ProduitsServices extends StatefulWidget {
  @override
  State<ProduitsServices> createState() => _ProduitsServicesState();
}

class _ProduitsServicesState extends State<ProduitsServices> {
  bool load = false;
  File? _imageFile;
  final TextEditingController _textEditingController = TextEditingController();
  bool _isSending = false;
  var _message = "";
  dynamic idFormulBoost = 0;
  dynamic valueMethodePaiement = "mtn";
  bool loading_formule_gratuit = false;
  List<Map<String, dynamic>> listeDesFormules = [];
  int value = 0;
  var label = "";
  int prix = 0;
  int jours = 0;
  final telController = TextEditingController(text: tel);
  final whatsappContactController = TextEditingController(text: tel);

  int prixBoost = 0;
  bool _participateInReward = false;
  int _rewardBudget = 0; // Budget choisi : 500, 1000, 2000 ou 5000 FCFA
  bool _boostFacebook = false;
  final _boostFacebookAmountController = TextEditingController(text: '700');

  int joursBoost = 0;
  double get _boostFacebookAmount {
    if (!_boostFacebook) return 0.0;
    return (int.tryParse(_boostFacebookAmountController.text) ?? 0).toDouble();
  }

  double get _subTotal =>
      prixBoost + _rewardProgramAmount + _dressurStatusAmount + _boostFacebookAmount;

  bool _publishOnDressurStatus = false;
  // 25% du prix de la formule choisie
  static const double _dressurStatusRate = 0.25;

  // --- CALCULS DES MONTANTS ---
  double get _rewardProgramAmount {
    if (!_participateInReward || _rewardBudget == 0) return 0.0;
    // Budget fixe choisi par le promoteur (inclut 20% commission Dressur)
    return _rewardBudget.toDouble();
  }

  double get _rewardPoolAmount => _rewardBudget * 0.8;
  double get _rewardCommissionAmount => _rewardBudget * 0.2;

  double get _dressurStatusAmount {
    if (!_publishOnDressurStatus || prixBoost < 1000) return 0.0;
    // 25% du prix de la formule choisie
    return prixBoost * _dressurStatusRate;
  }

  bool isImageSquare(File imageFile) {
    final image = img.decodeImage(File(imageFile.path).readAsBytesSync());
    if (image == null) return false;
    final width = image.width;
    final height = image.height;
    final aspectRatio = width / height;
    return aspectRatio >= 0.8 && aspectRatio <= 1.2;
  }

  Future<void> _selectImage() async {
    final picker = ImagePicker();
    final pickedImage = await picker.pickImage(source: ImageSource.gallery);
    if (pickedImage != null) {
      final imageFile = File(pickedImage.path);
      final fileSize = await imageFile.length();
      if (fileSize / (1024 * 1024) > 1) {
        dangerNoti(
            "Attention !!!",
            (langUserPhone == "fr")
                ? "L'image ne peut pas dépasser 1 Mo."
                : "Image size cannot exceed 1 MB.",
            context);
        return;
      }
      if (isImageSquare(imageFile)) {
        setState(() => _imageFile = imageFile);
      } else {
        dangerNoti(
            "Attention !!!",
            (langUserPhone == "fr")
                ? "L'image doit être proche d'un carré."
                : "The image should be close to a square.",
            context);
      }
    }
  }

  Future<void> _sendData() async {
    if (!telIsVerified) {
      showConfNumeroWhatsapp(context);
      return;
    }
    if (idFormulBoost == 0 ||
        idFormulBoost.toString().isEmpty ||
        _textEditingController.text.isEmpty ||
        _imageFile == null) {
      dangerNoti(
          "Attention !!!",
          (langUserPhone == "fr")
              ? 'Veuillez choisir une formule, entrer un texte et sélectionner une image.'
              : 'Please choose a formula, enter some text and select an image.',
          context);
      return;
    }
    if (_boostFacebook) {
      final amount = int.tryParse(_boostFacebookAmountController.text) ?? 0;
      if (amount < 700) {
        dangerNoti(
            "Attention !!!",
            (langUserPhone == "fr")
                ? "Le montant minimum pour le boost Facebook est de 700 FCFA."
                : "The minimum amount for the Facebook boost is 700 FCFA.",
            context);
        return;
      }
    }
    final waRegex = RegExp(r'^\+\d{11,}$');
    if (!waRegex.hasMatch(whatsappContactController.text.trim())) {
      dangerNoti(
          "Attention !!!",
          (langUserPhone == "fr")
              ? "Le numéro WhatsApp de contact doit commencer par + suivi d'au moins 11 chiffres."
              : "WhatsApp contact number must start with + followed by at least 11 digits.",
          context);
      return;
    }
    setState(() => _isSending = true);
    final url = Uri.parse('$generalRouteForApi/addProduitService');
    final request = http.MultipartRequest('POST', url);
    request.fields['idFormulePromoAffaire'] = idFormulBoost.toString();
    request.fields['text'] = _textEditingController.text;
    request.fields['uid'] = uidUser;
    request.fields['mode'] = "payant";
    request.fields['paymentMethod'] = valueMethodePaiement;
    request.fields['tel'] = telController.text;

    // Envoi des nouvelles options (à traiter en back-end plus tard)
    request.fields['inProgrammeRecompense'] = _participateInReward ? "1" : "0";
    request.fields['rewardBudget'] = _rewardBudget.toString();
    request.fields['publishOnDressurStatus'] =
        _publishOnDressurStatus ? "1" : "0";
    request.fields['boostFacebook'] = _boostFacebook ? "1" : "0";
    request.fields['montantBoostFacebook'] = _boostFacebookAmountController.text;
    request.fields['whatsappContact'] = whatsappContactController.text.trim();
    request.fields['totalAmount'] = _subTotal.toStringAsFixed(0);

    final tempDir = await getTemporaryDirectory();
    final filePath = '${tempDir.path}/temp_image.jpg';
    final image = img.decodeImage(_imageFile!.readAsBytesSync());
    final compressedImage = img.encodeJpg(image!, quality: 85);
    File(filePath).writeAsBytesSync(compressedImage);
    request.files.add(await http.MultipartFile.fromPath('image', filePath));

    final response = await request.send();
    if (response.statusCode == 200) {
      var data1 = await response.stream.bytesToString();
      if (!mounted) return;
      var data = jsonDecode(data1);
      if (data["error"] == true) {
        dangerNoti(data["titre"], data["message"], context);
        setState(() => _isSending = false);
      } else {
        setState(() {
          // 1. Réinitialisation des états de chargement et financiers
          _isSending = false;
          prixBoost = 0;
          joursBoost = 0;
          prix = 0;
          jours = 0;
          value = 0;
          idFormulBoost = 0;

          // 2. Réinitialisation des médias
          _imageFile = null;

          // 3. Réinitialisation des contrôleurs de texte
          _textEditingController.clear(); // Description
          whatsappContactController.text = tel; // Remettre le numéro utilisateur

          // 4. Réinitialisation des messages et labels
          _message = (langUserPhone == "fr")
              ? "Veuillez choisir une formule."
              : "Please choose a plan.";
          label = "";

          // 5. Réinitialisation des options spécifiques (Reward & Status & Boost Facebook)
          _participateInReward = false;
          _rewardBudget = 0;
          _publishOnDressurStatus = false;
          _boostFacebook = false;
          _boostFacebookAmountController.text = '700';

          // 6. Réinitialisation du mode de paiement par défaut
          valueMethodePaiement = "mtn";
        });

        cancelPromoReminderNotification();
        if (data["solde_used"] == true) {
          successNoti(
              (langUserPhone == "fr") ? "Succès" : "Success",
              data["message"] ?? ((langUserPhone == "fr") ? "Solde débité. Promotion Affaire enregistrée." : "Balance debited. Promotion registered."),
              context);
        } else if (data["direct"] == true) {
          successNoti(
              (langUserPhone == "fr") ? "Succès" : "Success",
              (langUserPhone == "fr")
                  ? "Veuillez confirmer le paiement pour finaliser l'enregistrement de votre promotion."
                  : "Please confirm payment to finalize the registration of your promotion.",
              context);
        } else {
          launchPaiement(data["url"]);
          successNoti(
              (langUserPhone == "fr") ? "Succès" : "Success",
              (langUserPhone == "fr")
                  ? "Veuillez confirmer le paiement pour finaliser l'enregistrement de votre promotion."
                  : "Please confirm payment to finalize the registration of your promotion.",
              context);
        }
      }
    } else {
      dangerNoti((langUserPhone == "fr") ? "Erreur" : "Error",
          'Code : ${response.statusCode}', context);
      setState(() => _isSending = false);
    }
  }

  void listeFormulePromoAffaire() async {
    if (await isConnectedToInternet()) {
      setState(() => loading_formule_gratuit = true);
      var response = await http
          .post(Uri.parse('$generalRouteForApi/listeFormulePromoAffaire'));
      if (response.statusCode == 200) {
        var data = jsonDecode(response.body);
        if (data["error"] == false) {
          setState(() {
            loading_formule_gratuit = false;
            listeDesFormules =
                List<Map<String, dynamic>>.from(data["listeFormulBoost"]);
            listeMethodePaiements =
                List<Map<String, dynamic>>.from(data["listeMethodePaiements"]);
            _message = (langUserPhone == "fr")
                ? "Veuillez choisir une formule."
                : "Please choose a plan.";
          });
        }
      }
    } else {
      dangerNoti(
          "Erreur",
          (langUserPhone == "fr")
              ? "Pas de connexion internet."
              : "No internet connection.",
          context);
      setState(() => loading_formule_gratuit = false);
    }
  }

  onChangeFormulBoost(val) {
    final selected = listeDesFormules
        .firstWhere((e) => e['value'].toString() == val.toString());
    setState(() {
      idFormulBoost = int.parse(val.toString());
      prixBoost = selected['prix'];
      joursBoost = selected['jours'];

      value = selected['value'];
      label = selected['label'];
      prix = selected['prix'];
      jours = selected['jours'];
      idFormulBoost = val;
      _message = (langUserPhone == "fr")
          ? "Formule de $jours jour(s) pour $prix FCFA."
          : "Plan of $jours day(s) for $prix FCFA.";

      // Désactiver le Statut WhatsApp si la formule est < 1000 FCFA
      if (prixBoost < 1000) {
        _publishOnDressurStatus = false;
      }
    });
  }

  @override
  void initState() {
    super.initState();
    listeFormulePromoAffaire();
  }

  @override
  void dispose() {
    _boostFacebookAmountController.dispose();
    whatsappContactController.dispose();
    super.dispose();
  }

  Widget _buildNumberInput(String label, TextEditingController controller) {
    return Row(
      children: [
        Expanded(child: Text(label, style: GoogleFonts.poppins(fontSize: 13))),
        SizedBox(
          width: 100,
          child: TextField(
            controller: controller,
            keyboardType: TextInputType.number,
            decoration:
                InputDecoration(isDense: true, border: OutlineInputBorder()),
          ),
        ),
      ],
    );
  }

  Widget _infoBox(String text, Color color) {
    return Container(
      width: double.infinity,
      padding: EdgeInsets.all(10),
      decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: color.withOpacity(0.3))),
      child: Text(text,
          style: GoogleFonts.poppins(
              color: color, fontWeight: FontWeight.bold, fontSize: 13),
          textAlign: TextAlign.center),
    );
  }

  Widget _buildRecap() {
    return Container(
      padding: EdgeInsets.all(15),
      decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: Colors.grey[300]!)),
      child: Column(
        children: [
          _recapRow((langUserPhone == "fr") ? "Formule Boost" : "Boost Plan",
              prixBoost.toDouble()),
          if (_participateInReward)
            _recapRow(
                (langUserPhone == "fr")
                    ? "Programme Récompense"
                    : "Reward Program",
                _rewardProgramAmount),
          if (_publishOnDressurStatus)
            _recapRow(
                (langUserPhone == "fr")
                    ? "Statut WhatsApp & Story Dressur"
                    : "WhatsApp Status & Story Dressur",
                _dressurStatusAmount),
          if (_boostFacebook)
            _recapRow(
                (langUserPhone == "fr")
                    ? "Boost Facebook"
                    : "Facebook Boost",
                _boostFacebookAmount),
          Divider(height: 20),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text((langUserPhone == "fr") ? "TOTAL" : "TOTAL",
                  style: GoogleFonts.poppins(
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                  )),
              Text("${_subTotal.toStringAsFixed(0)} FCFA",
                  style: GoogleFonts.poppins(
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                    color: primaryColor,
                  )),
            ],
          ),
        ],
      ),
    );
  }

  Widget _recapRow(String label, double amount,
      {bool isBold = false, bool isSmall = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label,
              style: GoogleFonts.poppins(
                  fontSize: isSmall ? 11 : 13,
                  fontWeight: isBold ? FontWeight.bold : FontWeight.normal)),
          Text("${amount.toStringAsFixed(0)} F",
              style: GoogleFonts.poppins(
                  fontSize: isSmall ? 11 : 13,
                  fontWeight: isBold ? FontWeight.bold : FontWeight.normal)),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    Theme.of(context);
    return Container(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const SizedBox(height: 5),
          loading_formule_gratuit
              ? const Center(
                  child: CircularProgressIndicator(color: primaryColor))
              : SelectFormField(
                  decoration: InputDecoration(
                      labelText: (langUserPhone == "fr")
                          ? 'Formule de Boost'
                          : 'Boost Plan',
                      border: const OutlineInputBorder()),
                  type: SelectFormFieldType.dropdown,
                  initialValue: '0',
                  items: listeDesFormules,
                  onChanged: (val) => onChangeFormulBoost(val),
                ),
          const SizedBox(height: 10),
          if (_message.isNotEmpty)
            Text(_message,
                style: GoogleFonts.poppins(fontSize: 14, color: primaryColor),
                textAlign: TextAlign.center),
          const SizedBox(height: 15),

          ElevatedButton(
            onPressed: _isSending ? null : _selectImage,
            style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                shape: const StadiumBorder(),
                padding: const EdgeInsets.symmetric(vertical: 13)),
            child: Text(
                (langUserPhone == "fr")
                    ? "Sélectionner l'image"
                    : 'Select image',
                style: GoogleFonts.poppins(
                    color: Colors.white, fontWeight: FontWeight.w600)),
          ),
          if (_imageFile != null)
            Container(
                margin: const EdgeInsets.only(top: 10),
                child: Image.file(_imageFile!, fit: BoxFit.contain)),
          const SizedBox(height: 10),
          TextField(
              controller: _textEditingController,
              minLines: 1,
              maxLines: 10,
              decoration: InputDecoration(
                  labelText: 'Description',
                  border: const OutlineInputBorder())),

          const SizedBox(height: 10),

          // --- WHATSAPP CONTACT ---
          TextFormField(
            controller: whatsappContactController,
            keyboardType: TextInputType.phone,
            decoration: InputDecoration(
              labelText: (langUserPhone == "fr")
                  ? 'Numéro WhatsApp de contact'
                  : 'WhatsApp contact number',
              hintText: '+22890000000',
              prefixIcon: Icon(FontAwesomeIcons.whatsapp, color: Colors.green),
              border: const OutlineInputBorder(),
            ),
            validator: (value) {
              final waRegex = RegExp(r'^\+\d{11,}$');
              if (value == null || !waRegex.hasMatch(value.trim())) {
                return (langUserPhone == "fr")
                    ? 'Format invalide : + suivi d\'au moins 11 chiffres'
                    : 'Invalid format: + followed by at least 11 digits';
              }
              return null;
            },
          ),

          const SizedBox(height: 10),

          // --- SECTION PROGRAMME DE RÉCOMPENSE ---
          _buildOptionHeader(
              FontAwesomeIcons.star,
              (langUserPhone == "fr")
                  ? "Programme de Récompense"
                  : "Reward Program",
              _participateInReward),
          SwitchListTile(
            title: Text(
                (langUserPhone == "fr")
                    ? "Ajouter votre promotion au programme"
                    : "Add your promotion to the program",
                style: GoogleFonts.poppins(
                    fontSize: 14, fontWeight: FontWeight.w500)),
            subtitle: Text(
                (langUserPhone == "fr")
                    ? "Attirez plus de vues en récompensant les utilisateurs qui le publieront sur leur statut WhatsApp. Dressur se charge de la mise en application et de la vérification."
                    : "Get more views by rewarding users who share it on their WhatsApp status. Dressur handles the implementation and verification.",
                style: GoogleFonts.poppins(fontSize: 11)),
            value: _participateInReward,
            activeColor: primaryColor,
            onChanged: (val) => setState(() {
              _participateInReward = val;
              if (val) _rewardBudget = 500;
            }),
          ),
          if (_participateInReward) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Column(
                children: [
                  Text(
                    (langUserPhone == "fr")
                        ? "Choisissez votre budget récompenses"
                        : "Choose your reward budget",
                    style: GoogleFonts.poppins(fontSize: 13, fontWeight: FontWeight.w500),
                  ),
                  const SizedBox(height: 10),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [500, 1000, 2000, 5000].map((budget) {
                      final selected = _rewardBudget == budget;
                      return GestureDetector(
                        onTap: () => setState(() => _rewardBudget = budget),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
                          decoration: BoxDecoration(
                            color: selected ? primaryColor : Colors.transparent,
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: primaryColor),
                          ),
                          child: Text(
                            "${budget == 1000 ? '1 000' : budget == 2000 ? '2 000' : budget == 5000 ? '5 000' : budget} F",
                            style: GoogleFonts.poppins(
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                              color: selected ? Colors.white : primaryColor,
                            ),
                          ),
                        ),
                      );
                    }).toList(),
                  ),
                ],
              ),
            ),
          ],

          const SizedBox(height: 15),

          // --- SECTION STATUT WHATSAPP & STORY DRESSUR (formule >= 1000 FCFA) ---
          if (prixBoost >= 1000) ...[
          _buildOptionHeader(
            FontAwesomeIcons.solidCircleCheck,
            (langUserPhone == "fr")
                ? "Statut WhatsApp de Dressur et Story"
                : "Dressur WhatsApp Status & Story",
            _publishOnDressurStatus,
          ),
          SwitchListTile(
            title: Text(
              (langUserPhone == "fr")
                  ? "Ajouter au statut WhatsApp de Dressur et à la Story sur Dressur"
                  : "Add to Dressur WhatsApp status and Dressur Story",
              style: GoogleFonts.poppins(
                  fontSize: 14, fontWeight: FontWeight.w500),
            ),
            subtitle: Text(
              (langUserPhone == "fr")
                  ? "Bénéficiez d'une visibilité maximale : votre promotion sera publiée sur le statut WhatsApp de Dressur et apparaîtra dans les Stories sur l'application pendant toute la durée de votre promotion."
                  : "Get maximum visibility: your promotion will be published on Dressur's WhatsApp status and appear in Stories on the app for the entire duration of your promotion.",
              style: GoogleFonts.poppins(fontSize: 11),
            ),
            value: _publishOnDressurStatus,
            activeColor: primaryColor,
            onChanged: (val) => setState(() => _publishOnDressurStatus = val),
          ),
          if (_publishOnDressurStatus)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 10),
              child: Column(
                children: [
                  _infoBox(
                      (langUserPhone == "fr")
                          ? "Frais Statut WhatsApp & Story Dressur : ${_dressurStatusAmount.toStringAsFixed(0)} FCFA"
                          : "WhatsApp Status & Story Dressur fee: ${_dressurStatusAmount.toStringAsFixed(0)} FCFA",
                      Colors.blue),
                ],
              ),
            ),
          ], // fin if (prixBoost >= 1000)

          const SizedBox(height: 15),

          // --- SECTION BOOST PAGE FACEBOOK ---
          _buildOptionHeader(
              FontAwesomeIcons.facebookF,
              (langUserPhone == "fr")
                  ? "Boost Page Facebook Dressur"
                  : "Dressur Facebook Page Boost",
              _boostFacebook),
          SwitchListTile(
            title: Text(
                (langUserPhone == "fr")
                    ? "Publier et booster sur la page Facebook de Dressur"
                    : "Post and boost on Dressur's Facebook page",
                style: GoogleFonts.poppins(
                    fontSize: 14, fontWeight: FontWeight.w500)),
            subtitle: Text(
                (langUserPhone == "fr")
                    ? "Votre promotion sera publiée sur la page Facebook officielle de Dressur et boostée auprès d'une audience ciblée. Budget minimum 700 FCFA."
                    : "Your promotion will be posted on Dressur's official Facebook page and boosted to a targeted audience. Minimum budget 700 FCFA.",
                style: GoogleFonts.poppins(fontSize: 11)),
            value: _boostFacebook,
            activeColor: primaryColor,
            onChanged: (val) => setState(() => _boostFacebook = val),
          ),
          if (_boostFacebook) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: TextField(
                controller: _boostFacebookAmountController,
                keyboardType: TextInputType.number,
                onChanged: (_) => setState(() {}),
                decoration: InputDecoration(
                  labelText: (langUserPhone == "fr")
                      ? "Budget boost (min. 700 FCFA)"
                      : "Boost budget (min. 700 FCFA)",
                  border: const OutlineInputBorder(),
                  suffixText: "FCFA",
                ),
              ),
            ),
          ],

          const SizedBox(height: 15),
          _buildRecap(),
          const SizedBox(height: 15),

          SelectFormField(
            decoration: const InputDecoration(
                labelText: 'Moyen de paiement', border: OutlineInputBorder()),
            type: SelectFormFieldType.dropdown,
            initialValue: 'mtn',
            items: listeMethodePaiements,
            onChanged: (val) => setState(() => valueMethodePaiement = val),
          ),
          const SizedBox(height: 10),
          TextField(
              controller: telController,
              decoration: InputDecoration(
                  labelText: (langUserPhone == "fr")
                      ? 'Numéro du paiement'
                      : 'Payment number',
                  border: const OutlineInputBorder())),

          const SizedBox(height: 15),
          ElevatedButton(
            onPressed: _isSending ? null : _sendData,
            style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                shape: const StadiumBorder(),
                padding: const EdgeInsets.symmetric(vertical: 15)),
            child: _isSending
                ? const CircularProgressIndicator(color: Colors.white)
                : Text(
                    (langUserPhone == "fr")
                        ? 'VALIDER ET PAYER'
                        : 'VALIDATE AND PAY',
                    style: GoogleFonts.poppins(
                        color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Widget _buildOptionHeader(IconData icon, String title, bool isActive) {
    return Row(
      children: [
        FaIcon(icon, color: isActive ? primaryColor : Colors.grey, size: 20),
        SizedBox(width: 10),
        Text(title,
            style: GoogleFonts.poppins(
                fontWeight: FontWeight.bold,
                fontSize: 15,
                color: isActive ? primaryColor : Colors.grey[700])),
      ],
    );
  }
}

class DemandesEmploi extends StatefulWidget {
  @override
  State<DemandesEmploi> createState() => _DemandesEmploiState();
}

class _DemandesEmploiState extends State<DemandesEmploi> {
  bool _desactive = false;
  var data;
  final titre_demande_poste_rechercher_controller = TextEditingController();
  final description_profil_demandeur_controller = TextEditingController();
  final competence_qualification_controller = TextEditingController();
  final niveau_experience_controller = TextEditingController();
  final secteur_activite_rechercher_controller = TextEditingController();
  final type_contrat_rechercher_controller = TextEditingController();
  final localisation_souhaite_controller = TextEditingController();
  final salaire_souhaite_controller = TextEditingController();
  final langues_parle_controller = TextEditingController();
  final lien_portfolio_controller = TextEditingController();
  final coordonne_demandeur_controller = TextEditingController();

  void add_dmd_emploi(
      String titre_demande_poste_rechercher,
      String description_profil_demandeur,
      String competence_qualification,
      String niveau_experience,
      String secteur_activite_rechercher,
      String type_contrat_rechercher,
      String localisation_souhaite,
      String salaire_souhaite,
      String langues_parle,
      String lien_portfolio,
      String coordonne_demandeur) async {
    bool isConnected = await isConnectedToInternet();
    if (isConnected) {
      setState(() {
        _desactive = true;
      });

      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/newDmdEmploi'));
      request.fields.addAll({
        'uid': uidUser,
        'titre_demande_poste_rechercher': titre_demande_poste_rechercher,
        'description_profil_demandeur': description_profil_demandeur,
        'competence_qualification': competence_qualification,
        'niveau_experience': niveau_experience,
        'secteur_activite_rechercher': secteur_activite_rechercher,
        'type_contrat_rechercher': type_contrat_rechercher,
        'localisation_souhaite': localisation_souhaite,
        'salaire_souhaite': salaire_souhaite,
        'langues_parle': langues_parle,
        'lien_portfolio': lien_portfolio,
        'coordonne_demandeur': coordonne_demandeur,
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
          });
          cancelPromoReminderNotification();
          successNoti(
              "Good",
              (langUserPhone == "fr")
                  ? "Votre demande d'emploi a été enregistrée et sera publiée après accord d'un des administrateurs de Dressur."
                  : "Your job application has been registered and will be published after approval by one of Dressur's administrators.",
              context);
          titre_demande_poste_rechercher_controller.clear();
          description_profil_demandeur_controller.clear();
          competence_qualification_controller.clear();
          niveau_experience_controller.clear();
          secteur_activite_rechercher_controller.clear();
          type_contrat_rechercher_controller.clear();
          localisation_souhaite_controller.clear();
          salaire_souhaite_controller.clear();
          langues_parle_controller.clear();
          lien_portfolio_controller.clear();
          coordonne_demandeur_controller.clear();
        }
      } else {
        if (langUserPhone != "fr") {
          dangerNoti("Mistake!",
              "We encountered a problem, contact the administrators.", context);
        } else {
          dangerNoti(
              "Erreur!",
              "Nous avons rencontré un problème, contacter les administrateurs.",
              context);
        }
        setState(() {
          _desactive = false;
        });
      }
    } else {
      if (langUserPhone != "fr") {
        dangerNoti(
            "Mistake!", "You are not connected to the internet.", context);
      } else {
        dangerNoti("Erreur!", "Vous n'ètes pas connecté a internet.", context);
      }
      setState(() {
        _desactive = false;
      });
    }
  }

  @override
  void initState() {
    super.initState();
    // listeFormulePromoAffaire(); // Loading the diary when the app starts
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      child: Column(
        children: [
          TextField(
            controller: titre_demande_poste_rechercher_controller,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? 'Titre de la demande ou poste recherché'
                  : 'Title of application or position sought',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: description_profil_demandeur_controller,
            minLines: 1,
            maxLines: 5,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? 'Description du profil du demandeur'
                  : "Description of the applicant's profile",
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: competence_qualification_controller,
            minLines: 1,
            maxLines: 3,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? 'Compétences et qualification (listes)'
                  : 'Skills and qualification (lists)',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: niveau_experience_controller,
            minLines: 1,
            maxLines: 2,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Niveau d'experience (Nombres d'année d'expérience)"
                  : 'Level of experience (Number of years of experience)',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: secteur_activite_rechercher_controller,
            minLines: 1,
            maxLines: 2,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Secteur d'activté rechercher"
                  : 'Sector of activity search',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: type_contrat_rechercher_controller,
            minLines: 1,
            maxLines: 3,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Type de contrat rechercher"
                  : 'Type of contract search',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: localisation_souhaite_controller,
            minLines: 1,
            maxLines: 3,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Localisation souhaité"
                  : 'Desired location',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: salaire_souhaite_controller,
            minLines: 1,
            maxLines: 1,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Salaire souhaité"
                  : 'Desired salary',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: langues_parle_controller,
            minLines: 1,
            maxLines: 3,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Langues parlées"
                  : 'Languages spoken',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: lien_portfolio_controller,
            minLines: 1,
            maxLines: 1,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Lien vers votre portfolio ou cv"
                  : 'Link to your portfolio or CV',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: coordonne_demandeur_controller,
            minLines: 1,
            maxLines: 2,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Coordonnées du demandeur"
                  : 'Applicant contact details',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          SizedBox(
            width: MediaQuery.of(context).size.width * 0.90,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                shape: const StadiumBorder(),
                padding: const EdgeInsets.symmetric(
                  vertical: 16,
                ),
              ),
              child: Text(
                _desactive
                    ? (langUserPhone == "fr")
                        ? "Patientez..."
                        : "Wait..."
                    : (langUserPhone == "fr")
                        ? "ENREGISTRER"
                        : "SAVED",
                style: GoogleFonts.poppins(
                  fontWeight: FontWeight.w400,
                  color: Colors.white,
                ),
              ),
              onPressed: () {
                _desactive
                    ? null
                    : add_dmd_emploi(
                        titre_demande_poste_rechercher_controller.text,
                        description_profil_demandeur_controller.text,
                        competence_qualification_controller.text,
                        niveau_experience_controller.text,
                        secteur_activite_rechercher_controller.text,
                        type_contrat_rechercher_controller.text,
                        localisation_souhaite_controller.text,
                        salaire_souhaite_controller.text,
                        langues_parle_controller.text,
                        lien_portfolio_controller.text,
                        coordonne_demandeur_controller.text,
                      );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class OffresEmploi extends StatefulWidget {
  @override
  State<OffresEmploi> createState() => _OffresEmploiState();
}

class _OffresEmploiState extends State<OffresEmploi> {
  bool _desactive = false;
  var data;
  final titre_poste_controller = TextEditingController();
  final description_poste_controller = TextEditingController();
  final competences_requises_controller = TextEditingController();
  final type_contrat_controller = TextEditingController();
  final lieu_travail_controller = TextEditingController();
  final salaire_controller = TextEditingController();
  final niveau_experience_controller = TextEditingController();
  final horaire_travail_controller = TextEditingController();
  final avantages_controller = TextEditingController();
  final dure_contrat_not_cdi_controller = TextEditingController();
  final contact_emploiyeur_controller = TextEditingController();
  final date_limite_candidature_controller = TextEditingController();
  final Lien_information_otionel_controller = TextEditingController();

  void add_offre_emploi(
      String titre_poste,
      String description_poste,
      String competences_requises,
      String type_contrat,
      String lieu_travail,
      String salaire,
      String niveau_experience,
      String horaire_travail,
      String avantages,
      String dure_contrat_not_cdi,
      String contact_emploiyeur,
      String date_limite_candidature,
      String Lien_information_otionel) async {
    bool isConnected = await isConnectedToInternet();
    if (isConnected) {
      setState(() {
        _desactive = true;
      });

      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/newOffreEmploi'));
      request.fields.addAll({
        'uid': uidUser,
        'titre_poste': titre_poste,
        'description_poste': description_poste,
        'competences_requises': competences_requises,
        'type_contrat': type_contrat,
        'lieu_travail': lieu_travail,
        'salaire': salaire,
        'niveau_experience': niveau_experience,
        'horaire_travail': horaire_travail,
        'avantages': avantages,
        'dure_contrat_not_cdi': dure_contrat_not_cdi,
        'contact_emploiyeur': contact_emploiyeur,
        'date_limite_candidature': date_limite_candidature,
        'lien_information_otionel': Lien_information_otionel,
      });

      http.StreamedResponse response = await request.send();

      if (response.statusCode == 200) {
        var data1 = await response.stream.bytesToString();
        print(data1);
        data = convert.jsonDecode(data1);
        if (data["error"] == true) {
          dangerNoti(data["titre"], data["message"], context);
          setState(() {
            _desactive = false;
          });
        } else {
          setState(() {
            _desactive = false;
          });
          cancelPromoReminderNotification();
          successNoti(
              "Good",
              (langUserPhone == "fr")
                  ? "Votre offre d'emploi a été enregistrée et sera publiée après accord d'un des administrateurs de Dressur."
                  : "Your job offer has been recorded and will be published after approval from one of the Dressur administrators.",
              context);
          titre_poste_controller.clear();
          description_poste_controller.clear();
          competences_requises_controller.clear();
          type_contrat_controller.clear();
          lieu_travail_controller.clear();
          salaire_controller.clear();
          niveau_experience_controller.clear();
          horaire_travail_controller.clear();
          avantages_controller.clear();
          dure_contrat_not_cdi_controller.clear();
          contact_emploiyeur_controller.clear();
          date_limite_candidature_controller.clear();
          Lien_information_otionel_controller.clear();
        }
      } else {
        if (langUserPhone != "fr") {
          dangerNoti("Mistake!",
              "We encountered a problem, contact the administrators.", context);
        } else {
          dangerNoti(
              "Erreur!",
              "Nous avons rencontré un problème, contacter les administrateurs.",
              context);
        }
        setState(() {
          _desactive = false;
        });
      }
    } else {
      if (langUserPhone != "fr") {
        dangerNoti(
            "Mistake!", "You are not connected to the internet.", context);
      } else {
        dangerNoti("Erreur!", "Vous n'ètes pas connecté a internet.", context);
      }
      setState(() {
        _desactive = false;
      });
    }
  }

  @override
  void initState() {
    super.initState();
    // listeFormulePromoAffaire(); // Loading the diary when the app starts
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      child: Column(
        children: [
          TextField(
            controller: titre_poste_controller,
            minLines: 1,
            maxLines: 1,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText:
                  (langUserPhone == "fr") ? "Titre du poste" : 'Job title',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: description_poste_controller,
            minLines: 1,
            maxLines: 5,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Description du poste"
                  : 'Job Description',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: competences_requises_controller,
            minLines: 1,
            maxLines: 5,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Compétences requises"
                  : 'Required skills',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: type_contrat_controller,
            minLines: 1,
            maxLines: 3,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Type de contrat"
                  : 'Type of contract',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: lieu_travail_controller,
            minLines: 1,
            maxLines: 2,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText:
                  (langUserPhone == "fr") ? "Lieu de travail" : 'Workplace',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: salaire_controller,
            minLines: 1,
            maxLines: 2,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr") ? "Salaire" : 'Salary',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: niveau_experience_controller,
            minLines: 1,
            maxLines: 2,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Niveau d'expériences"
                  : 'Level of experience',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: horaire_travail_controller,
            minLines: 1,
            maxLines: 3,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Horaire de travail"
                  : 'Work schedule',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: avantages_controller,
            minLines: 1,
            maxLines: 4,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr") ? "Avantages" : 'Benefits',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: dure_contrat_not_cdi_controller,
            minLines: 1,
            maxLines: 2,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Durée du contrat si ce n'est pas CDI"
                  : 'Duration of the contract if it is not permanent',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: contact_emploiyeur_controller,
            minLines: 1,
            maxLines: 3,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Contact de l'emploiyeur"
                  : 'Employer contact',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: date_limite_candidature_controller,
            minLines: 1,
            maxLines: 2,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Date limite de la candidature"
                  : 'Application deadline',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: Lien_information_otionel_controller,
            minLines: 1,
            maxLines: 2,
            decoration: InputDecoration(
              labelStyle: GoogleFonts.poppins(color: Colors.grey[400]),
              labelText: (langUserPhone == "fr")
                  ? "Lien vers plus d'information (optionel)"
                  : 'Link to more information (optional)',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 10),
          SizedBox(
            width: MediaQuery.of(context).size.width * 0.90,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                shape: const StadiumBorder(),
                padding: const EdgeInsets.symmetric(
                  vertical: 16,
                ),
              ),
              child: Text(
                _desactive
                    ? (langUserPhone == "fr")
                        ? "Patientez..."
                        : "Wait..."
                    : (langUserPhone == "fr")
                        ? "ENREGISTRER"
                        : "SAVED",
                style: GoogleFonts.poppins(
                  fontWeight: FontWeight.w400,
                  color: Colors.white,
                ),
              ),
              onPressed: () {
                _desactive
                    ? null
                    : add_offre_emploi(
                        titre_poste_controller.text,
                        description_poste_controller.text,
                        competences_requises_controller.text,
                        type_contrat_controller.text,
                        lieu_travail_controller.text,
                        salaire_controller.text,
                        niveau_experience_controller.text,
                        horaire_travail_controller.text,
                        avantages_controller.text,
                        dure_contrat_not_cdi_controller.text,
                        contact_emploiyeur_controller.text,
                        date_limite_candidature_controller.text,
                        Lien_information_otionel_controller.text,
                      );
              },
            ),
          ),
        ],
      ),
    );
  }
}
