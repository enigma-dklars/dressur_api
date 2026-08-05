// ignore_for_file: use_build_context_synchronously, prefer_const_constructors

import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:animate_do/animate_do.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;

import 'package:dressur/6_login_register/mot_de_passe_oublier.dart';
import 'package:dressur/components/constant.dart';
import 'package:dressur/components/noti.dart';
import 'package:dressur/components/bottomBar.dart';
import 'package:dressur/5_autre/support_assistance.dart';

class LoginPage extends StatelessWidget {
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
              FadeInDown(
                duration: Duration(milliseconds: 500),
                child: Text(
                  (langUserPhone == "fr")
                      ? "Ravi de vous revoir !"
                      : "Welcome Back!",
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
                      ? "Connectez-vous pour continuer votre aventure."
                      : "Log in to continue your adventure.",
                  style: GoogleFonts.poppins(
                      fontSize: 16, color: Colors.grey[600]),
                ),
              ),
              SizedBox(height: 50),
              LoginForm(),
            ],
          ),
        ),
      ),
    );
  }
}

class LoginForm extends StatefulWidget {
  @override
  State<LoginForm> createState() => _LoginFormState();
}

class _LoginFormState extends State<LoginForm> {
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;
  bool _isPasswordObscured = true;
  final _emailController = TextEditingController(text: mailConnexion);
  final _passwordController = TextEditingController();

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _login() async {
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
          'POST', Uri.parse('$generalRouteForApi/connect'));
      request.fields.addAll({
        'mail': _emailController.text,
        'password': _passwordController.text,
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
        isNouvelUtilisateur = false;
        initUserInformations(data["user"]);
        Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (context) => const BottomBar()));
      } else {
        throw Exception(data["message"] ?? ((langUserPhone == "fr") ? "Une erreur est survenue." : "An error occurred."));
      }
    } catch (e) {
      dangerNoti((langUserPhone == "fr") ? "Erreur" : "Error",
          e.toString().replaceAll("Exception: ", ""), context);
    } finally {
      if (mounted) setState(() => _isLoading = false);
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
                if (value == null || value.isEmpty) {
                  return (langUserPhone == "fr")
                      ? 'Le mot de passe est requis.'
                      : 'Password is required.';
                }
                return null;
              },
            ),
          ),
          SizedBox(height: 15),

          // --- LIEN MOT DE PASSE OUBLIÉ ---
          FadeInUp(
            duration: Duration(milliseconds: 500),
            delay: Duration(milliseconds: 700),
            child: Align(
              alignment: Alignment.centerRight,
              child: TextButton(
                onPressed: () => Navigator.push(
                    context,
                    MaterialPageRoute(
                        builder: (context) => RecuperationPage())),
                child: Text(
                  (langUserPhone == "fr")
                      ? "Mot de passe oublié ?"
                      : "Forgot password?",
                  style: GoogleFonts.poppins(
                      color: primaryColor, fontWeight: FontWeight.w600),
                ),
              ),
            ),
          ),
          SizedBox(height: 30),

          // --- BOUTON DE CONNEXION CLASSIQUE ---
          FadeInUp(
            duration: Duration(milliseconds: 500),
            delay: Duration(milliseconds: 800),
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _isLoading ? null : _login,
                child: _isLoading
                    ? SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(
                            color: Colors.white, strokeWidth: 2))
                    : Text((langUserPhone == "fr") ? "Se Connecter" : "Log In"),
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
          SizedBox(height: 30),
        ],
      ),
    );
  }

  InputDecoration _buildInputDecoration(
      {required String label, required IconData icon, Widget? suffixIcon}) {
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
      suffixIcon: suffixIcon,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: primaryColor, width: 2),
      ),
    );
  }
}
