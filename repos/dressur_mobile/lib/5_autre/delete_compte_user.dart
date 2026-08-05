// ignore_for_file: use_build_context_synchronously, prefer_const_constructors

import 'package:art_sweetalert/art_sweetalert.dart';
import 'package:flutter/material.dart';
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/6_login_register/connexion.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:dressur/components/sql_helper.dart';
import 'package:dressur/components/noti.dart';

class DeletecomptePage extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Scaffold(
      backgroundColor: isDark ? Color(0xFF121212) : Color(0xFFF8F9FA),
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        leading: IconButton(
          onPressed: () {
            Navigator.pop(context);
          },
          icon: const FaIcon(
            FontAwesomeIcons.chevronLeft,
            color: Colors.white,
          ),
        ),
        title: Text(
          (langUserPhone == "fr")
              ? "Suppression de compte"
              : "Account deletion",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
      ),
      body: SingleChildScrollView(
        physics: BouncingScrollPhysics(),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20.0, vertical: 10.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              SizedBox(height: 20),
              FaIcon(FontAwesomeIcons.triangleExclamation,
                  color: Colors.red, size: 70),
              SizedBox(height: 15),
              Text(
                (langUserPhone == "fr")
                    ? "Êtes-vous sûr de vouloir partir ?"
                    : "Are you sure you want to leave?",
                style: GoogleFonts.poppins(
                    fontSize: 22, fontWeight: FontWeight.bold),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 10),
              Text(
                (langUserPhone == "fr")
                    ? "Cette action est définitive et irréversible."
                    : "This action is final and irreversible.",
                style:
                    GoogleFonts.poppins(fontSize: 15, color: Colors.grey[600]),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 30),
              DeletecompteForm(),
              SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }
}

class DeletecompteForm extends StatefulWidget {
  @override
  State<DeletecompteForm> createState() => _DeletecompteFormState();
}

class _DeletecompteFormState extends State<DeletecompteForm> {
  bool _isLoading = false;
  final _formKey = GlobalKey<FormState>();
  final motifController = TextEditingController();
  final confirmController = TextEditingController();
  bool _canDelete = false;

  Future<void> deletecompteUser() async {
    if (!_formKey.currentState!.validate()) return;

    // Double confirmation avec une boîte de dialogue
    ArtDialogResponse response = await ArtSweetAlert.show(
      barrierDismissible: false,
      context: context,
      artDialogArgs: ArtDialogArgs(
          title: (langUserPhone == "fr") ? "Dernière chance" : "Last chance",
          text: (langUserPhone == "fr")
              ? "Toutes vos données seront perdues. Confirmez-vous la suppression ?"
              : "All your data will be lost. Do you confirm the deletion?",
          confirmButtonText:
              (langUserPhone == "fr") ? "Oui, supprimer" : "Yes, delete",
          denyButtonText: (langUserPhone == "fr") ? "Annuler" : "Cancel",
          type: ArtSweetAlertType.danger),
    );

    if (!response.isTapConfirmButton) return;

    setState(() => _isLoading = true);

    try {
      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/deleteCompteDS'));
      request.fields.addAll({
        'uid': uidUser,
        
        'motifDeleted': motifController.text
      });
      http.StreamedResponse httpResponse = await request.send();
      if (httpResponse.statusCode == 200) {
        var data =
            convert.jsonDecode(await httpResponse.stream.bytesToString());
        if (data["error"] == true) {
          dangerNoti(data["titre"], data["message"], context);
        } else {
          // Logique de nettoyage local
          if (contactsEnregistrer.isNotEmpty) {
            List<Contact> contacts =
                await FlutterContacts.getContacts(withProperties: true, withAccounts: true);
            for (var contact in contacts) {
              bool shouldDelete = false;
              for (var phone in contact.phones) {
                if (contactsEnregistrer.contains(
                    (phone.number).replaceAll(" ", "").replaceAll("-", ""))) {
                  shouldDelete = true;
                  break;
                }
              }
              if (shouldDelete) {
                await contact.delete();
              }
            }
          }
          SQLHelper.viderLaBaseDeDonneeLocal();
          Navigator.pushAndRemoveUntil(
            context,
            MaterialPageRoute(builder: (context) => LoginPage()),
            (route) => false,
          );
        }
      } else {
        dangerNoti(
          (langUserPhone == "fr") ? "Erreur" : "Error",
          (langUserPhone == "fr") ? "Un problème est survenu." : "A problem occurred.",
          context,
        );
      }
    } catch (e) {
      dangerNoti(
        (langUserPhone == "fr") ? "Erreur" : "Error",
        (langUserPhone == "fr") ? "Impossible de se connecter au serveur." : "Unable to connect to the server.",
        context,
      );
    }

    if (mounted) {
      setState(() => _isLoading = false);
    }
  }

