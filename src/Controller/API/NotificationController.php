<?php

namespace App\Controller\API;

use App\Repository\EnvRepository;
use App\Repository\NotificationRepository;
use App\Services\CookieDS;
use App\Services\VerificationsDS;
use App\Utilities\SendMail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class NotificationController extends AbstractController
{
    private $em;
    private $env;
    private $cookieDS;
    private $sendMail;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, CookieDS $cookieDS, SendMail $sendMail)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->cookieDS = $cookieDS;
        $this->sendMail = $sendMail;
    }

    #[Route('/getNotifications', name: 'getNotifications', methods: ['POST', 'GET'])]
    public function getNotifications(Request $request, NotificationRepository $notificationRepository, VerificationsDS $verificationsDS): Response
    {
        try {
            $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;

            $verificationUser = $verificationsDS->verifUSer($uid);
            if ($verificationUser['error'] == true) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => $verificationUser['titre'],
                    'message' => $verificationUser['message'],
                    'deleted' => $verificationUser['deleted'],
                    'blocked' => $verificationUser['blocked'],
                ]);
            }
            $user = $verificationUser['user'];

            $notifications = $notificationRepository->findForUser($user);

            $data = [];
            foreach ($notifications as $notification) {
                $data[] = [
                    'id' => $notification->getId(),
                    'text' => $notification->getText(),
                    'createdAt' => $notification->getCreatedAt()?->format('Y-m-d H:i:s'),
                ];
            }

            return new JsonResponse([
                'error' => false,
                'notifications' => $data,
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error getNotifications : NotificationController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => 'Service temporairement indisponible. Veuillez réessayer.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
