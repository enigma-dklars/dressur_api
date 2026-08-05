import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:dressur/components/noti.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:http/http.dart' as http;
import 'dart:convert' as convert;
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/components/constant.dart';
import 'package:image_picker/image_picker.dart';
import 'package:url_launcher/url_launcher.dart';

class HistoriqueRecompense {
  final String id;
  final String title;
  final String amount;
  final String date;
  final String views;
  final String imageUrl;
  final String status;
  final String description;

  HistoriqueRecompense({
    required this.id,
    required this.title,
    required this.amount,
    required this.date,
    required this.views,
    required this.imageUrl,
    required this.status,
    required this.description,
  });
}

class HistoriqueCompletPage extends StatefulWidget {
  final List<dynamic> allHistorique;

  const HistoriqueCompletPage({
    super.key,
    required this.allHistorique,
  });

  @override
  State<HistoriqueCompletPage> createState() => _HistoriqueCompletPageState();
}

class _HistoriqueCompletPageState extends State<HistoriqueCompletPage> {
  late Future<List<HistoriqueRecompense>> _futureHistoriqueRecompense;

  File? _proofImage1;
  File? _proofImage2;
  bool _isSubmitting = false;
  final ImagePicker _picker = ImagePicker();

  Future<List<HistoriqueRecompense>> partageInProgrammeRecompense(
      [List<dynamic>? inParam]) async {
    try {
      final List<dynamic> dataList = (inParam != null && inParam.isNotEmpty)
          ? inParam
          : widget.allHistorique;

      return dataList.map((data) {
        return HistoriqueRecompense(
          imageUrl: generalRouteForPromotionImage + (data['imageUrl'] ?? ""),
          id: data['id']?.toString() ?? "0",
          title: data['title'] ?? "",
          amount: data['amount']?.toString() ?? "0",
          date: data['date'] ?? "",
          views: data['views']?.toString() ?? "0",
          status: data['status'] ?? "",
          description: data['description'] ?? "",
        );
      }).toList();
    } catch (e) {
      print("Erreur dans le mapping de l'historique: $e");
      return [];
    }
  }

  Future<List<HistoriqueRecompense>>
      getMyProgrammeRecompenseInformations() async {
    try {
      var request = http.MultipartRequest(
          'POST',
          Uri.parse(
              '$generalRouteForApi/getMyProgrammeRecompenseInformations'));
      request.fields.addAll({
        'uid': uidUser,
      });

      http.StreamedResponse response = await request.send();

      if (response.statusCode == 200) {
        var data1 = await response.stream.bytesToString();
        var data = convert.jsonDecode(data1);

        if (data["error"] == true) {
          dangerNoti(data["titre"], data["message"], context);
          return [];
        } else {
          setState(() {
            _futureHistoriqueRecompense =
                partageInProgrammeRecompense(data["allHistorique"]);
          });
          return [];
        }
      }
      return [];
    } catch (e) {
      print("Erreur: $e");
      return [];
    }
  }

  @override
  void initState() {
    super.initState();
    _futureHistoriqueRecompense = partageInProgrammeRecompense();
  }

  @override
  Widget build(BuildContext context) {
    final bool isFr = langUserPhone == "fr";
    return Scaffold(
        appBar: AppBar(
          backgroundColor: primaryColor,
          elevation: 0,
          title: Text(
            isFr ? "Historique complet" : "Complete History",
            style: GoogleFonts.poppins(
              color: Colors.white,
              fontWeight: FontWeight.w400,
              fontSize: 18,
            ),
          ),
          leading: IconButton(
            icon: FaIcon(FontAwesomeIcons.chevronLeft,
                color: Colors.white, size: 20),
            onPressed: () => Navigator.pop(context),
          ),
        ),
        body: Padding(
          padding: EdgeInsetsGeometry.fromLTRB(10, 10, 10, 0),
          child: FutureBuilder<List<HistoriqueRecompense>>(
            future: _futureHistoriqueRecompense,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const Padding(
                  padding: EdgeInsets.all(20),
                  child: Center(child: CircularProgressIndicator(color: primaryColor)),
                );
              }

              if (snapshot.hasError) {
                return Center(
                    child: Text(isFr ? "Erreur de chargement" : "Loading error"));
              }

              if (!snapshot.hasData || snapshot.data!.isEmpty) {
                return Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      FaIcon(FontAwesomeIcons.clock,
                          size: 50, color: Colors.grey[300]),
                      const SizedBox(height: 10),
                      Text(
                        isFr
                            ? "Aucun historique disponible"
                            : "No history available",
                        style: GoogleFonts.poppins(
                            fontSize: 14, color: Colors.grey),
                      ),
                    ],
                  ),
                );
              }

