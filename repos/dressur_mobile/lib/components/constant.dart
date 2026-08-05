// ignore_for_file: prefer_typing_uninitialized_variables, non_constant_identifier_names, prefer_const_constructors

import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:animate_do/animate_do.dart';
import 'package:dressur/components/111_generaleApiDomaine.dart';
import 'package:dressur/components/noti_sys.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show ByteData, Uint8List, rootBundle;
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:dressur/components/sql_helper.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:http/http.dart' as http;

const versionApp = '1.2.7';
const oldDatabaseName = 'one_dressur.db';
const nowDataBaseName = 'two_dressur.db';
bool modeReconnaissanceContactArrierePlan = false;
bool isNouvelUtilisateur = false;
const generalRouteForApi = '$generalApiDomaine/api';
const generalRouteForPromotionImage = '$generalApiDomaine/promotion/';
const generalRouteForStoryImage = '$generalApiDomaine/story/';

const tiktokDS = "https://www.tiktok.com/@bluelife.tech";
const facebookDS = "https://www.facebook.com/dressurds";
const instagramDS =
    "https://www.instagram.com/bluelife.tech?igsh=Mjcyc2tpMmw4dXhu";
const chaineWhatsApp = "https://whatsapp.com/channel/0029Vag8B6cCBtxMRvCqaA3t";
const facebookBLT = "https://www.facebook.com/bluelife.tech";
const tiktokBLT = "https://www.tiktok.com/@bluelife.tech";
const youtubeBLT = "https://www.youtube.com/@bluelife-tech";
const whatsappDSURL = "whatsapp://send?phone=22964044294";
const whatsappDSURLConfirmation =
    "whatsapp://send?phone=22964044294&text=WhatsApp%20Confirmation";
const dressurConditionUtilisation =
    "https://dressur.site/conditions-utilisation";
const dressurPolitiqueConfidentialite =
    "https://dressur.site/politique-confidentialite";
const dressurUrlPlaystore =
    "https://play.google.com/store/apps/details?id=com.dressur.ds";

const primaryColor = Color(0xFF2a4b9a);
const secondaryColor = Colors.indigoAccent;
const String createUserInfosTable = """
    CREATE TABLE userInfos(
      idDS INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
      tableName TEXT,
      id INTEGER,
      uid TEXT,
      contactTel TEXT
    );
  """;
const String createDiscussionTable = """
    CREATE TABLE discussion(
      id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
      uid TEXT,
      nom TEXT,
      date INTEGER
    );
  """;
const String createMessageTable = """
    CREATE TABLE message(
      id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
      emetteur TEXT,
      recepteur TEXT,
      message TEXT,
      dateEnvoi TEXT,
      vue TEXT
    );
  """;

int nbrAffichageAvertissement = 0;
List<dynamic> userChatInfo = [];
List<dynamic> contactsEnregistrer = [];
String? langUserPhone = "fr";
String preferencePaysText = "";
int nombreContacts = 0;
int nombreContactDispo = 0;
bool mailIsMaxxFire = false;
bool affUserName = false;
bool telIsVerified = false;
bool mailIsVerified = false;
bool ihaveConnexion = false;
bool admin = false;
bool isVendeur = false;
bool aUnPartenaire = false;
String? monCodePartenaire;
var estPartenaire = false;
var condNom = false;
var condTel = false;
var condMail = false;
var condAnciennete = false;
var condCumul = false;
var joursInscrit = 0;
var cumulFcfa = 0;
String? selectedContactAccountName;
String? selectedContactAccountType;
bool permissionAdd = false;
bool boostEnCours = false;
bool addPageActu = true;
bool isInscritProgrammeRecompense = false;
int nbrBoostContact = 0;
int nbrPromoAffaire = 0;
int nbrPromoReseau = 0;
var modeMotDePasseOublier = false;
var mailConnexion = "";
var textChargementEvolution = "Loading...";
var addUserOnAutreProfilPage = "oui";
var myDressurVersion = '1.2.7';
var lesPublicites;
var uidAutreUser;
var uidUser;
var name_complete;
var pseudo;
var nom;
var mail;
var pays;
var tel;
var apropos;
var createdAt;
var messageErreurPermissionAdd;
var tiktok;
var instagram;
var facebook;
var youtube;
var soldeProgrammeRecompense;

