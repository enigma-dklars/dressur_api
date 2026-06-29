<?php

namespace App\Controller\API;

use App\Services\TraitementsDS;
use App\Repository\UserRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
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
        

        return new JsonResponse($user->getPreference()->getPaysChoisies());
    }

    #[Route('/updateUserPaysChoisies/{uid}/{langUserPhone}/{paysChoisieJson}', name: 'updateUserPaysChoisies', methods: ['POST', 'GET'])]
    public function updateUserPaysChoisies(User $user, $langUserPhone, $paysChoisieJson, SessionDS $sessionDS): Response
    {        
        

        $paysChoisieJson = json_decode($paysChoisieJson);
        $arrayPays = [];

        foreach ($paysChoisieJson as $key) {
            array_push($arrayPays, $key);
        }
        $user->getPreference()->setPaysChoisies($arrayPays);
        $this->em->flush();

        return new Response('OK');
    }

    #[Route('/getAddPageActu/{uid}', name: 'getAddPageActu', methods: ['POST', 'GET'])]
    public function getAddPageActu(User $user): Response
    {
        return new JsonResponse([
            'error' => false,
            'addPageActu' => $user->getPreference()->getAddPageActu(),
        ]);
    }

    #[Route('/updateAddPageActu/{uid}/{value}', name: 'updateAddPageActu', methods: ['POST', 'GET'])]
    public function updateAddPageActu(User $user, $value): Response
    {
        $user->getPreference()->setAddPageActu($value === '1' || $value === 'true');
        $this->em->flush();

        return new JsonResponse([
            'error' => false,
            'addPageActu' => $user->getPreference()->getAddPageActu(),
        ]);
    }

}
