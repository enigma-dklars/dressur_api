// ignore_for_file: use_build_context_synchronously, prefer_const_constructors

import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:animate_do/animate_do.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:art_sweetalert/art_sweetalert.dart';

// --- Importez vos pages et constantes ---
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/noti.dart';
import 'package:dressur/5_autre/support_assistance.dart';

class RecuperationPage extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Scaffold(
      backgroundColor: isDark ? Color(0xFF121212) : Colors.white,
      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.transparent,
        foregroundColor: isDark ? Colors.white : Colors.black,
        actions: [
          IconButton(
            onPressed: () => Navigator.push(context,
                MaterialPageRoute(builder: (context) => SupportPage())),
            icon: FaIcon(FontAwesomeIcons.circleQuestion),
          ),
        ],
      ),
      body: SingleChildScrollView(
        physics: BouncingScrollPhysics(),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox(height: 20),
              // --- EN-TÊTE ---
              FadeInDown(
                duration: Duration(milliseconds: 500),
                child: Text(
                  (langUserPhone == "fr")
                      ? "Mot de passe oublié ?"
                      : "Forgot Password?",
                  style: GoogleFonts.poppins(
                      fontSize: 32, fontWeight: FontWeight.bold),
                ),
              ),
              SizedBox(height: 10),
              FadeInDown(
                duration: Duration(milliseconds: 500),
                delay: Duration(milliseconds: 200),
                child: Text(
                  (langUserPhone == "fr")
                      ? "Pas de souci ! Entrez votre e-mail et nous vous enverrons un nouveau mot de passe."
                      : "No worries! Enter your email and we'll send you a new password.",
                  style: GoogleFonts.poppins(
                      fontSize: 16, color: Colors.grey[600]),
                ),
              ),
              SizedBox(height: 50),

              // --- FORMULAIRE ---
              RecuperationForm(),
            ],
          ),
        ),
      ),
    );
  }
}

class RecuperationForm extends StatefulWidget {
  @override
  State<RecuperationForm> createState() => _RecuperationFormState();
}

class _RecuperationFormState extends State<RecuperationForm> {
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;
  final _emailController = TextEditingController();

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _sendRecoveryEmail() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);
    try {
      bool isConnected = await isConnectedToInternet();
      if (!isConnected) {
        throw Exception((langUserPhone == "fr")
            ? "Pas de connexion internet."
            : "No internet connection.");
      }

      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/sendMailPassForgot'));
      request.fields.addAll({
        'mail': _emailController.text.trim(),
      });

      http.StreamedResponse response = await request.send();
      var responseBody = await response.stream.bytesToString();
      late Map<String, dynamic> data;
      try {
        data = convert.jsonDecode(responseBody);
      } catch (_) {
        throw Exception((langUserPhone == "fr")
            ? "Le serveur est temporairement indisponible. Veuillez réessayer."
            : "The server is temporarily unavailable. Please try again.");
      }

      if (response.statusCode == 200 && data["error"] == false) {
        modeMotDePasseOublier = true;
        mailConnexion = _emailController.text.trim();

        ArtDialogResponse artResponse = await ArtSweetAlert.show(
          barrierDismissible: false,
          context: context,
          artDialogArgs: ArtDialogArgs(
            type: ArtSweetAlertType.success,
            title: (langUserPhone == "fr") ? "E-mail Envoyé !" : "Email Sent!",
            text: (langUserPhone == "fr")
                ? "Un nouveau mot de passe a été envoyé à votre adresse e-mail. Veuillez l'utiliser pour vous connecter."
                : "A new password has been sent to your email address. Please use it to log in.",
            confirmButtonText: (langUserPhone == "fr")
                ? "Retour à la Connexion"
                : "Back to Login",
          ),
        );

        if (artResponse.isTapConfirmButton) {
          Navigator.pop(context);
        }
      } else {
        throw Exception(data["message"] ?? ((langUserPhone == "fr") ? "Une erreur est survenue." : "An error occurred."));
      }
    } catch (e) {
      dangerNoti((langUserPhone == "fr") ? "Erreur" : "Error",
          e.toString().replaceAll("Exception: ", ""), context);
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: Column(
        children: [
          // --- CHAMP E-MAIL ---
          FadeInUp(
            duration: Duration(milliseconds: 500),
            delay: Duration(milliseconds: 400),
            child: TextFormField(
              controller: _emailController,
              keyboardType: TextInputType.emailAddress,
              decoration: _buildInputDecoration(
                label: "E-mail",
                icon: FontAwesomeIcons.at,
              ),
              validator: (value) {
                if (value == null || value.isEmpty || !value.contains('@')) {
                  return (langUserPhone == "fr")
                      ? 'Veuillez entrer un email valide.'
                      : 'Please enter a valid email.';
                }
                return null;
              },
            ),
          ),
          SizedBox(height: 40),

          // --- BOUTON D'ENVOI ---
          FadeInUp(
            duration: Duration(milliseconds: 500),
            delay: Duration(milliseconds: 600),
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _isLoading ? null : _sendRecoveryEmail,
                child: _isLoading
                    ? SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(
                            color: Colors.white, strokeWidth: 2))
                    : Text((langUserPhone == "fr")
                        ? "Envoyer l'e-mail"
                        : "Send Email"),
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  padding: EdgeInsets.symmetric(vertical: 16),
                  textStyle: GoogleFonts.poppins(
                      fontSize: 16, fontWeight: FontWeight.bold),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // Helper pour construire la décoration des champs de texte
  InputDecoration _buildInputDecoration(
      {required String label, required IconData icon}) {
    return InputDecoration(
      labelText: label,
      prefixIcon: Padding(
        padding: const EdgeInsets.only(left: 20, right: 13, top: 14),
        child: FaIcon(
          icon,
          color: primaryColor,
          size: 18,
        ),
      ),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: primaryColor, width: 2),
      ),
    );
  }
}
