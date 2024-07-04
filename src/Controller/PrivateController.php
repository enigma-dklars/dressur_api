<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
