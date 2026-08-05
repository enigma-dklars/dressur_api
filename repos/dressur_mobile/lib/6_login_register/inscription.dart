// ignore_for_file: use_build_context_synchronously, prefer_const_constructors

import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:animate_do/animate_do.dart';
import 'package:country_code_picker/country_code_picker.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;

// --- Importez vos pages et constantes ---
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/noti.dart';
import 'package:dressur/components/noti_sys.dart';
import 'package:dressur/components/bottomBar.dart';
import 'package:dressur/5_autre/support_assistance.dart';
import 'package:url_launcher/url_launcher.dart';

class InscriptionPage extends StatelessWidget {
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
                      ? "Créer un compte"
                      : "Create Account",
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
                      ? "Rejoignez la communauté Dressur dès aujourd'hui."
                      : "Join the Dressur community today.",
                  style: GoogleFonts.poppins(
                      fontSize: 16, color: Colors.grey[600]),
                ),
              ),
              SizedBox(height: 50),

              // --- FORMULAIRE ---
              RegisterForm(),
            ],
          ),
        ),
      ),
    );
  }
}

class RegisterForm extends StatefulWidget {
  @override
  State<RegisterForm> createState() => _RegisterFormState();
}

class _RegisterFormState extends State<RegisterForm> {
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;
  bool _isPasswordObscured = true;
  bool _isConfirmPasswordObscured = true;
  String _selectedCountryCode = "+33";

  bool _agreedToTerms = false;
  bool _hasSubmittedOnce = false;

  final _telController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _passwordVerifController = TextEditingController();

  @override
  void dispose() {
    _telController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _passwordVerifController.dispose();
    super.dispose();
  }

