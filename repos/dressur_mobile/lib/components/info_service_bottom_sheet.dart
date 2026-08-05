// ignore_for_file: deprecated_member_use
import 'dart:async';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:dressur/components/constant.dart';

class ServiceInfoItem {
  final IconData icon;
  final String textFr;
  final String textEn;
  const ServiceInfoItem({
    required this.icon,
    required this.textFr,
    required this.textEn,
  });
}

void showServiceInfoModal(
  BuildContext context, {
  required String titleFr,
  required String titleEn,
  required List<ServiceInfoItem> items,
  int countdownSeconds = 2,
}) {
  showModalBottomSheet(
    context: context,
    isDismissible: false,
    enableDrag: false,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => _ServiceInfoBottomSheet(
      titleFr: titleFr,
      titleEn: titleEn,
      items: items,
      totalSeconds: countdownSeconds,
    ),
  );
}

class _ServiceInfoBottomSheet extends StatefulWidget {
  final String titleFr;
  final String titleEn;
  final List<ServiceInfoItem> items;
  final int totalSeconds;

  const _ServiceInfoBottomSheet({
    required this.titleFr,
    required this.titleEn,
    required this.items,
    required this.totalSeconds,
  });

  @override
  State<_ServiceInfoBottomSheet> createState() =>
      _ServiceInfoBottomSheetState();
}

class _ServiceInfoBottomSheetState extends State<_ServiceInfoBottomSheet>
    with SingleTickerProviderStateMixin {
  late int _remaining;
  Timer? _timer;
  late AnimationController _progressCtrl;

  @override
  void initState() {
    super.initState();
    _remaining = widget.totalSeconds;
    _progressCtrl = AnimationController(
      vsync: this,
      duration: widget.totalSeconds > 0
          ? Duration(seconds: widget.totalSeconds)
          : const Duration(milliseconds: 1),
      value: widget.totalSeconds > 0 ? 1.0 : 0.0,
    );

    if (widget.totalSeconds == 0) return;

    Future.delayed(const Duration(milliseconds: 700), () {
      if (!mounted) return;
      _progressCtrl.animateTo(0.0, curve: Curves.linear);
      _timer = Timer.periodic(const Duration(seconds: 1), (t) {
        if (!mounted) {
          t.cancel();
          return;
        }
        if (_remaining <= 1) {
          t.cancel();
          setState(() => _remaining = 0);
        } else {
          setState(() => _remaining--);
        }
      });
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _progressCtrl.dispose();
    super.dispose();
  }

  Widget _buildItem(IconData icon, String text) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 38,
          height: 38,
          decoration: BoxDecoration(
            color: Colors.red[50],
            borderRadius: BorderRadius.circular(10),
          ),
          child: Center(
            child: FaIcon(icon, size: 16, color: Colors.red[700]),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.only(top: 7),
            child: Text(
              text,
              style: GoogleFonts.poppins(
                fontSize: 13,
                color: Colors.grey[700],
                height: 1.5,
              ),
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final bool canClose = _remaining == 0;
    final bool isFr = langUserPhone == "fr";

    return WillPopScope(
      onWillPop: () async => false,
      child: Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        padding: EdgeInsets.fromLTRB(
          24,
          12,
          24,
          MediaQuery.of(context).padding.bottom + 28,
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 44,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(height: 24),
              Container(
                width: 76,
                height: 76,
                decoration: BoxDecoration(
                  color: Colors.red[50],
                  shape: BoxShape.circle,
                ),
                child: Center(
                  child: FaIcon(
                    FontAwesomeIcons.circleInfo,
                    color: Colors.red[700],
                    size: 34,
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                isFr ? widget.titleFr : widget.titleEn,
                style: GoogleFonts.poppins(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.red[700],
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 6),
              Text(
                isFr
                    ? "Lis attentivement avant de continuer"
                    : "Read carefully before continuing",
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  color: Colors.grey[500],
                ),
              ),
              const SizedBox(height: 24),
              ...widget.items.asMap().entries.map((entry) => Column(
                    children: [
                      _buildItem(
                        entry.value.icon,
                        isFr ? entry.value.textFr : entry.value.textEn,
                      ),
                      if (entry.key < widget.items.length - 1)
                        const SizedBox(height: 14),
                    ],
                  )),
              const SizedBox(height: 24),
              AnimatedBuilder(
                animation: _progressCtrl,
                builder: (_, __) => Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(6),
                      child: LinearProgressIndicator(
                        value: _progressCtrl.value,
                        backgroundColor: Colors.grey[200],
                        valueColor: AlwaysStoppedAnimation<Color>(
                          canClose ? Colors.green : primaryColor,
                        ),
                        minHeight: 6,
                      ),
                    ),
                    if (!canClose) ...[
                      const SizedBox(height: 6),
                      Text(
                        isFr
                            ? "Disponible dans $_remaining s"
                            : "Available in $_remaining s",
                        style: GoogleFonts.poppins(
                          fontSize: 11,
                          color: Colors.grey[400],
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed:
                      canClose ? () => Navigator.of(context).pop() : null,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.red[700],
                    disabledBackgroundColor: Colors.red[100],
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 15),
                    elevation: 0,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  child: Text(
                    canClose
                        ? (isFr ? "J'ai compris ✓" : "I understand ✓")
                        : (isFr
                            ? "J'ai compris ($_remaining)"
                            : "I understand ($_remaining)"),
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.w600,
                      fontSize: 15,
                      color: canClose ? Colors.white : Colors.red[300],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
