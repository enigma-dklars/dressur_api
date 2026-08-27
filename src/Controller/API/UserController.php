<?php

namespace App\Controller\API;

use DateTime;
use Exception;
use App\Entity\User;
use App\Entity\VerifMail;
use App\Entity\Preference;
use App\Entity\UserUsedIdentity;
use App\Utilities\SendMail;
use App\Repository\EnvRepository;
use App\Repository\UserRepository;
use App\Repository\UserBannedRepository;
use App\Repository\VerifMailRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Contact;
use App\Entity\HistoriqueProgrammeRecompense;
use App\Entity\Preuve;
use App\Entity\Suggestion;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\HistoriqueProgrammeRecompenseRepository;
use App\Repository\PromotionRepository;
use App\Repository\TransactionRepository;
use App\Services\CookieDS;
use App\Utilities\UuidGenerator;
use App\Services\TraitementsDS;
use App\Services\VerificationsDS;
use App\Services\UserRestrictionService;
use App\Services\UserUsedIdentityService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;

#[Route('/api', name: 'api_')]
class UserController extends AbstractController
{
    private $em;
    private $env;
    private $traitementsDS;
    private $cookieDS;
    private $sendMail;
    private $logger;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, TraitementsDS $traitementsDS, CookieDS $cookieDS, SendMail $sendMail, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->traitementsDS = $traitementsDS;
        $this->cookieDS = $cookieDS;
        $this->sendMail = $sendMail;
        $this->logger = $logger;
    }

    #[Route('/getVersionApp', name: 'getVersionApp', methods: ['POST', 'GET'])]
    public function getVersionApp(UserRepository $userRepository): Response
    {
        try {
            return new JsonResponse([
                'error' => false,
                'versionApp' => $this->env->getVersionApp(),
                'importantUpdate' => $this->env->getImportantUpdate(),
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error getVersionApp : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'message' => 'Service temporairement indisponible.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
        
    }

    #[Route('/connect', name: 'connect', methods: ['POST'])]
    public function connect(Request $request, UserRepository $userRepository, SendMail $sendMail, VerificationsDS $verificationsDS): Response
    {
        try {
        $datas = $request->request;
        
                

        $mail = strtolower(str_replace(" ", "", $datas->get('mail')));
        $password = $datas->get('password');

        if(empty($mail) and empty($password)){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez entrer une votre adresse E-Mail et le mot de passe de votre compte.',
            ]);
        }

        if(!$mail){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez entrer votre mail.',
            ]);
        }

        if(!$password){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez entrer votre mot de passe.',
            ]);
        }

        if (!$verificationsDS->verifMail($mail)) {
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir une adresse E-Mail valide.",]); 
        }

        $rawPassword = $datas->get('password');

        $user = $userRepository->findOneBy(['mail' => $mail]);
        if ($user) {
            $storedHash = $user->getPassword();
            if (str_starts_with((string) $storedHash, '$2y$') || str_starts_with((string) $storedHash, '$argon')) {
                if (!password_verify($rawPassword, $storedHash)) {
                    $user = null;
                }
            } else {
                $sha1Hash = sha1(sha1(sha1($rawPassword)));
                if ($sha1Hash !== $storedHash) {
                    $user = null;
                } else {
                    $user->setPassword(password_hash($rawPassword, PASSWORD_BCRYPT));
                    $this->em->flush();
                }
            }
        }

        if($user) {
            // enregistrement de la langue du user et du last login
            $lastLoginSource = ($datas->get('source') === 'web') ? 'web' : 'mobile';
            $user->setLastLoginTo(new DateTime())->setLastLoginSource($lastLoginSource);
            $this->em->flush();

            $this->traitementsDS->migrateUidIfNeeded($user);
            $verificationUser = $verificationsDS->verifUSer($user->getUid());
            if($verificationUser["error"] == true){
                return new JsonResponse([
                    'error' => true,
                    'titre' => $verificationUser["titre"],
                    'message' => $verificationUser["message"],
                ]);
            }

            $uidCookie = $this->cookieDS->set("uid", $user->getUid());
            $response = new JsonResponse([
                'error' => false,
                'message' => 'Connecter!',
                "user" => $this->traitementsDS->infosUser($user),
            ]);
            $response->headers->setCookie($uidCookie);
            return $response;
        } else {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Identifiants incorrects.',
            ]);
        }
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error connect : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => 'Service temporairement indisponible. Veuillez réessayer.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/updateUserInfo', name: 'updateUserInfo', methods: ['POST'])]
    public function updateUserInfo(Request $request, UserRepository $userRepository, VerificationsDS $verificationsDS, UserBannedRepository $userBannedRepository, UserUsedIdentityService $usedIdentityService): Response
    {
        $datas = $request->request;

        $mail = strtolower(str_replace(" ", "", $datas->get('mail')));
        $nom = (string)$verificationsDS->remove_emoji($datas->get('nom'));
        $pseudo = $datas->get('pseudo');
        $tel = $datas->get('tel');
        $apropos = $datas->get('apropos');
        
                

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;

        if ($tel) {
            $telClean = str_replace(" ", "", (string)$tel);
            foreach (['+229', '+225'] as $indicatif) {
                if (strpos($telClean, $indicatif) === 0) {
                    $afterIndicatif = substr($telClean, strlen($indicatif));
                    if (!preg_match('/^\d{10}$/', $afterIndicatif)) {
                        return new JsonResponse(['error' => true, 'titre' => 'Attention!', 'message' => 'Pour l\'indicatif '.$indicatif.', veuillez saisir exactement 10 chiffres après l\'indicatif.']);
                    }
                    break;
                }
            }
        }

        $telForBanCheck = $tel;
        if ($tel) {
            $telVerification = $verificationsDS->verifFormatNumTel($tel);
            if (($telVerification['error'] ?? true) === false) {
                $telForBanCheck = $telVerification['e164'];
            }
        }

        if($tel && ($userBannedRepository->existsByTel($telForBanCheck) || $userBannedRepository->existsByTel($tel))) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Ce numéro a été banni de Dressur. Contacter l'assistance s'il s'agit d'une erreur.",
            ]);
        }

        if($mail && $userBannedRepository->existsByMail($mail)) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Cette adresse mail a été banni de Dressur. Contactez l'assistance s'il s'agit d'une erreur.",
            ]);
        }

        $verificationUser = $verificationsDS->verifUSer($uid);
        if($verificationUser["error"] == true){
            return new JsonResponse([
                'error' => true,
                'titre' => $verificationUser["titre"],
                'message' => $verificationUser["message"],
                'deleted' => $verificationUser["deleted"],
                'blocked' => $verificationUser["blocked"],
            ]);
        }
        $user = $verificationUser["user"];

        if (!$verificationsDS->verifMail($mail)) {
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir une adresse E-Mail valide.",]); 
        }

        if(!$mail or !$pseudo){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez bien remplir tous les champs!',
            ]);
        }

        $currentMail = $usedIdentityService->normalizeMail($user->getMail());
        if ($usedIdentityService->isUsedByAnother(UserUsedIdentity::TYPE_MAIL, $mail, $currentMail)) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => "Cette adresse E-Mail a déjà été utilisée.",
            ]);
        }

        if(!$user->getAdmin() and !in_array($user->getMail(), ['dressur.ds@gmail.com', 'bluelife.tech@gmail.com', 'dklars.dev@gmail.com'])) {
            $verificationPseudo = $verificationsDS->verifPseudo($pseudo);
            if($verificationPseudo["error"] == true){
                return new JsonResponse([
                    'error' => true,
                    'titre' => $verificationPseudo["titre"],
                    'message' => $verificationPseudo["message"],
                ]);
            }
            $pseudo = $verificationPseudo["pseudo"];
        }

        $userPseudoUid =  $userRepository->findOneBy(['pseudo' => $pseudo]);
        $userPseudoUid =  $userPseudoUid ? $userPseudoUid->getUid() : null;
        if($userPseudoUid){
            if($userPseudoUid != $uid){
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Oups!',
                    'message' => 'Ce pseudo est déja utilisé!',
                ]);
            }
        }

        $userMailUid = $userRepository->findOneBy(['mail' => $mail]);
        $userMailUid =  $userMailUid ? $userMailUid->getUid() : null;
        if($userMailUid){
            if($userMailUid != $uid){
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Oups!',
                    'message' => "Cette adresse E-Mail est déja utilisé.",
                ]);
            }
        }

        if($user->getMail() != $mail){
            $user->setMailIsVerified(false);
        }

        $user->setMail($mail)
            ->setNom($nom)
            ->setPseudo($pseudo)
            ->setApropos($apropos)
        ;

        if($user->getTelIsVerified() == false) {
            $verificationNumTel = $verificationsDS->verifFormatNumTel($tel);
            if($verificationNumTel["error"] == true){
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix."]);
            }
            $tel = $verificationNumTel["e164"];
            $paysTel = $verificationNumTel["country_code"];

            $currentTel = $usedIdentityService->normalizeTel($user->getTel());
            if ($usedIdentityService->isUsedByAnother(UserUsedIdentity::TYPE_TEL, $tel, $currentTel)) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Accès Refuser!',
                    'message' => 'Ce numéro a déjà été utilisé.',
                ]);
            }

            $userTel = $userRepository->findOneBy(['tel' => $tel]);
            if($userTel) {
                if($userTel->getUid() != $user->getUid()) {
                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Accès Refuser!',
                        'message' => 'Ce numéro est déja utilisé.',
                    ]);
                }
            }
            $user->setTel($tel)->setPays($paysTel)->setLid(null);
        }

        $usedIdentityService->rememberUser($user);
        $this->em->flush();

        if ($user->getId()) {
            return new JsonResponse([
                'error' => false,
                'message' => 'Profil mis a jours!',
                'user' => $this->traitementsDS->infosUser($user),
            ]);
        }

        return new JsonResponse([
            'error' => true,
            'titre' => 'Erreur!',
            'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
        ]);
    }

    #[Route('/updateUserPassword', name: 'updateUserPassword', methods: ['POST'])]
    public function updateUserPassword(Request $request, UserRepository $userRepository, SendMail $sendMail): Response
    {
        $datas = $request->request;

        $rawCurrentPassword = $datas->get('currentPassword');
        $rawNewPassword = $datas->get('newPassword');
        $rawConfirmNewPassword = $datas->get('confirmNewPassword');
        
                

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;

        if(strlen($rawNewPassword) < 6) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => 'Pour votre propre sécurité, votre mot de passe doit contenir au minimum 6 caractères dont au moins une lettre majuscule et un chiffre.',
            ]);
        }

        if(!$rawCurrentPassword or !$rawNewPassword or !$rawConfirmNewPassword){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez bien remplir tous les champs!',
            ]);
        }

        $userUid = $userRepository->findOneBy(['uid' => $uid]);
        if(!$userUid){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
            ]);
        }

        $storedHash = $userUid->getPassword();
        if (str_starts_with((string) $storedHash, '$2y$') || str_starts_with((string) $storedHash, '$argon')) {
            $currentPasswordValid = password_verify($rawCurrentPassword, $storedHash);
        } else {
            $currentPasswordValid = sha1(sha1(sha1($rawCurrentPassword))) === $storedHash;
        }
        if (!$currentPasswordValid) {
            return new JsonResponse([
                'error' => true,
                'titre' => "Attention!",
                'message' => 'Mots de passe actuel incorrecte!',
            ]);
        }
        $user = $userUid;

        if($rawNewPassword != $rawConfirmNewPassword){
            return new JsonResponse([
                'error' => true,
                'titre' => "Attention!",
                'message' => 'Erreur de confirmation du mot de passe!',
            ]);
        }

        $user->setPassword(password_hash($rawNewPassword, PASSWORD_BCRYPT));
        $this->em->flush();

        if ($user->getId()) {
            $sendMail->smtpMail(
                $user->getMail(), 
                "Mot de Passe Modifié sur Dressur", 
                $this->renderView('emails/pass_edit_mail.html.twig', []
            ));

            return new JsonResponse([
                'error' => false,
                'message' => 'Mot de passe mis a jours!',
                "user" => $this->traitementsDS->infosUser($user),
            ]);
        }

        return new JsonResponse([
            'error' => true,
            'titre' => 'Erreur!',
            'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
        ]);
    }

    #[Route('/getUserInfo', name: 'getUserInfo', methods: ['POST'])]
    public function getUserInfo(Request $request, UserRepository $userRepository, VerificationsDS $verificationsDS): Response
    {
        try {
        $datas = $request->request;
        
                

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        $uid = is_string($uid)
            ? str_replace(["\n", "\r", " "], "", $uid)
            : null;

        if (!$uid) {
            return new JsonResponse([
                'error' => true,
                'code' => 'session_missing',
                'deleted' => true,
                'blocked' => false,
                'titre' => 'Session expirée',
                'message' => 'Votre session n’est plus disponible. Veuillez vous reconnecter.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $verificationUser = $verificationsDS->verifUSer($uid);
        if($verificationUser["error"] == true){
            $code = ($verificationUser["blocked"] ?? false)
                ? 'account_blocked'
                : (($verificationUser["deleted"] ?? false)
                    ? 'session_invalid'
                    : 'session_error');
            return new JsonResponse([
                'error' => true,
                'code' => $code,
                'titre' => $verificationUser["titre"],
                'message' => $verificationUser["message"],
                'deleted' => $verificationUser["deleted"],
                'blocked' => $verificationUser["blocked"],
            ]);
        }
        $user = $verificationUser["user"];
        $uidCookie = null;
        if ($user) {
            $this->traitementsDS->migrateUidIfNeeded($user);
            $uidCookie = $this->cookieDS->set("uid", $user->getUid());
        }
        
        if($user) {
            // enregistrement de la langue du user et du last login
            $user->setLastLoginTo(new DateTime());
            $this->em->flush();

            if ($user->getId()) {
                $response = new JsonResponse([
                    'error' => false,
                    'message' => 'Ok!',
                    "user" => $this->traitementsDS->infosUser($user),
                ]);
                if ($uidCookie) { $response->headers->setCookie($uidCookie); }
                return $response;
            }
        }

        return new JsonResponse([
            'error' => true,
            'titre' => 'Erreur!',
            'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
        ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error getUserInfo : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => 'Service temporairement indisponible. Veuillez réessayer.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/sendMailVerification', name: 'sendMailVerification', methods: ['POST'])]
    public function sendMailVerification(Request $request, UserRepository $userRepository, SendMail $sendMail, VerificationsDS $verificationsDS): Response
    {
        $datas = $request->request;
        
                

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;

        $verificationUser = $verificationsDS->verifUSer($uid);
        if($verificationUser["error"] == true){
            return new JsonResponse([
                'error' => true,
                'titre' => $verificationUser["titre"],
                'message' => $verificationUser["message"],
                'deleted' => $verificationUser["deleted"],
                'blocked' => $verificationUser["blocked"],
            ]);
        }
        $user = $verificationUser["user"];

        $verifMail = new VerifMail();
        $verifMail->setUser($user);
        $this->em->persist($verifMail);
        $this->em->flush();

        $html = $this->renderView("emails/verif_mail.html.twig",[
            'code' => $verifMail->getCode(),
            'username' => $user,
        ]);

        $sent = $sendMail->smtpMail($user->getMail(), "Confirmation du Mail", $html);
        if (!$sent) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Erreur d'envoi du mail.",
            ]);
        }
        return new JsonResponse([
            'error' => false,
        ]);
    }

    #[Route('/mailVerification', name: 'mailVerification', methods: ['POST'])]
    public function mailVerification(Request $request, UserRepository $userRepository, VerifMailRepository $verifMailRepository, VerificationsDS $verificationsDS, TraitementsDS $traitementsDS, UserRestrictionService $restrictionService): Response
    {
        $datas = $request->request;
        
                

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        $codeForVerifMail = $datas->get('codeForVerifMail');

        $verificationUser = $verificationsDS->verifUSer($uid);
        if($verificationUser["error"] == true){
            return new JsonResponse([
                'error' => true,
                'titre' => $verificationUser["titre"],
                'message' => $verificationUser["message"],
                'deleted' => $verificationUser["deleted"],
                'blocked' => $verificationUser["blocked"],
            ]);
        }
        $user = $verificationUser["user"];

        if($user->getMailIsVerified() == true){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Action Répéter!',
                'message' => "Votre mail est déja confirmer.",
            ]);
        }

        $verifMail = $verifMailRepository->findOneBy(['user' => $user, 'code' => $codeForVerifMail]);

        if(!$verifMail){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Vérifier bien le code saisie.",
            ]);
        }

        if((new DateTime()) > $verifMail->getDateExp()){

            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => "Ce code est expiré. Veuillez reprendre le processus de vérification.",
            ]);
        }

        $traitementsDS->addNotification("Votre mail a été confirmer.", $user);
        $user->setMailIsVerified(true);
        $restrictionService->restoreForUser($user);

        foreach ($verifMailRepository->findBy(['user' => $user]) as $element) {
            $this->em->remove($element);
        }
        $this->em->flush();

        return new JsonResponse([
            'error' => false,
            'user' => $this->traitementsDS->infosUser($user),
        ]);
    }

    #[Route('/sendMailPassForgotWithConnecte', name: 'sendMailPassForgotWithConnecte', methods: ['POST'])]
    public function sendMailPassForgotWithConnecte(Request $request, UserRepository $userRepository, SendMail $sendMail, TraitementsDS $traitementsDS, VerificationsDS $verificationsDS): Response
    {
        $datas = $request->request;
        
                

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;

        $verificationUser = $verificationsDS->verifUSer($uid);
        if($verificationUser["error"] == true){
            return new JsonResponse([
                'error' => true,
                'titre' => $verificationUser["titre"],
                'message' => $verificationUser["message"],
                'deleted' => $verificationUser["deleted"],
                'blocked' => $verificationUser["blocked"],
            ]);
        }
        $user = $verificationUser["user"];

        $this->traitementsDS->migrateUidIfNeeded($user);
        $uidCookie = $this->cookieDS->set("uid", $user->getUid());
        $newPassword = $traitementsDS->resetPassword();
        $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
        $this->em->flush();

        $html = $this->renderView("emails/passe_4got_mail.html.twig",[
            'code' => $newPassword,
            'username' => $user,
        ]);

        $sent = $sendMail->smtpMail($user->getMail(), "Réinitialisation du mot de passe", $html);
        if (!$sent) {
            $errResponse = new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Erreur d'envoi du mail. Veuillez réessayer.",
            ]);
            $errResponse->headers->setCookie($uidCookie);
            return $errResponse;
        }
        $okResponse = new JsonResponse(['error' => false]);
        $okResponse->headers->setCookie($uidCookie);
        return $okResponse;
    }

    #[Route('/sendMailPassForgot', name: 'sendMailPassForgot', methods: ['POST'])]
    public function sendMailPassForgot(Request $request, UserRepository $userRepository, SendMail $sendMail, TraitementsDS $traitementsDS): Response
    {
        try {
        $datas = $request->request;

                

        $mail = strtolower(str_replace(" ", "", $datas->get('mail')));

        if(!$mail){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez entrer votre mail.',
            ]);
        }

        $user = $userRepository->findOneBy(['mail' => $mail]);
        if(!$user){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Aucun compte ne correspond à : ".$mail,
            ]);
        }

        $this->traitementsDS->migrateUidIfNeeded($user);
        $newPassword = $traitementsDS->resetPassword();

        $html = $this->renderView("emails/passe_4got_mail.html.twig",[
            'code' => $newPassword,
            'username' => $user,
        ]);

        $sent = $sendMail->smtpMail($user->getMail(), "Réinitialisation du mot de passe", $html);
        if (!$sent) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Erreur d'envoi du mail. Veuillez réessayer.",
            ]);
        }

        $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
        $this->em->flush();

        return new JsonResponse([
            'error' => false,
        ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error sendMailPassForgot : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => 'Service temporairement indisponible. Veuillez réessayer.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }   

    #[Route('/inscriptionDS', name: 'inscriptionDS', methods: ['POST'])]
    public function inscriptionDS(Request $request, UserRepository $userRepository, TraitementsDS $traitementsDS, VerificationsDS $verificationsDS, SendMail $sendMail, UserBannedRepository $userBannedRepository, UserUsedIdentityService $usedIdentityService): Response
    {
        try {
        $datas = $request->request;

        $acceptPolicies = $datas->get('acceptPolicies');
        if ($acceptPolicies === null || $acceptPolicies === false || $acceptPolicies === 0 || $acceptPolicies === '0') {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => "Vous devez accepter les Conditions d'utilisation, la Politique de confidentialité et les Conditions Générales de Vente.",
            ]);
        }

                
        
        $tel = str_replace(" ", "", $datas->get('tel'));
        $mail = strtolower(str_replace(" ", "", $datas->get('mail')));
        $password = $datas->get('password');
        $confirmPassword = $datas->get('confirmPassword');

        $dressur = $userRepository->find(2);

        foreach (['+229', '+225'] as $indicatif) {
            if (strpos($tel, $indicatif) === 0) {
                $afterIndicatif = substr($tel, strlen($indicatif));
                if (!preg_match('/^\d{10}$/', $afterIndicatif)) {
                    return new JsonResponse(['error' => true, 'titre' => 'Attention!', 'message' => 'Pour l\'indicatif '.$indicatif.', veuillez saisir exactement 10 chiffres après l\'indicatif.']);
                }
                break;
            }
        }

        // Récupère le numéro depuis $datas et s'assure que c'est une chaîne
        $telRaw = (string) $tel;
        // Ne conserve que les chiffres
        $pseudo = preg_replace('/\D+/', '', $telRaw);

        if(!$pseudo or !$tel or !$mail or !$password or !$confirmPassword){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez bien remplir tous les champs!',
            ]);
        }
        
        $verificationNumTel = $verificationsDS->verifFormatNumTel($tel);
        if($verificationNumTel["error"] == true){
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix."]);
        }
        $tel = $verificationNumTel["e164"];
        $paysTel = $verificationNumTel["country_code"];

        if($userBannedRepository->existsByTel($tel)) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Ce numéro a été banni de Dressur. Contacter l'assistance s'il s'agit d'une erreur.",
            ]);
        }

        if($userBannedRepository->existsByMail($mail)) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Cette adresse mail a été banni de Dressur. Contactez l'assistance s'il s'agit d'une erreur.",
            ]);
        }

        if (!$verificationsDS->verifMail($mail)) {
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir une adresse E-Mail valide.",]); 
        }

        if($usedIdentityService->isUsedByAnother(UserUsedIdentity::TYPE_TEL, $tel)) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Accès Refuser!',
                'message' => 'Ce numéro a déjà été utilisé.',
            ]);
        }

        if($usedIdentityService->isUsedByAnother(UserUsedIdentity::TYPE_MAIL, $mail)) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => "Cette adresse E-Mail a déjà été utilisée.",
            ]);
        }

        $userTel = $userRepository->findOneBy(['tel' => $tel]);
        if($userTel){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Accès Refuser!',
                'message' => 'Ce numéro est déja utilisé.',
            ]);
        }

        $userMail = $userRepository->findOneBy(['mail' => $mail]);
        if($userMail){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => "Cette adresse E-Mail est déja utilisé.",
            ]);
        }

        $userPseudo = $userRepository->findOneBy(['pseudo' => $pseudo]);
        if($userPseudo){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => 'Ce pseudo est déja utilisé!',
            ]);
        }

        if(strlen($password) < 6 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => 'Pour votre propre sécurité, votre mot de passe doit contenir au minimum 6 caractères dont au moins une lettre majuscule et un chiffre.',
            ]);
        }
        
        $rawPassword = $datas->get('password');
        $rawConfirmPassword = $datas->get('confirmPassword');

        if($rawPassword != $rawConfirmPassword){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => 'Erreur de confirmation du mot de passe!',
            ]);
        }

        $registerSource = ($datas->get('source') === 'web') ? 'web' : 'mobile';

        $user = new User();
        $user->setPseudo($pseudo)
            ->setTel($tel)
            ->setMail($mail)
            ->setPassword(password_hash($rawPassword, PASSWORD_BCRYPT))
            ->setPays($paysTel)
            ->setLastLoginTo(new DateTime())
            ->setRegisterSource($registerSource)
            ->setLastLoginSource($registerSource)
        ;
        // Génération du code partenaire unique (8 caractères, alphabet sans ambiguïtés)
        do {
            $codePartenaire = User::generateCodePartenaire();
            $existingCode = $userRepository->findOneBy(['codePartenaire' => $codePartenaire]);
        } while ($existingCode !== null);
        $user->setCodePartenaire($codePartenaire);
        $this->em->persist($user);
        $usedIdentityService->rememberUser($user);

        $preference = new Preference();
        $preference->setUser($user)
            ->setPaysChoisies([(string)$user->getPays()])
        ;
        $this->em->persist($preference);

        $contact = new Contact();
        $contact->setUser($user);
        $contact->setNewIAdd($dressur);
        $this->em->persist($contact);

        $verifMail = new VerifMail();
        $verifMail->setUser($user);
        $this->em->persist($verifMail);

        $this->em->flush();

        $sendMail->smtpMail(
            $user->getMail(), 
            "Confirmation du Mail", 
            $this->renderView("emails/verif_mail.html.twig",[
                'code' => $verifMail->getCode(),
                'username' => $user,
            ])
        );
        $dressur->getContact()->setNewAddMe($user);
        $this->em->flush();

        $userAfterRegister = $userRepository->findOneBy(["mail" => $mail]);

        if ($userAfterRegister) {
            $sendMail->smtpMail(
                $userAfterRegister->getMail(), 
                "Bienvenu sur Dressur", 
                $this->renderView('emails/bienvenu_mail.html.twig', [
                    'pseudoUser' => $userAfterRegister->getPseudo(),
                ]
            ));

            $uidCookie = $this->cookieDS->set("uid", $userAfterRegister->getUid());
            $response = new JsonResponse([
                'error' => false,
                'user' => $this->traitementsDS->infosUser($userAfterRegister),
            ]);
            $response->headers->setCookie($uidCookie);
            return $response;
        }

        return new JsonResponse([
            'error' => true,
            'titre' => 'Erreur!',
            'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
        ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error inscriptionDS : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => 'Service temporairement indisponible. Veuillez réessayer.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/deleteCompteDS', name: 'deleteCompteDS', methods: ['POST'])]
    public function deleteCompteDS(Request $request, TraitementsDS $traitementsDS, VerificationsDS $verificationsDS, UserRepository $userRepository): Response
    {
        set_time_limit(10000);

        try {
            $datas = $request->request;
            $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
            $motifDeleted = $datas->get('motifDeleted');
            $user = $userRepository->findOneBy(['uid' => $uid]);

            if (!$user) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => "Utilisateur introuvable... Contactez l'assistance Dressur sur WhatsApp.",
                ]);
            }

            if (!$motifDeleted) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Le motif de suppression du compte est indispensable.',
                ]);
            }

            if (strlen($motifDeleted) < 100) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Le motif doit contenir au minimum 100 caractères.',
                ]);
            }

            // Le motif et la suppression sont traités dans une transaction unique.
            $traitementsDS->execPurge($user, true, $motifDeleted);
        } catch (\Throwable $th) {
            $this->logger->error('Échec de la suppression de compte via API.', [
                'exception' => $th,
            ]);

            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => 'La suppression du compte a échoué. Veuillez réessayer.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'error' => false,
        ]);
    }

    #[Route('/addSuggestion', name: 'addSuggestion')]
    public function addSuggestion(Request $request, VerificationsDS $verificationsDS): Response
    {
        $datas = $request->request;
        
                

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        $suggestion = $datas->get('suggestion');

        if(!$suggestion){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez bien entrer votre suggestion...',
            ]);
        }

        if(strlen($suggestion) < 10){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'La suggestion doit contenir au minimum 10 caractères',
            ]);
        }

        $verificationUser = $verificationsDS->verifUSer($uid);
        if($verificationUser["error"] == true){
            return new JsonResponse([
                'error' => true,
                'titre' => $verificationUser["titre"],
                'message' => $verificationUser["message"],
                'deleted' => $verificationUser["deleted"],
                'blocked' => $verificationUser["blocked"],
            ]);
        }
        $user = $verificationUser["user"];

        $signalement = new Suggestion();
        $signalement->setUser($user)->setSuggestion($suggestion);
        $this->em->persist($signalement);
        $this->em->flush();
        return new JsonResponse([
            'error' => false,
        ]);
    }

    #[Route('/getConditionsProgrammeRecompense', name: 'getConditionsProgrammeRecompense')]
    public function getConditionsProgrammeRecompense(Request $request, UserRepository $userRepository, TransactionRepository $transactionRepository): Response
    {
        try {
            $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
            $user = $userRepository->findOneBy(['uid' => $uid]);

            $inscritDepuis7Jours = $user->getCreatedAt() <= (new \DateTime('-7 days'));
            $mailConfirme        = $user->getMailIsVerified() === true;
            $whatsappConfirme    = $user->getTelIsVerified() === true;
            $nbrCommandes        = $transactionRepository->countPaidServicesTransactions($user);
            $cinqCommandes       = $nbrCommandes >= 5;

            return new JsonResponse([
                'error' => false,
                'conditions' => [
                    'inscritDepuis7Jours' => $inscritDepuis7Jours,
                    'mailConfirme'        => $mailConfirme,
                    'whatsappConfirme'    => $whatsappConfirme,
                    'cinqCommandes'       => $cinqCommandes,
                    'nbrCommandes'        => $nbrCommandes,
                ],
                'toutesConditionsRemplies' => $inscritDepuis7Jours && $mailConfirme && $whatsappConfirme && $cinqCommandes,
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error getConditionsProgrammeRecompense : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => "Oups !!!",
                'message' => "Nous avons rencontré une erreur. Veuillez réessayer ou contacter l'assistance Dressur sur WhatsApp.",
            ]);
        }
    }

    #[Route('/addToRecompenseProgramme', name: 'addToRecompenseProgramme')]
    public function addToRecompenseProgramme(Request $request, UserRepository $userRepository, TransactionRepository $transactionRepository): Response
    {
        try {
            $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
            $user = $userRepository->findOneBy(['uid' => $uid]);

            // --- Vérification des 4 conditions ---
            $inscritDepuis7Jours = $user->getCreatedAt() <= (new \DateTime('-7 days'));
            $mailConfirme        = $user->getMailIsVerified() === true;
            $whatsappConfirme    = $user->getTelIsVerified() === true;
            $nbrCommandes        = $transactionRepository->countPaidServicesTransactions($user);
            $cinqCommandes       = $nbrCommandes >= 5;

            if (!$inscritDepuis7Jours) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => "Condition non remplie",
                    'message' => "Votre compte doit être inscrit depuis au moins 7 jours pour rejoindre le programme.",
                ]);
            }
            if (!$mailConfirme) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => "Condition non remplie",
                    'message' => "Veuillez confirmer votre adresse e-mail avant de rejoindre le programme.",
                ]);
            }
            if (!$whatsappConfirme) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => "Condition non remplie",
                    'message' => "Veuillez confirmer votre numéro WhatsApp avant de rejoindre le programme.",
                ]);
            }
            if (!$cinqCommandes) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => "Condition non remplie",
                    'message' => "Vous devez avoir effectué au moins 5 commandes payantes (Boost Contact, Promotion Affaire ou Promotion Réseaux Sociaux) pour rejoindre le programme. Commandes actuelles : $nbrCommandes/5.",
                ]);
            }

            $user->setIsInscritProgrammeRecompense(true);
            $this->em->flush();

            return new JsonResponse([
                'error' => false,
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error addToRecompenseProgramme : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => "Oups !!!",
                'message' => "Nous avons rencontré une erreur. Veuillez réessayer ou contacter l'assistance Dressur sur WhatsApp.",
            ]);
        }
    }

    #[Route('/getPromotionAffaireInProgrammeRecompense', name: 'getPromotionAffaireInProgrammeRecompense')]
    public function getPromotionAffaireInProgrammeRecompense(Request $request, UserRepository $userRepository, TraitementsDS $traitementsDS): Response
    {
        try {
            $datas = $request->request;
        
                        

            $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
            
            $user = $userRepository->findOneBy(['uid' => $uid]);
            
            return new JsonResponse([
                'error' => false,
                'promotions' => $traitementsDS->listePromotionAffaireInProgrammeRecompense($user),
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error getPromotionAffaireInProgrammeRecompense : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => "Oups !!!",
                'message' => "Nous avons rencontré une erreur. Veuillez réessayer ou contacter l’assistance Dressur sur WhatsApp.",
            ]);
        }
    }

    #[Route('/partageInProgrammeRecompense', name: 'partageInProgrammeRecompense')]
    public function partageInProgrammeRecompense(Request $request, UserRepository $userRepository, PromotionRepository $promotionRepository, EntityManagerInterface $em, HistoriqueProgrammeRecompenseRepository $historiqueProgrammeRecompenseRepository): Response
    {
        try {
            $datas = $request->request;
        
                        

            $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
            $idPromoAffaire = $datas->get('idPromoAffaire');
            
            $user = $userRepository->findOneBy(['uid' => $uid]);
            $promoAffaire = $promotionRepository->findOneBy(['id' => $idPromoAffaire]);

            $lastAppreouvedForThisPromotion = $historiqueProgrammeRecompenseRepository->findOneBy(
                ['promotion' => $promoAffaire, 'status' => 'approuver', 'user' => $user], 
                ['id' => 'DESC']
            );
            if($lastAppreouvedForThisPromotion) {
                if((new DateTime()) < $lastAppreouvedForThisPromotion->getExpiredAt()) {
                    return new JsonResponse([
                        'error' => true,
                        'titre' => "Oups !!!",
                        'message' => "Vous ne pouvez pas partager cette promotion pour le moment, car votre précédente participation a déjà été approuvée.",
                    ]);
                }
            }

            $oldProgRecomp = $historiqueProgrammeRecompenseRepository->findOneBy([
                'user' => $user, 
                'promotion' => $promoAffaire,
                'status' => "en_cours",
            ]);

            if(!$oldProgRecomp) {
                $historyProgRecomp = new HistoriqueProgrammeRecompense();
                $historyProgRecomp->setUser($user)->setPromotion($promoAffaire);
                $em->persist($historyProgRecomp);
                $em->flush();

                return new JsonResponse([
                    'error' => false,
                    'referenceParticipation' => $historyProgRecomp->getReferenceParticipation(),
                ]);
            }

            $oldProgRecomp->estPartager()->setUpdatedAt(new DateTime());
            $em->flush();
                        
            return new JsonResponse([
                'error' => false,
                'referenceParticipation' => $oldProgRecomp->getReferenceParticipation(),
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error partageInProgrammeRecompense : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => "Oups !!!",
                'message' => "Nous avons rencontré une erreur. Veuillez réessayer ou contacter l’assistance Dressur sur WhatsApp.",
            ]);
        }
    }

    #[Route('/getMyProgrammeRecompenseInformations', name: 'getMyProgrammeRecompenseInformations')]
    public function getMyProgrammeRecompenseInformations(Request $request, UserRepository $userRepository, PromotionRepository $promotionRepository, EntityManagerInterface $em, HistoriqueProgrammeRecompenseRepository $historiqueProgrammeRecompenseRepository): Response
    {
        $vuesTotales = 0;
        $gainsTotales = 0;
        $soldeDisponible = 0;
        $sixLastHistorique = [];
        $allHistorique = [];

        try {
            $datas = $request->request;

            $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
            $user = $userRepository->findOneBy(['uid' => $uid]);
            $soldeDisponible = $user->getSoldeDressur();

            foreach ($historiqueProgrammeRecompenseRepository->findBy(['user' => $user], ['id' => 'DESC']) as $oneHistorique) {

                $promotion = $oneHistorique->getPromotion();
                $vuesTotales += $oneHistorique->getNbrVue();
                $gainsTotales += $oneHistorique->getRecompense();

                if(in_array($oneHistorique->getstatus(), ['en_cours', 'terminer'])) {
                    if ($oneHistorique->getCreatedAt() <= (new \DateTime('-23 hours', new \DateTimeZone('Africa/Lagos')))) {
                        $oneHistorique->setStatus('echouer');
                    }
                    if(!$promotion->isInProgrammeRecompense()) {
                        $oneHistorique->setStatus('echouer');
                    }
                    $em->flush();
                }

                $anotherHistorique = [
                    'id' => $oneHistorique->getId(),
                    'title' => mb_strlen($promotion->getDescription(), 'UTF-8') > 20
                        ? mb_substr($promotion->getDescription(), 0, 20, 'UTF-8') . '...'
                        : $promotion->getDescription(),
                    'amount' => $oneHistorique->getRecompense(),
                    'date' => $oneHistorique->getCreatedAt()->format('d/m/y'),
                    'views' => $oneHistorique->getNbrVue(),
                    'imageUrl' => $promotion->getImage(),
                    'status' => $oneHistorique->getstatus(),
                    'description' => $promotion->getDescription(),
                ];
                array_push($allHistorique, $anotherHistorique);
            }

            $sixLastHistorique = array_slice($allHistorique, 0, 6);

            return new JsonResponse([
                'error' => false,
                'vuesTotales' => $vuesTotales,
                'gainsTotales' => $gainsTotales,
                'soldeDisponible' => $soldeDisponible,
                'sixLastHistorique' => $sixLastHistorique,
                'allHistorique' => $allHistorique,
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error getMyProgrammeRecompenseInformations : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => "Oups !!!",
                'message' => "Nous avons rencontré une erreur. Veuillez réessayer ou contacter l’assistance Dressur sur WhatsApp.",
            ]);
        }
    }

    #[Route('/submitProgrammeRecompenseProofs', name: 'submitProgrammeRecompenseProofs')]
    public function submitProgrammeRecompenseProofs(Request $request, UserRepository $userRepository, EntityManagerInterface $em, HistoriqueProgrammeRecompenseRepository $historiqueProgrammeRecompenseRepository): Response
    {
        $filesystem = new Filesystem();
        $uploadDir = $this->getParameter('preuve_recompense');
        if (!$filesystem->exists($uploadDir)) {
            $filesystem->mkdir($uploadDir, 0775);
        }

        try {
            $datas = $request->request;
            $files = $request->files;

            $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
            $user = $userRepository->findOneBy(['uid' => $uid]);

            $idHistorique = $datas->get('idHistorique');
            $historiqueProgrammeRecompense = $historiqueProgrammeRecompenseRepository->find((int)$idHistorique);

            $capture1 = $files->get('capture1');
            if (!$capture1->isValid()) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Erreur lors du traitement de la capture – Liste des statuts.",
                ]);
            }
            $fileName1 = "preuve_$uid"."_".UuidGenerator::v4().'.'.$capture1->getClientOriginalExtension();


            $capture2 = $files->get('capture2');
            if (!$capture2->isValid()) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Erreur lors du traitement de la capture – Statut ouvert.",
                ]);
            }
            $fileName2 = "preuve_$uid"."_".UuidGenerator::v4().'.'.$capture2->getClientOriginalExtension();
            
            $historiqueProgrammeRecompense->setStatus("en_attente");
            
            $capture1->move($this->getParameter('preuve_recompense'), $fileName1);
            $capture2->move($this->getParameter('preuve_recompense'), $fileName2);

            $newPreuve = new Preuve();
            $newPreuve->setUser($user)
                ->setHistoriqueProgrammeRecompense($historiqueProgrammeRecompense)
                ->setCaptureListeStatut($fileName1)
                ->setCaptureStatutOuvert($fileName2)
            ;
            $em->persist($newPreuve);

            $em->flush();
                        
            return new JsonResponse([
                'error' => false,
                'titre' => "OK ...",
                'message' => "Votre preuve a été enregistrée, elle est en attente de vérification. Veuillez joindre la capture vidéo sur WhatsApp au numéro de l’assistance Dressur. Merci.",
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error submitProgrammeRecompenseProofs : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => "Oups !!!",
                'message' => "Nous avons rencontré une erreur. Veuillez réessayer ou contacter l’assistance Dressur sur WhatsApp.",
            ]);
        }
    }

    #[Route('/updateUserLang', name: 'updateUserLang', methods: ['POST'])]
    public function updateUserLang(Request $request, VerificationsDS $verificationsDS): Response
    {
        try {
            $datas = $request->request;

            $uid = $datas->get('uid');
            $lang = $datas->get('lang') === 'fr' ? 'fr' : 'en';

            $verificationUser = $verificationsDS->verifUSer($uid);
            if ($verificationUser["error"] == true) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => $verificationUser["titre"],
                    'message' => $verificationUser["message"],
                ]);
            }

            $user = $verificationUser["user"];
            $user->setLang($lang);
            $this->em->flush();

            return new JsonResponse(['error' => false]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error updateUserLang : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => 'Service temporairement indisponible. Veuillez reessayer.',
            ]);
        }
    }
}
