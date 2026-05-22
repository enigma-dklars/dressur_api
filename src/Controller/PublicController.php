<?php

namespace App\Controller;

use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Controller\PrivateController;
use App\Repository\FormuleBoostRepository;
use App\Repository\PromotionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\FormuleDressurBotRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\FormulePromoAffaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PublicController extends AbstractController
{
    private $is_connect;
    private $theme;
    private $traitementsDS;
    public function __construct(CookieDS $cookieDS, TraitementsDS $traitementsDS)
    {
        $this->traitementsDS = $traitementsDS;
        $this->is_connect = $cookieDS->check("uid") ? "oui" : "non";
        if($cookieDS->check("theme")) {
            if($cookieDS->get("theme") == "dark-theme") {
                $this->theme = "dark-theme";
            } else {
                $this->theme = "light-theme";
            }
        } else {
            $this->theme = "light-theme";
        }
        $this->is_connect = $cookieDS->check("uid") ? "oui" : "non";
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

    #[Route('/', name: 'app_public')]
    public function index(TraitementsDS $traitementsDS): Response
    {
        return $this->render('public/index.html.twig', [
            'actus' => $traitementsDS->getTopAffaires(6),
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/inscription', name: 'app_inscription')]
    public function inscription(): Response
    {
        return $this->render('public/register.html.twig', [
            'controller_name' => 'PublicController',
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/connexion', name: 'app_connexion')]
    public function connexion(PrivateController $privateController): Response
    {
        if($this->traitementsDS->getUserByUidInCookies()){
            return $this->redirectToRoute('app_private');
        }
        return $this->render('public/login.html.twig', [
            'controller_name' => 'PublicController',
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/mot-de-passe-oublier', name: 'app_mot_de_passe_oublier')]
    public function mot_de_passe_oublier(): Response
    {
        return $this->render('public/passe4get.html.twig', [
            'controller_name' => 'PublicController',
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/contacts', name: 'app_contactez_nous')]
    public function contactez_nous(): Response
    {
        return $this->render('public/contactez_nous.html.twig', [
            'controller_name' => 'PublicController',
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/politique-confidentialite', name: 'politique_confidentialite')]
    public function politiqueConfidentialite(): Response
    {
        return $this->render('public/politique_confidentialite.html.twig', [
            'controller_name' => 'PublicController',
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/conditions-utilisation', name: 'app_conditions_utilisation')]
    public function conditionsUtilisation(): Response
    {
        return $this->render('public/conditions_utilisation.html.twig', [
            'controller_name' => 'PublicController',
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/tarifs', name: 'app_tarifs')]
    public function tarifs(FormuleBoostRepository $formuleBoostRepository, FormuleDressurBotRepository $formuleDressurBotRepository, FormulePromoAffaireRepository $formulePromoAffaireRepository, FormulePromoReseauRepository $formulePromoReseauRepository): Response
    {
        return $this->render('public/tarifs.html.twig', [
            'controller_name' => 'PublicController',
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
            'formule_boosts' => $formuleBoostRepository->findAll(),
            'formule_dressur_bots' => $formuleDressurBotRepository->findBy(['activated' => true]),
            'formule_promo_affaires' => $formulePromoAffaireRepository->findBy(['activated' => true]),
            'formule_promo_reseaus' => $formulePromoReseauRepository->findBy([], ['parent' => 'ASC']),
        ]);
    }

    #[Route('/actualite', name: 'app_actualite')]
    public function actualite(TraitementsDS $traitementsDS): Response
    {
        $rawActus = $traitementsDS->getAffaires(90);
        $total = count($rawActus);
        $actus = array_map(function ($a) {
            $a['token'] = $this->encodePromoToken($a['id']);
            return $a;
        }, array_slice($rawActus, 0, 12));
        return $this->render('public/actualite.html.twig', [
            'actus' => $actus,
            'total' => $total,
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/actualite/more', name: 'app_actualite_more')]
    public function actualiteMore(Request $request, TraitementsDS $traitementsDS): Response
    {
        $offset = max(0, (int) $request->query->get('offset', 0));
        $limit  = 12;

        $rawActus = $traitementsDS->getAffaires(90);
        $total    = count($rawActus);
        $actus    = array_map(function ($a) {
            $a['token'] = $this->encodePromoToken($a['id']);
            return $a;
        }, array_slice($rawActus, $offset, $limit));

        $hasMore = $total > $offset + $limit;
        $html    = $this->renderView('public/actualite_cards.html.twig', ['actus' => $actus]);

        return new Response($html, 200, [
            'X-Has-More'   => $hasMore ? '1' : '0',
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    #[Route('/actualite/{token}', name: 'app_actualite_detail')]
    public function actualiteDetail(string $token, PromotionRepository $promotionRepository, TraitementsDS $traitementsDS, EntityManagerInterface $em): Response
    {
        $id = $this->decodePromoToken($token);
        if ($id === null) {
            return $this->redirectToRoute('app_actualite');
        }

        $promo = $promotionRepository->find($id);
        if (!$promo) {
            return $this->redirectToRoute('app_actualite');
        }

        $promo->setNombreDeVue(($promo->getNombreDeVue() ?? 0) + 1);
        $em->flush();

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
            "isFakeVue"            => $promo->getIsFakeVue(),
            "status"               => $promo->getStatus(),
            "datePublished"        => $promo->getDateDebut() ? $promo->getDateDebut()->format('Y-m-d') : (new \DateTime())->format('Y-m-d'),
        ];

        $rawAutres = array_filter(
            $traitementsDS->getTopAffaires(10),
            fn($p) => $p['id'] !== $id
        );
        $autresPromos = array_map(function ($p) {
            $p['token'] = $this->encodePromoToken($p['id']);
            return $p;
        }, array_slice(array_values($rawAutres), 0, 3));

        return $this->render('public/actualite_detail.html.twig', [
            'promo'        => $promoData,
            'autresPromos' => $autresPromos,
            'is_connect'   => $this->is_connect,
            'theme'        => $this->theme,
        ]);
    }

    #[Route('/dressur-bot', name: 'app_dressur_bot')]
    public function dressur_bot(FormuleDressurBotRepository $formuleDressurBotRepository): Response
    {
        $prices = array_map(fn($f) => $f->getPrix(), $formuleDressurBotRepository->findAll());
        sort($prices);
        return $this->render('public/dressur_bot.html.twig', [
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
            'min_price' => !empty($prices) ? (int) $prices[0] : 0,
        ]);
    }

    #[Route('/boost-contact', name: 'app_boost_contact')]
    public function boost_contact(): Response
    {
        return $this->render('public/boost_contact.html.twig', [
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/promotion-affaire', name: 'app_promotion_affaire')]
    public function promotion_affaire(FormulePromoAffaireRepository $formulePromoAffaireRepository): Response
    {
        $prices = array_map(fn($f) => $f->getPrix(), $formulePromoAffaireRepository->findBy(['activated' => true]));
        sort($prices);
        return $this->render('public/promotion_affaire.html.twig', [
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
            'min_price' => !empty($prices) ? (int) $prices[0] : 0,
        ]);
    }

    #[Route('/promotion-reseaux-sociaux', name: 'app_promotion_reseaux_sociaux')]
    public function promotion_reseau_sociaux(FormulePromoReseauRepository $formulePromoReseauRepository): Response
    {
        $formulas = array_filter($formulePromoReseauRepository->findAll(), fn($f) => $f->getPrix() > 0 && $f->isAvailable());
        $prices = array_map(fn($f) => (int) round($f->getPrix() * 1.2 * 1.7 * 700), $formulas);
        sort($prices);
        return $this->render('public/promotion_reseaux_sociaux.html.twig', [
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
            'min_price' => !empty($prices) ? $prices[0] : 0,
        ]);
    }

    #[Route('/services', name: 'app_services')]
    public function services(
        FormulePromoAffaireRepository $formulePromoAffaireRepository,
        FormulePromoReseauRepository  $formulePromoReseauRepository
    ): Response {
        $affairePrices = array_map(fn($f) => $f->getPrix(), $formulePromoAffaireRepository->findBy(['activated' => true]));
        sort($affairePrices);

        $reseauFormulas = array_filter($formulePromoReseauRepository->findAll(), fn($f) => $f->getPrix() > 0 && $f->isAvailable());
        $reseauPrices   = array_map(fn($f) => (int) round($f->getPrix() * 1.2 * 1.7 * 700), $reseauFormulas);
        sort($reseauPrices);

        return $this->render('public/services.html.twig', [
            'is_connect'        => $this->is_connect,
            'theme'             => $this->theme,
            'min_price_affaire' => !empty($affairePrices) ? (int) $affairePrices[0] : 0,
            'min_price_reseau'  => !empty($reseauPrices)  ? $reseauPrices[0]        : 0,
        ]);
    }

    #[Route('/sitemap.xml', name: 'app_sitemap', defaults: ['_format' => 'xml'])]
    public function sitemap(PromotionRepository $promotionRepository, CacheInterface $cache): Response
    {
        $xml = $cache->get('sitemap_xml', function (ItemInterface $item) use ($promotionRepository) {
            $item->expiresAfter(86400); // 24 heures

            $rawPromos = $promotionRepository->findForSitemap();
            $promos = array_map(function ($promo) {
                return ['token' => $this->encodePromoToken($promo->getId())];
            }, $rawPromos);

            return $this->renderView('sitemap.xml.twig', ['promos' => $promos]);
        });

        return new Response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
