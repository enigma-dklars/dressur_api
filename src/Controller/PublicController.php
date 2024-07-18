<?php

namespace App\Controller;

use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

    #[Route('/actualite', name: 'app_actualite')]
    public function actualite(TraitementsDS $traitementsDS): Response
    {
        return $this->render('public/actualite.html.twig', [
            'actus' => $traitementsDS->getAffaires(),
            'is_connect' => $this->is_connect,
            'theme' => $this->theme,
        ]);
    }
}
