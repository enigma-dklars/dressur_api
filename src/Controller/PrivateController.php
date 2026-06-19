<?php

namespace App\Controller;

use App\Controller\API\UserController;
use App\Entity\FormulePromoReseau;
use App\Repository\BoostRepository;
use App\Repository\DeletedDSRepository;
use App\Repository\EnvRepository;
use App\Repository\FormuleBoostRepository;
use App\Repository\FormulePromoAffaireRepository;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\PromoReseauRepository;
use App\Repository\PromotionRepository;
use App\Repository\StoryRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserBotRepository;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateController extends AbstractController
{
    private $em;
    private $env;
    private $theme;
    private $cookieDS;
    private $traitementsDS;
    private $userRepository;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, UserRepository $userRepository, CookieDS $cookieDS, TraitementsDS $traitementsDS, FormulePromoReseauRepository $formulePromoReseauRepository)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->cookieDS = $cookieDS;
        $this->traitementsDS = $traitementsDS;
        $this->userRepository = $userRepository;
        if($this->cookieDS->check("theme")) {
            if($this->cookieDS->get("theme") == "dark-theme") {
                $this->theme = "dark-theme";
            } else {
                $this->theme = "light-theme";
            }
        } else {
            $this->theme = "light-theme";
        }
        
    }

    private function encodePromoToken(int $id): string
    {
        $key = substr(hash('sha256', $this->getParameter('kernel.secret'), true), 0, 16);
        $encrypted = openssl_encrypt((string) $id, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }

    private function decodePromoToken(string $token): ?int
    {
        $key = substr(hash('sha256', $this->getParameter('kernel.secret'), true), 0, 16);
        $padded = strtr($token, '-_', '+/');
        $pad = strlen($padded) % 4;
        if ($pad) {
            $padded .= str_repeat('=', 4 - $pad);
        }
        $decrypted = openssl_decrypt(base64_decode($padded), 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return ($decrypted !== false && ctype_digit($decrypted)) ? (int) $decrypted : null;
    }

    #[Route('/export_vcf', name: 'app_export_vcf')]
    public function export_vcf(): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $pseudo = str_replace(".", "", $user->getPseudo());
        $contacts = $this->traitementsDS->userContacts($user);

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/vcard; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="contacts_'.$pseudo.'.vcf"');

        $response->setCallback(function () use ($contacts) {
            $handle = fopen('php://output', 'w');

            foreach ($contacts as $contact) {
                fwrite($handle, "BEGIN:VCARD\n");
                fwrite($handle, "VERSION:3.0\n");
                fwrite($handle, "FN:" . $contact['nom'] . " #DS\n");
                fwrite($handle, "EMAIL:" . $contact['mail'] . "\n");
                fwrite($handle, "TEL:" . $contact['tel'] . "\n");
                fwrite($handle, "END:VCARD\n");
            }

            fclose($handle);
        });

        return $response;
    }

    #[Route('/export_csv', name: 'app_export_csv')]
    public function export_csv(): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $pseudo = str_replace(".", "", $user->getPseudo());
        $contacts = $this->traitementsDS->userContacts($user);

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="contacts_'.$pseudo.'.csv"');

        $response->setCallback(function () use ($contacts) {
            $handle = fopen('php://output', 'w');

            // Headers CSV
            fputcsv($handle, ['Nom', 'Email', 'Téléphone']);

            // Données des contacts
            foreach ($contacts as $contact) {
                fputcsv($handle, [
                    $contact['nom']." #DS", 
                    $contact['mail'], $contact['tel']
                ]);
            }

            fclose($handle);
        });

        return $response;
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(CookieDS $cookieDS, UserRepository $userRepository, TraitementsDS $traitementsDS): Response
    {
        $cookieDS->remove("uid");
        return $this->redirectToRoute('app_connexion');
    }

    #[Route('/private', name: 'app_private')]
    public function index(CookieDS $cookieDS, UserRepository $userRepository, TraitementsDS $traitementsDS, PromotionRepository $promotionRepository, PromoReseauRepository $promoReseauRepository, UserBotRepository $userBotRepository, DeletedDSRepository $deletedDSRepository, BoostRepository $boostRepository, StoryRepository $storyRepository): Response
    {
        if($cookieDS->get("uid")){
            $uid = $cookieDS->get("uid");
            $user = $userRepository->findOneBy(['uid' => $uid]);

            $userinfo = $this->traitementsDS->infosUser($user);

            $actusList = json_decode($userinfo['lesPublicites'], true) ?? [];
            foreach ($actusList as &$a) {
                $a['token'] = $this->encodePromoToken($a['id']);
            }
            unset($a);
            $actu = $this->renderView('private/actu_partial.html.twig', [
                'actus' => $actusList,
            ]);

            if($user){
                $user->setLastLoginTo(new DateTime('now', new \DateTimeZone('Africa/Lagos')));
                $this->em->flush();

                $stories = $storyRepository->findActiveStories();
                shuffle($stories);

                return $this->render('private/index.html.twig', [
                    'theme' => $this->theme,
                    'user' => $traitementsDS->infosUser($user),
                    'contacts_user' => $traitementsDS->formatNumber(count($traitementsDS->userContacts($user))),
                    'actu' => $actu,
                    'stories' => $stories,
                ]);
            }
        }
        return $this->redirectToRoute('app_connexion');
    }

    #[Route('/admin', name: 'app_admin')]
    public function admin(CookieDS $cookieDS, UserRepository $userRepository, TraitementsDS $traitementsDS, PromotionRepository $promotionRepository, PromoReseauRepository $promoReseauRepository, UserBotRepository $userBotRepository, DeletedDSRepository $deletedDSRepository, BoostRepository $boostRepository, TransactionRepository $transactionRepository): Response
    {
        if($cookieDS->get("uid")){
            $uid = $cookieDS->get("uid");
            $user = $userRepository->findOneBy(['uid' => $uid]);

            if($user){
                $user->setLastLoginTo(new DateTime());    
                $this->em->flush();

                $usersStats      = $userRepository->getDailyStats30Days();
                $boostsStats     = $boostRepository->getDailyStats30Days();
                $promoAffStats   = $promotionRepository->getDailyStats30Days();
                $promoResStats   = $promoReseauRepository->getDailyStats30Days();

                $chartLabels     = array_keys($usersStats);
                $chartUsers      = array_values($usersStats);
                $chartBoosts     = array_values($boostsStats);
                $chartPromoAff   = array_values($promoAffStats);
                $chartPromoRes   = array_values($promoResStats);

                // Totals current period (last 30 days including today)
                $curStart = new DateTime('-29 days');
                $curEnd   = new DateTime('tomorrow');
                $prevStart = new DateTime('-59 days');
                $prevEnd  = new DateTime('-29 days');

                $totalCurUsers    = array_sum($chartUsers);
                $totalCurBoosts   = array_sum($chartBoosts);
                $totalCurPromoAff = array_sum($chartPromoAff);
                $totalCurPromoRes = array_sum($chartPromoRes);

                $totalPrevUsers    = $userRepository->countByDateRange($prevStart, $prevEnd);
                $totalPrevBoosts   = $boostRepository->countByDateRange($prevStart, $prevEnd);
                $totalPrevPromoAff = $promotionRepository->countByDateRange($prevStart, $prevEnd);
                $totalPrevPromoRes = $promoReseauRepository->countByDateRange($prevStart, $prevEnd);

                $calcVariation = function(int $cur, int $prev): float|null {
                    if ($prev === 0) return $cur > 0 ? 100.0 : null;
                    return round(($cur - $prev) / $prev * 100, 1);
                };

                $chartSummary = [
                    ['label' => 'Inscriptions utilisateurs', 'color' => '#4e73df', 'icon' => 'fas fa-users',
                     'current' => $totalCurUsers, 'previous' => $totalPrevUsers,
                     'variation' => $calcVariation($totalCurUsers, $totalPrevUsers)],
                    ['label' => 'Boost Contact', 'color' => '#f6a21e', 'icon' => 'fas fa-users-rectangle',
                     'current' => $totalCurBoosts, 'previous' => $totalPrevBoosts,
                     'variation' => $calcVariation($totalCurBoosts, $totalPrevBoosts)],
                    ['label' => 'Promotion Affaire', 'color' => '#1cc88a', 'icon' => 'fas fa-briefcase',
                     'current' => $totalCurPromoAff, 'previous' => $totalPrevPromoAff,
                     'variation' => $calcVariation($totalCurPromoAff, $totalPrevPromoAff)],
                    ['label' => 'Promo Réseau Sociaux', 'color' => '#9b59b6', 'icon' => 'fas fa-share-nodes',
                     'current' => $totalCurPromoRes, 'previous' => $totalPrevPromoRes,
                     'variation' => $calcVariation($totalCurPromoRes, $totalPrevPromoRes)],
                ];
                
                return $this->render('private/index_admin.html.twig', [
                    'theme' => $this->theme,
                    'user' => $traitementsDS->infosUser($user),
                    'contacts_user' => $traitementsDS->formatNumber(count($traitementsDS->userContacts($user))),
                    'nbr_tel_mail_no_conf' => count($userRepository->findBy(['telIsVerified' => false, 'mailIsVerified' => false])),
                    'nbr_tel_mail_yes_conf' => count($userRepository->findBy(['telIsVerified' => true, 'mailIsVerified' => true])),
                    'nbr_tel_no_conf' => count($userRepository->findBy(['telIsVerified' => false])),
                    'nbr_mail_no_conf' => count($userRepository->findBy(['mailIsVerified' => false])),
                    'affaire_valider_sans_payer' => count($promotionRepository->findBy(['status' => 2])),
                    'valid_promo_affaire' => count($promotionRepository->findBy(['status' => 1])),
                    'valid_promo_reseau' => count($promoReseauRepository->findBy(['status' => 1])),
                    'nbr_user' => count($userRepository->findAll()),
                    'nbr_user_bot' => count($userBotRepository->findAll()),
                    'deleted_users' => count($deletedDSRepository->findAll()),
                    'banned_users' => count($this->env->getUserBanned()) / 3,
                    'encour_boost' => $traitementsDS->getBoostEnCoursCount(),
                    'programmer_boost' => $traitementsDS->getAddProgrammer(),
                    'encour_affaire' => count($promotionRepository->findBy(['status' => 3])),
                    'users_prog_recomp' => count($userRepository->findBy(['isInscritProgrammeRecompense' => true])),
                    'p_aff_recomp' => count($promotionRepository->findBy(['inProgrammeRecompense' => true])),
                    'p_aff_ds_statut' => count($promotionRepository->findBy(['publishOnDressurStatus' => true])),
                    'soldeZefame' => $traitementsDS->getSoldeZefame(),
                    'userSourceCounts' => $userRepository->getRegisterSourceCounts(),
                    'promotionSourceCounts' => $promotionRepository->getSourceCounts(),
                    'promoReseauSourceCounts' => $promoReseauRepository->getSourceCounts(),
                    'boostSourceCounts' => $boostRepository->getSourceCounts(),
                    'transactionSourceCounts' => $transactionRepository->getSourceCounts(),
                    'chartLabels'   => $chartLabels,
                    'chartUsers'    => $chartUsers,
                    'chartBoosts'   => $chartBoosts,
                    'chartPromoAff' => $chartPromoAff,
                    'chartPromoRes' => $chartPromoRes,
                    'chartSummary'  => $chartSummary,
                ]);
            }
        }
        return $this->redirectToRoute('app_connexion');
    }

    #[Route('/actu', name: 'app_actu')]
    public function actu(PromotionRepository $promotionRepository): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $userinfo = $this->traitementsDS->infosUser($user);
        $actusList = json_decode($userinfo['lesPublicites'], true) ?? [];
        foreach ($actusList as &$a) {
            $a['token'] = $this->encodePromoToken($a['id']);
            $promo = $promotionRepository->find($a['id']);
            if ($promo) {
                $promo->setNombreImpression(($promo->getNombreImpression() ?? 0) + 1);
            }
        }
        unset($a);
        $this->em->flush();
        return $this->render('private/actu.html.twig', [
            'actus' => $actusList,
            'user' => $userinfo,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/actu/{token}', name: 'app_actu_detail')]
    public function actualite(string $token, PromotionRepository $promotionRepository, TraitementsDS $traitementsDS): Response
    {
        $id = $this->decodePromoToken($token);
        if ($id === null) {
            return $this->redirectToRoute('app_actu');
        }

        $user = $this->traitementsDS->getUserByUidInCookies();
        $promo = $promotionRepository->find($id);

        if (!$promo) {
            return $this->redirectToRoute('app_actu');
        }

        $promo->setNombreDeVue(($promo->getNombreDeVue() ?? 0) + 1);
        $this->em->flush();

        $descpPromo = $promo->getDescription();
        if ($promo->getTypePromotionAffaire() == "offre_emploi") {
            $descpPromo = $promo->getAnnotherInfo()["description_poste"] ?? $promo->getDescription();
        }
        if ($promo->getTypePromotionAffaire() == "dmd_emploi") {
            $descpPromo = $promo->getAnnotherInfo()["description_profil_demandeur"] ?? $promo->getDescription();
        }

        $promoData = [
            "token"                => $token,
            "image"                => $promo->getImage(),
            "description"          => $descpPromo,
            "whatsappNumber"       => $promo->getUser()->getTel(),
            "pseudoAnnonceur"      => $promo->getUser()->getPseudo(),
            "nombreDeVues"         => (string) $traitementsDS->formatNumber($promo->getNombreDeVue()),
            "nombreImpression"     => (string) $traitementsDS->formatNumber($promo->getNombreImpression()),
            "typePromotionAffaire" => $promo->getTypePromotionAffaire(),
            "annotherInfo"         => $promo->getAnnotherInfo(),
        ];

        $rawAutres = array_filter(
            $traitementsDS->getTopAffaires(10),
            fn($p) => $p['id'] !== $id
        );
        $autresPromos = array_map(function ($p) {
            $p['token'] = $this->encodePromoToken($p['id']);
            return $p;
        }, array_slice(array_values($rawAutres), 0, 3));

        return $this->render('private/actualite.html.twig', [
            'promo'        => $promoData,
            'autresPromos' => $autresPromos,
            'user'         => $traitementsDS->infosUser($user),
            'theme'        => $this->theme,
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $contacts = $this->traitementsDS->userContacts($user);
        return $this->render('private/contact.html.twig', [
            'contacts' => $contacts,
            'user' => $this->traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }


    #[Route('/newpromoreseau', name: 'app_newpromoreseau')]
    public function newpromoreseau(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        return $this->render('private/newpromoreseau.html.twig', [
            'listeMethodePaiements' => $traitementsDS->listeMethodePaiements(),
            'listSocialNetworks' => $traitementsDS->listeFormulePromoReseau(),
            'user' => $traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }

    #[Route('/listepromoreseau', name: 'app_listepromoreseau')]
    public function listepromoreseau(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        return $this->render('private/listepromoreseau.html.twig', [
            'listepromoreseau' => $traitementsDS->userPromoReseaus($user->getPromoReseaus()),
            'user' => $traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }

    #[Route('/newpromoaffaire', name: 'app_newpromoaffaire')]
    public function newpromoaffaire(TraitementsDS $traitementsDS, SessionDS $sessionDS, FormulePromoAffaireRepository $formulePromoAffaireRepository): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        return $this->render('private/newpromoaffaire.html.twig', [
            'listeMethodePaiements' => $traitementsDS->listeMethodePaiements(),
            'formuleBoosts' => $formulePromoAffaireRepository->findBy(['activated' => true]),
            'user' => $traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }

    #[Route('/listepromoaffaire', name: 'app_listepromoaffaire')]
    public function listepromoaffaire(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        return $this->render('private/listepromoaffaire.html.twig', [
            'listeMethodePaiements' => $traitementsDS->listeMethodePaiements(),
            'listepromoaffaire' => $traitementsDS->userPromos($user->getPromotions()),
            'listeFormulBoost' => $traitementsDS->listeFormulePromoAffaire(),
            'user' => $traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }

    #[Route('/accepterSansSuite', name: 'app_accepterSansSuite')]
    public function accepterSansSuite(TraitementsDS $traitementsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        return $this->render('private/accepterSansSuite.html.twig', [
            'accepterSansSuite' => $traitementsDS->userPromos($promotionRepository->findBy(['status' => 2])),
            'listeFormulBoost' => $traitementsDS->listeFormulePromoAffaire(),
            'user' => $traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }

    #[Route('/editprofil', name: 'app_editprofil')]
    public function editprofil(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        return $this->render('private/editprofil.html.twig', [
            'user' => $traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }

    #[Route('/partagerDressur', name: 'app_partagerDressur')]
    public function partagerDressur(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        return $this->render('private/partagerDressur.html.twig', [
            'user' => $traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }

    #[Route('/editPassword', name: 'app_editPassword')]
    public function editPassword(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        return $this->render('private/editPassword.html.twig', [
            'user' => $traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }

    #[Route('/invitezVosAmis', name: 'app_invitezVosAmis')]
    public function invitezVosAmis(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        return $this->render('private/invitezVosAmis.html.twig', [
            'user' => $traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }

    #[Route('/newboostcontact', name: 'app_newboostcontact')]
    public function newboostcontact(TraitementsDS $traitementsDS, SessionDS $sessionDS, FormuleBoostRepository $formuleBoostRepository): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        return $this->render('private/newboostcontact.html.twig', [
            'listeMethodePaiements' => $traitementsDS->listeMethodePaiements(),
            'formuleBoosts' => $formuleBoostRepository->findAll(),
            'user' => $traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }

    #[Route('/listeboostcontact', name: 'app_listeboostcontact')]
    public function listeboostcontact(TraitementsDS $traitementsDS, SessionDS $sessionDS, BoostRepository $boostRepository): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        return $this->render('private/listeboostcontact.html.twig', [
            'user' => $traitementsDS->infosUser($user),
            'lesBoostContact' => $traitementsDS->userBoosts($boostRepository->findBy(['user' => $user])),
            'theme' => $this->theme,
        ]);
    }

    #[Route('/addSuggestion', name: 'app_addSuggestion')]
    public function addSuggestion(TraitementsDS $traitementsDS, SessionDS $sessionDS, BoostRepository $boostRepository): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        return $this->render('private/addSuggestion.html.twig', [
            'user' => $traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }

    #[Route('/signalerUser', name: 'app_signalerUser')]
    public function signalerUser(TraitementsDS $traitementsDS, SessionDS $sessionDS, BoostRepository $boostRepository): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        return $this->render('private/signalerUser.html.twig', [
            'user' => $traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }

    #[Route('/preferencePays', name: 'app_preferencePays')]
    public function preferencePays(TraitementsDS $traitementsDS): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        return $this->render('private/preferencePays.html.twig', [
            'user' => $traitementsDS->infosUser($user),
            'paysChoisieJson' => $user->getPreference()->getPaysChoisies(),
            'countryCodes' => [
                '93' => 'Afghanistan',
                '355' => 'Albanie',
                '213' => 'Algérie',
                '376' => 'Andorre',
                '244' => 'Angola',
                '1264' => 'Anguilla',
                '1268' => 'Antigua-et-Barbuda',
                '54' => 'Argentine',
                '374' => 'Arménie',
                '297' => 'Aruba',
                '247' => 'Ascension',
                '61' => 'Australie',
                '43' => 'Autriche',
                '994' => 'Azerbaïdjan',
                '1242' => 'Bahamas',
                '973' => 'Bahreïn',
                '880' => 'Bangladesh',
                '1246' => 'Barbade',
                '375' => 'Biélorussie',
                '32' => 'Belgique',
                '501' => 'Belize',
                '229' => 'Bénin',
                '1441' => 'Bermudes',
                '975' => 'Bhoutan',
                '591' => 'Bolivie',
                '387' => 'Bosnie-Herzégovine',
                '267' => 'Botswana',
                '55' => 'Brésil',
                '246' => 'Territoire britannique de l\'océan Indien',
                '1284' => 'Îles Vierges britanniques',
                '673' => 'Brunéi Darussalam',
                '359' => 'Bulgarie',
                '226' => 'Burkina Faso',
                '257' => 'Burundi',
                '855' => 'Cambodge',
                '237' => 'Cameroun',
                '1' => 'Canada',
                '238' => 'Cap-Vert',
                '1345' => 'Îles Caïmans',
                '236' => 'République centrafricaine',
                '235' => 'Tchad',
                '64' => 'Nouvelle-Zélande',
                '56' => 'Chili',
                '86' => 'Chine',
                '57' => 'Colombie',
                '269' => 'Comores',
                '242' => 'République du Congo',
                '243' => 'République démocratique du Congo',
                '682' => 'Îles Cook',
                '506' => 'Costa Rica',
                '385' => 'Croatie',
                '53' => 'Cuba',
                '599' => 'Curaçao',
                '357' => 'Chypre',
                '420' => 'République tchèque',
                '45' => 'Danemark',
                '253' => 'Djibouti',
                '1767' => 'Dominique',
                '1809' => 'République dominicaine',
                '1829' => 'République dominicaine',
                '1849' => 'République dominicaine',
                '593' => 'Équateur',
                '20' => 'Égypte',
                '503' => 'El Salvador',
                '240' => 'Guinée équatoriale',
                '291' => 'Érythrée',
                '372' => 'Estonie',
                '251' => 'Éthiopie',
                '500' => 'Îles Falkland',
                '298' => 'Îles Féroé',
                '679' => 'Fidji',
                '358' => 'Finlande',
                '33' => 'France',
                '594' => 'Guyane française',
                '689' => 'Polynésie française',
                '241' => 'Gabon',
                '220' => 'Gambie',
                '995' => 'Géorgie',
                '49' => 'Allemagne',
                '233' => 'Ghana',
                '350' => 'Gibraltar',
                '30' => 'Grèce',
                '299' => 'Groenland',
                '1473' => 'Grenade',
                '590' => 'Guadeloupe',
                '1671' => 'Guam',
                '502' => 'Guatemala',
                '44' => 'Royaume-Uni',
                '224' => 'Guinée',
                '245' => 'Guinée-Bissau',
                '592' => 'Guyana',
                '509' => 'Haïti',
                '504' => 'Honduras',
                '852' => 'Hong Kong',
                '36' => 'Hongrie',
                '354' => 'Islande',
                '91' => 'Inde',
                '62' => 'Indonésie',
                '98' => 'Iran',
                '964' => 'Irak',
                '353' => 'Irlande',
                '972' => 'Israël',
                '39' => 'Italie',
                '225' => 'Côte d\'Ivoire',
                '1876' => 'Jamaïque',
                '81' => 'Japon',
                '962' => 'Jordanie',
                '7' => 'Kazakhstan',
                '254' => 'Kenya',
                '686' => 'Kiribati',
                '965' => 'Koweït',
                '996' => 'Kirghizistan',
                '856' => 'Laos',
                '371' => 'Lettonie',
                '961' => 'Liban',
                '266' => 'Lesotho',
                '231' => 'Libéria',
                '218' => 'Libye',
                '423' => 'Liechtenstein',
                '370' => 'Lituanie',
                '352' => 'Luxembourg',
                '853' => 'Macau',
                '389' => 'Macédoine',
                '261' => 'Madagascar',
                '265' => 'Malawi',
                '60' => 'Malaisie',
                '960' => 'Maldives',
                '223' => 'Mali',
                '356' => 'Malte',
                '692' => 'Îles Marshall',
                '596' => 'Martinique',
                '222' => 'Mauritanie',
                '230' => 'Maurice',
                '262' => 'Mayotte',
                '52' => 'Mexique',
                '691' => 'Micronésie',
                '373' => 'Moldavie',
                '377' => 'Monaco',
                '976' => 'Mongolie',
                '382' => 'Monténégro',
                '1664' => 'Montserrat',
                '212' => 'Maroc',
                '258' => 'Mozambique',
                '95' => 'Myanmar',
                '264' => 'Namibie',
                '674' => 'Nauru',
                '977' => 'Népal',
                '31' => 'Pays-Bas',
                '687' => 'Nouvelle-Calédonie',
                '505' => 'Nicaragua',
                '227' => 'Niger',
                '234' => 'Nigeria',
                '683' => 'Niue',
                '672' => 'Île Norfolk',
                '850' => 'Corée du Nord',
                '1670' => 'Îles Mariannes du Nord',
                '47' => 'Norvège',
                '968' => 'Oman',
                '92' => 'Pakistan',
                '680' => 'Palaos',
                '970' => 'Palestine',
                '507' => 'Panama',
                '675' => 'Papouasie-Nouvelle-Guinée',
                '595' => 'Paraguay',
                '51' => 'Pérou',
                '63' => 'Philippines',
                '48' => 'Pologne',
                '351' => 'Portugal',
                '1787' => 'Porto Rico',
                '1939' => 'Porto Rico',
                '974' => 'Qatar',
                '40' => 'Roumanie',
                '250' => 'Rwanda',
                '290' => 'Sainte-Hélène',
                '1869' => 'Saint-Kitts-et-Nevis',
                '1758' => 'Sainte-Lucie',
                '508' => 'Saint-Pierre-et-Miquelon',
                '1784' => 'Saint-Vincent-et-les-Grenadines',
                '685' => 'Samoa',
                '378' => 'Saint-Marin',
                '239' => 'Sao Tomé-et-Principe',
                '966' => 'Arabie saoudite',
                '221' => 'Sénégal',
                '381' => 'Serbie',
                '248' => 'Seychelles',
                '232' => 'Sierra Leone',
                '65' => 'Singapour',
                '1721' => 'Saint-Martin (partie néerlandaise)',
                '421' => 'Slovaquie',
                '386' => 'Slovénie',
                '677' => 'Îles Salomon',
                '252' => 'Somalie',
                '27' => 'Afrique du Sud',
                '82' => 'Corée du Sud',
                '211' => 'Soudan du Sud',
                '34' => 'Espagne',
                '94' => 'Sri Lanka',
                '249' => 'Soudan',
                '597' => 'Suriname',
                '4779' => 'Svalbard et Jan Mayen',
                '268' => 'Swaziland',
                '46' => 'Suède',
                '41' => 'Suisse',
                '963' => 'Syrie',
                '886' => 'Taïwan',
                '992' => 'Tadjikistan',
                '255' => 'Tanzanie',
                '66' => 'Thaïlande',
                '670' => 'Timor oriental',
                '228' => 'Togo',
                '690' => 'Tokelau',
                '676' => 'Tonga',
                '1868' => 'Trinité-et-Tobago',
                '216' => 'Tunisie',
                '90' => 'Turquie',
                '993' => 'Turkménistan',
                '1649' => 'Îles Turques-et-Caïques',
                '688' => 'Tuvalu',
                '1340' => 'Îles Vierges américaines',
                '256' => 'Ouganda',
                '380' => 'Ukraine',
                '971' => 'Émirats arabes unis',
                '598' => 'Uruguay',
                '998' => 'Ouzbékistan',
                '678' => 'Vanuatu',
                '379' => 'Cité du Vatican',
                '58' => 'Venezuela',
                '84' => 'Vietnam',
                '681' => 'Wallis-et-Futuna',
                '967' => 'Yémen',
                '260' => 'Zambie',
                '263' => 'Zimbabwe',
            ],
            'theme' => $this->theme,
        ]);
    }

    #[Route('/centreInteret', name: 'app_centreInteret')]
    public function centreInteret(TraitementsDS $traitementsDS, SessionDS $sessionDS, BoostRepository $boostRepository): Response
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        return $this->render('private/centreInteret.html.twig', [
            'user' => $traitementsDS->infosUser($user),
            'theme' => $this->theme,
        ]);
    }
}
