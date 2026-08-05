// ignore_for_file: use_build_context_synchronously, prefer_const_constructors

import 'package:dressur/5_autre/autre_profil.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/components/constant.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:dressur/components/noti.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart'; // Pour les icônes des réseaux sociaux

class ProfilPage extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
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
          (langUserPhone == "fr") ? "Mon Profil" : "My profile",
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontWeight: FontWeight.w400,
            fontSize: 18,
          ),
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 10.0),
            child: Row(
              children: [
                GestureDetector(
                  onTap: () {
                    uidAutreUser = uidUser;
                    addUserOnAutreProfilPage = "non";
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (context) => AutreProfilPage(),
                      ),
                    );
                  },
                  child: const FaIcon(
                    FontAwesomeIcons.circleInfo,
                    size: 30.0,
                    color: Colors.white,
                  ),
                )
              ],
            ),
          ),
        ],
      ),
      body: SingleChildScrollView(
        physics: BouncingScrollPhysics(),
        child: Padding(
          padding: const EdgeInsets.all(15.0),
          child: Column(
            children: [
              // --- EN-TÊTE VISUELLE ---
              FaIcon(FontAwesomeIcons.locationDot,
                  size: 60, color: primaryColor),
              SizedBox(height: 10),
              Text(
                (langUserPhone == "fr")
                    ? "Complétez vos informations"
                    : "Complete your information",
                style: GoogleFonts.poppins(
                    fontSize: 18, fontWeight: FontWeight.w500),
              ),
              SizedBox(height: 5),
              Text(
                (langUserPhone == "fr")
                    ? "Un profil complet inspire confiance."
                    : "A complete profile inspires trust.",
                style:
                    GoogleFonts.poppins(fontSize: 14, color: Colors.grey[600]),
              ),
              SizedBox(height: 25),
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
  bool _isLoading = false;
  final _formKey = GlobalKey<FormState>();

  final telController = TextEditingController(text: tel);
  final emailController = TextEditingController(text: mail);
  final nameController = TextEditingController(text: nom);
  final pseudoController = TextEditingController(text: pseudo);
  final aproposController = TextEditingController(text: apropos);
  final tiktokController = TextEditingController(text: tiktok);
  final instagramController = TextEditingController(text: instagram);
  final facebookController = TextEditingController(text: facebook);
  final youtubeController = TextEditingController(text: youtube);

  @override
  void dispose() {
    telController.dispose();
    emailController.dispose();
    nameController.dispose();
    pseudoController.dispose();
    aproposController.dispose();
    tiktokController.dispose();
    instagramController.dispose();
    facebookController.dispose();
    youtubeController.dispose();
    super.dispose();
  }

  Future<void> _updateProfile() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isLoading = true);

    try {
      var request = http.MultipartRequest(
          'POST', Uri.parse('$generalRouteForApi/updateUserInfo'));
      request.fields.addAll({
        'uid': uidUser,
        
        'tel': telController.text,
        'mail': emailController.text,
        'nom': nameController.text,
        'pseudo': pseudoController.text,
        'apropos': aproposController.text,
        'tiktok': tiktokController.text,
        'instagram': instagramController.text,
        'facebook': facebookController.text,
        'youtube': youtubeController.text,
      });
      http.StreamedResponse response = await request.send();
      if (response.statusCode == 200) {
        var data = convert.jsonDecode(await response.stream.bytesToString());
        if (!mounted) return;
        if (data["error"] == true) {
          dangerNoti(data["titre"], data["message"], context);
        } else {
          initUserInformations(data['user']);
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            backgroundColor: Colors.green,
            behavior: SnackBarBehavior.floating,
            content: Text(
                (langUserPhone == "fr")
                    ? 'Profil mis à jour !'
                    : 'Profile updated!',
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

  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // --- SECTION INFORMATIONS DE BASE ---
          _buildSectionTitle((langUserPhone == "fr")
              ? "Informations de base"
              : "Basic Information"),
          _buildTextField(
              controller: nameController,
              label: (langUserPhone == "fr")
                  ? 'Nom & Prénom(s)'
                  : "Last & First Name",
              icon: FontAwesomeIcons.user),
          SizedBox(height: 12),
          _buildTextField(
              controller: pseudoController,
              label: 'Pseudo',
              icon: FontAwesomeIcons.at),
          SizedBox(height: 12),
          _buildTextField(
              controller: emailController,
              label: 'E-mail',
              icon: FontAwesomeIcons.solidEnvelope,
              keyboardType: TextInputType.emailAddress),
          SizedBox(height: 12),
          _buildTextField(
              controller: telController,
              label: (langUserPhone == "fr")
                  ? 'Numéro WhatsApp'
                  : 'WhatsApp Number',
              icon: FontAwesomeIcons.phone,
              keyboardType: TextInputType.phone,
              readOnly: telIsVerified == true),
          if (telIsVerified == true) ...[
            SizedBox(height: 6),
            Row(
              children: [
                FaIcon(FontAwesomeIcons.circleCheck,
                    color: Colors.green[600], size: 13),
                SizedBox(width: 6),
                Expanded(
                  child: Text(
                    (langUserPhone == "fr")
                        ? "Numéro confirmé, non modifiable. Pour utiliser un autre numéro, créez un nouveau compte."
                        : "Confirmed number, read-only. To use another number, create a new account.",
                    style: GoogleFonts.poppins(
                        fontSize: 11,
                        color: Colors.green[700],
                        fontWeight: FontWeight.w400),
                  ),
                ),
              ],
            ),
          ],
          SizedBox(height: 12),

          // --- SECTION RÉSEAUX SOCIAUX ---
          _buildSectionTitle((langUserPhone == "fr")
              ? "Réseaux Sociaux (Optionnel)"
              : "Social Media (Optional)"),
          _buildTextField(
              controller: tiktokController,
              label: (langUserPhone == "fr") ? 'Lien TikTok' : 'TikTok Link',
              icon: FontAwesomeIcons.tiktok),
          SizedBox(height: 12),
          _buildTextField(
              controller: instagramController,
              label: (langUserPhone == "fr") ? 'Lien Instagram' : 'Instagram Link',
              icon: FontAwesomeIcons.instagram),
          SizedBox(height: 12),
          _buildTextField(
              controller: facebookController,
              label: (langUserPhone == "fr") ? 'Lien Facebook' : 'Facebook Link',
              icon: FontAwesomeIcons.facebook),
          SizedBox(height: 12),
          _buildTextField(
              controller: youtubeController,
              label: (langUserPhone == "fr") ? 'Lien YouTube' : 'YouTube Link',
              icon: FontAwesomeIcons.youtube),
          SizedBox(height: 12),

          // --- SECTION BIOGRAPHIE ---
          _buildSectionTitle(
              (langUserPhone == "fr") ? "Biographie" : "About You"),
          _buildTextField(
              controller: aproposController,
              label: (langUserPhone == "fr")
                  ? 'Parlez un peu de vous...'
                  : 'Tell us a bit about yourself...',
              icon: FontAwesomeIcons.penToSquare,
              maxLines: 4),
          SizedBox(height: 30),

          // --- BOUTON D'ENREGISTREMENT ---
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _isLoading ? null : _updateProfile,
              icon: _isLoading
                  ? Container()
                  : FaIcon(FontAwesomeIcons.floppyDisk, color: Colors.white),
              label: _isLoading
                  ? SizedBox(
                      height: 24,
                      width: 24,
                      child: CircularProgressIndicator(
                          color: Colors.white, strokeWidth: 3))
                  : Text(
                      (langUserPhone == "fr") ? "ENREGISTRER" : "SAVE",
                      style: GoogleFonts.poppins(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                          color: Colors.white),
                    ),
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
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

  // --- WIDGETS HELPERS POUR LE DESIGN ---

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10, top: 15),
      child: Text(
        title,
        style: GoogleFonts.poppins(
            fontSize: 14, fontWeight: FontWeight.bold, color: primaryColor),
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    int maxLines = 1,
    TextInputType? keyboardType,
    bool readOnly = false,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      keyboardType: keyboardType,
      readOnly: readOnly,
      style: GoogleFonts.poppins(
        fontWeight: FontWeight.w500,
        color: readOnly
            ? (isDark ? Colors.grey[500] : Colors.grey[600])
            : null,
      ),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: GoogleFonts.poppins(color: Colors.grey[600]),
        prefixIcon: Padding(
          padding: const EdgeInsets.only(left: 20, right: 13, top: 14),
          child: FaIcon(icon,
              color: readOnly ? Colors.green[400] : Colors.grey[500],
              size: 20),
        ),
        suffixIcon: readOnly
            ? Icon(Icons.lock_rounded, color: Colors.green[400], size: 18)
            : null,
        suffixIconConstraints: readOnly
            ? const BoxConstraints(minWidth: 44, minHeight: 44)
            : null,
        filled: true,
        fillColor: readOnly
            ? (isDark ? Colors.grey[900] : Colors.grey[200])
            : (isDark ? Colors.grey[850] : Colors.grey[100]),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: readOnly
              ? BorderSide(color: Colors.green, width: 1)
              : BorderSide(color: primaryColor, width: 2),
        ),
      ),
      validator: (value) {
        if (label.contains('Nom') && (value == null || value.trim().isEmpty)) {
          return (langUserPhone == "fr")
              ? 'Le nom est requis.'
              : 'Name is required.';
        }
        return null;
      },
    );
  }
}
