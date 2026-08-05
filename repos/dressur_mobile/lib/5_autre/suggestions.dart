// ignore_for_file: use_build_context_synchronously, prefer_const_constructors

import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:dressur/components/noti.dart';

class SuggestionsPage extends StatelessWidget {
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
          "Suggestions",
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
              Container(
                padding: EdgeInsets.all(20),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.amber.withOpacity(0.15),
                ),
                child: FaIcon(
                  FontAwesomeIcons.lightbulb,
                  size: 60,
                  color: Colors.amber[700],
                ),
              ),
              SizedBox(height: 20),
              Text(
                (langUserPhone == "fr")
                    ? "Une idée à partager ?"
                    : "Have an idea to share?",
                style: GoogleFonts.poppins(
                    fontSize: 24, fontWeight: FontWeight.bold),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 10),
              Text(
                (langUserPhone == "fr")
                    ? "Votre avis est précieux ! Aidez-nous à améliorer l'application."
                    : "Your opinion is valuable! Help us improve the application.",
                style:
                    GoogleFonts.poppins(fontSize: 15, color: Colors.grey[600]),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 30),
              SuggestionsForm(),
              SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }
}

class SuggestionsForm extends StatefulWidget {
  @override
  State<SuggestionsForm> createState() => _SuggestionsFormState();
}

class _SuggestionsFormState extends State<SuggestionsForm> {
  bool _isLoading = false;
  final _formKey = GlobalKey<FormState>();
  final motifController = TextEditingController();

  @override
  void dispose() {
    motifController.dispose();
    super.dispose();
  }

  Future<void> addSuggestion() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isLoading = true);

    try {
      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/addSuggestion'));
      request.fields.addAll({
        'uid': uidUser,
        
        'suggestion': motifController.text
      });
      http.StreamedResponse response = await request.send();
      if (response.statusCode == 200) {
        var data = convert.jsonDecode(await response.stream.bytesToString());
        if (!mounted) return;
        if (data["error"] == true) {
          dangerNoti(data["titre"], data["message"], context);
        } else {
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            backgroundColor: Colors.green,
            behavior: SnackBarBehavior.floating,
            content: Text(
              (langUserPhone == "fr")
                  ? 'Merci pour votre suggestion !'
                  : 'Thanks for your suggestion!',
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
      if (!mounted) return;
      dangerNoti(
        (langUserPhone == "fr") ? "Erreur" : "Error",
        (langUserPhone == "fr") ? "Impossible de se connecter au serveur." : "Unable to connect to the server.",
        context,
      );
    }

    if (mounted) setState(() => _isLoading = false);
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Form(
      key: _formKey,
      child: Column(
        children: [
          // --- CHAMP DE TEXTE AMÉLIORÉ ---
          TextFormField(
            controller: motifController,
            minLines: 6,
            maxLines: 10,
            style: GoogleFonts.poppins(fontWeight: FontWeight.w500),
            decoration: InputDecoration(
              hintText: (langUserPhone == "fr")
                  ? 'Décrivez votre idée ou suggestion ici...'
                  : 'Describe your idea or suggestion here...',
              hintStyle: GoogleFonts.poppins(color: Colors.grey[500]),
              filled: true,
              fillColor: isDark ? Colors.grey[850] : Colors.grey[100],
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(15),
                borderSide: BorderSide.none,
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(15),
                borderSide: BorderSide(color: primaryColor, width: 2),
              ),
            ),
            validator: (value) {
              if (value == null || value.trim().isEmpty) {
                return (langUserPhone == "fr")
                    ? 'Veuillez entrer votre suggestion.'
                    : 'Please enter your suggestion.';
              }
              return null;
            },
          ),
          SizedBox(height: 25),

          // --- BOUTON D'ENVOI AMÉLIORÉ ---
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _isLoading ? null : addSuggestion,
              icon: _isLoading
                  ? Container()
                  : FaIcon(FontAwesomeIcons.paperPlane
, color: Colors.white),
              label: _isLoading
                  ? SizedBox(
                      height: 24,
                      width: 24,
                      child: CircularProgressIndicator(
                          color: Colors.white, strokeWidth: 3))
                  : Text(
                      (langUserPhone == "fr")
                          ? "ENVOYER MA SUGGESTION"
                          : "SEND MY SUGGESTION",
                      style: GoogleFonts.poppins(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                          color: Colors.white),
                    ),
              style: ElevatedButton.styleFrom(
                backgroundColor:
                    primaryColor, // Utilisation de la couleur primaire pour la cohérence
                padding: EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
                // Ajout d'une ombre subtile pour faire ressortir le bouton
                elevation: 3,
                shadowColor: primaryColor.withOpacity(0.4),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
