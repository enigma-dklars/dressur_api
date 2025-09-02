<?php

namespace App\Controller;

use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Controller\PrivateController;
use App\Repository\FormuleBoostRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\FormuleDressurBotRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\FormuleCampagneMailRepository;
use App\Repository\FormulePromoAffaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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

    #[Route('/tarifs', name: 'app_tarifs')]
    public function tarifs(FormuleBoostRepository $formuleBoostRepository, FormuleCampagneMailRepository $formuleCampagneMailRepository, FormuleDressurBotRepository $formuleDressurBotRepository, FormulePromoAffaireRepository $formulePromoAffaireRepository, FormulePromoReseauRepository $formulePromoReseauRepository): Response
    {
        return $this->render('public/tarifs.html.twig', [
            'controller_name' => 'PublicController',
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
            'formule_boosts' => $formuleBoostRepository->findAll(),
            'formule_campagne_mails' => $formuleCampagneMailRepository->findAll(),
            'formule_dressur_bots' => $formuleDressurBotRepository->findAll(),
            'formule_promo_affaires' => $formulePromoAffaireRepository->findAll(),
            'formule_promo_reseaus' => $formulePromoReseauRepository->findBy([], ['parent' => 'ASC']),
        ]);
    }

    #[Route('/actualite', name: 'app_actualite')]
    public function actualite(TraitementsDS $traitementsDS): Response
    {
        return $this->render('public/actualite.html.twig', [
            'actus' => $traitementsDS->getAffaires(90),
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/dressur-bot', name: 'app_dressur_bot')]
    public function dressur_bot(): Response
    {
        return $this->render('public/dressur_bot.html.twig', [
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
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

    #[Route('/campagne-mail', name: 'app_campagne_mail')]
    public function campagne_mail(): Response
    {
        return $this->render('public/campagne_mail.html.twig', [
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/promotion-affaire', name: 'app_promotion_affaire')]
    public function promotion_affaire(): Response
    {
        return $this->render('public/promotion_affaire.html.twig', [
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/carte-visite-numerique', name: 'app_carte_visite_numerique')]
    public function carte_visite_numerique(): Response
    {
        return $this->render('public/carte_visite_numerique.html.twig', [
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }

    #[Route('/promotion-reseaux-sociaux', name: 'app_promotion_reseaux_sociaux')]
    public function promotion_reseau_sociaux(): Response
    {
        return $this->render('public/promotion_reseaux_sociaux.html.twig', [
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }
}
