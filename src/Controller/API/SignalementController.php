<?php

namespace App\Controller\API;

use App\Entity\Signalement;
use App\Services\SessionWP;
use App\Repository\EnvRepository;
use App\Services\VerificationsWP;
use App\Repository\UserRepository;
use App\Controller\API\UserController;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SignalementRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]
class SignalementController extends AbstractController
{
    private $em;
    private $env;
    private $userController;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, UserController $userController)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->userController = $userController;
    }

    #[Route('/addSignalement', name: 'addSignalement')]
    public function addSignalement(Request $request, UserRepository $userRepository, SignalementRepository $signalementRepository, VerificationsWP $verificationsWP, SessionWP $sessionWP): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionWP->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $telSignaler = $datas->get('telSignaler');
        $motifSignaler = $datas->get('motifSignaler');

        if(!$telSignaler or !$motifSignaler){
            if($sessionWP->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "Both the number and the reason for the report are essential...",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Le numéro et le motif du signalement sont tous deux indispensables...',
            ]);
        }

        if(strlen($motifSignaler) < 100){
            if($sessionWP->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "The pattern must contain at least 100 characters",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Le motif doit contenir au minimum 100 caractères',
            ]);
        }

        $verificationNumTel = $verificationsWP->verifFormatNumTel($telSignaler);
        if($verificationNumTel["error"] == true){
            if($sessionWP->get("langUserPhone") != "fr") {
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Please enter a valid phone number preceded by its prefix Exp(+229 62005500)."]);
            }
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix Exp(+229 62005500)."]);
        }
        $telSignaler = $verificationNumTel["e164"];

        $verificationUser = $verificationsWP->verifUSer($uid);
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

        $userSignaler = $userRepository->findOneBy(['tel' => $telSignaler]);
        if(!$userSignaler){
            if($sessionWP->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "No user matches this number",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Aucun utilisateur ne correspond à ce numéro.",
            ]);
        }

        $signalementExiste = $signalementRepository->findOneBy(['signaler' => $userSignaler, 'signalant' => $user]);
        if($signalementExiste) {
            if($sessionWP->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "You have already reported this number.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => "Vous avez déja signaler ce numéro.",
            ]);
        }

        $signalement = new Signalement();
        $signalement->setSignaler($userSignaler)
                    ->setMotif($motifSignaler)
                    ->setSignalant($user)
        ;
        $this->em->persist($signalement);
        $this->em->flush();

        $countSignaler = count($signalementRepository->findBy(['signaler' => $userSignaler]));
        if ($countSignaler >= 2) {
            $userSignaler->setBlocked(true);
            $this->em->flush();
        }

        if($sessionWP->get("langUserPhone") != "fr") {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Mistake!',
                'message' => "Report number. WP thanks you for your participation.",
            ]);
        }
        return new JsonResponse([
            'error' => false,
            'message' => 'Numéro signaler. WP vous remercie pour votre participation.',
        ]);
    }
}