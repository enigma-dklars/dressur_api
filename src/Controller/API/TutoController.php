<?php

namespace App\Controller\API;

use App\Repository\EnvRepository;
use App\Repository\TutoRepository;
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
class TutoController extends AbstractController
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

    #[Route('/getTutos', name: 'getTutos', methods: ['POST', 'GET'])]
    public function getTutos(Request $request, TutoRepository $tutoRepository, VerificationsDS $verificationsDS): Response
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

            $tutos = $tutoRepository->findBy(['activated' => true], ['id' => 'DESC']);

            $data = [];
            foreach ($tutos as $tuto) {
                $data[] = [
                    'id'          => $tuto->getId(),
                    'titre'       => $tuto->getTitre(),
                    'description' => $tuto->getDescription(),
                    'url'         => $tuto->getUrl(),
                ];
            }

            return new JsonResponse([
                'error' => false,
                'tutos' => $data,
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error getTutos : TutoController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => 'Service temporairement indisponible. Veuillez réessayer.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