Future<bool> isConnectedToInternet() async {
  try {
    final result = await InternetAddress.lookup('google.com');
    if (result.isNotEmpty && result[0].rawAddress.isNotEmpty) {
      return true;
    }
  } catch (e) {
    return false;
  }
  return false;
}

Future<void> insertNumTelUserIntoDataBase(numberTel) async {
  SQLHelper.insert("userInfos", {
    'tableName': "numsTelUser",
    'contactTel': numberTel,
  });
}

Future<void> initUserInformations(userInfos) async {
  myDressurVersion = userInfos["myDressurVersion"];
  mailIsMaxxFire = userInfos["mailIsMaxxFire"];
  uidUser = userInfos["uid"];
  name_complete = userInfos["name_complete"];
  pseudo = userInfos["pseudo"];
  nom = userInfos["nom"];
  mail = userInfos["mail"];
  pays = userInfos["pays"];
  tel = userInfos["tel"];
  apropos = userInfos["apropos"];
  tiktok = userInfos["tiktok"];
  instagram = userInfos["instagram"];
  facebook = userInfos["facebook"];
  youtube = userInfos["youtube"];
  mailIsVerified = userInfos["mailIsVerified"];
  telIsVerified = userInfos["telIsVerified"];
  langUserPhone = userInfos["lang"];
  createdAt = userInfos["createdAt"];
  admin = userInfos["admin"];
  permissionAdd = userInfos["permissionAdd"];
  messageErreurPermissionAdd = userInfos["messageErreurPermissionAdd"];
  lesPublicites = userInfos["lesPublicites"];
  preferencePaysText = preferencePaysToText(userInfos["preferencePays"]);
  addPageActu = userInfos["addPageActu"] ?? true;
  nombreContactDispo = userInfos["nombreContactDispo"];
  nombreContacts = userInfos["nombreContacts"] ?? 0;
  boostEnCours = userInfos["boostEnCours"];
  isInscritProgrammeRecompense =
      userInfos["isInscritProgrammeRecompense"] ?? false;
  soldeProgrammeRecompense = userInfos["soldeProgrammeRecompense"] ?? 0;
  isVendeur = userInfos["vendeur"] ?? false;
  aUnPartenaire = userInfos["aUnPartenaire"] ?? false;
  monCodePartenaire = userInfos["codePartenaire"];
  estPartenaire = userInfos["estPartenaire"] ?? false;
  condNom = userInfos["condNom"] ?? false;
  condTel = userInfos["condTel"] ?? false;
  condMail = userInfos["condMail"] ?? false;
  condAnciennete = userInfos["condAnciennete"] ?? false;
  condCumul = userInfos["condCumul"] ?? false;
  joursInscrit = userInfos["joursInscrit"] ?? 0;
  cumulFcfa = userInfos["cumulFcfa"] ?? 0;
  SQLHelper.viderLaBaseDeDonneeLocal();

  SQLHelper.insert("userInfos", {
    'tableName': "user",
    'id': 0,
    'uid': userInfos["uid"],
  });
}

String preferencePaysToText(preferencePays) {
  preferencePaysText = "";
  countryCodes.entries.map((entry) {
    var dataIsselected = preferencePays.contains(entry.key) ? true : false;
    if (dataIsselected == true) {
      if (preferencePaysText.isEmpty) {
        preferencePaysText = entry.value;
      } else {
        preferencePaysText = "$preferencePaysText, ${entry.value}";
      }
    }
  }).toList();
  if (preferencePaysText.isEmpty) {
    preferencePaysText = (langUserPhone == 'fr') ? "Aucun Choix" : "No Choice";
  }
  return preferencePaysText;
}

