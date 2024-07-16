<?php

namespace App\Controller;

use App\Services\TraitementsDS;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicController extends AbstractController
{
    #[Route('/', name: 'app_public')]
    public function index(TraitementsDS $traitementsDS): Response
    {
        return $this->render('public/index.html.twig', [
            'actus' => $traitementsDS->getTopAffaires(6),
        ]);
    }

    #[Route('/inscription', name: 'app_inscription')]
    public function inscription(): Response
    {
        return $this->render('public/register.html.twig', [
            'controller_name' => 'PublicController',
        ]);
    }

    #[Route('/connexion', name: 'app_connexion')]
    public function connexion(PrivateController $privateController): Response
    {
        if($privateController->getUserByUidInCookies()){
            return $this->redirectToRoute('app_private');
        }
        return $this->render('public/login.html.twig', [
            'controller_name' => 'PublicController',
        ]);
    }

    #[Route('/mot-de-passe-oublier', name: 'app_mot_de_passe_oublier')]
    public function mot_de_passe_oublier(): Response
    {
        return $this->render('public/passe4get.html.twig', [
            'controller_name' => 'PublicController',
        ]);
    }

    #[Route('/contacts', name: 'app_contactez_nous')]
    public function contactez_nous(): Response
    {
        return $this->render('public/contactez_nous.html.twig', [
            'controller_name' => 'PublicController',
        ]);
    }

    #[Route('/actualite', name: 'app_actualite')]
    public function actualite(TraitementsDS $traitementsDS): Response
    {
        return $this->render('public/actualite.html.twig', [
            'actus' => $traitementsDS->getAffaires(),
        ]);
    }
}
