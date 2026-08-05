// ignore_for_file: use_build_context_synchronously

import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:dressur/components/constant.dart';

class AssistantPage extends StatefulWidget {
  const AssistantPage({Key? key}) : super(key: key);

  @override
  State<AssistantPage> createState() => _AssistantPageState();
}

class _AssistantPageState extends State<AssistantPage> {
  final List<_ChatMsg> _messages = [];
  final TextEditingController _controller = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  final GlobalKey _lastUserMsgKey = GlobalKey();

  bool _isSending = false;       // empêche un double envoi
  bool _isTyping = false;        // affiche la bulle d'animation dans la liste
  bool _historyLoading = true;   // chargement initial de l'historique
  bool _showScrollToBottom = false; // bouton descendre en bas style WhatsApp

  /// Index du dernier message envoyé par l'utilisateur (-1 si aucun).
  int get _lastUserMsgIndex {
    for (int i = _messages.length - 1; i >= 0; i--) {
      if (_messages[i].role == 'user') return i;
    }
    return -1;
  }

  // ── Lifecycle ──────────────────────────────────────────────────────────────

  @override
  void initState() {
    super.initState();
    _loadHistory();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.removeListener(_onScroll);
    _controller.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_scrollController.hasClients) return;
    final distanceFromBottom =
        _scrollController.position.maxScrollExtent - _scrollController.offset;
    final shouldShow = distanceFromBottom > 120;
    if (shouldShow != _showScrollToBottom) {
      setState(() => _showScrollToBottom = shouldShow);
    }
  }

  // ── Chargement de l'historique au démarrage ────────────────────────────────

  Future<void> _loadHistory() async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$generalRouteForApi/chat/history'),
      )..fields['uid'] = uidUser ?? '';
      final streamed =
          await request.send().timeout(const Duration(seconds: 10));
      final response = await http.Response.fromStream(streamed);
      if (!mounted) return;
      final data = jsonDecode(response.body);
      if (data['error'] == false && data['messages'] is List) {
        final msgs = (data['messages'] as List)
            .map((m) => _ChatMsg(
                  role: m['role'] as String,
                  content: m['content'] as String,
                ))
            .toList();
        setState(() => _messages.addAll(msgs));
        if (msgs.isNotEmpty) _scrollToLastUserMsg();
      }
    } catch (_) {
      // L'historique est optionnel : on ignore silencieusement les erreurs.
    } finally {
      if (!mounted) return;
      setState(() => _historyLoading = false);
    }
  }

  // ── Envoi d'un message ────────────────────────────────────────────────────

  Future<void> _sendMessage() async {
    final text = _controller.text.trim();
    if (text.isEmpty || _isSending) return;

    setState(() {
      _messages.add(_ChatMsg(role: 'user', content: text));
      _isSending = true;
      _isTyping = true;
    });
    _controller.clear();
    _scrollToBottom();

    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$generalRouteForApi/chat'),
      )
        ..fields['uid'] = uidUser ?? ''
        ..fields['message'] = text
        ..fields['platform'] = 'mobile';

      // On attend à la fois la réponse API ET un délai minimum de 1,5 s
      // pour que l'animation "en train d'écrire" reste visible même si l'API
      // répond instantanément.
      final results = await Future.wait<dynamic>([
        request.send().then((s) => http.Response.fromStream(s)),
        Future.delayed(const Duration(milliseconds: 1500)),
      ]);

      final response = results[0] as http.Response;
      final data = jsonDecode(response.body);

      if (!mounted) return;
      // Petit délai après la fin de l'animation avant d'afficher la réponse
      setState(() => _isTyping = false);
      await Future.delayed(const Duration(milliseconds: 120));
      if (!mounted) return;

      if (data['error'] == false && data['reply'] != null) {
        setState(() {
          _messages.add(_ChatMsg(role: 'assistant', content: data['reply']));
        });
      } else {
        _showError();
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _isTyping = false);
      await Future.delayed(const Duration(milliseconds: 120));
      if (!mounted) return;
      _showError();
    } finally {
      if (!mounted) return;
      setState(() => _isSending = false);
      _scrollToBottom();
    }
  }

  void _showError() {
    setState(() {
      _messages.add(_ChatMsg(
        role: 'assistant',
        content: (langUserPhone == 'fr')
            ? 'Une erreur est survenue. Veuillez réessayer.'
            : 'An error occurred. Please try again.',
        isError: true,
      ));
    });
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  /// Scrolle jusqu'au dernier message envoyé par l'utilisateur.
  /// Si aucun message user, scrolle en bas.
  /// Utilisé à l'ouverture de la page et sur le bouton scroll-to-bottom.
  ///
  /// Fonctionne grâce à cacheExtent: double.maxFinite sur le ListView
  /// qui garde tous les items rendus en permanence → la GlobalKey est
  /// toujours valide quelle que soit la position de scroll.
  void _scrollToLastUserMsg() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scrollController.hasClients) return;
      if (_lastUserMsgIndex == -1) {
        // Aucun message user : simple scroll au bas
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
        return;
      }
      final ctx = _lastUserMsgKey.currentContext;
      if (ctx != null) {
        // Positionne la base du dernier message user en bas du viewport
        Scrollable.ensureVisible(
          ctx,
          alignment: 1.0,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      } else {
        // Fallback (ne devrait pas arriver avec cacheExtent: double.maxFinite)
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  // ── Build ──────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final showEmpty = !_historyLoading && _messages.isEmpty && !_isTyping;

    return Scaffold(
      backgroundColor:
          isDark ? const Color(0xFF121212) : const Color(0xFFF8F9FA),
      appBar: AppBar(
        elevation: 0,
        backgroundColor: primaryColor,
        leading: IconButton(
          onPressed: () => Navigator.pop(context),
          icon: const FaIcon(
            FontAwesomeIcons.chevronLeft,
            color: Colors.white,
          ),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              (langUserPhone == 'fr') ? 'Assistant IA' : 'AI Assistant',
              style: GoogleFonts.poppins(
                color: Colors.white,
                fontWeight: FontWeight.w400,
                fontSize: 18,
              ),
            ),
            Text(
              (langUserPhone == 'fr') ? 'Powered by IA' : 'Powered by AI',
              style: GoogleFonts.poppins(color: Colors.white70, fontSize: 11),
            ),
          ],
        ),
      ),
      body: Column(
        children: [
          // ── Zone des messages + bouton scroll-to-bottom ───────────────────
          Expanded(
            child: Stack(
              children: [
                _historyLoading
                    ? const Center(child: CircularProgressIndicator())
                    : showEmpty
                        ? _buildEmptyState(isDark)
                        : ListView.builder(
                            controller: _scrollController,
                            // Garde tous les items rendus en permanence :
                            // indispensable pour que GlobalKey.currentContext
                            // soit toujours valide lors du scroll ciblé.
                            cacheExtent: double.maxFinite,
                            padding: const EdgeInsets.symmetric(
                                horizontal: 12, vertical: 16),
                            // +1 quand la bulle "en train d'écrire" est active
                            itemCount: _messages.length + (_isTyping ? 1 : 0),
                            itemBuilder: (context, index) {
                              if (_isTyping && index == _messages.length) {
                                return _buildTypingBubble(isDark);
                              }
                              final isLastUser = index == _lastUserMsgIndex;
                              return _buildBubble(
                                _messages[index],
                                isDark,
                                key: isLastUser ? _lastUserMsgKey : null,
                              );
                            },
                          ),

                // ── Bouton descendre en bas (style WhatsApp) ───────────────
                if (_showScrollToBottom)
                  Positioned(
                    bottom: 12,
                    right: 12,
                    child: GestureDetector(
                      onTap: _scrollToLastUserMsg,
                      child: Container(
                        width: 38,
                        height: 38,
                        decoration: BoxDecoration(
                          color: isDark
                              ? const Color(0xFF2A2A2A)
                              : Colors.white,
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.18),
                              blurRadius: 6,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: Icon(
                          Icons.keyboard_arrow_down_rounded,
                          color: primaryColor,
                          size: 24,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ),

          // ── Champ de saisie ────────────────────────────────────────────────
          _buildInputBar(isDark),
        ],
      ),
    );
  }

  // ── Bulle d'animation "en train d'écrire" ─────────────────────────────────

  Widget _buildTypingBubble(bool isDark) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          CircleAvatar(
            radius: 14,
            backgroundColor: primaryColor,
            child: const Icon(Icons.smart_toy, size: 16, color: Colors.white),
          ),
          const SizedBox(width: 6),
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
            decoration: BoxDecoration(
              color: isDark ? const Color(0xFF2A2A2A) : Colors.white,
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(16),
                topRight: Radius.circular(16),
                bottomLeft: Radius.circular(4),
                bottomRight: Radius.circular(16),
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.06),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: const _TypingDots(),
          ),
        ],
      ),
    );
  }

  // ── État vide ──────────────────────────────────────────────────────────────

  Widget _buildEmptyState(bool isDark) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.smart_toy_outlined,
              size: 64, color: primaryColor.withOpacity(0.5)),
          const SizedBox(height: 16),
          Text(
            (langUserPhone == 'fr')
                ? 'Bonjour ${nom ?? ''} 👋\nComment puis-je vous aider ?'
                : 'Hello ${nom ?? ''} 👋\nHow can I help you?',
            textAlign: TextAlign.center,
            style: GoogleFonts.poppins(
              fontSize: 16,
              fontWeight: FontWeight.w500,
              color: isDark ? Colors.white70 : Colors.black54,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            (langUserPhone == 'fr')
                ? 'Je réponds uniquement aux questions sur Dressur.'
                : 'I only answer questions about Dressur.',
            textAlign: TextAlign.center,
            style: GoogleFonts.poppins(fontSize: 12, color: Colors.grey),
          ),
        ],
      ),
    );
  }

  // ── Bulle de message ───────────────────────────────────────────────────────

  Widget _buildBubble(_ChatMsg msg, bool isDark, {Key? key}) {
    final isUser = msg.role == 'user';
    return Padding(
      key: key,
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        mainAxisAlignment:
            isUser ? MainAxisAlignment.end : MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          if (!isUser) ...[
            CircleAvatar(
              radius: 14,
              backgroundColor: primaryColor,
              child: const Icon(Icons.smart_toy, size: 16, color: Colors.white),
            ),
            const SizedBox(width: 6),
          ],
          Flexible(
            child: Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: isUser
                    ? primaryColor
                    : (msg.isError
                        ? Colors.red.shade50
                        : (isDark ? const Color(0xFF2A2A2A) : Colors.white)),
                borderRadius: BorderRadius.only(
                  topLeft: const Radius.circular(16),
                  topRight: const Radius.circular(16),
                  bottomLeft: Radius.circular(isUser ? 16 : 4),
                  bottomRight: Radius.circular(isUser ? 4 : 16),
                ),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.06),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Text(
                msg.content,
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  color: isUser
                      ? Colors.white
                      : (msg.isError
                          ? Colors.red
                          : (isDark ? Colors.white : Colors.black87)),
                ),
              ),
            ),
          ),
          if (isUser) ...[
            const SizedBox(width: 6),
            CircleAvatar(
              radius: 14,
              backgroundColor: primaryColor.withOpacity(0.2),
              child: Text(
                ((nom != null && nom!.isNotEmpty) ? nom! : 'U')
                    .substring(0, 1)
                    .toUpperCase(),
                style: GoogleFonts.poppins(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: primaryColor,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  // ── Barre de saisie ────────────────────────────────────────────────────────

  Widget _buildInputBar(bool isDark) {
    return Container(
      padding: const EdgeInsets.fromLTRB(12, 8, 12, 16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E1E1E) : Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.08),
            blurRadius: 8,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: TextField(
              controller: _controller,
              onSubmitted: (_) => _sendMessage(),
              maxLines: 3,
              minLines: 1,
              textCapitalization: TextCapitalization.sentences,
              style: GoogleFonts.poppins(fontSize: 13),
              decoration: InputDecoration(
                hintText: (langUserPhone == 'fr')
                    ? 'Posez votre question...'
                    : 'Ask your question...',
                hintStyle:
                    GoogleFonts.poppins(fontSize: 13, color: Colors.grey),
                filled: true,
                fillColor: isDark
                    ? const Color(0xFF2A2A2A)
                    : const Color(0xFFF0F2F5),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(24),
                  borderSide: BorderSide.none,
                ),
                contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16, vertical: 10),
              ),
            ),
          ),
          const SizedBox(width: 8),
          GestureDetector(
            onTap: _isSending ? null : _sendMessage,
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: _isSending ? Colors.grey.shade300 : primaryColor,
                shape: BoxShape.circle,
              ),
              child: Icon(
                Icons.send_rounded,
                color: _isSending ? Colors.grey.shade500 : Colors.white,
                size: 20,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Animation trois points style WhatsApp ─────────────────────────────────────

class _TypingDots extends StatefulWidget {
  const _TypingDots();

  @override
  State<_TypingDots> createState() => _TypingDotsState();
}

class _TypingDotsState extends State<_TypingDots>
    with SingleTickerProviderStateMixin {
  late AnimationController _ctrl;
  late List<Animation<double>> _anims;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1000),
    )..repeat();

    // Chaque point démarre avec un décalage de 0.2 par rapport au précédent
    _anims = List.generate(3, (i) {
      final start = i * 0.2;
      final end = (start + 0.5).clamp(0.0, 1.0);
      return TweenSequence<double>([
        TweenSequenceItem(
            tween: Tween(begin: 0.0, end: -6.0)
                .chain(CurveTween(curve: Curves.easeOut)),
            weight: 1),
        TweenSequenceItem(
            tween: Tween(begin: -6.0, end: 0.0)
                .chain(CurveTween(curve: Curves.easeIn)),
            weight: 1),
        TweenSequenceItem(
            tween: Tween(begin: 0.0, end: 0.0), weight: 1),
      ]).animate(CurvedAnimation(
        parent: _ctrl,
        curve: Interval(start, end),
      ));
    });
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _ctrl,
      builder: (_, __) => Row(
        mainAxisSize: MainAxisSize.min,
        children: List.generate(
          3,
          (i) => Transform.translate(
            offset: Offset(0, _anims[i].value),
            child: Container(
              width: 8,
              height: 8,
              margin: const EdgeInsets.symmetric(horizontal: 3),
              decoration: BoxDecoration(
                color: Colors.grey.shade400,
                shape: BoxShape.circle,
              ),
            ),
          ),
        ),
      ),
    );
  }
}

// ── Modèle de message ─────────────────────────────────────────────────────────

class _ChatMsg {
  final String role;
  final String content;
  final bool isError;
  const _ChatMsg(
      {required this.role, required this.content, this.isError = false});
}
