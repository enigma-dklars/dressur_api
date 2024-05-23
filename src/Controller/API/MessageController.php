<?php

namespace App\Controller\API;

use App\Entity\Message;
use App\Entity\User;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



#[Route('/api', name: 'api_')]
class MessageController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1);
    }

    #[Route('/addMessage', name: 'addMessage', methods: ['POST', "GET"])]
    public function addMessage(Request $request, UserRepository $userRepository, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        try {
            $userEmetteur = $userRepository->findOneBy(['uid' => $datas->get('emetteur')]);
            $userRecepteur = $userRepository->findOneBy(['uid' => $datas->get('recepteur')]);
            $dateEnvoi = (new DateTime())->setTimestamp($datas->get('dateEnvoi') / 1000);

            $newMessage = new Message();
            $newMessage->setEmetteur($userEmetteur)
                ->setRecepteur($userRecepteur)
                ->setMessage($datas->get('message'))
                ->setDateEnvoi($dateEnvoi)
            ;
            $this->em->persist($newMessage);
            $this->em->flush();
            return new JsonResponse([
                'error' => false,
            ]);
        } catch (\Throwable $th) {
            return new JsonResponse([
                'error' => true,
            ]);
        }
    }
}