void insertDressurContact() async {
  if ((await SQLHelper.getOneNumsTelUser("+22964044294")).isEmpty) {
    final newContact = Contact()
      ..isStarred = true
      ..name.first = "Dressur Assistance ✅"
      ..phones = [Phone("+22964044294")]
      ..emails = [Email("dressur.ds@gmail.com")]
      ..websites = [
        Website(whatsappDSURL),
        Website(facebookDS),
        Website(facebookBLT),
        Website(tiktokBLT),
        Website(youtubeBLT),
      ]
      ..accounts = (selectedContactAccountName != null &&
              selectedContactAccountType != null)
          ? [
              Account(
                '',
                selectedContactAccountType!,
                selectedContactAccountName!,
                [],
              ),
            ]
          : [];
    await newContact.insert();
    await insertNumTelUserIntoDataBase("+22964044294");
  }
}

List<Map<String, dynamic>> listeMethodePaiements = [];

// map de tous les pays du monde
Map<String, String> countryCodes = {
  '93': 'Afghanistan',
  '355': 'Albanie',
  '213': 'Algérie',
  '376': 'Andorre',
  '244': 'Angola',
  '1264': 'Anguilla',
  '1268': 'Antigua-et-Barbuda',
  '54': 'Argentine',
  '374': 'Arménie',
  '297': 'Aruba',
  '247': 'Ascension',
  '61': 'Australie',
  '43': 'Autriche',
  '994': 'Azerbaïdjan',
  '1242': 'Bahamas',
  '973': 'Bahreïn',
  '880': 'Bangladesh',
  '1246': 'Barbade',
  '375': 'Biélorussie',
  '32': 'Belgique',
  '501': 'Belize',
  '229': 'Bénin',
  '1441': 'Bermudes',
  '975': 'Bhoutan',
  '591': 'Bolivie',
  '387': 'Bosnie-Herzégovine',
  '267': 'Botswana',
  '55': 'Brésil',
  '246': 'Territoire britannique de l\'océan Indien',
  '1284': 'Îles Vierges britanniques',
  '673': 'Brunéi Darussalam',
  '359': 'Bulgarie',
  '226': 'Burkina Faso',
  '257': 'Burundi',
  '855': 'Cambodge',
  '237': 'Cameroun',
  '1': 'Canada',
  '238': 'Cap-Vert',
  '1345': 'Îles Caïmans',
  '236': 'République centrafricaine',
  '235': 'Tchad',
  '64': 'Nouvelle-Zélande',
  '56': 'Chili',
  '86': 'Chine',
  '57': 'Colombie',
  '269': 'Comores',
  '242': 'République du Congo',
  '243': 'République démocratique du Congo',
  '682': 'Îles Cook',
  '506': 'Costa Rica',
  '385': 'Croatie',
  '53': 'Cuba',
  '599': 'Curaçao',
  '357': 'Chypre',
  '420': 'République tchèque',
  '45': 'Danemark',
  '253': 'Djibouti',
  '1767': 'Dominique',
  '1809': 'République dominicaine',
  '1829': 'République dominicaine',
  '1849': 'République dominicaine',
  '593': 'Équateur',
  '20': 'Égypte',
  '503': 'El Salvador',
  '240': 'Guinée équatoriale',
  '291': 'Érythrée',
  '372': 'Estonie',
  '251': 'Éthiopie',
  '500': 'Îles Falkland',
  '298': 'Îles Féroé',
  '679': 'Fidji',
  '358': 'Finlande',
  '33': 'France',
  '594': 'Guyane française',
  '689': 'Polynésie française',
  '241': 'Gabon',
  '220': 'Gambie',
  '995': 'Géorgie',
  '49': 'Allemagne',
  '233': 'Ghana',
  '350': 'Gibraltar',
  '30': 'Grèce',
  '299': 'Groenland',
  '1473': 'Grenade',
  '590': 'Guadeloupe',
  '1671': 'Guam',
  '502': 'Guatemala',
  '44': 'Royaume-Uni',
  '224': 'Guinée',
  '245': 'Guinée-Bissau',
  '592': 'Guyana',
  '509': 'Haïti',
  '504': 'Honduras',
  '852': 'Hong Kong',
  '36': 'Hongrie',
  '354': 'Islande',
  '91': 'Inde',
  '62': 'Indonésie',
  '98': 'Iran',
  '964': 'Irak',
  '353': 'Irlande',
  '972': 'Israël',
  '39': 'Italie',
  '225': 'Côte d\'Ivoire',
  '1876': 'Jamaïque',
  '81': 'Japon',
  '962': 'Jordanie',
  '7': 'Kazakhstan',
  '254': 'Kenya',
  '686': 'Kiribati',
  '965': 'Koweït',
  '996': 'Kirghizistan',
  '856': 'Laos',
  '371': 'Lettonie',
  '961': 'Liban',
  '266': 'Lesotho',
  '231': 'Libéria',
  '218': 'Libye',
  '423': 'Liechtenstein',
  '370': 'Lituanie',
  '352': 'Luxembourg',
  '853': 'Macau',
  '389': 'Macédoine',
  '261': 'Madagascar',
  '265': 'Malawi',
  '60': 'Malaisie',
  '960': 'Maldives',
  '223': 'Mali',
  '356': 'Malte',
  '692': 'Îles Marshall',
  '596': 'Martinique',
  '222': 'Mauritanie',
  '230': 'Maurice',
  '262': 'Mayotte',
  '52': 'Mexique',
  '691': 'Micronésie',
  '373': 'Moldavie',
  '377': 'Monaco',
  '976': 'Mongolie',
  '382': 'Monténégro',
  '1664': 'Montserrat',
  '212': 'Maroc',
  '258': 'Mozambique',
  '95': 'Myanmar',
  '264': 'Namibie',
  '674': 'Nauru',
  '977': 'Népal',
  '31': 'Pays-Bas',
  '687': 'Nouvelle-Calédonie',
  '505': 'Nicaragua',
  '227': 'Niger',
  '234': 'Nigeria',
  '683': 'Niue',
  '672': 'Île Norfolk',
  '850': 'Corée du Nord',
  '1670': 'Îles Mariannes du Nord',
  '47': 'Norvège',
  '968': 'Oman',
  '92': 'Pakistan',
  '680': 'Palaos',
  '970': 'Palestine',
  '507': 'Panama',
  '675': 'Papouasie-Nouvelle-Guinée',
  '595': 'Paraguay',
  '51': 'Pérou',
  '63': 'Philippines',
  '48': 'Pologne',
  '351': 'Portugal',
  '1787': 'Porto Rico',
  '1939': 'Porto Rico',
  '974': 'Qatar',
  '40': 'Roumanie',
  '250': 'Rwanda',
  '290': 'Sainte-Hélène',
  '1869': 'Saint-Kitts-et-Nevis',
  '1758': 'Sainte-Lucie',
  '508': 'Saint-Pierre-et-Miquelon',
  '1784': 'Saint-Vincent-et-les-Grenadines',
  '685': 'Samoa',
  '378': 'Saint-Marin',
  '239': 'Sao Tomé-et-Principe',
  '966': 'Arabie saoudite',
  '221': 'Sénégal',
  '381': 'Serbie',
  '248': 'Seychelles',
  '232': 'Sierra Leone',
  '65': 'Singapour',
  '1721': 'Saint-Martin (partie néerlandaise)',
  '421': 'Slovaquie',
  '386': 'Slovénie',
  '677': 'Îles Salomon',
  '252': 'Somalie',
  '27': 'Afrique du Sud',
  '82': 'Corée du Sud',
  '211': 'Soudan du Sud',
  '34': 'Espagne',
  '94': 'Sri Lanka',
  '249': 'Soudan',
  '597': 'Suriname',
  '4779': 'Svalbard et Jan Mayen',
  '268': 'Swaziland',
  '46': 'Suède',
  '41': 'Suisse',
  '963': 'Syrie',
  '886': 'Taïwan',
  '992': 'Tadjikistan',
  '255': 'Tanzanie',
  '66': 'Thaïlande',
  '670': 'Timor oriental',
  '228': 'Togo',
  '690': 'Tokelau',
  '676': 'Tonga',
  '1868': 'Trinité-et-Tobago',
  '216': 'Tunisie',
  '90': 'Turquie',
  '993': 'Turkménistan',
  '1649': 'Îles Turques-et-Caïques',
  '688': 'Tuvalu',
  '1340': 'Îles Vierges américaines',
  '256': 'Ouganda',
  '380': 'Ukraine',
  '971': 'Émirats arabes unis',
  '598': 'Uruguay',
  '998': 'Ouzbékistan',
  '678': 'Vanuatu',
  '379': 'Cité du Vatican',
  '58': 'Venezuela',
  '84': 'Vietnam',
  '681': 'Wallis-et-Futuna',
  '967': 'Yémen',
  '260': 'Zambie',
  '263': 'Zimbabwe',
};

