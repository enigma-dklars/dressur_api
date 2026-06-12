<?php

namespace App\Controller\API;

use App\Entity\Contact;
use App\Entity\Preference;
use App\Entity\User;
use App\Entity\VerifMail;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Services\VerificationsDS;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleAuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private TraitementsDS $traitementsDS,
        private VerificationsDS $verificationsDS,
        private CookieDS $cookieDS,
        private HttpClientInterface $http,
    ) {}

    // ── Mobile : échange du code OAuth reçu depuis le navigateur Flutter ──────
    #[Route('/api/auth/google', name: 'api_auth_google', methods: ['POST'])]
    public function mobileGoogleAuth(Request $request, SessionDS $sessionDS): JsonResponse
    {
        $code          = $request->request->get('code');
        $redirectUri   = $request->request->get('redirect_uri');
        $langUserPhone = $request->request->get('langUserPhone') ?? 'fr';
        $sessionDS->set('langUserPhone', $langUserPhone);

        if (!$code || !$redirectUri) {
            return new JsonResponse(['error' => true, 'message' => 'Paramètres Google manquants.']);
        }

        // Échanger le code contre un access token
        try {
            $tokenResponse = $this->http->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'code'          => $code,
                    'client_id'     => getenv('GOOGLE_WEB_CLIENT_ID'),
                    'client_secret' => getenv('GOOGLE_WEB_CLIENT_SECRET'),
                    'redirect_uri'  => $redirectUri,
                    'grant_type'    => 'authorization_code',
                ],
            ]);
            $tokenData = $tokenResponse->toArray(false);
        } catch (\Throwable) {
            return new JsonResponse(['error' => true, 'message' => 'Vérification Google échouée.']);
        }

        $accessToken = $tokenData['access_token'] ?? null;
        if (!$accessToken) {
            return new JsonResponse(['error' => true, 'message' => 'Token Google invalide.']);
        }

        // Récupérer les infos utilisateur Google
        try {
            $uiResponse = $this->http->request('GET', 'https://www.googleapis.com/oauth2/v3/userinfo', [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ]);
            $googleUser = $uiResponse->toArray(false);
        } catch (\Throwable) {
            return new JsonResponse(['error' => true, 'message' => 'Impossible de récupérer le profil Google.']);
        }

        $email         = strtolower(trim($googleUser['email'] ?? ''));
        $emailVerified = $googleUser['email_verified'] ?? false;
        $givenName     = $googleUser['given_name'] ?? 'User';
        $familyName    = $googleUser['family_name'] ?? '';

        if (!$email || !$emailVerified) {
            return new JsonResponse(['error' => true, 'message' => 'Email Google non vérifié.']);
        }

        $user = $this->findOrCreateGoogleUser($email, $givenName, $familyName, 'google_mobile');

        $user->setLastLoginTo(new DateTime())->setLastLoginSource('google_mobile');
        if ($user->getLang() !== $langUserPhone) {
            $user->setLang($langUserPhone);
        }
        $this->em->flush();

        $verif = $this->verificationsDS->verifUSer($user->getUid());
        if ($verif['error'] === true) {
            return new JsonResponse([
                'error'   => true,
                'titre'   => $verif['titre'],
                'message' => $verif['message'],
            ]);
        }

        $this->cookieDS->set('uid', $user->getUid());

        return new JsonResponse([
            'error'   => false,
            'message' => 'Connecté avec Google !',
            'user'    => $this->traitementsDS->infosUser($user),
        ]);
    }

    // ── Web : redirection vers la page de consentement Google ────────────────
    #[Route('/auth/google', name: 'auth_google_redirect', methods: ['GET'])]
    public function webGoogleRedirect(Request $request): RedirectResponse
    {
        $redirectUri = $request->getSchemeAndHttpHost() . '/auth/google/callback';

        $params = http_build_query([
            'client_id'     => getenv('GOOGLE_WEB_CLIENT_ID'),
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);

        return new RedirectResponse('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    }

    // ── Web : callback après autorisation Google ──────────────────────────────
    #[Route('/auth/google/callback', name: 'auth_google_callback', methods: ['GET'])]
    public function webGoogleCallback(Request $request): Response
    {
        $code = $request->query->get('code');
        if (!$code) {
            return $this->redirectToRoute('app_connexion');
        }

        $redirectUri = $request->getSchemeAndHttpHost() . '/auth/google/callback';

        // Échanger le code contre un access token
        try {
            $tokenResponse = $this->http->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'code'          => $code,
                    'client_id'     => getenv('GOOGLE_WEB_CLIENT_ID'),
                    'client_secret' => getenv('GOOGLE_WEB_CLIENT_SECRET'),
                    'redirect_uri'  => $redirectUri,
                    'grant_type'    => 'authorization_code',
                ],
            ]);
            $tokenData = $tokenResponse->toArray(false);
        } catch (\Throwable) {
            return $this->redirectToRoute('app_connexion');
        }

        $accessToken = $tokenData['access_token'] ?? null;
        if (!$accessToken) {
            return $this->redirectToRoute('app_connexion');
        }

        // Récupérer les infos utilisateur Google
        try {
            $uiResponse = $this->http->request('GET', 'https://www.googleapis.com/oauth2/v3/userinfo', [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ]);
            $googleUser = $uiResponse->toArray(false);
        } catch (\Throwable) {
            return $this->redirectToRoute('app_connexion');
        }

        $email         = strtolower(trim($googleUser['email'] ?? ''));
        $emailVerified = $googleUser['email_verified'] ?? false;
        $givenName     = $googleUser['given_name'] ?? 'User';
        $familyName    = $googleUser['family_name'] ?? '';

        if (!$email || !$emailVerified) {
            return $this->redirectToRoute('app_connexion');
        }

        $user = $this->findOrCreateGoogleUser($email, $givenName, $familyName, 'google_web');

        $verif = $this->verificationsDS->verifUSer($user->getUid());
        if ($verif['error'] === true) {
            return $this->redirectToRoute('app_connexion');
        }

        $user->setLastLoginTo(new DateTime())->setLastLoginSource('google_web');
        $this->em->flush();

        $this->cookieDS->set('uid', $user->getUid());

        return $this->redirectToRoute('app_private');
    }

    // ── Helper : trouver ou créer un utilisateur via Google ──────────────────
    private function findOrCreateGoogleUser(
        string $email,
        string $givenName,
        string $familyName,
        string $source
    ): User {
        $user = $this->userRepository->findOneBy(['mail' => $email]);
        if ($user) {
            return $user;
        }

        // Construire un pseudo valide à partir du prénom Google
        $pseudo = preg_replace('/[^a-zA-Z0-9_]/', '', $givenName);
        if (strlen($pseudo) < 2) {
            $pseudo = 'user' . rand(10000, 99999);
        }

        // S'assurer que le pseudo n'est pas déjà pris
        if ($this->userRepository->findOneBy(['pseudo' => $pseudo])) {
            $pseudo .= rand(10, 999);
        }

        $user = new User();
        $user->setPseudo($pseudo)
            ->setNom($familyName ?: $givenName)
            ->setMail($email)
            ->setPassword(password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT))
            ->setMailIsVerified(true)
            ->setCreatedAt(new DateTime())
            ->setLastLoginTo(new DateTime())
            ->setRegisterSource($source)
            ->setLastLoginSource($source);

        $this->em->persist($user);

        $preference = new Preference();
        $preference->setUser($user)->setPaysChoisies([]);
        $this->em->persist($preference);

        $contact = new Contact();
        $contact->setUser($user);
        $this->em->persist($contact);

        $verifMail = new VerifMail();
        $verifMail->setUser($user);
        $this->em->persist($verifMail);

        $this->em->flush();

        return $user;
    }
}
