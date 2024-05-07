<?php

namespace App\Controller\API;

use App\Entity\Signalement;
use App\Services\SessionDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
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
    public function addSignalement(Request $request, UserRepository $userRepository, SignalementRepository $signalementRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $telSignaler = $datas->get('telSignaler');
        $motifSignaler = $datas->get('motifSignaler');

        if(!$telSignaler or !$motifSignaler){
            if($sessionDS->get("langUserPhone") != "fr") {
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
            if($sessionDS->get("langUserPhone") != "fr") {
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

        $verificationNumTel = $verificationsDS->verifFormatNumTel($telSignaler);
        if($verificationNumTel["error"] == true){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Please enter a valid phone number preceded by its prefix Exp(+229 62005500)."]);
            }
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix Exp(+229 62005500)."]);
        }
        $telSignaler = $verificationNumTel["e164"];

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

        $userSignaler = $userRepository->findOneBy(['tel' => $telSignaler]);
        if(!$userSignaler){
            if($sessionDS->get("langUserPhone") != "fr") {
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
            if($sessionDS->get("langUserPhone") != "fr") {
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
        if ($countSignaler >= 20) {
            $userSignaler->setBlocked(true);
            $this->em->flush();
        }

        if($sessionDS->get("langUserPhone") != "fr") {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Mistake!',
                'message' => "Report number. Dressur thanks you for your participation.",
            ]);
        }
        return new JsonResponse([
            'error' => false,
            'message' => 'Numéro signaler. Dressur vous remercie pour votre participation.',
        ]);
    }
}