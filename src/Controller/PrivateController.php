<?php

namespace App\Controller;

use App\Controller\API\UserController;
use App\Repository\BoostRepository;
use App\Repository\DSBonusHistoriqueRepository;
use App\Repository\EnvRepository;
use App\Repository\FormuleBoostRepository;
use App\Repository\FormuleCampagneMailRepository;
use App\Repository\PromotionRepository;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
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

    public function __construct(EntityManagerInterface $em, EnvRepository $env, UserRepository $userRepository, CookieDS $cookieDS, TraitementsDS $traitementsDS)
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

    function getUserByUidInCookies(){
        if($this->cookieDS->get("uid")){
            $uid = $this->cookieDS->get("uid");
            $user = $this->userRepository->findOneBy(['uid' => $uid]);
            if($user){
                return $user;
            }
        }
        return false;
    }

    #[Route('/export_vcf', name: 'app_export_vcf')]
    public function export_vcf(): Response
    {
        $user = $this->getUserByUidInCookies();
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
                fwrite($handle, "FN:" . $contact['nom'] . "\n");
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
        $user = $this->getUserByUidInCookies();
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
                fputcsv($handle, [$contact['nom'], $contact['mail'], $contact['tel']]);
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
    public function index(CookieDS $cookieDS, UserRepository $userRepository, TraitementsDS $traitementsDS, PromotionRepository $promotionRepository): Response
    {
        if($cookieDS->get("uid")){
            $uid = $cookieDS->get("uid");
            $user = $userRepository->findOneBy(['uid' => $uid]);
            $count = $traitementsDS->vuesImpressionsCumulerUserPromos($user->getPromotions());
            if($user){
                return $this->render('private/index.html.twig', [
                    'theme' => $this->theme,
                    'user' => $traitementsDS->infosUser($user),
                    'bonus_user' => $traitementsDS->formatNumber($user->getSoldeBonus()),
                    'contacts_user' => $traitementsDS->formatNumber(count($traitementsDS->userContacts($user))),
                    'countVues' => $traitementsDS->formatNumber($count['countVues']),
                    'countImpressions' => $traitementsDS->formatNumber($count['countImpressions']),
                    'top_trois_affaires' => $traitementsDS->getTopAffaires(3),
                ]);
            }
        }
        return $this->redirectToRoute('app_connexion');
    }

    #[Route('/actu', name: 'app_actu')]
    public function actu(): Response
    {
        $user = $this->getUserByUidInCookies();
        $userinfo = $this->traitementsDS->infosUser($user);
        $html = $this->renderView('private/actu.html.twig', [
            'actus' => json_decode($userinfo['lesPublicites']),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        $user = $this->getUserByUidInCookies();
        $contacts = $this->traitementsDS->userContacts($user);
        $html = $this->renderView('private/contact.html.twig', [
            'contacts' => $contacts,
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/newcampagemail', name: 'app_newcampagemail')]
    public function newcampagemail(FormuleCampagneMailRepository $formuleCampagneMailRepository, TraitementsDS $traitementsDS): Response
    {
        $user = $this->getUserByUidInCookies();
        // dd($formuleCampageMails);
        $html = $this->renderView('private/newcampagemail.html.twig', [
            'formuleCampageMails' => $formuleCampagneMailRepository->findAll(),
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/listecampagemail', name: 'app_listecampagemail')]
    public function listecampagemail(FormuleCampagneMailRepository $formuleCampagneMailRepository, TraitementsDS $traitementsDS): Response
    {
        $user = $this->getUserByUidInCookies();
        // dd($traitementsDS->userCampagneMail($user->getCampagneMails()));
        $html = $this->renderView('private/listecampagemail.html.twig', [
            'campagemails' => $traitementsDS->userCampagneMail($user->getCampagneMails()),
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/newpromoreseau', name: 'app_newpromoreseau')]
    public function newpromoreseau(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/newpromoreseau.html.twig', [
            'listSocialNetworks' => $traitementsDS->listeFormulePromoReseau(),
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/listepromoreseau', name: 'app_listepromoreseau')]
    public function listepromoreseau(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/listepromoreseau.html.twig', [
            'listepromoreseau' => $traitementsDS->userPromoReseaus($user->getPromoReseaus()),
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/newpromoaffaire', name: 'app_newpromoaffaire')]
    public function newpromoaffaire(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/newpromoaffaire.html.twig', [
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/listepromoaffaire', name: 'app_listepromoaffaire')]
    public function listepromoaffaire(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/listepromoaffaire.html.twig', [
            'listepromoaffaire' => $traitementsDS->userPromos($user->getPromotions()),
            'listeFormulBoost' => $traitementsDS->listeFormulBoost(),
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/editprofil', name: 'app_editprofil')]
    public function editprofil(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/editprofil.html.twig', [
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/editPassword', name: 'app_editPassword')]
    public function editPassword(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/editPassword.html.twig', [
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/invitezVosAmis', name: 'app_invitezVosAmis')]
    public function invitezVosAmis(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/invitezVosAmis.html.twig', [
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/newboostcontact', name: 'app_newboostcontact')]
    public function newboostcontact(TraitementsDS $traitementsDS, SessionDS $sessionDS, FormuleBoostRepository $formuleBoostRepository): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/newboostcontact.html.twig', [
            'formuleBoosts' => $formuleBoostRepository->findAll(),
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/listeboostcontact', name: 'app_listeboostcontact')]
    public function listeboostcontact(TraitementsDS $traitementsDS, SessionDS $sessionDS, BoostRepository $boostRepository): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/listeboostcontact.html.twig', [
            'user' => $traitementsDS->infosUser($user),
            'lesBoostContact' => $traitementsDS->userBoosts($boostRepository->findBy(['user' => $user]))
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/listeBonusRecu', name: 'app_listeBonusRecu')]
    public function listeBonusRecu(TraitementsDS $traitementsDS, SessionDS $sessionDS, BoostRepository $boostRepository, DSBonusHistoriqueRepository $dSBonusHistoriqueRepository): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/listeBonusRecu.html.twig', [
            'user' => $traitementsDS->infosUser($user),
            'lesBonus' => $traitementsDS->bonusTab($dSBonusHistoriqueRepository->findBy(['user' => $user], ['id' => "DESC"]))
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/addSuggestion', name: 'app_addSuggestion')]
    public function addSuggestion(TraitementsDS $traitementsDS, SessionDS $sessionDS, BoostRepository $boostRepository): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/addSuggestion.html.twig', [
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/signalerUser', name: 'app_signalerUser')]
    public function signalerUser(TraitementsDS $traitementsDS, SessionDS $sessionDS, BoostRepository $boostRepository): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/signalerUser.html.twig', [
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/preferencePays', name: 'app_preferencePays')]
    public function preferencePays(TraitementsDS $traitementsDS): Response
    {
        $user = $this->getUserByUidInCookies();
        $html = $this->renderView('private/preferencePays.html.twig', [
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
            ]
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/centreInteret', name: 'app_centreInteret')]
    public function centreInteret(TraitementsDS $traitementsDS, SessionDS $sessionDS, BoostRepository $boostRepository): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/centreInteret.html.twig', [
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }
}