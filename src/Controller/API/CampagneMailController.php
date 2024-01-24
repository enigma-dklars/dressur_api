<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Services\SessionDS;
use App\Entity\CampagneMail;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\BoostRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FormuleBoostRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]

class CampagneMailController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1); 
    }

    #[Route('/newCampagneMail', name: 'newCampagneMail', methods: ['POST'])]
    public function newCampagneMail(Request $request, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $titre = $datas->get('titre');
        $sujet = $datas->get('sujet');
        $replyto = $datas->get('replyto');
        $sendto = $datas->get('sendto');
        $contentmail = $datas->get('contentmail');

        if(!$titre or !$sujet or !$replyto or !$sendto or !$contentmail) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Please complete all fields!',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez bien remplir tous les champs!',
            ]);
        }

        $verificationUser = $verificationsDS->verifUSer($uid);
        if($verificationUser["error"] == true){
            return new JsonResponse([
                'error' => true,
                'titre' => $verificationUser["titre"],
                'message' => $verificationUser["message"],
                'deleted' => $verificationUser["deleted"],
                'blocked' => $verificationUser["blocked"],
            ]);
        }
        $user = $verificationUser["user"];

        if(!$user->getTelIsVerified()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Your WhatsApp number has not yet been confirmed. If this is an error, contact us on WhatsApp.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Votre numéro WhatsApp na pas encore été confirmer. S'il s'agit d'une erreur, contactez-nous sur WhatsApp.",
            ]);
        }

        if(!$user->getMailIsVerified()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Please confirm your email address.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Veuillez confirmez votre adresse mail.",
            ]);
        }

        $campagneMail  = new CampagneMail();
        $campagneMail->setUser($user)
            ->setTitre($titre)
            ->setSujet($sujet)
            ->setReplyto($replyto)
            ->setSendto($sendto)
            ->setContentmail($contentmail)
        ;
        $this->em->persist($campagneMail);
        $this->em->flush();

        return new JsonResponse([
            'error' => false,
        ]);
    }

    #[Route('/listCampagneMail/{uid}/{langUserPhone}', name: 'listCampagneMail', methods: ['POST', "GET"])]
    public function listCampagneMail(User $user, $langUserPhone, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $sessionDS->set("langUserPhone", $langUserPhone);
        
        return new JsonResponse($traitementsDS->userCampagneMail($user->getCampagneMails()));
    }
}