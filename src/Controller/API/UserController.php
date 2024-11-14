<?php

namespace App\Controller\API;

use DateTime;
use Exception;
use App\Entity\User;
use App\Entity\VerifMail;
use App\Entity\Preference;
use App\Utilities\SendMail;
use App\Entity\DSBonusHistorique;
use App\Repository\EnvRepository;
use App\Repository\UserRepository;
use App\Repository\BoostRepository;
use App\Repository\DSBonusRepository;
use App\Repository\VerifMailRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Contact;
use App\Entity\DeletedDS;
use App\Entity\Env;
use App\Entity\FormuleBoost;
use App\Entity\FormuleCampagneMail;
use App\Entity\FormulePromoReseau;
use App\Entity\Suggestion;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\DSBonusHistoriqueRepository;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\SuggestionRepository;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Services\VerificationsDS;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;

#[Route('/api', name: 'api_')]
class UserController extends AbstractController
{
    private $em;
    private $env;
    private $traitementsDS;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, TraitementsDS $traitementsDS)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->traitementsDS = $traitementsDS;
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
            // vider les tables Env, FormuleBoost, FormuleCampagneMail, FormulePromoReseau
            $platform = $this->em->getConnection()->getDatabasePlatform();
            $this->em->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            $this->em->getConnection()->executeStatement($platform->getTruncateTableSQL($this->em->getClassMetadata(Env::class)->getTableName()));
            $this->em->getConnection()->executeStatement($platform->getTruncateTableSQL($this->em->getClassMetadata(FormuleBoost::class)->getTableName()));
            $this->em->getConnection()->executeStatement($platform->getTruncateTableSQL($this->em->getClassMetadata(FormuleCampagneMail::class)->getTableName()));
            $this->em->getConnection()->executeStatement($platform->getTruncateTableSQL($this->em->getClassMetadata(FormulePromoReseau::class)->getTableName()));
            $this->em->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1');

            // mise en place de l'env
            $this->em->persist((new Env())->setCommissionBonus(5000)->setVersionApp("1.0.0")->setImportantUpdate(true)->setUsersTel([])->setDoBoostPayant(false)->setLinkLocalServer("PAS_ENCORE_DE_LINK"));

            // creation des utilisateures important
            if(count($userRepository->findAll()) == 0) {
                $user = (new User())->setPseudo("profil.google")->setTel("+22900000000")->setMail("equipe.test.dressur.ds@gmail.com")->setCodeBonus("DS0000")->setPassword(sha1(sha1(sha1("DressurDS3@"))))->setPays("229")->setLang("en")->setSoldeBonus(2000)->setMailIsVerified(true)->setTelIsVerified(true); $preference = (new Preference())->setUser($user)->setPaysChoisies([])->setCentreInteretLoisirChoisies([]); $contact = (new Contact())->setUser($user); $this->em->persist($user); $this->em->persist($preference); $this->em->persist($contact);
                $user = (new User())->setAvatar("dressur.png")->setPseudo("dressur.ds")->setTel("+22964044294")->setMail("dressur.ds@gmail.com")->setCodeBonus("DRESSUR-DS")->setPassword(sha1(sha1(sha1("0000"))))->setPays("229")->setLang("fr")->setSoldeBonus(100000)->setMailIsVerified(true)->setTelIsVerified(true)->setAdmin(true); $preference = (new Preference())->setUser($user)->setPaysChoisies([])->setCentreInteretLoisirChoisies([]); $contact = (new Contact())->setUser($user); $this->em->persist($user); $this->em->persist($preference); $this->em->persist($contact);
                $user = (new User())->setAvatar("blt.png")->setBanniere("banniere_blt.jpg")->setPseudo("bluelife.tech")->setTel("+22958519556")->setMail("bluelife.tech@gmail.com")->setCodeBonus("BLUE-LIFE-TECH")->setPassword(sha1(sha1(sha1("0000"))))->setPays("229")->setLang("fr")->setSoldeBonus(200000)->setMailIsVerified(true)->setTelIsVerified(true)->setAdmin(true)->setNom("BLUE LIFE TECH")->setTiktok("https://www.tiktok.com/@bluelife.tech")->setInstagram("https://www.instagram.com/bluelife.tech/")->setYoutube("https://www.youtube.com/@bluelife-tech")->setFacebook("https://www.facebook.com/bluelife.tech"); $preference = (new Preference())->setUser($user)->setPaysChoisies([])->setCentreInteretLoisirChoisies([]); $contact = (new Contact())->setUser($user); $this->em->persist($user); $this->em->persist($preference); $this->em->persist($contact);
                $user = (new User())->setAvatar("elitics.png")->setBanniere("banniere_elitics.jpg")->setPseudo("elitics.core")->setTel("+22990978787")->setMail("elitics.core@tech-center.com")->setCodeBonus("ELITICS-CORE")->setPassword(sha1(sha1(sha1("0000"))))->setPays("229")->setLang("fr")->setSoldeBonus(200000)->setMailIsVerified(true)->setTelIsVerified(true)->setAdmin(true); $preference = (new Preference())->setUser($user)->setPaysChoisies([])->setCentreInteretLoisirChoisies([]); $contact = (new Contact())->setUser($user); $this->em->persist($user); $this->em->persist($preference); $this->em->persist($contact);
                
                $user = (new User())->setPseudo("louise")->setTel("+22956518088")->setMail("affichekpolouise@gmail.com")->setCodeBonus("LOUISE")->setPassword(sha1(sha1(sha1("0000"))))->setPays("229")->setLang("fr")->setSoldeBonus(50000)->setMailIsVerified(true)->setTelIsVerified(true); $preference = (new Preference())->setUser($user)->setPaysChoisies([])->setCentreInteretLoisirChoisies([]); $contact = (new Contact())->setUser($user); $this->em->persist($user); $this->em->persist($preference); $this->em->persist($contact);
                $user = (new User())->setPseudo("dklars")->setTel("+22962273861")->setMail("dklars.dev@gmail.com")->setCodeBonus("DKLARS")->setPassword(sha1(sha1(sha1("0000"))))->setPays("229")->setLang("fr")->setSoldeBonus(50000)->setMailIsVerified(true)->setTelIsVerified(true)->setAdmin(true); $preference = (new Preference())->setUser($user)->setPaysChoisies([])->setCentreInteretLoisirChoisies([]); $contact = (new Contact())->setUser($user); $this->em->persist($user); $this->em->persist($preference); $this->em->persist($contact);
                $user = (new User())->setPseudo("san")->setTel("+22969165323")->setMail("alladayesandym@gmail.com")->setCodeBonus("SAN")->setPassword(sha1(sha1(sha1("0000"))))->setPays("229")->setLang("fr")->setSoldeBonus(50000)->setMailIsVerified(true)->setTelIsVerified(true); $preference = (new Preference())->setUser($user)->setPaysChoisies([])->setCentreInteretLoisirChoisies([]); $contact = (new Contact())->setUser($user); $this->em->persist($user); $this->em->persist($preference); $this->em->persist($contact);
                $user = (new User())->setPseudo("noe")->setTel("+22997542851")->setMail("noegouton@gmail.com")->setCodeBonus("NOE")->setPassword(sha1(sha1(sha1("0000"))))->setPays("229")->setLang("fr")->setSoldeBonus(50000)->setMailIsVerified(true)->setTelIsVerified(true); $preference = (new Preference())->setUser($user)->setPaysChoisies([])->setCentreInteretLoisirChoisies([]); $contact = (new Contact())->setUser($user); $this->em->persist($user); $this->em->persist($preference); $this->em->persist($contact);
                $user = (new User())->setPseudo("akpatanativite")->setTel("+22951484969")->setMail("akpatanativite@gmail.com")->setCodeBonus("AKPATA")->setPassword(sha1(sha1(sha1("0000"))))->setPays("229")->setLang("fr")->setSoldeBonus(50000)->setMailIsVerified(true)->setTelIsVerified(true); $preference = (new Preference())->setUser($user)->setPaysChoisies([])->setCentreInteretLoisirChoisies([]); $contact = (new Contact())->setUser($user); $this->em->persist($user); $this->em->persist($preference); $this->em->persist($contact);
                
                $user = (new User())->setPseudo("dev")->setTel("+22963856891")->setMail("kofukunoatama@gmail.com")->setCodeBonus("DEV")->setPassword(sha1(sha1(sha1("0000"))))->setPays("229")->setLang("fr")->setSoldeBonus(50000)->setMailIsVerified(true)->setTelIsVerified(true); $preference = (new Preference())->setUser($user)->setPaysChoisies([])->setCentreInteretLoisirChoisies([]); $contact = (new Contact())->setUser($user); $this->em->persist($user); $this->em->persist($preference); $this->em->persist($contact);
                $user = (new User())->setPseudo("dynams")->setTel("+22955487985")->setMail("dynamslars@gmail.com")->setCodeBonus("DYNAMS")->setPassword(sha1(sha1(sha1("0000"))))->setPays("229")->setLang("fr")->setSoldeBonus(50000)->setMailIsVerified(true)->setTelIsVerified(true); $preference = (new Preference())->setUser($user)->setPaysChoisies([])->setCentreInteretLoisirChoisies([]); $contact = (new Contact())->setUser($user); $this->em->persist($user); $this->em->persist($preference); $this->em->persist($contact);
                
                // $user = (new User())->setPseudo("")->setTel("")->setMail("")->setCodeBonus("")->setPassword(sha1(sha1(sha1("0000"))))->setPays("229")->setLang("fr")->setSoldeBonus(50000)->setMailIsVerified(true)->setTelIsVerified(true); $preference = (new Preference())->setUser($user)->setPaysChoisies([])->setCentreInteretLoisirChoisies([]); $contact = (new Contact())->setUser($user); $this->em->persist($user); $this->em->persist($preference); $this->em->persist($contact);
            }

            // les FormuleBoost
            $this->em->persist((new FormuleBoost())->setTitre("Formule A")->setPrix(500)->setNbrJour(2)->setAlert(false));
            $this->em->persist((new FormuleBoost())->setTitre("Formule B")->setPrix(1000)->setNbrJour(4)->setAlert(false));
            $this->em->persist((new FormuleBoost())->setTitre("Formule C")->setPrix(1500)->setNbrJour(7)->setAlert(false));
            $this->em->persist((new FormuleBoost())->setTitre("Formule D")->setPrix(3000)->setNbrJour(14)->setAlert(false));
            $this->em->persist((new FormuleBoost())->setTitre("Formule E")->setPrix(7000)->setNbrJour(30)->setAlert(false));
            $this->em->persist((new FormuleBoost())->setTitre("Formule F")->setPrix(12500)->setNbrJour(60)->setAlert(false));
            $this->em->persist((new FormuleBoost())->setTitre("Formule G")->setPrix(25000)->setNbrJour(120)->setAlert(false));

            // les FormuleCampagneMail
            $this->em->persist((new FormuleCampagneMail())->setTitre("Formule A")->setPrix(1000)->setNombreMail(15));
            $this->em->persist((new FormuleCampagneMail())->setTitre("Formule B")->setPrix(6000)->setNombreMail(100));
            $this->em->persist((new FormuleCampagneMail())->setTitre("Formule C")->setPrix(9000)->setNombreMail(150));
            $this->em->persist((new FormuleCampagneMail())->setTitre("Formule D")->setPrix(30000)->setNombreMail(500));
            $this->em->persist((new FormuleCampagneMail())->setTitre("Formule E")->setPrix(55000)->setNombreMail(1000));
            $this->em->persist((new FormuleCampagneMail())->setTitre("Formule F")->setPrix(330000)->setNombreMail(5000));
            $this->em->persist((new FormuleCampagneMail())->setTitre("Formule G")->setPrix(600000)->setNombreMail(10000));
            
            $this->em->flush();

            return new JsonResponse([
                'error' => false,
                'versionApp' => "1.0.0",
                'importantUpdate' => false,
            ]);
        }
        
    }

    #[Route('/connect', name: 'connect', methods: ['POST'])]
    public function connect(Request $request, UserRepository $userRepository, SendMail $sendMail, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
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

        $password = sha1(sha1(sha1($datas->get('password'))));

        $user = $userRepository->findOneBy(['mail' => $mail, 'password' => $password]);

        if($user) {
            // enregistrement de la langue du user et du last login
            $user->setLastLoginTo(new DateTime());
            if($user->getLang() != $langUserPhone) { 
                $user->setLang($langUserPhone);
            }
            $this->em->flush();

            $verificationUser = $verificationsDS->verifUSer($user->getUid());
            if($verificationUser["error"] == true){
                return new JsonResponse([
                    'error' => true,
                    'titre' => $verificationUser["titre"],
                    'message' => $verificationUser["message"],
                ]);
            }

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

        $uid = $datas->get('uid');

        if(in_array($tel, $this->env->getUserBanned())) {
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

        if(in_array($mail, $this->env->getUserBanned())) {
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

        $verificationPseudo = $verificationsDS->verifPseudo($pseudo);
        if($verificationPseudo["error"] == true){
            return new JsonResponse([
                'error' => true,
                'titre' => $verificationPseudo["titre"],
                'message' => $verificationPseudo["message"],
            ]);
        }
        $pseudo = $verificationPseudo["pseudo"];

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

        $currentPassword = sha1(sha1(sha1($datas->get('currentPassword'))));
        $newPassword = sha1(sha1(sha1($datas->get('newPassword'))));
        $confirmNewPassword = sha1(sha1(sha1($datas->get('confirmNewPassword'))));
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');

        if(strlen($newPassword) < 6) {
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

        if(!$currentPassword or !$newPassword or !$confirmNewPassword){
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

        $user = $userRepository->findOneBy(['uid' => $uid, 'password' => $currentPassword]);
        if(!$user){
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

        if($newPassword != $confirmNewPassword){
            return new JsonResponse([
                'error' => true,
                'titre' => "Attention!",
                'message' => 'Erreur de confirmation du mot de passe!',
            ]);
        }

        $user->setPassword($newPassword);
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

        $uid = $datas->get('uid');
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

    #[Route('/addParrain', name: 'addParrain', methods: ['POST'])]
    public function addParrain(Request $request, UserRepository $userRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;

        $codeBonus = str_replace(" ", "", $datas->get('codeBonus'));
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');

        if(!$codeBonus){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "Please enter referral code.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Veuillez saisir le code de parrainage.",
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

        if(in_array($user->getTel(), $this->env->getUsersParrainer())) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Fraudster!',
                    'message' => "Fraud attempt failed. This number has already been sponsored once.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Fraudeur!',
                'message' => "Tentative de fraude échoué. Ce numéro a déjà été parrainé une fois.",
            ]);
        }

        if(in_array($user->getMail(), $this->env->getUsersParrainer())) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Fraudster!',
                    'message' => "Fraud attempt failed. This email address has already been sponsored once.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Fraudeur!',
                'message' => "Tentative de fraude échoué. Cette adresse mail a déjà été parrainé une fois.",
            ]);
        }

        if($user->getParrain()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "You already have a sponsor.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Vous avez déja un parrain.",
            ]);
        }

        if(!$user->getTelIsVerified()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => "Please confirm your WhatsApp number first.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => "Veuillez d'abord confirmer votre numéro WhatsApp.",
            ]);
        }

        if(!$user->getMailIsVerified()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => "Please confirm your email address first.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => "Veuillez d'abord confirmer votre adresse Mail.",
            ]);
        }

        $userCodeBonus = $userRepository->findOneBy(['codeBonus' => $codeBonus]);
        if(!$userCodeBonus){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "The code you entered is not a Dressur referral code. Please double check the referral code with your sponsor and ask them to direct you.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Le code saisi n'est pas un code de parrainage Dressur. Vérifiez bien le code parrainage auprès de votre parrain et demandez-lui de vous orienter.",
            ]);
        }

        if($user->getUid() == $userCodeBonus->getUid()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Whoops!',
                    'message' => "You cannot self-sponsor.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => "Vous ne pouvez pas vous auto-parrainer.",
            ]);
        }

        if(!$userCodeBonus->getTelIsVerified()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => "Your sponsor must first confirm his WhatsApp number.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => "Votre parrain doit d'abord confirmer son numéro WhatsApp.",
            ]);
        }

        if(!$userCodeBonus->getMailIsVerified()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => "Your sponsor must first confirm their email address.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => "Votre parrain doit d'abord confirmer son adresse Mail.",
            ]);
        }

        $DSBH = new DSBonusHistorique();
        if($user->getLang() == "fr") {
            $DSBH->setTitre("Parrainer par ".$userCodeBonus->getTel());
        } else {
            $DSBH->setTitre("Sponsor by ".$userCodeBonus->getTel());
        }
        $DSBH->setUser($user)->setMontant($this->env->getCommissionBonus());
        $this->em->persist($DSBH);

        $DSBHParrain = new DSBonusHistorique();
        if($userCodeBonus->getLang() == "fr") {
            $DSBHParrain->setTitre("+1 filleul ".$user->getTel());
        } else {
            $DSBHParrain->setTitre("+1 referral ".$user->getTel());
        }
        $DSBHParrain->setUser($userCodeBonus)->setMontant($this->env->getCommissionBonus());
        $this->em->persist($DSBHParrain);

        $user->setParrain($userCodeBonus)->addSoldeBonus($this->env->getCommissionBonus());
        $userCodeBonus->addSoldeBonus($this->env->getCommissionBonus());
        
        $this->env->addUsersParrainer($user->getTel());
        $this->env->addUsersParrainer($user->getMail());

        $this->em->flush();

        if($sessionDS->get("langUserPhone") != "fr") {
            return new JsonResponse([
                'error' => false,
                'soldeBonus' => $user->getSoldeBonus(),
                'message' => "Congratulations!\nYou have been sponsored!\nYou have received ".$this->env->getCommissionBonus(). " Bonus Points!",
                'user' => $this->traitementsDS->infosUser($user),
            ]);
        }
        return new JsonResponse([
            'error' => false,
            'soldeBonus' => $user->getSoldeBonus(),
            'message' => "Félicitations!\nVous étes parrainer!\nVous avez reçu ".$this->env->getCommissionBonus(). " Points Bonus!",
            'user' => $this->traitementsDS->infosUser($user),
        ]);
    }

    #[Route('/addBonusPromo', name: 'addBonusPromo', methods: ['POST'])]
    public function addBonusPromo(Request $request, UserRepository $userRepository, DSBonusRepository $wPBonusRepository, DSBonusHistoriqueRepository $wPBonusHistoriqueRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;

        $codePromo = str_replace(" ", "", $datas->get('codePromo'));
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');

        if(!$codePromo){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Please enter the promo code.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Veuillez saisir le code promo.",
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

        $DSBonus = $wPBonusRepository->findOneBy(['code' => $codePromo]);
        if(!$DSBonus){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "The code entered is not a Dressur promo code. Double check the promo code.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Le code saisi n'est pas un code promo Dressur. Vérifiez bien le code promo.",
            ]);
        }

        if((new Datetime()) > $DSBonus->getDateExp()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Whoops!',
                    'message' => "This promo code has expired",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => "Ce code promo est expiré.",
            ]);
        }

        $wPBonusHistorique = $wPBonusHistoriqueRepository->findOneBy(['user' => $user, 'dsbonus' => $DSBonus]);
        if($wPBonusHistorique){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Whoops!',
                    'message' => "You have already taken advantage of this bonus.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => "Vous aviez déja profité de ce bonus.",
            ]);
        }

        $DSBH = new DSBonusHistorique();
        if($user->getLang() == "fr") {
            $DSBH->setTitre("Bonus Promo");
        } else {
            $DSBH->setTitre("Promo Bonus");
        }
        $DSBH->setUser($user)->setWpbonus($DSBonus)->setMontant($DSBonus->getMontant());
        $user->addSoldeBonus($DSBonus->getMontant());
        $this->em->persist($DSBH);
        $this->em->flush();

        if($sessionDS->get("langUserPhone") != "fr") {
            return new JsonResponse([
                'error' => false,
                'soldeBonus' => $user->getSoldeBonus(),
                'message' => "Congratulations!\nYou have benefited from the promo code!\nYou have received ".$DSBonus->getMontant(). "DS of Bonus Boost!",
                'user' => $this->traitementsDS->infosUser($user),
            ]);
        }
        return new JsonResponse([
            'error' => false,
            'soldeBonus' => $user->getSoldeBonus(),
            'message' => "Félicitations!\nVous avez bénéficier du code promo!\nVous avez reçu ".$DSBonus->getMontant(). "DS de Bonus Boost!",
            'user' => $this->traitementsDS->infosUser($user),
        ]);
    }

    #[Route('/sendMailVerification', name: 'sendMailVerification', methods: ['POST'])]
    public function sendMailVerification(Request $request, UserRepository $userRepository, SendMail $sendMail, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');

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

        try {
            $sendMail->smtpMail($user->getMail(), "Confirmation du Mail", $html);
            return new JsonResponse([
                'error' => false,
            ]);
        } catch (Exception $e) {
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
    }

    #[Route('/mailVerification', name: 'mailVerification', methods: ['POST'])]
    public function mailVerification(Request $request, UserRepository $userRepository, VerifMailRepository $verifMailRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
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

        $uid = $datas->get('uid');

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

        $newPassword = $traitementsDS->resetPassword();
        $user->setPassword(sha1(sha1(sha1($newPassword))));
        $this->em->flush();

        $html = $this->renderView("emails/passe_4got_mail.html.twig",[
            'code' => $newPassword,
            'username' => $user,
        ]);

        try {
            $sendMail->smtpMail($user->getMail(), "Réinitialisation du mot de passe", $html);
            return new JsonResponse([
                'error' => false,
            ]);
        } catch (Exception $e) {
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
    }

    #[Route('/sendMailPassForgot', name: 'sendMailPassForgot', methods: ['POST'])]
    public function sendMailPassForgot(Request $request, UserRepository $userRepository, SendMail $sendMail, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
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

        $newPassword = $traitementsDS->resetPassword();
        $user->setPassword(sha1(sha1(sha1($newPassword))));
        $this->em->flush();

        $html = $this->renderView("emails/passe_4got_mail.html.twig",[
            'code' => $newPassword,
            'username' => $user,
        ]);

        try {
            $sendMail->smtpMail($user->getMail(), "Réinitialisation du mot de passe", $html);
            return new JsonResponse([
                'error' => false,
            ]);
        } catch (Exception $e) {
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
    }   

    #[Route('/inscriptionDS', name: 'inscriptionDS', methods: ['POST'])]
    public function inscriptionDS(Request $request, UserRepository $userRepository, TraitementsDS $traitementsDS, VerificationsDS $verificationsDS, SessionDS $sessionDS, SendMail $sendMail): Response
    {
        $datas = $request->request;

        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);
        
        $pseudo = $datas->get('pseudo');
        $tel = $datas->get('tel'); 
        $mail = strtolower(str_replace(" ", "", $datas->get('mail')));
        $password = $datas->get('password');
        $confirmPassword = $datas->get('confirmPassword');
        $pseudo = $traitementsDS->makePseudoWithEmailAdress($mail);

        $dressur = $userRepository->find(2);

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
        
        if(in_array($tel, $this->env->getUserBanned())) {
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

        if(in_array($mail, $this->env->getUserBanned())) {
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

        $verificationPseudo = $verificationsDS->verifPseudo($pseudo);
        if($verificationPseudo["error"] == true){
            return new JsonResponse([
                'error' => true,
                'titre' => $verificationPseudo["titre"],
                'message' => $verificationPseudo["message"],
            ]);
        }
        $pseudo = $verificationPseudo["pseudo"];

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
        
        $password = sha1(sha1(sha1($datas->get('password'))));
        $confirmPassword = sha1(sha1(sha1($datas->get('confirmPassword'))));

        if($password != $confirmPassword){
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

        $user = new User();
        $user->setPseudo($pseudo)
            ->setTel($tel)
            ->setMail($mail)
            ->setPassword($password)
            ->setPays($paysTel)
            ->setLang($langUserPhone)
            ->setLastLoginTo(new DateTime())
        ;
        if(in_array($tel, $this->env->getUsersTel())) { 
            $user->setSoldeBonus(0);
        } else { 
            $user->setSoldeBonus($this->env->getCommissionBonus());
            $this->env->addUsersTel($tel);
        }
        $this->em->persist($user);

        $DSBH = new DSBonusHistorique();
        if($user->getLang() == "fr") {
            $DSBH->setTitre("Bonus de Bienvenu");
        } else {
            $DSBH->setTitre("Welcome Bonus");
        }
        $DSBH->setUser($user)->setMontant($this->env->getCommissionBonus());
        $this->em->persist($DSBH);

        $preference = new Preference();
        $preference->setUser($user)
            ->setPaysChoisies([(string)$user->getPays()])
            ->setCentreInteretLoisirChoisies([])
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
            try {
                $response_add_taff = file_get_contents($this->env->getLinkLocalServer()."/add_taff");
            } catch (\Throwable $th) {
                //throw $th;
            }

            $sendMail->smtpMail(
                $userAfterRegister->getMail(), 
                "Bienvenu sur Dressur", 
                $this->renderView('emails/bienvenu_mail.html.twig', [
                    'pseudoUser' => $userAfterRegister->getPseudo(),
                ]
            ));

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
    }

    #[Route('/listBonus/{uid}/{langUserPhone}', name: 'listBonus', methods: ['POST', "GET"])]
    public function listBonus(User $user, $langUserPhone, BoostRepository $boostRepository, TraitementsDS $traitementsDS, SessionDS $sessionDS, DSBonusHistoriqueRepository $dSBonusHistoriqueRepository): Response
    {
        $sessionDS->set("langUserPhone", $langUserPhone);

        return new JsonResponse($traitementsDS->bonusTab($dSBonusHistoriqueRepository->findBy(['user' => $user], ['id' => "DESC"])),);
    }

    #[Route('/deleteCompteDS', name: 'deleteCompteDS', methods: ['POST'])]
    public function deleteCompteDS(Request $request, TraitementsDS $traitementsDS, VerificationsDS $verificationsDS, SessionDS $sessionDS, UserRepository $userRepository): Response
    {
        set_time_limit(10000);

        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
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

        $uid = $datas->get('uid');
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
}