List<Map<String, dynamic>> listeTypePromoAffaire() => [
      {
        'value': 'produit_service',
        'label': (langUserPhone == "fr")
            ? 'Produits, Services, Événements etc.'
            : 'Products, Services, Events, etc.',
      },
      {
        'value': 'dmd_emploi',
        'label':
            (langUserPhone == "fr") ? "Demandes d'emploi" : "Job Applications",
      },
      {
        'value': 'offre_emploi',
        'label': (langUserPhone == "fr") ? "Offres d'emploi" : "Job Offers",
      },
    ];

String getCurrentYear() {
  // Obtenez la date actuelle
  DateTime now = DateTime.now();

  // Obtenez l'année à partir de la date actuelle
  String currentYear = now.year.toString();

  return currentYear;
}

void launchPhoneCall(String phoneNumber) async {
  final Uri _url = Uri.parse('tel:$phoneNumber');
  if (!await launchUrl(_url, mode: LaunchMode.externalApplication)) {
    throw 'Could not launch $_url';
  }
}

void launchSMS(String phoneNumber) async {
  final Uri _url = Uri.parse('sms:$phoneNumber');
  if (!await launchUrl(_url, mode: LaunchMode.externalApplication)) {
    throw 'Could not launch $_url';
  }
}

void launchEmail(String emailAddress) async {
  final Uri _url = Uri.parse('mailto:$emailAddress');
  if (!await launchUrl(_url, mode: LaunchMode.externalApplication)) {
    throw 'Could not launch $_url';
  }
}

