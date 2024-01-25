<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EssaieController extends AbstractController
{
    #[Route('/essaie', name: 'app_essaie')]
    public function index(): Response
    {
        // return $this->render('emails/verif_mail.html.twig', [
        // return $this->render('emails/passe_4got_mail.html.twig', [
        // return $this->render('emails/pass_edit_mail.html.twig', [
        return $this->render('emails/bienvenu_mail.html.twig', [
            'controller_name' => 'EssaieController',
        ]);
    }
}
