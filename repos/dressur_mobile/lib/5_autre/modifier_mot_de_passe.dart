// ignore_for_file: use_build_context_synchronously, prefer_const_constructors

import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/6_login_register/connexion.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:dressur/components/noti.dart';

class ModifierMdpPage extends StatelessWidget {
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
          (langUserPhone == "fr") ? "Modifier Mot de Passe" : "Change Password",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          children: [
            SizedBox(height: 20),
            FaIcon(FontAwesomeIcons.lock, color: primaryColor, size: 70),
            SizedBox(height: 15),
            Text(
              (langUserPhone == "fr")
                  ? "Changer votre mot de passe"
                  : "Change Your Password",
              style: GoogleFonts.poppins(
                  fontSize: 22, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 10),
            Text(
              (langUserPhone == "fr")
                  ? "Utilisez un mot de passe fort pour sécuriser votre compte."
                  : "Use a strong password to secure your account.",
              textAlign: TextAlign.center,
              style: GoogleFonts.poppins(fontSize: 15, color: Colors.grey[600]),
            ),
            SizedBox(height: 30),
            // --- FORMULAIRE ---
            PasswordChangeForm(),
          ],
        ),
      ),
    );
  }
}

class PasswordChangeForm extends StatefulWidget {
  @override
  State<PasswordChangeForm> createState() => _PasswordChangeFormState();
}

class _PasswordChangeFormState extends State<PasswordChangeForm> {
  bool _isLoading = false;
  bool _isSendingMail = false;
  final _formKey = GlobalKey<FormState>();

  // Visibilité des mots de passe
  bool _oldPasswordVisible = false;
  bool _newPasswordVisible = false;
  bool _confirmPasswordVisible = false;

  // Contrôleurs
  final ancienPasswordController = TextEditingController();
  final passwordController = TextEditingController();
  final passwordVerifController = TextEditingController();

  @override
  void dispose() {
    ancienPasswordController.dispose();
    passwordController.dispose();
    passwordVerifController.dispose();
    super.dispose();
  }

  Future<void> _changePassword() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isLoading = true);

    try {
      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/updateUserPassword'));
      request.fields.addAll({
        'uid': uidUser,
        
        'currentPassword': ancienPasswordController.text,
        'newPassword': passwordController.text,
        'confirmNewPassword': passwordVerifController.text,
      });
      http.StreamedResponse response = await request.send();
      if (response.statusCode == 200) {
        var data = convert.jsonDecode(await response.stream.bytesToString());
        if (!mounted) return;
        if (data["error"] == true) {
          dangerNoti(data["titre"], data["message"], context);
        } else {
          initUserInformations(data['user']);
          ancienPasswordController.clear();
          passwordController.clear();
          passwordVerifController.clear();
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            backgroundColor: Colors.green,
            behavior: SnackBarBehavior.floating,
            content: Text(
                (langUserPhone == "fr")
                    ? 'Mot de passe modifié avec succès !'
                    : 'Password changed successfully!',
                style: GoogleFonts.poppins(color: Colors.white)),
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

  Future<void> _sendPasswordResetMail() async {
    setState(() => _isSendingMail = true);

    bool isConnected = await isConnectedToInternet();
    if (!mounted) return;
    if (isConnected) {
      setState(() {
        _isSendingMail = true;
      });

      var request = http.MultipartRequest('POST',
          Uri.parse('$generalRouteForApi/sendMailPassForgotWithConnecte'));
      request.fields
          .addAll({'uid': uidUser,});

      http.StreamedResponse response = await request.send();

      if (response.statusCode == 200) {
        var data1 = await response.stream.bytesToString();
        if (!mounted) return;
        var data = convert.jsonDecode(data1);
        if (data["error"] == true) {
          dangerNoti(data["titre"], data["message"], context);
          setState(() {
            _isSendingMail = false;
          });
        } else {
          setState(() {
            _isSendingMail = false;
          });
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => LoginPage()),
          );
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
          _isSendingMail = false;
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
        _isSendingMail = false;
      });
    }

    if (mounted) setState(() => _isSendingMail = false);
  }

  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: Column(
        children: [
          _buildPasswordField(
            controller: ancienPasswordController,
            label: (langUserPhone == "fr")
                ? 'Ancien mot de passe'
                : 'Old Password',
            isVisible: _oldPasswordVisible,
            onToggleVisibility: () =>
                setState(() => _oldPasswordVisible = !_oldPasswordVisible),
          ),
          SizedBox(height: 15),
          _buildPasswordField(
            controller: passwordController,
            label: (langUserPhone == "fr")
                ? 'Nouveau mot de passe'
                : 'New Password',
            isVisible: _newPasswordVisible,
            onToggleVisibility: () =>
                setState(() => _newPasswordVisible = !_newPasswordVisible),
          ),
          SizedBox(height: 15),
          _buildPasswordField(
            controller: passwordVerifController,
            label: (langUserPhone == "fr")
                ? 'Confirmer le nouveau mot de passe'
                : 'Confirm New Password',
            isVisible: _confirmPasswordVisible,
            onToggleVisibility: () => setState(
                () => _confirmPasswordVisible = !_confirmPasswordVisible),
            validator: (value) {
              if (value != passwordController.text) {
                return (langUserPhone == "fr")
                    ? 'Les mots de passe ne correspondent pas.'
                    : 'Passwords do not match.';
              }
              return null;
            },
          ),
          SizedBox(height: 30),

          // --- BOUTON PRINCIPAL ---
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _isLoading ? null : _changePassword,
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                foregroundColor: Colors.white,
                padding: EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
              child: _isLoading
                  ? SizedBox(
                      height: 24,
                      width: 24,
                      child: CircularProgressIndicator(
                          color: Colors.white, strokeWidth: 3))
                  : Text(
                      (langUserPhone == "fr") ? "MODIFIER" : "CHANGE",
                      style: GoogleFonts.poppins(
                          fontWeight: FontWeight.bold, fontSize: 16),
                    ),
            ),
          ),
          SizedBox(height: 20),

          // --- BOUTON SECONDAIRE ---
          TextButton(
            onPressed: _isSendingMail ? null : _sendPasswordResetMail,
            child: Text(
              (langUserPhone == "fr")
                  ? "Mot de passe oublié ?"
                  : "Forgot your password?",
              style: GoogleFonts.poppins(
                  fontWeight: FontWeight.w600, color: secondaryColor),
            ),
          ),
        ],
      ),
    );
  }

  // --- WIDGET HELPER POUR LES CHAMPS DE MOT DE PASSE ---
  Widget _buildPasswordField({
    required TextEditingController controller,
    required String label,
    required bool isVisible,
    required VoidCallback onToggleVisibility,
    String? Function(String?)? validator,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return TextFormField(
      controller: controller,
      obscureText: !isVisible,
      style: GoogleFonts.poppins(fontWeight: FontWeight.w500),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: GoogleFonts.poppins(color: Colors.grey[600]),
        prefixIcon: Padding(
          padding: const EdgeInsets.only(left: 20, right: 13, top: 14),
          child:
              FaIcon(FontAwesomeIcons.lock, color: Colors.grey[500], size: 22),
        ),
        suffixIcon: IconButton(
          icon: FaIcon(
              isVisible ? FontAwesomeIcons.eyeSlash : FontAwesomeIcons.eye,
              color: Colors.grey[500]),
          onPressed: onToggleVisibility,
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
        if (value == null || value.isEmpty) {
          return (langUserPhone == "fr")
              ? 'Ce champ est requis.'
              : 'This field is required.';
        }
        if (validator != null) {
          return validator(value);
        }
        return null;
      },
    );
  }
}