void launchWhatsApp(String phoneNumber) async {
  final Uri _url = Uri.parse('https://wa.me/$phoneNumber');
  if (!await launchUrl(_url, mode: LaunchMode.externalApplication)) {
    throw 'Could not launch $_url';
  }
}

void launchPaiement(String urlPaiement) async {
  final Uri _url = Uri.parse(urlPaiement);
  if (!await launchUrl(_url, mode: LaunchMode.externalApplication)) {
    throw 'Could not launch $_url';
  }
}

Future<void> shareMessageWithImage(BuildContext context) async {
  var messageShare = (langUserPhone == "fr")
      ? "ADD WhatsApp Gratuitement.\nUtilisez simplement la fonctionnalité Boost Contact de Dressur après votre inscription.\nPour Android : https://play.google.com/store/apps/details?id=com.dressur.ds \nPour iPhone : https://dressur.site/inscription"
      : "Add WhatsApp for free.\nSimply use Dressur's Boost Contact feature after registering.\nFor Android: https://play.google.com/store/apps/details?id=com.dressur.ds \nFor iPhone: https://dressur.site/inscription";

  // Load the image from assets
  final ByteData bytes = await rootBundle.load('images/flyers_dressur_fr.png');
  final Uint8List list = bytes.buffer.asUint8List();

  final ByteData bytes2 = await rootBundle.load('images/flyers_dressur_en.png');
  final Uint8List list2 = bytes2.buffer.asUint8List();

  // Get the temporary directory
  final tempDir = await getTemporaryDirectory();

  final file = await File('${tempDir.path}/flyers_dressur_fr.png').create();
  file.writeAsBytesSync(list);

  final file2 = await File('${tempDir.path}/flyers_dressur_en.png').create();
  file2.writeAsBytesSync(list2);

  if (langUserPhone == "fr") {
    // Share the image and the message
    await Share.shareXFiles(
      [XFile(file.path)],
      text: messageShare,
      subject: 'Partager Dressur!',
    );
  } else {
    // Share the image and the message
    await Share.shareXFiles(
      [XFile(file2.path)],
      text: messageShare,
      subject: 'Share Dressur!',
    );
  }
}