  void _checkCanDelete() {
    final expected = (langUserPhone == "fr") ? 'supprimer' : 'delete';
    final canDelete = motifController.text.isNotEmpty &&
        confirmController.text.toLowerCase() == expected;
    if (canDelete != _canDelete) {
      setState(() {
        _canDelete = canDelete;
      });
    }
  }

  @override
  void initState() {
    super.initState();
    motifController.addListener(_checkCanDelete);
    confirmController.addListener(_checkCanDelete);
  }

  @override
  void dispose() {
    motifController.dispose();
    confirmController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // --- EXPLICATION DES CONSÉQUENCES ---
          _buildConsequenceItem(
            FontAwesomeIcons.database,
            (langUserPhone == "fr")
                ? "Perte de toutes vos données"
                : "Loss of all your data",
          ),
          _buildConsequenceItem(
            FontAwesomeIcons.clockRotateLeft,
            (langUserPhone == "fr")
                ? "Perte de vos historiques"
                : "Loss of your histories",
          ),
          _buildConsequenceItem(
            FontAwesomeIcons.userSlash,
            (langUserPhone == "fr")
                ? "Action irréversible"
                : "Irreversible action",
          ),

          SizedBox(height: 30),

          // --- CHAMP POUR LE MOTIF ---
          TextFormField(
            controller: motifController,
            minLines: 3,
            maxLines: 5,
            style: GoogleFonts.poppins(fontWeight: FontWeight.w500),
            decoration: InputDecoration(
              hintText: (langUserPhone == "fr")
                  ? "Pourquoi nous quittez-vous ?"
                  : "Why are you leaving us?",
              hintStyle: GoogleFonts.poppins(color: Colors.grey[500]),
              filled: true,
              fillColor: isDark ? Colors.grey[850] : Colors.grey[100],
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(15),
                  borderSide: BorderSide.none),
            ),
          ),
          SizedBox(height: 20),

          // --- CHAMP DE CONFIRMATION ---
          Text(
            (langUserPhone == "fr")
                ? "Pour confirmer, veuillez taper \"supprimer\" ci-dessous :"
                : "To confirm, please type \"delete\" below:",
            style: GoogleFonts.poppins(
                fontWeight: FontWeight.w500,
                color: isDark ? Colors.white70 : Colors.black87),
          ),
          SizedBox(height: 10),
          TextFormField(
            controller: confirmController,
            style: GoogleFonts.poppins(
                fontWeight: FontWeight.bold, color: Colors.red),
            decoration: InputDecoration(
              filled: true,
              fillColor: isDark ? Colors.grey[850] : Colors.grey[100],
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none),
              focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide(color: Colors.red, width: 2)),
            ),
          ),
          SizedBox(height: 30),

          // --- BOUTON DE SUPPRESSION ---
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _canDelete && !_isLoading ? deletecompteUser : null,
              icon: _isLoading
                  ? Container()
                  : FaIcon(FontAwesomeIcons.trash, color: Colors.white),
              label: _isLoading
                  ? SizedBox(
                      height: 24,
                      width: 24,
                      child: CircularProgressIndicator(
                          color: Colors.white, strokeWidth: 3))
                  : Text(
                      (langUserPhone == "fr")
                          ? "SUPPRIMER DÉFINITIVEMENT"
                          : "DELETE PERMANENTLY",
                      style: GoogleFonts.poppins(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                          color: Colors.white),
                    ),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red,
                disabledBackgroundColor: Colors.grey[400],
                padding: EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildConsequenceItem(IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10.0),
      child: Row(
        children: [
          FaIcon(icon, color: Colors.grey[600], size: 20),
          SizedBox(width: 12),
          Expanded(
              child: Text(text,
                  style: GoogleFonts.poppins(
                      fontSize: 14, color: Colors.grey[600]))),
        ],
      ),
    );
  }
}
