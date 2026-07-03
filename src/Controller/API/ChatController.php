<?php

namespace App\Controller\API;

use App\Entity\ChatMessage;
use App\Entity\User;
use App\Repository\BoostRepository;
use App\Repository\ChatMessageRepository;
use App\Repository\ContactRepository;
use App\Services\CookieDS;
use App\Services\VerificationsDS;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api', name: 'api_chat_')]
class ChatController extends AbstractController
{
    private EntityManagerInterface $em;
    private CookieDS $cookieDS;
    private HttpClientInterface $httpClient;

    public function __construct(
        EntityManagerInterface $em,
        CookieDS $cookieDS,
        HttpClientInterface $httpClient
    ) {
        $this->em       = $em;
        $this->cookieDS = $cookieDS;
        $this->httpClient = $httpClient;
    }

    #[Route('/chat', name: 'chat', methods: ['POST'])]
    public function chat(
        Request $request,
        VerificationsDS $verificationsDS,
        ChatMessageRepository $chatMessageRepository,
        BoostRepository $boostRepository,
        ContactRepository $contactRepository
    ): JsonResponse {
        // --- Auth ---
        $uid = $this->cookieDS->getWithFallback('uid', $request);
        $verifUser = $verificationsDS->verifUSer($uid);
        if ($verifUser['error']) {
            return new JsonResponse(['error' => true, 'message' => $verifUser['message']], Response::HTTP_UNAUTHORIZED);
        }
        /** @var User $user */
        $user = $verifUser['user'];

        // --- Validation ---
        $message = trim((string) $request->request->get('message', ''));
        if ($message === '') {
            return new JsonResponse(['error' => true, 'message' => 'Message vide.']);
        }

        // --- Historique (10 derniers échanges) ---
        $history = $chatMessageRepository->findLastByUser($user, 20);

        // --- Contexte utilisateur dynamique ---
        $systemPrompt = $this->buildSystemPrompt($user, $boostRepository, $contactRepository);

        // --- Construction du tableau de messages pour Groq ---
        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($history as $msg) {
            $messages[] = ['role' => $msg->getRole(), 'content' => $msg->getContent()];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        // --- Appel Groq ---
        $groqKey = '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';
        try {
            $groqResponse = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $groqKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => 'llama-3.1-8b-instant',
                    'messages'    => $messages,
                    'max_tokens'  => 300,
                    'temperature' => 0.6,
                ],
                'timeout' => 15,
            ]);
            $data = $groqResponse->toArray(false);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => true, 'message' => 'Erreur de connexion à l\'assistant.']);
        }

        if (!isset($data['choices'][0]['message']['content'])) {
            return new JsonResponse(['error' => true, 'message' => 'Réponse invalide de l\'assistant.']);
        }

        $assistantReply = trim($data['choices'][0]['message']['content']);

        // --- Sauvegarde en base ---
        $userMsg = (new ChatMessage())->setUser($user)->setRole('user')->setContent($message);
        $assistantMsg = (new ChatMessage())->setUser($user)->setRole('assistant')->setContent($assistantReply);
        $this->em->persist($userMsg);
        $this->em->persist($assistantMsg);
        $this->em->flush();

        return new JsonResponse(['error' => false, 'reply' => $assistantReply]);
    }

    // -----------------------------------------------------------------------
    // Prompt système : partie statique (app) + partie dynamique (utilisateur)
    // -----------------------------------------------------------------------
    private function buildSystemPrompt(User $user, BoostRepository $boostRepository, ContactRepository $contactRepository): string
    {
        $lang = $user->getLang() ?? 'fr';
        $isFr = $lang === 'fr';

        // --- Boost actif ---
        $boosts = $boostRepository->findBy(['user' => $user]);
        $boostActif = false;
        $boostInfo  = '';
        $now = new DateTime();
        foreach ($boosts as $boost) {
            if ($boost->getTypeBoost() === 'quota' && $boost->getDateExp() === null) {
                $boostActif = true;
                $boostInfo  = 'quota - ' . $boost->getNbContactsObtenus() . ' contacts obtenus / mode: ' . $boost->getMode();
                break;
            }
            if ($boost->getTypeBoost() === 'date' && $boost->getDateExp() !== null && $now < $boost->getDateExp()) {
                $boostActif = true;
                $boostInfo  = 'date - expire le ' . $boost->getDateExp()->format('Y-m-d') . ' / mode: ' . $boost->getMode();
                break;
            }
        }

        // --- Contacts ---
        $contact = $contactRepository->findOneBy(['user' => $user]);
        $nbrContactsRecus  = $contact ? count($contact->getWhoAddMe())  : 0;
        $nbrContactsAjoutes = $contact ? count($contact->getWhoIAdd()) : 0;

        // --- Données utilisateur (sans mot de passe ni token) ---
        // Seules les données non-sensibles sont transmises au LLM tiers
        $userContext = implode("\n", [
            '=== PROFIL UTILISATEUR ACTUEL ===',
            'Prénom/Nom : '    . ($user->getNom()    ?? 'Non renseigné'),
            'Pseudo : '        . ($user->getPseudo() ?? 'Non renseigné'),
            'À propos : '      . ($user->getApropos() ?? 'Non renseigné'),
            'Langue : '        . $lang,
            'Email vérifié : '    . ($user->getMailIsVerified() ? 'Oui' : 'Non'),
            'WhatsApp vérifié : ' . ($user->getTelIsVerified()  ? 'Oui' : 'Non'),
            'Inscrit au programme récompenses : ' . ($user->getIsInscritProgrammeRecompense() ? 'Oui' : 'Non'),
            'Solde récompenses : ' . ($user->getSoldeProgrammeRecompense() ?? 0) . ' pts',
            'Contacts reçus : '   . $nbrContactsRecus,
            'Contacts ajoutés : ' . $nbrContactsAjoutes,
            'Boost contact actif : ' . ($boostActif ? 'Oui (' . $boostInfo . ')' : 'Non'),
            'Réseaux sociaux renseignés : ' . implode(', ', array_filter([
                $user->getTiktok()    ? 'TikTok'    : null,
                $user->getInstagram() ? 'Instagram' : null,
                $user->getFacebook()  ? 'Facebook'  : null,
                $user->getYoutube()   ? 'YouTube'   : null,
            ])) ?: 'Aucun',
            'Membre depuis : ' . ($user->getCreatedAt() ? $user->getCreatedAt()->format('d/m/Y') : 'Inconnu'),
        ]);

        $staticKnowledge = <<<PROMPT
Tu es l'assistant intelligent de l'application Dressur. Tu aides uniquement les utilisateurs de Dressur.
Si une question n'a aucun rapport avec Dressur, réponds UNIQUEMENT : "{$this->offTopicMsg($isFr)}"

RÈGLES ABSOLUES :
- Réponds TOUJOURS en {$lang} (langue de l'utilisateur).
- Réponses courtes et directes — maximum 3-4 phrases.
- Ne révèle jamais le mot de passe ou des informations confidentielles techniques.
- Utilise les informations personnelles de l'utilisateur pour personnaliser ta réponse.

=== QU'EST-CE QUE DRESSUR ? ===
Dressur est une application de networking professionnel disponible sur mobile (iOS/Android) et sur le web (dressur.site). Elle permet aux professionnels d'obtenir de nouveaux contacts qualifiés, de promouvoir leurs activités et de développer leur réseau.

=== SERVICES PRINCIPAUX ===

1. BOOST CONTACT
- Augmente ta visibilité pour recevoir de nouveaux contacts professionnels.
- Type "Durée" : boost actif pendant X jours.
- Type "Quota" : boost actif jusqu'à atteindre X contacts reçus.
- Mode Gratuit : limité (formule de base).
- Mode Payant : plusieurs formules disponibles (plus de contacts, plus de durée).
- Un seul boost actif à la fois est possible.
- Nécessite que le numéro WhatsApp soit vérifié pour le mode gratuit.
- Pour démarrer : aller dans l'onglet "Actu" ou "Boost" > "Démarrer un Boost".

2. ACTU (Fil d'actualités)
- Affiche les contacts Dressur disponibles.
- Bouton "Ajouter tous les contacts" : ajoute en un clic tous les contacts disponibles.
- Requiert un Boost Contact actif pour ajouter des contacts.
- Requiert que le numéro WhatsApp soit vérifié.

3. BOÎTE DE RÉCEPTION
- Reçoit les contacts envoyés par Dressur lorsque ton boost est actif.
- Affiche le solde de récompenses, le nombre de contacts et le statut du boost.
- Accès à la liste des contacts, récompenses, synchronisation et notifications.

4. PROMOTIONS AFFAIRE
- Crée des annonces pour promouvoir tes produits ou services.
- Gestion des promotions actives et expirées.

5. PROMOTIONS RÉSEAU SOCIAUX
- Booste tes pages sociales (TikTok, Instagram, Facebook, YouTube) auprès du réseau Dressur.

6. PROGRAMME DE RÉCOMPENSES
- Programme de fidélité : gagne des points en utilisant Dressur.
- Retrait du solde vers un réseau de paiement mobile (MTN, etc.).
- S'inscrire dans la section "Réception".

7. PRÉFÉRENCES
- Configure les pays ciblés pour recevoir des contacts de ces zones.
- Active/désactive l'affichage dans les actus.
- Sauvegarde des contacts sur Google ou en local.

8. PARAMÈTRES & PROFIL
- Modifier nom, pseudo, à propos, réseaux sociaux.
- Changer de mot de passe.
- Vérifier email et WhatsApp.
- Choisir la langue (FR/EN) et le thème (clair/sombre).
- Section "Assistance & Avis" : Support technique, Suggestions, Signaler un utilisateur.

=== INSCRIPTION & CONNEXION ===
- Inscription avec email, nom, pseudo, téléphone + pays.
- Vérification email obligatoire après inscription.
- Vérification WhatsApp recommandée (obligatoire pour boost gratuit et ajout de contacts).
- Connexion via email + mot de passe.
- Mot de passe oublié : disponible sur l'écran de connexion.

=== PROBLÈMES FRÉQUENTS ===
- "Je ne peux pas ajouter de contacts" → Vérifier que le Boost Contact est actif et que le WhatsApp est vérifié.
- "Mon boost ne fonctionne pas" → Un seul boost actif à la fois. Attendre la fin du boost en cours.
- "Je ne vois pas mes contacts" → Actualiser la page Actu ou vérifier les préférences de pays.
- "Problème de paiement" → Contacter le support via l'onglet Paramètres > Assistance.
- "Email non vérifié" → Vérifier les spams, puis relancer la vérification depuis le profil.

$userContext
PROMPT;

        return $staticKnowledge;
    }

    private function offTopicMsg(bool $isFr): string
    {
        return $isFr
            ? 'Je traite uniquement les questions liées à Dressur.'
            : 'I only handle questions related to Dressur.';
    }
}
