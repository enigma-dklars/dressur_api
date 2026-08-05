// ignore_for_file: use_build_context_synchronously, prefer_const_constructors

import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:dressur/components/noti.dart';

class SignalerPage extends StatelessWidget {
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
          (langUserPhone == "fr") ? "Signaler un utilisateur" : "Report a user",
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
                  color: Colors.redAccent, size: 70),
              SizedBox(height: 15),
              Text(
                (langUserPhone == "fr")
                    ? "Signaler un comportement"
                    : "Report a Behavior",
                style: GoogleFonts.poppins(
                    fontSize: 22, fontWeight: FontWeight.bold),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 10),
              Text(
                (langUserPhone == "fr")
                    ? "Aidez-nous à maintenir une communauté sûre. Tout signalement sera traité avec sérieux."
                    : "Help us maintain a safe community. All reports will be treated seriously.",
                style:
                    GoogleFonts.poppins(fontSize: 15, color: Colors.grey[600]),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 30),
              SignalerForm(),
              SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }
}

class SignalerForm extends StatefulWidget {
  @override
  State<SignalerForm> createState() => _SignalerFormState();
}

class _SignalerFormState extends State<SignalerForm> {
  bool _isLoading = false;
  final _formKey = GlobalKey<FormState>();
  final telController = TextEditingController();
  final motifController = TextEditingController();

  Future<void> signaleUser() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isLoading = true);

    try {
      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/addSignalement'));
      request.fields.addAll({
        'uid': uidUser,
        
        'telSignaler': telController.text,
        'motifSignaler': motifController.text,
      });
      http.StreamedResponse response = await request.send();
      if (response.statusCode == 200) {
        var data = convert.jsonDecode(await response.stream.bytesToString());
        if (data["error"] == true) {
          dangerNoti(data["titre"], data["message"], context);
        } else {
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            backgroundColor: Colors.green,
            behavior: SnackBarBehavior.floating,
            content: Text(
              (langUserPhone == "fr")
                  ? 'Signalement envoyé. Merci pour votre aide.'
                  : 'Report sent. Thank you for your help.',
              style: GoogleFonts.poppins(color: Colors.white),
            ),
          ));
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

    setState(() => _isLoading = false);
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Form(
      key: _formKey,
      child: Column(
        children: [
          // --- CHAMP POUR LE NUMÉRO ---
          TextFormField(
            controller: telController,
            keyboardType: TextInputType.phone,
            style: GoogleFonts.poppins(fontWeight: FontWeight.w500),
            decoration: InputDecoration(
              labelText: (langUserPhone == "fr")
                  ? 'Numéro WhatsApp à signaler'
                  : 'WhatsApp Number to Report',
              labelStyle: GoogleFonts.poppins(color: Colors.grey[600]),
              prefixIcon: Padding(
                padding: const EdgeInsets.only(left: 20, right: 13, top: 14),
                child: FaIcon(FontAwesomeIcons.phone,
                    color: Colors.grey[500], size: 22),
              ),
              filled: true,
              fillColor: isDark ? Colors.grey[850] : Colors.grey[100],
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none),
              focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide(color: primaryColor, width: 2)),
            ),
            validator: (value) {
              if (value == null || value.trim().isEmpty) {
                return (langUserPhone == "fr")
                    ? 'Le numéro est requis.'
                    : 'The number is required.';
              }
              return null;
            },
          ),
          SizedBox(height: 15),

          // --- CHAMP POUR LE MOTIF ---
          TextFormField(
            controller: motifController,
            minLines: 5,
            maxLines: 8,
            style: GoogleFonts.poppins(fontWeight: FontWeight.w500),
            decoration: InputDecoration(
              hintText: (langUserPhone == "fr")
                  ? 'Décrivez précisément le problème rencontré...'
                  : 'Describe the problem you encountered in detail...',
              hintStyle: GoogleFonts.poppins(color: Colors.grey[500]),
              filled: true,
              fillColor: isDark ? Colors.grey[850] : Colors.grey[100],
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(15),
                  borderSide: BorderSide.none),
              focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(15),
                  borderSide: BorderSide(color: primaryColor, width: 2)),
            ),
            validator: (value) {
              if (value == null || value.trim().isEmpty) {
                return (langUserPhone == "fr")
                    ? 'Le motif est requis.'
                    : 'The reason is required.';
              }
              return null;
            },
          ),
          SizedBox(height: 25),

          // --- BOUTON DE SIGNALEMENT ---
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _isLoading ? null : signaleUser,
              icon: _isLoading
                  ? Container()
                  : FaIcon(FontAwesomeIcons.triangleExclamation,
                      color: Colors.white),
              label: _isLoading
                  ? SizedBox(
                      height: 24,
                      width: 24,
                      child: CircularProgressIndicator(
                          color: Colors.white, strokeWidth: 3))
                  : Text(
                      (langUserPhone == "fr")
                          ? "ENVOYER LE SIGNALEMENT"
                          : "SEND REPORT",
                      style: GoogleFonts.poppins(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                          color: Colors.white),
                    ),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.redAccent,
                padding: EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
                elevation: 3,
                shadowColor: Colors.red.withOpacity(0.4),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
