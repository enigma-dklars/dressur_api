<?php

namespace App\Controller\API;

use DateTime;
use Exception;
use App\Entity\User;
use App\Entity\VerifMail;
use App\Entity\Preference;
use App\Utilities\SendMail;
use App\Repository\EnvRepository;
use App\Repository\UserRepository;
use App\Repository\VerifMailRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Contact;
use App\Entity\DeletedDS;
use App\Entity\HistoriqueProgrammeRecompense;
use App\Entity\Preuve;
use App\Entity\Suggestion;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\HistoriqueProgrammeRecompenseRepository;
use App\Repository\PromotionRepository;
use App\Services\CookieDS;
use App\Services\SessionDS;
use App\Utilities\UuidGenerator;
use App\Services\TraitementsDS;
use App\Services\VerificationsDS;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;

#[Route('/api', name: 'api_')]
class UserController extends AbstractController
{
    private $em;
    private $env;
    private $traitementsDS;
    private $cookieDS;
    private $sendMail;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, TraitementsDS $traitementsDS, CookieDS $cookieDS, SendMail $sendMail)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->traitementsDS = $traitementsDS;
        $this->cookieDS = $cookieDS;
        $this->sendMail = $sendMail;
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
    public function connect(Request $request, UserRepository $userRepository, SendMail $sendMail, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        try {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $mail = strtolower(str_replace(" ", "", $datas->get('mail')));
        $password = $datas->get('password');

        if(empty($mail) and empty($password)){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Please enter your E-Mail address and your account password.',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez entrer une votre adresse E-Mail et le mot de passe de votre compte.',
            ]);
        }

        if(!$mail){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Please enter your email.',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez entrer votre mail.',
            ]);
        }

        if(!$password){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Please enter your password.',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez entrer votre mot de passe.',
            ]);
        }

        if (!$verificationsDS->verifMail($mail)) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Please enter a valid email address.",]); 
            }
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
            $user->setLastLoginTo(new DateTime())
                ->setLastLoginSource($lastLoginSource);
            if($user->getLang() != $langUserPhone) { 
                $user->setLang($langUserPhone);
            }
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

            $this->cookieDS->set("uid", $user->getUid());
            return new JsonResponse([
                'error' => false,
                'message' => 'Connecter!',
                "user" => $this->traitementsDS->infosUser($user),
            ]);
        } else {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Incorrect credentials.',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Identifiants incorrects.',
            ]);
        }

        if($sessionDS->get("langUserPhone") != "fr") {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Mistake!',
                'message' => "We have encountered a problem, contact Assistance by WhatsApp.",
            ]);
        }
        return new JsonResponse([
            'error' => true,
            'titre' => 'Erreur!',
            'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
        ]);
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
    public function updateUserInfo(Request $request, UserRepository $userRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;

        $mail = strtolower(str_replace(" ", "", $datas->get('mail')));
        $nom = (string)$verificationsDS->remove_emoji($datas->get('nom'));
        $pseudo = $datas->get('pseudo');
        $tel = $datas->get('tel');
        $apropos = $datas->get('apropos');
        $tiktok = $datas->get('tiktok');
        $instagram = $datas->get('instagram');
        $facebook = $datas->get('facebook');
        $youtube = $datas->get('youtube');
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;

        if ($tel) {
            $telClean = str_replace(" ", "", (string)$tel);
            foreach (['+229', '+225'] as $indicatif) {
                if (strpos($telClean, $indicatif) === 0) {
                    $afterIndicatif = substr($telClean, strlen($indicatif));
                    if (!preg_match('/^\d{10}$/', $afterIndicatif)) {
                        if($sessionDS->get("langUserPhone") != "fr") {
                            return new JsonResponse(['error' => true, 'titre' => 'Attention!', 'message' => 'For the '.$indicatif.' prefix, please enter exactly 10 digits after the prefix.']);
                        }
                        return new JsonResponse(['error' => true, 'titre' => 'Attention!', 'message' => 'Pour l\'indicatif '.$indicatif.', veuillez saisir exactement 10 chiffres après l\'indicatif.']);
                    }
                    break;
                }
            }
        }

        if($tel && $this->env->getUserBanned() && in_array($tel, $this->env->getUserBanned())) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "This number has been banned from Dressur. Contact support if this is an error.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Ce numéro a été banni de Dressur. Contacter l'assistance s'il s'agit d'une erreur.",
            ]);
        }

        if($mail && $this->env->getUserBanned() && in_array($mail, $this->env->getUserBanned())) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "This email address has been banned from Dressur. Contact support if this is a mistake.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Cette adresse mail a été banni de Dressur. Contactez l'assistance s'il s'agit d'une erreur.",
            ]);
        }

        if($instagram) {
            if (!$verificationsDS->isValidSocialUrl($instagram, 'instagram')) {
                if($sessionDS->get("langUserPhone") != "fr") {
                    return new JsonResponse([
                        'error' => true,
                        'message' => strtoupper('instagram').' Invalid URL.',
                    ]);
                }
                return new JsonResponse([
                    'error' => true,
                    'message' => strtoupper('instagram').' URL invalide.',
                ]);
            }
        }

        if($facebook) {
            if (!$verificationsDS->isValidSocialUrl($facebook, 'facebook')) {
                if($sessionDS->get("langUserPhone") != "fr") {
                    return new JsonResponse([
                        'error' => true,
                        'message' => strtoupper('facebook').' Invalid URL.',
                    ]);
                }
                return new JsonResponse([
                    'error' => true,
                    'message' => strtoupper('facebook').' URL invalide.',
                ]);
            }
        }

        if($youtube) {
            if (!$verificationsDS->isValidSocialUrl($youtube, 'youtube')) {
                if($sessionDS->get("langUserPhone") != "fr") {
                    return new JsonResponse([
                        'error' => true,
                        'message' => strtoupper('youtube').' Invalid URL.',
                    ]);
                }
                return new JsonResponse([
                    'error' => true,
                    'message' => strtoupper('youtube').' URL invalide.',
                ]);
            }
        }

        if($tiktok) {
            if (!$verificationsDS->isValidSocialUrl($tiktok, 'tiktok')) {
                if($sessionDS->get("langUserPhone") != "fr") {
                    return new JsonResponse([
                        'error' => true,
                        'message' => strtoupper('tiktok').' Invalid URL.',
                    ]);
                }
                return new JsonResponse([
                    'error' => true,
                    'message' => strtoupper('tiktok').' URL invalide.',
                ]);
            }
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
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Please enter a valid email address.",]); 
            }
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir une adresse E-Mail valide.",]); 
        }

        if(!$mail or !$pseudo){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Please complete all fields!',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez bien remplir tous les champs!',
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
                if($sessionDS->get("langUserPhone") != "fr") {
                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Whoops!',
                        'message' => 'This nickname is already used!',
                    ]);
                }
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
                if($sessionDS->get("langUserPhone") != "fr") {
                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Whoops!',
                        'message' => "This E-Mail address is already in use.",
                    ]);
                }
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
            ->setTiktok($tiktok)
            ->setInstagram($instagram)
            ->setFacebook($facebook)
            ->setYoutube($youtube)
        ;

        if($user->getTelIsVerified() == false) {
            $verificationNumTel = $verificationsDS->verifFormatNumTel($tel);
            if($verificationNumTel["error"] == true){
                if($sessionDS->get("langUserPhone") != "fr") {
                    return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Please enter a valid phone number preceded by its prefix."]);
                }
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix."]);
            }
            $tel = $verificationNumTel["e164"];
            $paysTel = $verificationNumTel["country_code"];

            $userTel = $userRepository->findOneBy(['tel' => $tel]);
            if($userTel) {
                if($userTel->getUid() != $user->getUid()) {
                    if($sessionDS->get("langUserPhone") != "fr") {
                        return new JsonResponse([
                            'error' => true,
                            'titre' => 'Access Deny!',
                            'message' => 'This number is already used.',
                        ]);
                    }
                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Accès Refuser!',
                        'message' => 'Ce numéro est déja utilisé.',
                    ]);
                }
            }
            $user->setTel($tel)->setPays($paysTel);
        }

        $this->env->addUsersTel($tel);
        $this->em->flush();

        if ($user->getId()) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => false,
                    'message' => 'Profile updated!',
                    'user' => $this->traitementsDS->infosUser($user),
                ]);
            }
            return new JsonResponse([
                'error' => false,
                'message' => 'Profil mis a jours!',
                'user' => $this->traitementsDS->infosUser($user),
            ]);
        }

        if($sessionDS->get("langUserPhone") != "fr") {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Mistake!',
                'message' => "We have encountered a problem, contact Assistance by WhatsApp.",
            ]);
        }
        return new JsonResponse([
            'error' => true,
            'titre' => 'Erreur!',
            'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
        ]);
    }

    #[Route('/updateUserPassword', name: 'updateUserPassword', methods: ['POST'])]
    public function updateUserPassword(Request $request, UserRepository $userRepository, SessionDS $sessionDS, SendMail $sendMail): Response
    {
        $datas = $request->request;

        $rawCurrentPassword = $datas->get('currentPassword');
        $rawNewPassword = $datas->get('newPassword');
        $rawConfirmNewPassword = $datas->get('confirmNewPassword');
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;

        if(strlen($rawNewPassword) < 6) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Whoops!',
                    'message' => "For your own security, your password must contain at least 6 characters including at least one capital letter and one number.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => 'Pour votre propre sécurité, votre mot de passe doit contenir au minimum 6 caractères dont au moins une lettre majuscule et un chiffre.',
            ]);
        }

        if(!$rawCurrentPassword or !$rawNewPassword or !$rawConfirmNewPassword){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Please complete all fields!',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez bien remplir tous les champs!',
            ]);
        }

        $userUid = $userRepository->findOneBy(['uid' => $uid]);
        if(!$userUid){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "We have encountered a problem, contact Assistance by WhatsApp.",
                ]);
            }
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
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => "Attention!",
                    'message' => 'Current passwords incorrect!',
                ]);
            }
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
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => false,
                    'message' => 'Password updated!',
                    "user" => $this->traitementsDS->infosUser($user),
                ]);
            }
            return new JsonResponse([
                'error' => false,
                'message' => 'Mot de passe mis a jours!',
                "user" => $this->traitementsDS->infosUser($user),
            ]);
        }

        if($sessionDS->get("langUserPhone") != "fr") {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Mistake!',
                'message' => "We have encountered a problem, contact Assistance by WhatsApp.",
            ]);
        }
        return new JsonResponse([
            'error' => true,
            'titre' => 'Erreur!',
            'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
        ]);
    }

    #[Route('/getUserInfo', name: 'getUserInfo', methods: ['POST'])]
    public function getUserInfo(Request $request, UserRepository $userRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        $uid = str_replace(["\n", "\r", " "], "", $uid);

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
        if ($user) {
            $this->traitementsDS->migrateUidIfNeeded($user);
            $this->cookieDS->set("uid", $user->getUid());
        }
        
        if($user) {
            // enregistrement de la langue du user et du last login
            $user->setLastLoginTo(new DateTime());
            if($user->getLang() != $langUserPhone) {
                $user->setLang($langUserPhone);
            }
            $this->em->flush();

            if ($user->getId()) {
                return new JsonResponse([
                    'error' => false,
                    'message' => 'Ok!',
                    "user" => $this->traitementsDS->infosUser($user),
                ]);
            }
        }

        if($sessionDS->get("langUserPhone") != "fr") {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Mistake!',
                'message' => "We have encountered a problem, contact Assistance by WhatsApp.",
            ]);
        }
        return new JsonResponse([
            'error' => true,
            'titre' => 'Erreur!',
            'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
        ]);
    }

    #[Route('/sendMailVerification', name: 'sendMailVerification', methods: ['POST'])]
    public function sendMailVerification(Request $request, UserRepository $userRepository, SendMail $sendMail, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

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
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "Error sending email.",
                ]);
            }
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
    public function mailVerification(Request $request, UserRepository $userRepository, VerifMailRepository $verifMailRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

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
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Action Repeat!',
                    'message' => "Your email is already confirmed.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Action Répéter!',
                'message' => "Votre mail est déja confirmer.",
            ]);
        }

        $verifMail = $verifMailRepository->findOneBy(['user' => $user, 'code' => $codeForVerifMail]);

        if(!$verifMail){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "Check the entered code carefully.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Vérifier bien le code saisie.",
            ]);
        }

        if((new Datetime()) > $verifMail->getDateExp()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Whoops!',
                    'message' => "This code has expired. Please resume the verification process.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => "Ce code est expiré. Veuillez reprendre le processus de vérification.",
            ]);
        }

        $user->setMailIsVerified(true);
        $this->em->flush();

        foreach ($verifMailRepository->findBy(['user' => $user]) as $element) {
            $verifMailRepository->remove($element, true);
        }

        return new JsonResponse([
            'error' => false,
            'user' => $this->traitementsDS->infosUser($user),
        ]);
    }

    #[Route('/sendMailPassForgotWithConnecte', name: 'sendMailPassForgotWithConnecte', methods: ['POST'])]
    public function sendMailPassForgotWithConnecte(Request $request, UserRepository $userRepository, SendMail $sendMail, TraitementsDS $traitementsDS, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

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
        $this->cookieDS->set("uid", $user->getUid());
        $newPassword = $traitementsDS->resetPassword();
        $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
        $this->em->flush();

        $html = $this->renderView("emails/passe_4got_mail.html.twig",[
            'code' => $newPassword,
            'username' => $user,
        ]);

        $sent = $sendMail->smtpMail($user->getMail(), "Réinitialisation du mot de passe", $html);
        if (!$sent) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "Error sending email. Please try again.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Erreur d'envoi du mail. Veuillez réessayer.",
            ]);
        }
        return new JsonResponse([
            'error' => false,
        ]);
    }

    #[Route('/sendMailPassForgot', name: 'sendMailPassForgot', methods: ['POST'])]
    public function sendMailPassForgot(Request $request, UserRepository $userRepository, SendMail $sendMail, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        try {
        $datas = $request->request;

        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $mail = strtolower(str_replace(" ", "", $datas->get('mail')));

        if(!$mail){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "Please enter your email.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez entrer votre mail.',
            ]);
        }

        $user = $userRepository->findOneBy(['mail' => $mail]);
        if(!$user){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "No account matches : ".$mail,
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Aucun compte ne correspond à : ".$mail,
            ]);
        }

        $this->traitementsDS->migrateUidIfNeeded($user);
        $newPassword = $traitementsDS->resetPassword();
        $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
        $this->em->flush();

        $html = $this->renderView("emails/passe_4got_mail.html.twig",[
            'code' => $newPassword,
            'username' => $user,
        ]);

        $sent = $sendMail->smtpMail($user->getMail(), "Réinitialisation du mot de passe", $html);
        if (!$sent) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "Error sending email. Please try again.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Erreur d'envoi du mail. Veuillez réessayer.",
            ]);
        }
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
    public function inscriptionDS(Request $request, UserRepository $userRepository, TraitementsDS $traitementsDS, VerificationsDS $verificationsDS, SessionDS $sessionDS, SendMail $sendMail): Response
    {
        try {
        $datas = $request->request;

        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);
        
        $tel = str_replace(" ", "", $datas->get('tel'));
        $mail = strtolower(str_replace(" ", "", $datas->get('mail')));
        $password = $datas->get('password');
        $confirmPassword = $datas->get('confirmPassword');

        $dressur = $userRepository->find(2);

        foreach (['+229', '+225'] as $indicatif) {
            if (strpos($tel, $indicatif) === 0) {
                $afterIndicatif = substr($tel, strlen($indicatif));
                if (!preg_match('/^\d{10}$/', $afterIndicatif)) {
                    if($sessionDS->get("langUserPhone") != "fr") {
                        return new JsonResponse(['error' => true, 'titre' => 'Attention!', 'message' => 'For the '.$indicatif.' prefix, please enter exactly 10 digits after the prefix.']);
                    }
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
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Please complete all fields correctly!',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez bien remplir tous les champs!',
            ]);
        }
        
        if($tel && $this->env->getUserBanned() && in_array($tel, $this->env->getUserBanned())) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "This number has been banned from Dressur. Contact support if this is an error.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Ce numéro a été banni de Dressur. Contacter l'assistance s'il s'agit d'une erreur.",
            ]);
        }

        if($mail && $this->env->getUserBanned() && in_array($mail, $this->env->getUserBanned())) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "This email address has been banned from Dressur. Contact support if this is a mistake.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Cette adresse mail a été banni de Dressur. Contactez l'assistance s'il s'agit d'une erreur.",
            ]);
        }

        $verificationNumTel = $verificationsDS->verifFormatNumTel($tel);
        if($verificationNumTel["error"] == true){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Please enter a valid phone number preceded by its prefix."]);
            }
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix."]);
        }
        $tel = $verificationNumTel["e164"];
        $paysTel = $verificationNumTel["country_code"];

        if (!$verificationsDS->verifMail($mail)) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Please enter a valid email address.",]); 
            }
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir une adresse E-Mail valide.",]); 
        }

        $userTel = $userRepository->findOneBy(['tel' => $tel]);
        if($userTel){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Access Deny!',
                    'message' => 'This number is already used.',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Accès Refuser!',
                'message' => 'Ce numéro est déja utilisé.',
            ]);
        }

        $userMail = $userRepository->findOneBy(['mail' => $mail]);
        if($userMail){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Whoops!',
                    'message' => "This E-Mail address is already in use.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => "Cette adresse E-Mail est déja utilisé.",
            ]);
        }

        $userPseudo = $userRepository->findOneBy(['pseudo' => $pseudo]);
        if($userPseudo){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Whoops!',
                    'message' => "This nickname is already used!",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => 'Ce pseudo est déja utilisé!',
            ]);
        }

        if(strlen($password) < 6) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Whoops!',
                    'message' => "For your own security, your password must contain at least 6 characters including at least one capital letter and one number.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => 'Pour votre propre sécurité, votre mot de passe doit contenir au minimum 6 caractères dont au moins une lettre majuscule et un chiffre.',
            ]);
        }
        
        $rawPassword = $datas->get('password');
        $rawConfirmPassword = $datas->get('confirmPassword');

        if($rawPassword != $rawConfirmPassword){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Whoops!',
                    'message' => "Password confirmation error!",
                ]);
            }
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
            ->setLang($langUserPhone)
            ->setLastLoginTo(new DateTime())
            ->setRegisterSource($registerSource)
            ->setLastLoginSource($registerSource)
        ;
        if(!in_array($tel, $this->env->getUsersTel())) {
            $this->env->addUsersTel($tel);
        }
        $this->em->persist($user);

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
        
        $sendMail->smtpMail(
            $user->getMail(), 
            "Confirmation du Mail", 
            $this->renderView("emails/verif_mail.html.twig",[
                'code' => $verifMail->getCode(),
                'username' => $user,
            ])
        );

        $this->em->flush();
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

            $this->cookieDS->set("uid", $userAfterRegister->getUid());
            return new JsonResponse([
                'error' => false,
                'user' => $this->traitementsDS->infosUser($userAfterRegister),
            ]);
        }

        if($sessionDS->get("langUserPhone") != "fr") {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Mistake!',
                'message' => "We have encountered a problem, contact Assistance by WhatsApp.",
            ]);
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
    public function deleteCompteDS(Request $request, TraitementsDS $traitementsDS, VerificationsDS $verificationsDS, SessionDS $sessionDS, UserRepository $userRepository): Response
    {
        set_time_limit(10000);

        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        $motifDeleted = $datas->get('motifDeleted');

        $user = $userRepository->findOneBy(['uid' => $uid]);

        if(!$user) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'User not found... Contact Dressur support on WhatsApp.',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => "Utilisateur introuvable... Contactez l'assistance Dressur sur WhatsApp.",
            ]);
        }

        if(!$motifDeleted){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'The reason for deleting the account is essential...',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Le motif de suppresion du compte est indispensable...',
            ]);
        }

        if(strlen($motifDeleted) < 100){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'The pattern must contain at least 100 characters.',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Le motif doit contenir au minimum 100 caractères.',
            ]);
        }

        $deletedDS = new DeletedDS();
        $deletedDS->setMail($user->getMail())
            ->setTel($user->getTel())
            ->setMotif($motifDeleted)
        ;
        $this->em->persist($deletedDS);
        $this->em->flush();

        $traitementsDS->execPurge($user);

        return new JsonResponse([
            'error' => false,
        ]);
    }

    #[Route('/addSuggestion', name: 'addSuggestion')]
    public function addSuggestion(Request $request, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        $suggestion = $datas->get('suggestion');

        if(!$suggestion){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "Please enter your suggestion carefully...",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez bien entrer votre suggestion...',
            ]);
        }

        if(strlen($suggestion) < 10){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "The suggestion must contain at least 10 characters",
                ]);
            }
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

    #[Route('/addToRecompenseProgramme', name: 'addToRecompenseProgramme')]
    public function addToRecompenseProgramme(Request $request,UserRepository $userRepository, SessionDS $sessionDS): Response
    {
        try {
            $datas = $request->request;
        
            $langUserPhone = $datas->get('langUserPhone');
            $sessionDS->set("langUserPhone", $langUserPhone);

            $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
            
            $user = $userRepository->findOneBy(['uid' => $uid]);
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
                'message' => "Nous avons rencontré une erreur. Veuillez réessayer ou contacter l’assistance Dressur sur WhatsApp.",
            ]);
        }
    }

    #[Route('/getPromotionAffaireInProgrammeRecompense', name: 'getPromotionAffaireInProgrammeRecompense')]
    public function getPromotionAffaireInProgrammeRecompense(Request $request, UserRepository $userRepository, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        try {
            $datas = $request->request;
        
            $langUserPhone = $datas->get('langUserPhone');
            $sessionDS->set("langUserPhone", $langUserPhone);

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
    public function partageInProgrammeRecompense(Request $request, UserRepository $userRepository, PromotionRepository $promotionRepository, EntityManagerInterface $em, HistoriqueProgrammeRecompenseRepository $historiqueProgrammeRecompenseRepository, SessionDS $sessionDS): Response
    {
        try {
            $datas = $request->request;
        
            $langUserPhone = $datas->get('langUserPhone');
            $sessionDS->set("langUserPhone", $langUserPhone);

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
    public function getMyProgrammeRecompenseInformations(Request $request, UserRepository $userRepository, PromotionRepository $promotionRepository, EntityManagerInterface $em, HistoriqueProgrammeRecompenseRepository $historiqueProgrammeRecompenseRepository, SessionDS $sessionDS): Response
    {
        $vuesTotales = 0;
        $gainsTotales = 0;
        $soldeDisponible = 0;
        $sixLastHistorique = [];
        $allHistorique = [];

        try {
            $datas = $request->request;
        
            $langUserPhone = $datas->get('langUserPhone');
            $sessionDS->set("langUserPhone", $langUserPhone);

            $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
            $user = $userRepository->findOneBy(['uid' => $uid]);
            $soldeDisponible = $user->getSoldeProgrammeRecompense();

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
    public function submitProgrammeRecompenseProofs(Request $request, UserRepository $userRepository, EntityManagerInterface $em, HistoriqueProgrammeRecompenseRepository $historiqueProgrammeRecompenseRepository, SessionDS $sessionDS): Response
    {
        $filesystem = new Filesystem();
        $uploadDir = $this->getParameter('preuve_recompense');
        if (!$filesystem->exists($uploadDir)) {
            $filesystem->mkdir($uploadDir, 0775);
        }

        try {
            $datas = $request->request;
            $files = $request->files;
        
            $langUserPhone = $datas->get('langUserPhone');
            $sessionDS->set("langUserPhone", $langUserPhone);

            $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
            $user = $userRepository->findOneBy(['uid' => $uid]);

            $idHistorique = $datas->get('idHistorique');
            $historiqueProgrammeRecompense = $historiqueProgrammeRecompenseRepository->find((int)$idHistorique);

            $capture1 = $files->get('capture1');
            if (!$capture1->isValid()) {
                if($sessionDS->get("langUserPhone") != "fr") {
                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Erreur!',
                        'message' => "Error processing capture – Status list.",
                    ]);
                }
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Erreur lors du traitement de la capture – Liste des statuts.",
                ]);
            }
            $fileName1 = "preuve_$uid"."_".UuidGenerator::v4().'.'.$capture1->getClientOriginalExtension();


            $capture2 = $files->get('capture2');
            if (!$capture2->isValid()) {
                if($sessionDS->get("langUserPhone") != "fr") {
                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Erreur!',
                        'message' => "Error processing capture – Open status.",
                    ]);
                }
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

    #[Route('/saveRetraitConfiguration', name: 'saveRetraitConfiguration')]
    public function saveRetraitConfiguration(Request $request, UserRepository $userRepository, EntityManagerInterface $em, HistoriqueProgrammeRecompenseRepository $historiqueProgrammeRecompenseRepository, SessionDS $sessionDS): Response
    {

        try {
            $datas = $request->request;
        
            $langUserPhone = $datas->get('langUserPhone');
            $sessionDS->set("langUserPhone", $langUserPhone);

            $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
            $user = $userRepository->findOneBy(['uid' => $uid]);

            $reseauRetrait = $datas->get('reseauRetrait') ?? "";
            $numeroRetrait = $datas->get('numeroRetrait') ?? "";
            
            $user->setReseauRetrait($reseauRetrait)->setNumeroRetrait($numeroRetrait);

            $em->flush();
                        
            return new JsonResponse([
                'error' => false,
                'titre' => "OK ...",
                'message' => "Configuration enregistrer.",
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error saveRetraitConfiguration : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => "Oups !!!",
                'message' => "Nous avons rencontré une erreur. Veuillez réessayer ou contacter l’assistance Dressur sur WhatsApp.",
            ]);
        }
    }

    #[Route('/getRetraitConfiguration', name: 'getRetraitConfiguration')]
    public function getRetraitConfiguration(Request $request, UserRepository $userRepository, EntityManagerInterface $em, HistoriqueProgrammeRecompenseRepository $historiqueProgrammeRecompenseRepository, SessionDS $sessionDS): Response
    {

        try {
            $datas = $request->request;
        
            $langUserPhone = $datas->get('langUserPhone');
            $sessionDS->set("langUserPhone", $langUserPhone);

            $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
            $user = $userRepository->findOneBy(['uid' => $uid]);

            return new JsonResponse([
                'error' => false,
                'reseauRetrait' => $user->getReseauRetrait(),
                'numeroRetrait' => $user->getNumeroRetrait(),
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error getRetraitConfiguration : UserController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => "Oups !!!",
                'message' => "Nous avons rencontré une erreur. Veuillez réessayer ou contacter l’assistance Dressur sur WhatsApp.",
            ]);
        }
    }
}