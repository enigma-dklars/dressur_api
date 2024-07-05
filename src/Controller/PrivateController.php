<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PrivateController extends AbstractController
{
    #[Route('/logout', name: 'app_logout')]
    public function logout(CookieDS $cookieDS, UserRepository $userRepository, TraitementsDS $traitementsDS): Response
    {
        $cookieDS->remove("uid");
        return $this->redirectToRoute('app_connexion');
    }

    #[Route('/private', name: 'app_private')]
    public function index(CookieDS $cookieDS, UserRepository $userRepository, TraitementsDS $traitementsDS): Response
    {
        if($cookieDS->get("uid")){
            $uid = $cookieDS->get("uid");
            $user = $userRepository->findOneBy(['uid' => $uid]);
            if($user){
                return $this->render('private/index.html.twig', [
                    'user' => $traitementsDS->infosUser($user),
                ]);
            }
        }
        return $this->redirectToRoute('app_connexion');
    }

    #[Route('/actu', name: 'app_actu')]
    public function actu(): Response
    {
        $html = $this->renderView('private/actu.html.twig', [
            'controller_name' => 'PrivateController',
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }
}