              return ListView.builder(
                itemCount: snapshot.data!.length,
                physics: const BouncingScrollPhysics(
                    parent: AlwaysScrollableScrollPhysics()),
                cacheExtent: 500,
                itemBuilder: (context, index) {
                  final item = snapshot.data![index];
                  return RepaintBoundary(
                      child: _promotionItem(context, item));
                },
              );
            },
          ),
        ));
  }

  Widget _promotionItem(BuildContext context, HistoriqueRecompense item) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final statusConfig = getStatusBadgeConfig(item.status);

    return InkWell(
      onTap: () => _showStatusDetailsBottomSheet(context, item),
      onDoubleTap: () => _showStatusDetailsBottomSheet(context, item),
      onLongPress: () => _showStatusDetailsBottomSheet(context, item),
      child: Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: isDark ? Colors.white.withOpacity(0.05) : Colors.white,
            borderRadius: BorderRadius.circular(15),
            border: Border.all(
                color: Theme.of(context).dividerColor.withOpacity(0.1)),
          ),
          child: Row(
            children: [
              Container(
                width: 55,
                height: 55,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Stack(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: CachedNetworkImage(
                        imageUrl: item.imageUrl,
                        width: 55,
                        height: 55,
                        fit: BoxFit.cover,
                        placeholder: (context, url) =>
                            Container(color: Colors.grey[200]),
                        errorWidget: (context, url, error) =>
                            FaIcon(FontAwesomeIcons.circleExclamation),
                      ),
                    ),
                    Positioned(
                      bottom: 2,
                      right: 2,
                      child: Container(
                        padding: const EdgeInsets.all(2),
                        decoration: BoxDecoration(
                          color: statusConfig["color"],
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 1.5),
                        ),
                        child: FaIcon(
                          statusConfig["icon"],
                          color: Colors.white,
                          size: 10,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item.title,
                      style: GoogleFonts.poppins(
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Text(item.date,
                            style: GoogleFonts.poppins(
                                fontSize: 12, color: Colors.grey)),
                        const SizedBox(width: 8),
                        Container(
                          width: 3,
                          height: 3,
                          decoration: const BoxDecoration(
                            color: Colors.grey,
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 8),
                        const FaIcon(FontAwesomeIcons.eye,
                            size: 12, color: Colors.grey),
                        const SizedBox(width: 4),
                        Text(item.views,
                            style: GoogleFonts.poppins(
                                fontSize: 12, color: Colors.grey)),
                      ],
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    "${item.amount} FCFA",
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                      color: statusConfig["color"],
                    ),
                  ),
                  const SizedBox(height: 5),
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: statusConfig["color"].withOpacity(0.15),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        FaIcon(
                          statusConfig["icon"],
                          size: 12,
                          color: statusConfig["color"],
                        ),
                        const SizedBox(width: 4),
                        Text(
                          statusConfig["label"],
                          style: GoogleFonts.poppins(
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                            color: statusConfig["color"],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showStatusDetailsBottomSheet(
      BuildContext context, HistoriqueRecompense item) {
    final bool isFr = langUserPhone == "fr";
    setState(() {
      _proofImage1 = null;
      _proofImage2 = null;
    });

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(25)),
      ),
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return DraggableScrollableSheet(
              initialChildSize: 0.7,
              minChildSize: 0.4,
              maxChildSize: 0.95,
              expand: false,
              builder: (context, scrollController) {
                return SingleChildScrollView(
                  controller: scrollController,
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Center(
                        child: Container(
                          width: 40,
                          height: 5,
                          decoration: BoxDecoration(
                            color: Colors.grey[300],
                            borderRadius: BorderRadius.circular(10),
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),
                      Text(
                        item.title,
                        style: GoogleFonts.poppins(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        isFr
                            ? "Récompense : ${item.amount} FCFA"
                            : "Reward: ${item.amount} FCFA",
                        style: GoogleFonts.poppins(
                          fontSize: 14,
                          color: primaryColor,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      const Divider(height: 30),
                      _buildStatusContent(item, setModalState, isFr),
                      const SizedBox(height: 30),
                      if (item.status == "terminer" ||
                          item.status == "en_cours" ||
                          item.status == "en_attente")
                        _buildWhatsAppButton(isFr),
                    ],
                  ),
                );
              },
            );
          },
        );
      },
    );
  }

  Widget _buildStatusContent(
      HistoriqueRecompense item, StateSetter setModalState, bool isFr) {
    switch (item.status) {
      case "en_attente":
        return _statusInfo(
          FontAwesomeIcons.hourglassEmpty,
          Colors.orange,
          isFr ? "En attente d'approbation" : "Awaiting approval",
          isFr
              ? "Vous avez soumis vos preuves de participation. Notre équipe examine actuellement votre demande.\n\nSi vous ne l'avez pas encore fait, veuillez envoyer la capture vidéo comme dernière preuve par WhatsApp au numéro d'assistance de Dressur."
              : "You have submitted your participation proofs. Our team is currently reviewing your request.\n\nIf you haven't done so yet, please send the video capture as final proof via WhatsApp to Dressur's support number.",
        );
      case "terminer":
        return Column(
          children: [
            _statusInfo(
              FontAwesomeIcons.clock,
              Colors.green,
              isFr
                  ? "Temps écoulé - Soumission requise"
                  : "Time elapsed - Submission required",
              isFr
                  ? "Le temps de participation est terminé. Vous devez maintenant soumettre vos preuves (les deux captures d'écran) via le formulaire ci-dessous.\n\nNote : La capture vidéo doit être envoyée séparément par WhatsApp."
                  : "The participation time has ended. You must now submit your proofs (both screenshots) via the form below.\n\nNote: The video capture must be sent separately via WhatsApp.",
            ),
            const SizedBox(height: 20),
            _buildSubmissionForm(item, setModalState, isFr),
          ],
        );
      case "echouer":
        return _statusInfo(
          FontAwesomeIcons.circleExclamation,
          Colors.red,
          isFr ? "Participation échouée" : "Participation failed",
          isFr
              ? "Malheureusement, vous n'avez pas soumis vos preuves de participation dans les délais impartis. Il est désormais trop tard pour le faire pour cette promotion."
              : "Unfortunately, you did not submit your participation proofs within the required time. It is now too late to do so for this promotion.",
        );
      case "refuser":
        return _statusInfo(
          FontAwesomeIcons.slash,
          Colors.red,
          isFr ? "Preuves refusées" : "Proofs rejected",
          isFr
              ? "Les preuves que vous avez fournies n'ont pas été jugées recevables par notre équipe de modération. En conséquence, la récompense ne peut pas être accordée."
              : "The proofs you provided were not deemed acceptable by our moderation team. As a result, the reward cannot be granted.",
        );
      case "approuver":
        return _statusInfo(
          FontAwesomeIcons.solidCircleCheck,
          Colors.green,
          isFr ? "Félicitations ! Approuvé" : "Congratulations! Approved",
          isFr
              ? "Vos preuves de participation ont été vérifiées et validées. La récompense a été créditée sur votre solde Dressur."
              : "Your participation proofs have been verified and validated. The reward has been credited to your Dressur balance.",
        );
      case "en_cours":
        return Column(
          children: [
            _statusInfo(
              FontAwesomeIcons.arrowsRotate,
              Colors.blue,
              isFr ? "Participation en cours" : "Participation in progress",
              isFr
                  ? "Le temps de soumission n'est pas encore arrivé. Cependant, si vous estimez avoir déjà atteint votre objectif, vous pouvez soumettre vos preuves dès maintenant."
                  : "The submission time has not yet arrived. However, if you believe you have already reached your goal, you can submit your proofs now.",
            ),
            const SizedBox(height: 20),
            _buildSubmissionForm(item, setModalState, isFr),
          ],
        );
      default:
        return Text(isFr ? "Statut inconnu" : "Unknown status");
    }
  }

  Widget _statusInfo(
      IconData icon, Color color, String title, String description) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            FaIcon(icon, color: color, size: 28),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                title,
                style: GoogleFonts.poppins(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: color,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 15),
        Text(
          description,
          style: GoogleFonts.poppins(
            fontSize: 14,
            color: Colors.grey[700],
            height: 1.5,
          ),
        ),
      ],
    );
  }

  Widget _buildSubmissionForm(
      HistoriqueRecompense item, StateSetter setModalState, bool isFr) {
    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(15),
        border: Border.all(color: Colors.grey[300]!),
      ),
      child: Column(
        children: [
          Text(
            isFr ? "Formulaire de soumission" : "Submission form",
            style: GoogleFonts.poppins(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 15),
          _proofCard(
              "1",
              isFr ? "Capture – Liste des statuts" : "Screenshot – Status list",
              isFr
                  ? "• Affiche la liste des statuts WhatsApp\n• Le statut de la promotion doit être visible"
                  : "• Shows the WhatsApp status list\n• The promotion status must be visible",
              _proofImage1,
              () => _pickImage(1, setModalState),
              isFr),
          const SizedBox(height: 15),
          _proofCard(
              "2",
              isFr ? "Capture – Statut ouvert" : "Screenshot – Open status",
              isFr
                  ? "• Image complète\n• Texte descriptif complet\n• Nombre de vues, date et heure visibles"
                  : "• Full image\n• Complete descriptive text\n• Number of views, date and time visible",
              _proofImage2,
              () => _pickImage(2, setModalState),
              isFr),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _isSubmitting
                  ? null
                  : () => _submitProofs(item, setModalState, isFr),
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10)),
                padding: EdgeInsets.symmetric(vertical: 12),
              ),
              child: _isSubmitting
                  ? SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                          color: Colors.white, strokeWidth: 2))
                  : Text(
                      isFr ? "Soumettre les preuves" : "Submit proofs",
                      style: GoogleFonts.poppins(
                          color: Colors.white, fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _proofCard(String number, String title, String instructions,
      File? image, VoidCallback onTap, bool isFr) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            CircleAvatar(
                radius: 10,
                backgroundColor: primaryColor,
                child: Text(number,
                    style: GoogleFonts.poppins(
                        color: Colors.white, fontSize: 10))),
            const SizedBox(width: 8),
            Text(title,
                style: GoogleFonts.poppins(
                    fontSize: 13, fontWeight: FontWeight.w600)),
          ],
        ),
        const SizedBox(height: 5),
        Text(instructions,
            style: GoogleFonts.poppins(fontSize: 11, color: Colors.grey[600])),
        const SizedBox(height: 10),
        InkWell(
          onTap: onTap,
          child: Container(
            height: 120,
            width: double.infinity,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.grey[300]!),
              image: image != null
                  ? DecorationImage(image: FileImage(image), fit: BoxFit.cover)
                  : null,
            ),
            child: image == null
                ? Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      FaIcon(FontAwesomeIcons.camera,
                          color: primaryColor, size: 30),
                      const SizedBox(height: 5),
                      Text(
                          isFr ? "Cliquez pour choisir" : "Tap to choose",
                          style: GoogleFonts.poppins(
                              fontSize: 12, color: Colors.grey)),
                    ],
                  )
                : null,
          ),
        ),
      ],
    );
  }

  Future<void> _pickImage(int index, StateSetter setModalState) async {
    final XFile? pickedFile =
        await _picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
    if (pickedFile != null) {
      setState(() {
        if (index == 1)
          _proofImage1 = File(pickedFile.path);
        else
          _proofImage2 = File(pickedFile.path);
      });
      setModalState(() {});
    }
  }

  Future<void> _submitProofs(
      HistoriqueRecompense item, StateSetter setModalState, bool isFr) async {
    if (_proofImage1 == null || _proofImage2 == null) {
      dangerNoti(
        isFr ? "Attention" : "Warning",
        isFr
            ? "Veuillez sélectionner les deux captures d'écran."
            : "Please select both screenshots.",
        context,
      );
      return;
    }

    setModalState(() => _isSubmitting = true);
    setState(() => _isSubmitting = true);

    try {
      var request = http.MultipartRequest('POST',
          Uri.parse('$generalRouteForApi/submitProgrammeRecompenseProofs'));
      request.fields['uid'] = uidUser;
      request.fields['idHistorique'] = item.id;
      request.files.add(
          await http.MultipartFile.fromPath('capture1', _proofImage1!.path));
      request.files.add(
          await http.MultipartFile.fromPath('capture2', _proofImage2!.path));

      var response = await request.send();
      var responseData = await response.stream.bytesToString();
      var data = jsonDecode(responseData);
      if (!mounted) return;

      if (response.statusCode == 200 && data['error'] == false) {
        Navigator.pop(context);
        successNoti(
          isFr ? "Succès" : "Success",
          data['message'] ??
              (isFr
                  ? "Preuves soumises avec succès !"
                  : "Proofs submitted successfully!"),
          context,
        );

        setModalState(() => _isSubmitting = false);
        setState(() => _isSubmitting = false);
        await getMyProgrammeRecompenseInformations();
      } else {
        dangerNoti(
          isFr ? "Erreur" : "Error",
          data['message'] ??
              (isFr ? "Une erreur est survenue." : "An error occurred."),
          context,
        );
      }
    } catch (e) {
      if (!mounted) return;
      dangerNoti(
        isFr ? "Erreur" : "Error",
        isFr
            ? "Impossible de contacter le serveur."
            : "Unable to contact the server.",
        context,
      );
    } finally {
      setModalState(() => _isSubmitting = false);
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  Widget _buildWhatsAppButton(bool isFr) {
    return SizedBox(
      width: double.infinity,
      child: OutlinedButton.icon(
        onPressed: () async {
          final Uri _url = Uri.parse(whatsappDSURL);
          if (!await launchUrl(_url, mode: LaunchMode.externalApplication)) {
          }
        },
        icon: FaIcon(FontAwesomeIcons.solidComment, size: 18),
        label: Text(
            isFr ? "Envoyer la vidéo sur WhatsApp" : "Send video on WhatsApp"),
        style: OutlinedButton.styleFrom(
          foregroundColor: Colors.green,
          side: BorderSide(color: Colors.green),
          padding: EdgeInsets.symmetric(vertical: 12),
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
        ),
      ),
    );
  }

  Map<String, dynamic> getStatusBadgeConfig(String status) {
    final bool isFr = langUserPhone == "fr";
    switch (status) {
      case "en_attente":
        return {
          "label": isFr ? "Attente validation" : "Awaiting validation",
          "icon": FontAwesomeIcons.clock,
          "color": Colors.orange
        };
      case "terminer":
        return {
          "label": isFr ? "Preuves requises" : "Proofs required",
          "icon": FontAwesomeIcons.solidCircleCheck,
          "color": Colors.green
        };
      case "echouer":
        return {
          "label": isFr ? "Échoué" : "Failed",
          "icon": FontAwesomeIcons.xmark,
          "color": Colors.red
        };
      case "refuser":
        return {
          "label": isFr ? "Refusé" : "Rejected",
          "icon": FontAwesomeIcons.xmark,
          "color": Colors.red
        };
      case "approuver":
        return {
          "label": isFr ? "Approuvé" : "Approved",
          "icon": FontAwesomeIcons.solidCircleCheck,
          "color": Colors.green
        };
      case "en_cours":
        return {
          "label": isFr ? "En cours" : "In progress",
          "icon": FontAwesomeIcons.arrowsRotate,
          "color": Colors.blue
        };
      default:
        return {
          "label": isFr ? "Inconnu" : "Unknown",
          "icon": FontAwesomeIcons.circleQuestion,
          "color": Colors.grey
        };
    }
  }
}
