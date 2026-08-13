<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\TraitementsDS;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]

class PurgeController extends AbstractController
{
    private $traitementsDS;

    public function __construct(TraitementsDS $traitementsDS)
    {
        $this->traitementsDS = $traitementsDS;
    }

    #[Route('/purge_ds_by_user_id/{id}', name: 'purge_ds_by_user_id')]
    public function purge_ds_by_user_id(User $user): Response
    {
        set_time_limit(10000);

        try {
            $this->traitementsDS->execPurge($user);
        } catch (\Throwable $th) {
            return $this->purgeErrorResponse();
        }
        
        return new Response("ok");
    }

    #[Route('/purge_ds', name: 'purge_ds')]
    public function purge_ds(UserRepository $userRepository): Response
    {
        set_time_limit(10000);

        try {
            $users = $userRepository->findBy(['blocked' => true]);
            foreach ($users as $user) {
                $this->traitementsDS->execPurge($user);
            }

            $usersDeleted = $userRepository->findBy(['deleted' => true]);
            foreach ($usersDeleted as $userDeleted) {
                $this->traitementsDS->execPurge($userDeleted);
            }

            $usersSuspend = $userRepository->findBy(['suspended' => true]);
            foreach ($usersSuspend as $userSuspend) {
                $this->traitementsDS->execPurge($userSuspend);
            }

            $usersnomailnotel = $userRepository->findBy(['telIsVerified' => false, 'mailIsVerified' => false]);
            foreach ($usersnomailnotel as $usernomailnotel) {
                $this->traitementsDS->execPurge($usernomailnotel);
            }

            $usersnotelhavemail = $userRepository->findBy(['telIsVerified' => false, 'mailIsVerified' => true]);
            foreach ($usersnotelhavemail as $usernotelhavemail) {
                $this->traitementsDS->execPurge($usernotelhavemail);
            }
        } catch (\Throwable $th) {
            return $this->purgeErrorResponse();
        }
        
        return new Response("ok");
    }

    #[Route('/del_user_qui_bouge_pas/{id_max}', name: 'del_user_qui_bouge_pas')]
    public function del_user_qui_bouge_pas($id_max, UserRepository $userRepository): Response
    {
        set_time_limit(10000);

        try {
            $usersDeleted = $userRepository->findBy(['deleted' => true]);
            foreach ($usersDeleted as $userDeleted) {
                $this->traitementsDS->execPurge($userDeleted);
            }

            $usersSuspend = $userRepository->findBy(['suspended' => true]);
            foreach ($usersSuspend as $userSuspend) {
                $this->traitementsDS->execPurge($userSuspend);
            }

            $usersnottel = $userRepository->findBy(['telIsVerified' => false]);
            foreach ($usersnottel as $usernottel) {
                if ($id_max >= $usernottel->getId()) {
                    $this->traitementsDS->execPurge($usernottel);
                }
            }

            $usersnotmail = $userRepository->findBy(['mailIsVerified' => false]);
            foreach ($usersnotmail as $usernotmail) {
                if ($id_max >= $usernotmail->getId()) {
                    $this->traitementsDS->execPurge($usernotmail);
                }
            }
        } catch (\Throwable $th) {
            return $this->purgeErrorResponse();
        }
        
        return new Response("ok");
    }

    private function purgeErrorResponse(): JsonResponse
    {
        return new JsonResponse([
            'error' => true,
            'message' => 'La suppression du compte a échoué. Veuillez réessayer.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