Future<void> sharePromotion(
  BuildContext context,
  String imageLink,
  String imageName,
  String messageShare,
) async {
  messageShare += "\n\n";
  messageShare +=
      (langUserPhone == "fr") ? "Depuis Dressur : " : "From Dressur : ";
  messageShare += dressurUrlPlaystore;

  // Télécharger l'image depuis le lien HTTP
  final http.Response response;
  try {
    response = await http.get(Uri.parse(imageLink));
  } catch (_) {
    return;
  }

  if (response.statusCode == 200) {
    // Obtenir le répertoire temporaire
    final tempDir = await getTemporaryDirectory();

    // Créer un fichier et écrire l'image téléchargée
    final file = await File('${tempDir.path}/$imageName').create();
    await file.writeAsBytes(response.bodyBytes);

    // Partager l'image et le message
    await Share.shareXFiles(
      [XFile(file.path)],
      text: messageShare,
      subject:
          (langUserPhone == 'fr') ? 'Partager Promotion!' : 'Share Promotion!',
    );
  } else {
    // Gérer les erreurs de téléchargement
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
        content: Text(
          (langUserPhone == 'fr')
              ? 'Erreur de téléchargement de l\'image'
              : 'Image download error',
          style: GoogleFonts.poppins(color: Colors.white),
        ),
      ),
    );
  }
}

Future<String> shortenUrl(String longUrl) async {
  try {
    final url = 'https://tinyurl.com/api-create.php?url=$longUrl';
    final response = await http.get(Uri.parse(url));
    if (response.statusCode == 200) {
      return response.body;
    }
    return longUrl;
  } catch (_) {
    return longUrl;
  }
}

Future<String> expandShortUrl(String shortUrl) async {
  try {
    final url = 'https://unshorten.me/s/$shortUrl';
    final response = await http.get(Uri.parse(url));
    if (response.statusCode == 200) {
      return response.body;
    }
    return shortUrl;
  } catch (_) {
    return shortUrl;
  }
}

Widget _waStep(
    {required String number, required String text, required bool isDark}) {
  return Row(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Container(
        width: 24,
        height: 24,
        margin: const EdgeInsets.only(right: 10, top: 1),
        decoration: BoxDecoration(
          color: Colors.green,
          shape: BoxShape.circle,
        ),
        child: Center(
          child: Text(
            number,
            style: const TextStyle(
                color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
          ),
        ),
      ),
      Expanded(
        child: Text(
          text,
          style: TextStyle(
            fontSize: 14,
            height: 1.5,
            color: isDark ? Colors.grey[400] : Colors.grey[700],
          ),
        ),
      ),
    ],
  );
}

