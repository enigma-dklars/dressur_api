<?php

namespace App\Controller\API;

use App\Services\TraitementsDS;
use App\Services\VerificationsDS;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Repository\PreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SessionDS;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]
class UserPreferenceController extends AbstractController
{
    private $em;
    private $userRepository;

    public function __construct(EntityManagerInterface $em, UserRepository $userRepository)
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
    }

    #[Route('/listPaysChoisies/{uid}/{langUserPhone}', name: 'listPaysChoisies', methods: ['POST', 'GET'])]
    public function listPaysChoisies(User $user,$langUserPhone, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {        
        $sessionDS->set("langUserPhone", $langUserPhone);

        return new JsonResponse($traitementsDS->mergePaysDisponibleAndUserPreferencePays($user->getPreference()));
    }

    #[Route('/updateUserPaysChoisies/{uid}/{langUserPhone}/{paysChoisieJson}', name: 'updateUserPaysChoisies', methods: ['POST', 'GET'])]
    public function updateUserPaysChoisies(User $user, $langUserPhone, $paysChoisieJson, SessionDS $sessionDS): Response
    {        
        $sessionDS->set("langUserPhone", $langUserPhone);

        $paysChoisieJson = json_decode($paysChoisieJson);
        $arrayPays = [];

        foreach ($paysChoisieJson as $key) {
            if($key->isSelected == true){
                array_push($arrayPays, $key->name);
            }
        }
        $user->getPreference()->setPaysChoisies($arrayPays);
        $this->em->flush();

        return new Response('OK');
    }

    #[Route('/updateUserPreferenceNom', name: 'updateUserPreferenceNom', methods: ['POST'])]
    public function updateUserPreferenceNom(Request $request, UserRepository $userRepository, PreferenceRepository $preferenceRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $valNom = $datas->get('valNom');

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

        $preference = $preferenceRepository->findOneBy(['user' => $user]);
        if(!$preference){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "We have encountered a problem, contact WhatsPerson Assistance by WhatsApp.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Nous avons rencontré un problème, contactez l'Assistance WhatsPerson par WhatsApp.",
            ]);
        }

        if($valNom == "true") {$affNom = true; } else {$affNom = false; }

        $preference->setAffNom($affNom);
        $this->em->flush();
        return new JsonResponse([
            'error' => false,
        ]);
    }
}
