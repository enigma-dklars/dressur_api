// ignore_for_file: prefer_const_constructors, use_build_context_synchronously

import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/5_autre/support_assistance.dart';
import 'package:dressur/components/bottomBar.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:dressur/components/noti.dart';
import 'package:pinput/pinput.dart'; // Import du package Pinput

class CodeMailConfirmePage extends StatefulWidget {
  @override
  State<CodeMailConfirmePage> createState() => _CodeMailConfirmePageState();
}

class _CodeMailConfirmePageState extends State<CodeMailConfirmePage> {
  final _pinController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  bool _isConfirming = false;
  bool _isResending = false;

  // --- LOGIQUE API (regroupée pour la clarté) ---

  Future<void> _resendCode() async {
    if (_isResending) return;
    setState(() => _isResending = true);

    try {
      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/sendMailVerification'));
      request.fields
          .addAll({'uid': uidUser, });
      http.StreamedResponse response = await request.send();

      if (response.statusCode == 200) {
        var data = convert.jsonDecode(await response.stream.bytesToString());
        if (data["error"] == true) {
          dangerNoti(data["titre"], data["message"], context);
        } else {
          successNoti(
            (langUserPhone == "fr") ? "Code Renvoyé !" : "Code Resent!",
            (langUserPhone == "fr")
                ? "Un nouveau code a été envoyé à votre adresse e-mail."
                : "A new code has been sent to your email address.",
            context,
          );
        }
      } else {
        dangerNoti(
          (langUserPhone == "fr") ? "Erreur" : "Error",
          (langUserPhone == "fr") ? "Impossible de renvoyer le code." : "Unable to resend the code.",
          context,
        );
      }
    } catch (e) {
      dangerNoti(
        (langUserPhone == "fr") ? "Erreur" : "Error",
        (langUserPhone == "fr") ? "Problème de connexion." : "Connection problem.",
        context,
      );
    }

    setState(() => _isResending = false);
  }

  Future<void> _verifyCode(String pin) async {
    if (pin.length < 4) return; // Assurez-vous que le code est complet
    setState(() => _isConfirming = true);

    try {
      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/mailVerification'));
      request.fields.addAll({
        'uid': uidUser,
        
        'codeForVerifMail': pin
      });
      http.StreamedResponse response = await request.send();

      if (response.statusCode == 200) {
        var data = convert.jsonDecode(await response.stream.bytesToString());
        if (data["error"] == true) {
          dangerNoti(data["titre"], data["message"], context);
        } else {
          setState(() => mailIsVerified = true);
          initUserInformations(data['user']);
          successNoti(
            (langUserPhone == "fr") ? "Succès !" : "Success!",
            (langUserPhone == "fr") ? "Votre e-mail a été confirmé." : "Your email has been confirmed.",
            context,
          );
          await Future.delayed(Duration(seconds: 2));
          Navigator.pushAndRemoveUntil(
            context,
            MaterialPageRoute(builder: (context) => const BottomBar()),
            (route) => false,
          );
        }
      } else {
        dangerNoti(
          (langUserPhone == "fr") ? "Erreur" : "Error",
          (langUserPhone == "fr") ? "Impossible de vérifier le code." : "Unable to verify the code.",
          context,
        );
      }
    } catch (e) {
      dangerNoti(
        (langUserPhone == "fr") ? "Erreur" : "Error",
        (langUserPhone == "fr") ? "Problème de connexion." : "Connection problem.",
        context,
      );
    }

    setState(() => _isConfirming = false);
  }

