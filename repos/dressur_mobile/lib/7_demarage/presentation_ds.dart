// ignore_for_file: prefer_const_constructors, use_build_context_synchronously

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:smooth_page_indicator/smooth_page_indicator.dart';
import 'package:animate_do/animate_do.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';

import 'package:dressur/6_login_register/connexion.dart';
import 'package:dressur/6_login_register/inscription.dart';
import 'package:dressur/components/constant.dart';
import 'package:url_launcher/url_launcher.dart';

class _SlideData {
  final IconData icon;
  final Color accent;
  final String titleFr;
  final String titleEn;
  final String descFr;
  final String descEn;

  const _SlideData({
    required this.icon,
    required this.accent,
    required this.titleFr,
    required this.titleEn,
    required this.descFr,
    required this.descEn,
  });
}

final List<_SlideData> _slides = [
  _SlideData(
    icon: FontAwesomeIcons.signal,
    accent: Color(0xFF5B8DEF),
    titleFr: 'Booste ton contact',
    titleEn: 'Boost your contact',
    descFr:
        'Rends ton numéro visible à des milliers\nd\'utilisateurs dans les pays de ton choix.',
    descEn:
        'Make your number visible to thousands\nof users in the countries you choose.',
  ),
  _SlideData(
    icon: FontAwesomeIcons.addressBook,
    accent: Color(0xFF00BFA5),
    titleFr: 'Ajoute des contacts\nen 1 clic',
    titleEn: 'Add contacts\nin 1 tap',
    descFr:
        'Reçois automatiquement des dizaines\nde nouveaux contacts dans ton téléphone.',
    descEn:
        'Automatically receive dozens\nof new contacts into your phone.',
  ),
  _SlideData(
    icon: FontAwesomeIcons.coins,
    accent: Color(0xFFFFB300),
    titleFr: 'Gagne des FCFA',
    titleEn: 'Earn FCFA',
    descFr:
        'Partage les promotions du programme\nrécompenses et touche des FCFA à chaque partage.',
    descEn:
        'Share reward program promotions\nand earn FCFA with every share.',
  ),
  _SlideData(
    icon: FontAwesomeIcons.bullhorn,
    accent: Color(0xFFFF6D00),
    titleFr: 'Promeut ton business',
    titleEn: 'Promote your business',
    descFr:
        'Fais connaître tes services et produits\nà des milliers de personnes ciblées.',
    descEn:
        'Promote your services and products\nto thousands of targeted users.',
  ),
  _SlideData(
    icon: FontAwesomeIcons.thumbsUp,
    accent: Color(0xFFE91E8C),
    titleFr: 'Booste tes réseaux sociaux',
    titleEn: 'Boost your social media',
    descFr:
        'Gagne des abonnés, des vues et des likes\nsur TikTok, Instagram et YouTube.',
    descEn:
        'Gain followers, views and likes\non TikTok, Instagram and YouTube.',
  ),
];

class PresentationPage extends StatefulWidget {
  @override
  _PresentationPageState createState() => _PresentationPageState();
}

class _PresentationPageState extends State<PresentationPage> {
  final PageController _pageController = PageController();
  int _currentPage = 0;
  final int _totalPages = 5;

