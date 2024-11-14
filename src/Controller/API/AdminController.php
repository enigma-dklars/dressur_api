<?php

namespace App\Controller\API;

use App\Entity\User;
use FedaPay\FedaPay;
use FedaPay\Transaction;
use App\Services\SessionDS;
use App\Entity\CampagneMail;
use App\Entity\Promotion;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\BoostRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FormuleBoostRepository;
use App\Repository\PromotionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Transaction as EntityTransaction;
use App\Repository\CampagneMailRepository;
use App\Repository\FormuleCampagneMailRepository;
use App\Repository\PromoReseauRepository;
use App\Repository\UserRepository;
use App\Utilities\SendMail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]

class AdminController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1); 
    }

    #[Route('/sendMailToDressur', name: 'sendMailToDressur')]
    public function sendMailToDressur(Request $request, SendMail $sendMail): Response
    {
        $objet = $request->get("objet");
        $name = $request->get("name");
        $email = $request->get("email");
        $message = $request->get("message");

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Response("<b>$email</b> n'est pas une adresse e-mail valide.");
        }

        $html = $this->renderView('emails/contactMail.html.twig',[
            "objet" => $objet,
            "name" => $name,
            "email" => $email,
            "message" => $message,
        ]);

        try {
            $sendMail->smtpMail(
                "dressur.ds@gmail.com", 
                "Page Contact Web Dressur",
                $html,
                $email,
                "Message From Web No ".time(), 
            );
            
            return new JsonResponse([
                'error' => false,
            ]);
        } catch (\Throwable $th) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Mail non envoyer.",
            ]);
        }

        return new JsonResponse([
            'error' => true,
            'titre' => 'Erreur!',
            'message' => "Erreur de traitement.",
        ]);
    }
}