  Widget _helpItem(bool isDark, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 5),
            child: Container(
              width: 6,
              height: 6,
              decoration: BoxDecoration(
                color: primaryColor,
                shape: BoxShape.circle,
              ),
            ),
          ),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: GoogleFonts.poppins(
                fontSize: 13,
                color: isDark ? Colors.grey[300] : Colors.grey[800],
                height: 1.5,
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    // Thème pour Pinput
    final defaultPinTheme = PinTheme(
      width: 56,
      height: 60,
      textStyle: GoogleFonts.poppins(fontSize: 22, fontWeight: FontWeight.w600),
      decoration: BoxDecoration(
        color: isDark ? Colors.grey[800] : Colors.grey[200],
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDark ? Colors.grey[600]! : Colors.grey[400]!,
          width: 1.5,
        ),
      ),
    );

    return Scaffold(
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
              ? "Confirmation du Mail"
              : "Email Confirmation",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
        actions: [
          PopupMenuButton<int>(
            itemBuilder: (context) => [
              PopupMenuItem(
                value: 1,
                child: Row(
                  children: [
                    Text(
                      (langUserPhone == "fr") ? "Actualiser" : "Refresh",
                    ),
                  ],
                ),
              ),
              PopupMenuItem(
                value: 2,
                child: Row(
                  children: [
                    Text(
                      (langUserPhone == "fr") ? "Aide" : "Help",
                    ),
                  ],
                ),
              ),
            ],
            offset: const Offset(0, 60),
            color: primaryColor,
            elevation: 2,
            onSelected: (value) {
              if (value == 2) {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (context) => SupportPage()),
                );
              }
            },
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              SizedBox(height: 20),
              FaIcon(FontAwesomeIcons.envelopeOpen,
                  color: primaryColor, size: 80),
              SizedBox(height: 20),
              Text(
                (langUserPhone == "fr")
                    ? "Vérifiez vos e-mails"
                    : "Check Your Email",
                style: GoogleFonts.poppins(
                    fontSize: 24, fontWeight: FontWeight.bold),
              ),
              SizedBox(height: 10),
              Text(
                (langUserPhone == "fr")
                    ? "Nous avons envoyé un code de confirmation à votre adresse : $mail"
                    : "We've sent a confirmation code to your address: $mail",
                textAlign: TextAlign.center,
                style: GoogleFonts.poppins(
                    fontSize: 15, color: Colors.grey[600], height: 1.5),
              ),
              SizedBox(height: 40),

              // --- CHAMP DE SAISIE PINPUT ---
              Pinput(
                length: 6, // Ajustez selon la longueur de votre code
                controller: _pinController,
                defaultPinTheme: defaultPinTheme,
                focusedPinTheme: defaultPinTheme.copyWith(
                  decoration: defaultPinTheme.decoration!.copyWith(
                    border: Border.all(color: primaryColor, width: 2),
                  ),
                ),
                onCompleted: (pin) => _verifyCode(pin),
              ),
              SizedBox(height: 40),

              // --- BOUTON DE CONFIRMATION ---
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isConfirming
                      ? null
                      : () => _verifyCode(_pinController.text),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
                    foregroundColor: Colors.white,
                    padding: EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                  child: _isConfirming
                      ? SizedBox(
                          height: 24,
                          width: 24,
                          child: CircularProgressIndicator(
                              color: Colors.white, strokeWidth: 3))
                      : Text(
                          (langUserPhone == "fr") ? "CONFIRMER" : "CONFIRM",
                          style: GoogleFonts.poppins(
                              fontWeight: FontWeight.bold, fontSize: 16),
                        ),
                ),
              ),
              SizedBox(height: 20),

              // --- BOUTON POUR RENVOYER LE CODE ---
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: _isResending ? null : _resendCode,
                  icon: _isResending
                      ? SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: primaryColor,
                          ),
                        )
                      : FaIcon(
                          FontAwesomeIcons.paperPlane,
                          size: 15,
                          color: _isResending ? Colors.grey : primaryColor,
                        ),
                  label: Text(
                    (langUserPhone == "fr")
                        ? _isResending ? "Envoi en cours..." : "Renvoyer le code"
                        : _isResending ? "Sending..." : "Resend Code",
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.w600,
                      fontSize: 15,
                      color: _isResending ? Colors.grey : primaryColor,
                    ),
                  ),
                  style: OutlinedButton.styleFrom(
                    padding: EdgeInsets.symmetric(vertical: 14),
                    side: BorderSide(
                      color: _isResending ? Colors.grey : primaryColor,
                      width: 2,
                    ),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ),
              SizedBox(height: 24),

              // --- BLOC D'AIDE ---
              Container(
                width: double.infinity,
                padding: EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: isDark ? Colors.grey[850] : Colors.blue[50],
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: isDark ? Colors.grey[700]! : Colors.blue[200]!,
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        FaIcon(
                          FontAwesomeIcons.circleInfo,
                          color: primaryColor,
                          size: 18,
                        ),
                        SizedBox(width: 8),
                        Text(
                          (langUserPhone == "fr")
                              ? "Vous ne recevez pas le code ?"
                              : "Not receiving the code?",
                          style: GoogleFonts.poppins(
                            fontWeight: FontWeight.w600,
                            fontSize: 14,
                            color: primaryColor,
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 12),
                    _helpItem(
                      isDark,
                      (langUserPhone == "fr")
                          ? "Vérifiez que votre adresse e-mail a été saisie correctement lors de l'inscription."
                          : "Make sure you entered your email address correctly when registering.",
                    ),
                    _helpItem(
                      isDark,
                      (langUserPhone == "fr")
                          ? "Si elle est incorrecte, allez dans Paramètres → Profil pour la corriger, puis renvoyez un nouveau code."
                          : "If it's wrong, go to Settings → Profile to fix it, then resend a new code.",
                    ),
                    _helpItem(
                      isDark,
                      (langUserPhone == "fr")
                          ? "Pensez à vérifier votre dossier Spam ou Indésirables — le mail peut parfois s'y retrouver."
                          : "Check your Spam or Junk folder — the email may have landed there.",
                    ),
                    _helpItem(
                      isDark,
                      (langUserPhone == "fr")
                          ? "Si vous avez plusieurs adresses e-mail, assurez-vous d'ouvrir la bonne boîte de réception."
                          : "If you have multiple email addresses, make sure you're checking the right inbox.",
                    ),
                  ],
                ),
              ),
              SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }
}