  Future<bool> _onWillPop() async => false;

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: _onWillPop,
      child: Scaffold(
        body: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color(0xFF1A237E), Colors.black],
            ),
          ),
          child: Stack(
            children: [
              PageView.builder(
                controller: _pageController,
                onPageChanged: (page) => setState(() => _currentPage = page),
                itemCount: _totalPages,
                itemBuilder: (context, index) => _buildSlide(index),
              ),
              _buildSkipButton(),
              _buildBottomControls(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSlide(int index) {
    final data = _slides[index];
    final bool isFr = langUserPhone == "fr";

    return Padding(
      padding: EdgeInsets.symmetric(horizontal: 36),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          // Cercle iconique avec lueur
          FadeInDown(
            key: ValueKey('icon_$index'),
            duration: Duration(milliseconds: 600),
            child: Container(
              width: 140,
              height: 140,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: data.accent.withOpacity(0.12),
                border: Border.all(
                  color: data.accent.withOpacity(0.35),
                  width: 2,
                ),
                boxShadow: [
                  BoxShadow(
                    color: data.accent.withOpacity(0.25),
                    blurRadius: 40,
                    spreadRadius: 10,
                  ),
                ],
              ),
              child: Center(
                child: FaIcon(data.icon, size: 54, color: data.accent),
              ),
            ),
          ),

          SizedBox(height: 50),

          // Titre
          FadeInUp(
            key: ValueKey('title_$index'),
            duration: Duration(milliseconds: 600),
            delay: Duration(milliseconds: 150),
            child: Text(
              isFr ? data.titleFr : data.titleEn,
              textAlign: TextAlign.center,
              style: GoogleFonts.poppins(
                fontSize: 26,
                fontWeight: FontWeight.bold,
                color: Colors.white,
                height: 1.25,
              ),
            ),
          ),

          SizedBox(height: 20),

          // Description
          FadeInUp(
            key: ValueKey('desc_$index'),
            duration: Duration(milliseconds: 600),
            delay: Duration(milliseconds: 280),
            child: Text(
              isFr ? data.descFr : data.descEn,
              textAlign: TextAlign.center,
              style: GoogleFonts.poppins(
                fontSize: 15,
                color: Colors.white60,
                height: 1.7,
              ),
            ),
          ),

          SizedBox(height: 36),

          // Barre de couleur décorative
          FadeInUp(
            key: ValueKey('bar_$index'),
            duration: Duration(milliseconds: 600),
            delay: Duration(milliseconds: 420),
            child: Container(
              width: 48,
              height: 4,
              decoration: BoxDecoration(
                color: data.accent,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),

          // Espace pour ne pas être masqué par les contrôles bas
          SizedBox(height: 100),
        ],
      ),
    );
  }

  Widget _buildSkipButton() {
    return Align(
      alignment: Alignment.topRight,
      child: Padding(
        padding: EdgeInsets.only(top: 52, right: 20),
        child: TextButton(
          onPressed: () => Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (_) => WelcomePage()),
          ),
          child: Text(
            langUserPhone == "fr" ? "Passer" : "Skip",
            style: GoogleFonts.poppins(
              color: Colors.white54,
              fontSize: 15,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildBottomControls() {
    final bool isLastPage = _currentPage == _totalPages - 1;
    final Color accent = _slides[_currentPage].accent;

    return Align(
      alignment: Alignment.bottomCenter,
      child: Padding(
        padding: EdgeInsets.symmetric(horizontal: 28, vertical: 38),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            // Points de navigation (couleur change avec la slide)
            AnimatedSwitcher(
              duration: Duration(milliseconds: 300),
              child: SmoothPageIndicator(
                key: ValueKey(accent),
                controller: _pageController,
                count: _totalPages,
                effect: WormEffect(
                  dotHeight: 10,
                  dotWidth: 10,
                  activeDotColor: accent,
                  dotColor: Colors.white24,
                ),
              ),
            ),

            // Bouton Suivant / Démarrer
            AnimatedContainer(
              duration: Duration(milliseconds: 300),
              child: ElevatedButton(
                onPressed: () {
                  if (isLastPage) {
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(builder: (_) => WelcomePage()),
                    );
                  } else {
                    _pageController.nextPage(
                      duration: Duration(milliseconds: 400),
                      curve: Curves.easeInOut,
                    );
                  }
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: accent,
                  foregroundColor: Colors.white,
                  elevation: 0,
                  shape: StadiumBorder(),
                  padding: EdgeInsets.symmetric(horizontal: 32, vertical: 14),
                ),
                child: Text(
                  isLastPage
                      ? (langUserPhone == "fr" ? 'Démarrer' : 'Get Started')
                      : (langUserPhone == "fr" ? 'Suivant' : 'Next'),
                  style: GoogleFonts.poppins(
                    fontWeight: FontWeight.w600,
                    fontSize: 15,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Page de bienvenue finale (inchangée) ────────────────────────────────────

class WelcomePage extends StatelessWidget {
  Future<void> _launchURL(String url) async {
    if (!await launchUrl(Uri.parse(url),
        mode: LaunchMode.externalApplication)) {
      print('Could not launch $url');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Spacer(),
              FadeInDown(
                duration: Duration(milliseconds: 800),
                child: Image.asset('images/welcome.png', height: 250),
              ),
              SizedBox(height: 30),
              FadeInUp(
                duration: Duration(milliseconds: 800),
                delay: Duration(milliseconds: 200),
                child: Text(
                  (langUserPhone == "fr")
                      ? 'Bienvenue sur Dressur'
                      : 'Welcome to Dressur',
                  style: GoogleFonts.poppins(
                      fontSize: 26, fontWeight: FontWeight.bold),
                ),
              ),
              SizedBox(height: 10),
              FadeInUp(
                duration: Duration(milliseconds: 800),
                delay: Duration(milliseconds: 400),
                child: Text(
                  (langUserPhone == "fr")
                      ? 'Connectez-vous ou créez un compte pour commencer.'
                      : 'Log in or create an account to get started.',
                  textAlign: TextAlign.center,
                  style: GoogleFonts.poppins(
                      fontSize: 16, color: Colors.grey[600]),
                ),
              ),
              SizedBox(height: 40),
              FadeInUp(
                duration: Duration(milliseconds: 800),
                delay: Duration(milliseconds: 600),
                child: Row(
                  children: [
                    Expanded(
                      child: ElevatedButton(
                        onPressed: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                                builder: (context) => LoginPage())),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: primaryColor,
                          foregroundColor: Colors.white,
                          padding: EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12)),
                        ),
                        child: Text(
                            (langUserPhone == "fr") ? 'Connexion' : 'Login',
                            style: GoogleFonts.poppins(
                                fontWeight: FontWeight.w600)),
                      ),
                    ),
                    SizedBox(width: 15),
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                                builder: (context) => InscriptionPage())),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: primaryColor,
                          side: BorderSide(color: primaryColor),
                          padding: EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12)),
                        ),
                        child: Text(
                            (langUserPhone == "fr") ? 'Inscription' : 'Sign Up',
                            style: GoogleFonts.poppins(
                                fontWeight: FontWeight.w600)),
                      ),
                    ),
                  ],
                ),
              ),
              Spacer(),
              _buildLegalLinks(),
              SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLegalLinks() {
    return FadeInUp(
      duration: Duration(milliseconds: 800),
      delay: Duration(milliseconds: 800),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          TextButton(
            onPressed: () => _launchURL(dressurConditionUtilisation),
            child: Text(
              (langUserPhone == "fr")
                  ? "Conditions d'utilisation"
                  : "Terms of Use",
              style:
                  GoogleFonts.poppins(color: Colors.grey[500], fontSize: 12),
            ),
          ),
          Text("•",
              style: GoogleFonts.poppins(color: Colors.grey[400])),
          TextButton(
            onPressed: () => _launchURL(dressurPolitiqueConfidentialite),
            child: Text(
              (langUserPhone == "fr")
                  ? 'Politique de confidentialité'
                  : "Privacy Policy",
              style:
                  GoogleFonts.poppins(color: Colors.grey[500], fontSize: 12),
            ),
          ),
        ],
      ),
    );
  }
}