void showWhatsappConfirmation(BuildContext context) {
  final theme = Theme.of(context);
  final isDark = theme.brightness == Brightness.dark;

  showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(25)),
    ),
    backgroundColor: isDark ? const Color(0xFF1E1E1E) : Colors.white,
    builder: (_) => SafeArea(
      child: SingleChildScrollView(
        child: Padding(
          padding: EdgeInsets.only(
            left: 20,
            right: 20,
            top: 25,
            bottom: MediaQuery.of(context).viewInsets.bottom + 25,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              /// Poignée UX
              Container(
                width: 40,
                height: 5,
                decoration: BoxDecoration(
                  color: Colors.grey[400],
                  borderRadius: BorderRadius.circular(10),
                ),
              ),

              const SizedBox(height: 25),

              /// Icône WhatsApp (FontAwesome)
              FaIcon(FontAwesomeIcons.whatsapp, size: 55, color: Colors.green),

              const SizedBox(height: 18),

              /// Titre
              Text(
                (langUserPhone == "fr")
                    ? "Confirmation du numéro WhatsApp"
                    : "WhatsApp Number Confirmation",
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: isDark ? Colors.white : Colors.black87,
                ),
              ),

              const SizedBox(height: 15),

              /// Message
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    (langUserPhone == "fr")
                        ? "Suivez ces étapes pour confirmer votre numéro :"
                        : "Follow these steps to confirm your number:",
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 14.5,
                      height: 1.6,
                      color: isDark ? Colors.grey[400] : Colors.grey[600],
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Étape 1
                  _waStep(
                    number: "1",
                    text: (langUserPhone == "fr")
                        ? "Ouvrez WhatsApp depuis le numéro utilisé lors de votre inscription."
                        : "Open WhatsApp from the number you used when signing up.",
                    isDark: isDark,
                  ),
                  const SizedBox(height: 10),

                  // Étape 2
                  _waStep(
                    number: "2",
                    text: (langUserPhone == "fr")
                        ? "Envoyez-nous EXACTEMENT ce message, sans faute ni modification :"
                        : "Send us EXACTLY this message, with no mistakes or changes:",
                    isDark: isDark,
                  ),
                  const SizedBox(height: 8),

                  // Bloc message à copier
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(
                        vertical: 12, horizontal: 16),
                    decoration: BoxDecoration(
                      color: Colors.green.withOpacity(isDark ? 0.15 : 0.08),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: Colors.green.withOpacity(0.4)),
                    ),
                    child: Text(
                      "WhatsApp Confirmation",
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        letterSpacing: 0.5,
                        fontFamily: 'monospace',
                        color: Colors.green[700],
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),

                  // Étape 3
                  _waStep(
                    number: "3",
                    text: (langUserPhone == "fr")
                        ? "Votre numéro sera confirmé dès que possible."
                        : "Your number will be confirmed as soon as possible.",
                    isDark: isDark,
                  ),
                ],
              ),

              const SizedBox(height: 30),

              /// Bouton Demander
              SizedBox(
                width: double.infinity,
                height: 55,
                child: ElevatedButton(
                  onPressed: () async {
                    final Uri _url = Uri.parse(whatsappDSURLConfirmation);
                    if (!await launchUrl(
                      _url,
                      mode: LaunchMode.externalApplication,
                    )) {}
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(15),
                    ),
                  ),
                  child: Text(
                    (langUserPhone == "fr") ? "Demander" : "Request",
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),

              const SizedBox(height: 10),

              /// Bouton Fermer
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: Text((langUserPhone == "fr") ? "Fermer" : "Close"),
              ),
            ],
          ),
        ),
      ),
    ),
  );
}

void showConfNumeroWhatsapp(BuildContext context) {
  final theme = Theme.of(context);
  final isDark = theme.brightness == Brightness.dark;

  showModalBottomSheet(
    context: context,
    isScrollControlled:
        true, // Permet au modal de prendre toute la hauteur si nécessaire
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(25)),
    ),
    backgroundColor: isDark ? const Color(0xFF1E1E1E) : Colors.white,
    builder: (_) => FadeInUp(
      duration: const Duration(milliseconds: 400),
      child: SafeArea(
        // Ajout de SafeArea pour éviter les débordements en bas
        child: SingleChildScrollView(
          // Ajout de SingleChildScrollView pour éviter l'overflow
          child: Padding(
            padding: EdgeInsets.only(
              left: 20,
              right: 20,
              top: 25,
              bottom: MediaQuery.of(context).viewInsets.bottom +
                  25, // Ajustement pour le clavier
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                /// Poignée UX
                Container(
                  width: 40,
                  height: 5,
                  decoration: BoxDecoration(
                    color: Colors.grey[400],
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),

                const SizedBox(height: 25),

                /// Icône
                Icon(
                  Icons.settings_outlined,
                  size: 55,
                  color: primaryColor, // Utilisation de la couleur accent
                ),

                const SizedBox(height: 18),

                /// Titre
                Text(
                  (langUserPhone == "fr")
                      ? "Configuration et Confirmation du Compte"
                      : "Account Setup and Confirmation",
                  textAlign: TextAlign.center,
                  style: GoogleFonts.poppins(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: isDark ? Colors.white : Colors.black87,
                  ),
                ),

                const SizedBox(height: 15),

                /// Message principal
                Text(
                  (langUserPhone == "fr")
                      ? "Vous n’avez pas encore terminé la configuration et la confirmation de votre compte.\n\n"
                          "Veuillez fermer cette fenêtre et consulter la page Actu. "
                          "Sur la page Actu, vous verrez clairement les étapes à suivre pour finaliser la configuration et la confirmation de votre compte.\n\n"
                          "Une fois ces étapes complétées, vous pourrez revenir ici et utiliser cette fonctionnalité sans problème."
                      : "You have not yet completed the setup and confirmation of your account.\n\n"
                          "Please close this window and visit the News page. "
                          "On the News page, you will clearly see the steps to follow to finalize your account setup and confirmation.\n\n"
                          "Once these steps are completed, you can come back here and use this feature without any issues.",
                  textAlign: TextAlign.center,
                  style: GoogleFonts.poppins(
                    fontSize: 14.5,
                    height: 1.6,
                    color: isDark ? Colors.grey[400] : Colors.grey[600],
                  ),
                ),

                const SizedBox(height: 30),

                SizedBox(
                  width: double.infinity,
                  height: 55,
                  child: ElevatedButton(
                    onPressed: () => Navigator.pop(context),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: primaryColor,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(15),
                      ),
                    ),
                    child: Text(
                      (langUserPhone == "fr") ? "J'ai compris" : "I understand",
                      style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ),
                SizedBox(height: 10),
              ],
            ),
          ),
        ),
      ),
    ),
  );
}

