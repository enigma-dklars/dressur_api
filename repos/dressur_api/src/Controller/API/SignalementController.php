<?php

namespace App\Controller\API;

use App\Entity\Signalement;
use App\Services\CookieDS;
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
    private $cookieDS;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, UserController $userController, CookieDS $cookieDS)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->userController = $userController;
        $this->cookieDS = $cookieDS;
    }

    #[Route('/addSignalement', name: 'addSignalement')]
    public function addSignalement(Request $request, UserRepository $userRepository, SignalementRepository $signalementRepository, VerificationsDS $verificationsDS): Response
    {
        $datas = $request->request;
        
        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        $telSignaler = $datas->get('telSignaler');
        $motifSignaler = $datas->get('motifSignaler');

        if(!$telSignaler or !$motifSignaler){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Le numéro et le motif du signalement sont tous deux indispensables...',
            ]);
        }

        if(strlen($motifSignaler) < 25){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Le motif doit contenir au minimum 25 caractères',
            ]);
        }

        $verificationNumTel = $verificationsDS->verifFormatNumTel($telSignaler);
        if($verificationNumTel["error"] == true){
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix."]);
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
        if(!$userSignaler) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Aucun utilisateur ne correspond à ce numéro.",
            ]);
        }

        $signalementExiste = $signalementRepository->findOneBy(['signaler' => $userSignaler, 'signalant' => $user]);
        if($signalementExiste) {
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

        return new JsonResponse([
            'error' => false,
            'message' => 'Numéro signaler. Dressur vous remercie pour votre participation.',
        ]);
    }
}