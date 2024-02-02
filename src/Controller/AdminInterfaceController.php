<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminInterfaceController extends AbstractController
{
    #[Route('/admin/interface', name: 'app_admin_interface')]
    public function index(): Response
    {
        return $this->render('admin_interface/index.html.twig', [
            'controller_name' => 'AdminInterfaceController',
        ]);
    }
}