Future<void> saveContactDsIfNotExiste() async {
  int nombreNewContact = 0;
  nombreNewContact = 0;
  contactsEnregistrer = [];
  final url = Uri.parse('$generalRouteForApi/listContactDS/$uidUser/fr');
  final response = await http.get(url);
  if (response.statusCode == 200) {
    final jsonData = jsonDecode(response.body) as List<dynamic>;
    if (jsonData.isNotEmpty) {
      for (var contact in jsonData) {
        if (contact['tel'] != "+22964044294" &&
            contact['tel'] != "22964044294" &&
            contact['tel'] != "64044294" &&
            !contactsEnregistrer.contains(contact['tel'])) {
          contactsEnregistrer.add(contact['tel']);
        }
        if ((await SQLHelper.getOneNumsTelUser(contact['tel'])).isEmpty) {
          final String _nom = (contact["nom"] ?? "").toString().trim();
          final String _pseudo = (contact["pseudo"] ?? "").toString();
          final String _telSansPlus = contact["tel"].toString().replaceAll(
                "+",
                "",
              );
          final List<String> _nameParts = [
            _nom,
            _pseudo,
            _telSansPlus,
          ].where((s) => s.isNotEmpty).toList();
          final String _expectedName = "${_nameParts.join(" - ")} #DS";
          final newContact = Contact()
            ..name.first = _expectedName
            ..phones = [Phone(contact["tel"])]
            ..accounts = (selectedContactAccountName != null &&
                    selectedContactAccountType != null)
                ? [
                    Account(
                      '',
                      selectedContactAccountType!,
                      selectedContactAccountName!,
                      [],
                    ),
                  ]
                : [];
          await newContact.insert();
          await insertNumTelUserIntoDataBase(contact["tel"]);
          nombreNewContact++;
        }
      }
      if (nombreNewContact == 1) {
        showNotification(
          (langUserPhone == "fr")
              ? "ADD Contacts Dressur"
              : "ADD Dressur Contacts",
          (langUserPhone == "fr")
              ? "$nombreNewContact nouveau contact enregistré par Dressur."
              : "$nombreNewContact new contact saved by Dressur.",
        );
      } else if (nombreNewContact > 1) {
        showNotification(
          (langUserPhone == "fr")
              ? "ADD Contacts Dressur"
              : "ADD Dressur Contacts",
          (langUserPhone == "fr")
              ? "$nombreNewContact nouveaux contacts enregistrés par Dressur."
              : "$nombreNewContact new contacts saved by Dressur.",
        );
      }
    }
  }
}

extension StringExtension on String {
  String capitalize() {
    if (isEmpty) return this;
    return this[0].toUpperCase() + substring(1).toLowerCase();
  }
}
