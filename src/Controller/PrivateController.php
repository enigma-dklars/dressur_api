<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PrivateController extends AbstractController
{
    #[Route('/private', name: 'app_private')]
    public function index(): Response
    {
        return $this->render('private/index.html.twig', [
            'controller_name' => 'PrivateController',
        ]);
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