  Future<void> _register() async {
    setState(() => _hasSubmittedOnce = true);

    final isFormValid = _formKey.currentState!.validate();

    if (!isFormValid || !_agreedToTerms) {
      // On peut ajouter un petit feedback pour l'utilisateur si on le souhaite
      if (!_agreedToTerms) {
        warningNoti(
            (langUserPhone == "fr") ? "Attention !!!" : "Warning !!!",
            (langUserPhone == "fr")
                ? "Veuillez accepter les conditions pour continuer."
                : "Please accept the terms to continue.",
            context);
      }
      return;
    }

    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);
    try {
      bool isConnected = await isConnectedToInternet();
      if (!isConnected) {
        throw Exception((langUserPhone == "fr")
            ? "Pas de connexion internet."
            : "No internet connection.");
      }

      String fullPhoneNumber =
          _selectedCountryCode + _telController.text.trim();
      fullPhoneNumber = fullPhoneNumber.replaceAll(RegExp(r'\D'), '');

      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/inscriptionDS'));
      request.fields.addAll({
        
        'tel': "+$fullPhoneNumber",
        'mail': _emailController.text.trim(),
        'password': _passwordController.text,
        'confirmPassword': _passwordVerifController.text,
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
        modeReconnaissanceContactArrierePlan = true;
        isNouvelUtilisateur = true;
        scheduleBoostReminderNotification();
        schedulePromoReminderNotification();
        initUserInformations(data['user']);
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (context) => const BottomBar()),
          (route) => false,
        );
      } else {
        throw Exception(data["message"] ??
            ((langUserPhone == "fr") ? "Une erreur est survenue lors de l'inscription." : "An error occurred during registration."));
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

  Future<void> _launchURL(String url) async {
    if (!await launchUrl(Uri.parse(url),
        mode: LaunchMode.externalApplication)) {
      print('Could not launch $url');
    }
  }

  @override
  Widget build(BuildContext context) {
    // Détermine la couleur du texte des conditions en fonction de l'état
    final termsTextColor = (_hasSubmittedOnce && !_agreedToTerms)
        ? Colors.red // Rouge si erreur
        : Theme.of(context)
            .textTheme
            .bodySmall
            ?.color; // Couleur par défaut sinon

    return Form(
      key: _formKey,
      child: Column(
        children: [
          // --- CHAMP TÉLÉPHONE ---
          FadeInUp(
            duration: Duration(milliseconds: 500),
            delay: Duration(milliseconds: 400),
            child: TextFormField(
              controller: _telController,
              keyboardType: TextInputType.phone,
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              decoration: _buildInputDecorationNumeroWhatsApp(
                label: (langUserPhone == "fr")
                    ? 'Numéro WhatsApp'
                    : 'WhatsApp Number',
                prefix: CountryCodePicker(
                  onChanged: (country) => setState(
                      () => _selectedCountryCode = country.dialCode ?? "+33"),
                  initialSelection: 'FR',
                  favorite: ['+33', '+229', '+228', '+225'],
                  showCountryOnly: false,
                  showOnlyCountryWhenClosed: false,
                  alignLeft: false,
                  padding: EdgeInsetsGeometry.all(0),
                  margin: EdgeInsetsGeometry.all(10),
                  dialogBackgroundColor: Theme.of(context)
                      .scaffoldBackgroundColor, // Le fond du dialogue prend la couleur du Scaffold
                  dialogTextStyle: GoogleFonts.poppins(
                      color: Theme.of(context)
                          .textTheme
                          .bodyLarge
                          ?.color), // Le texte prend la couleur du texte principal
                  searchDecoration: InputDecoration(
                    prefixIconColor: Theme.of(context)
                        .iconTheme
                        .color, // Couleur de l'icône de recherche
                    labelStyle: GoogleFonts.poppins(
                        color: Theme.of(context)
                            .textTheme
                            .bodyLarge
                            ?.color), // Couleur du label de recherche
                  ),
                  // --- FIN DES AJOUTS ---
                ),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return (langUserPhone == "fr")
                      ? 'Le numéro est requis.'
                      : 'Phone number is required.';
                }
                return null;
              },
            ),
          ),
          SizedBox(height: 20),

          // --- CHAMP E-MAIL ---
          FadeInUp(
            duration: Duration(milliseconds: 500),
            delay: Duration(milliseconds: 500),
            child: TextFormField(
              controller: _emailController,
              keyboardType: TextInputType.emailAddress,
              decoration: _buildInputDecoration(
                  label: "E-mail", icon: FontAwesomeIcons.at),
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
          SizedBox(height: 20),

          // --- CHAMP MOT DE PASSE ---
          FadeInUp(
            duration: Duration(milliseconds: 500),
            delay: Duration(milliseconds: 600),
            child: TextFormField(
              controller: _passwordController,
              obscureText: _isPasswordObscured,
              decoration: _buildInputDecoration(
                label: (langUserPhone == "fr") ? 'Mot de passe' : 'Password',
                icon: FontAwesomeIcons.lock,
                suffixIcon: IconButton(
                  icon: FaIcon(
                    _isPasswordObscured
                        ? FontAwesomeIcons.eyeSlash
                        : FontAwesomeIcons.eye,
                    size: 18,
                  ),
                  onPressed: () => setState(
                      () => _isPasswordObscured = !_isPasswordObscured),
                ),
              ),
              validator: (value) {
                if (value == null || value.length < 6) {
                  return (langUserPhone == "fr")
                      ? '6 caractères minimum.'
                      : 'Minimum 6 characters.';
                }
                return null;
              },
            ),
          ),
          SizedBox(height: 20),

          // --- CHAMP CONFIRMATION MOT DE PASSE ---
          FadeInUp(
            duration: Duration(milliseconds: 500),
            delay: Duration(milliseconds: 700),
            child: TextFormField(
              controller: _passwordVerifController,
              obscureText: _isConfirmPasswordObscured,
              decoration: _buildInputDecoration(
                label: (langUserPhone == "fr")
                    ? 'Confirmer le mot de passe'
                    : 'Confirm Password',
                icon: FontAwesomeIcons.lock,
                suffixIcon: IconButton(
                  icon: FaIcon(
                    _isConfirmPasswordObscured
                        ? FontAwesomeIcons.eyeSlash
                        : FontAwesomeIcons.eye,
                    size: 18,
                  ),
                  onPressed: () => setState(() =>
                      _isConfirmPasswordObscured = !_isConfirmPasswordObscured),
                ),
              ),
              validator: (value) {
                if (value != _passwordController.text) {
                  return (langUserPhone == "fr")
                      ? 'Les mots de passe ne correspondent pas.'
                      : 'Passwords do not match.';
                }
                return null;
              },
            ),
          ),
          SizedBox(height: 25),
          FadeInUp(
            duration: Duration(milliseconds: 500),
            delay: Duration(milliseconds: 800),
            child: CheckboxListTile(
              value: _agreedToTerms,
              onChanged: (bool? value) {
                setState(() {
                  _agreedToTerms = value ?? false;
                });
              },
              title: RichText(
                text: TextSpan(
                  style: GoogleFonts.poppins(
                    fontSize: 10,
                    color: termsTextColor, // Utilise la couleur dynamique
                  ),
                  children: [
                    TextSpan(
                        text: (langUserPhone == "fr")
                            ? "J'ai lu et j'accepte les "
                            : "I have read and agree to the "),
                    TextSpan(
                      text: (langUserPhone == "fr")
                          ? "Conditions d'utilisation"
                          : "Terms of Use",
                      style: GoogleFonts.poppins(
                          color: Colors.blue,
                          decoration: TextDecoration.underline),
                      recognizer: TapGestureRecognizer()
                        ..onTap = () => _launchURL(dressurConditionUtilisation),
                    ),
                    TextSpan(
                        text:
                            (langUserPhone == "fr") ? " et la " : " and the "),
                    TextSpan(
                      text: (langUserPhone == "fr")
                          ? "Politique de confidentialité"
                          : "Privacy Policy",
                      style: GoogleFonts.poppins(
                          color: Colors.blue,
                          decoration: TextDecoration.underline),
                      recognizer: TapGestureRecognizer()
                        ..onTap =
                            () => _launchURL(dressurPolitiqueConfidentialite),
                    ),
                    TextSpan(text: "."),
                  ],
                ),
              ),
              controlAffinity: ListTileControlAffinity
                  .leading, // Met la case à cocher à gauche
              contentPadding: EdgeInsets.zero,
            ),
          ),
          SizedBox(height: 25),
          // --- BOUTON D'INSCRIPTION ---
          FadeInUp(
            duration: Duration(milliseconds: 500),
            delay: Duration(milliseconds: 800),
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _isLoading ? null : _register,
                child: _isLoading
                    ? SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(
                            color: Colors.white, strokeWidth: 2))
                    : Text((langUserPhone == "fr") ? "S'inscrire" : "Sign Up"),
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
          SizedBox(height: 20),
        ],
      ),
    );
  }

  InputDecoration _buildInputDecorationNumeroWhatsApp(
      {required String label, Widget? prefix, Widget? suffixIcon}) {
    return InputDecoration(
      labelText: label,
      prefix: prefix,
      suffixIcon: suffixIcon,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: primaryColor, width: 2),
      ),
    );
  }

  InputDecoration _buildInputDecoration(
      {required String label,
      IconData? icon,
      Widget? prefix,
      Widget? suffixIcon}) {
    return InputDecoration(
      labelText: label,
      prefixIcon: Padding(
        padding: const EdgeInsets.only(left: 20, right: 13, top: 14),
        child: icon != null
            ? FaIcon(
                icon,
                color: primaryColor,
                size: 18,
              )
            : null,
      ),
      prefix: prefix,
      suffixIcon: suffixIcon,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: primaryColor, width: 2),
      ),
    );
  }
}
