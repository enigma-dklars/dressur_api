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

    #[Route('/traitementAdmin', name: 'traitementAdmin', methods: ['POST', "GET"])]
    public function traitementAdmin(CampagneMailRepository $campagneMailRepository, PromotionRepository $promotionRepository, UserRepository $userRepository): Response
    {
        $traitementAdmin = [];
        if(count($campagneMailRepository->findBy(['status' => 1])) >= 1) { array_push($traitementAdmin, "Campage Mail En Attente");}
        if(count($promotionRepository->findBy(['status' => 1])) >= 1) { array_push($traitementAdmin, "Promotion Affaire En Attente");}
        if(count($userRepository->findBy(['telIsVerified' => false, 'blocked' => false])) >= 1) { array_push($traitementAdmin, "Validation Numéro En Attente");}
        return new JsonResponse($traitementAdmin);
    }

    #[Route('/adminListCampagneMail', name: 'adminListCampagneMail', methods: ['POST', "GET"])]
    public function adminListCampagneMail(TraitementsDS $traitementsDS, SessionDS $sessionDS, CampagneMailRepository $campagneMailRepository): Response
    {
        $sessionDS->set("langUserPhone", "fr");        
        return new JsonResponse($traitementsDS->userCampagneMail($campagneMailRepository->findBy(['status' => 1])));
    }

    #[Route('/adminListCampagneMail/accepter/{id}', name: 'adminListCampagneMailAccepter', methods: ['POST', "GET"])]
    public function adminListCampagneMailAccepter(CampagneMail $campagneMail): Response
    {
        $campagneMail->setStatus(2);
        $this->em->flush();        
        return new Response("OK");
    }

    #[Route('/adminListCampagneMail/refuser/{id}/{motif}', name: 'adminListCampagneMailRefuser', methods: ['POST', "GET"])]
    public function adminListCampagneMailRefuser(CampagneMail $campagneMail, $motif): Response
    {
        $campagneMail->setStatus(0)->setMotif($motif);
        $this->em->flush();        
        return new Response("OK");
    }

    #[Route('/adminListPromotion', name: 'adminListPromotion', methods: ['POST', "GET"])]
    public function adminListPromotion(TraitementsDS $traitementsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository): Response
    {
        $sessionDS->set("langUserPhone", "fr");        
        return new JsonResponse($traitementsDS->userPromos($promotionRepository->findBy(['status' => 1])));
    }

    #[Route('/adminListPromotion/accepter/{id}', name: 'adminListPromotionAccepter', methods: ['POST', "GET"])]
    public function adminListPromotionAccepter(Promotion $promotion): Response
    {
        $promotion->setStatus(2);
        $this->em->flush();        
        return new Response("OK");
    }

    #[Route('/adminListPromotion/refuser/{id}/{motif}', name: 'adminListPromotionRefuser', methods: ['POST', "GET"])]
    public function adminListPromotionRefuser(Promotion $promotion, $motif): Response
    {
        $promotion->setStatus(0)->setMotif($motif);
        $this->em->flush();        
        return new Response("OK");
    }

    #[Route('/adminNumWhatsApp', name: 'adminNumWhatsApp', methods: ['POST', "GET"])]
    public function adminNumWhatsApp(TraitementsDS $traitementsDS, SessionDS $sessionDS, UserRepository $userRepository): Response
    {
        $sessionDS->set("langUserPhone", "fr");        
        return new JsonResponse($traitementsDS->adminListeContacts($userRepository->findBy(['telIsVerified' => false, 'blocked' => false], ["id" => 'DESC'])));
    }

    #[Route('/adminNumWhatsApp/accepter/{uid}', name: 'adminNumWhatsAppAccepter', methods: ['POST', "GET"])]
    public function adminNumWhatsAppAccepter(User $user): Response
    {
        $user->setTelIsVerified(true);
        $this->em->flush();        
        return new Response("OK");
    }

    #[Route('/adminListeUser', name: 'adminListeUser', methods: ['POST', "GET"])]
    public function adminListeUser(TraitementsDS $traitementsDS, SessionDS $sessionDS, UserRepository $userRepository): Response
    {
        $sessionDS->set("langUserPhone", "fr");        
        return new JsonResponse($traitementsDS->adminListeContacts($userRepository->findBy([], ["id" => 'DESC'])));
    }

    #[Route('/adminListPromoReseau', name: 'adminListPromoReseau', methods: ['POST', "GET"])]
    public function adminListPromoReseau(TraitementsDS $traitementsDS, SessionDS $sessionDS, PromoReseauRepository $promoReseauRepository): Response
    {
        $sessionDS->set("langUserPhone", "fr");        
        return new JsonResponse($traitementsDS->userPromoReseaus($promoReseauRepository->findBy(['status' => 1])));
    }
}