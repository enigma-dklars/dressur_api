<?php

namespace App\Controller\API;

use App\Entity\Message;
use App\Entity\User;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Repository\MessageRepository;
use App\Services\CookieDS;
use App\Services\VerificationsDS;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Utilities\SendMail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



#[Route('/api', name: 'api_')]
class MessageController extends AbstractController
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

    #[Route('/addMessage', name: 'addMessage', methods: ['POST', "GET"])]
    public function addMessage(Request $request, UserRepository $userRepository, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone') ?? 'fr';
        $sessionDS->set("langUserPhone", $langUserPhone);

        try {
            $userEmetteur = $userRepository->findOneBy(['uid' => $this->cookieDS->getWithFallback('uid', $request) ?: null]);
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
            $this->sendMail->sendReport('Error addMessage : MessageController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
            ]);
        }
    }

    #[Route('/getMessageEnAttente', name: 'getMessageEnAttente', methods: ['POST', "GET"])]
    public function getMessageEnAttente(Request $request, UserRepository $userRepository, SessionDS $sessionDS, MessageRepository $messageRepository): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone') ?? 'fr';
        $sessionDS->set("langUserPhone", $langUserPhone);

        try {
            $lesMessages = [];
            $user = $userRepository->findOneBy(['uid' => $this->cookieDS->getWithFallback('uid', $request) ?: null]);

            foreach ($messageRepository->findBy(['recepteur' => $user]) as $message) {
                array_push($lesMessages, [
                    "idMessage" => $message->getId(),
                    "emetteurName" => $message->getEmetteur()->__toString(),
                    "emetteur" => $message->getEmetteur()->getUid(),
                    "recepteur" => $message->getRecepteur()->getUid(),
                    "message" => $message->getMessage(),
                    "dateEnvoi" => $message->getDateEnvoi()->getTimestamp() * 1000,
                    "vue" => "non"
                ]);
            }

            return new JsonResponse([
                'error' => false,
                'lesMessages' => $lesMessages,
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error getMessageEnAttente : MessageController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
            ]);
        }
    }

    #[Route('/deleteMessageEnAttente/{lastIdMessage}/{uidUser}', name: 'deleteMessageEnAttente', methods: ['POST', "GET"])]
    public function deleteMessageEnAttente($lastIdMessage, $uidUser, Request $request, UserRepository $userRepository, SessionDS $sessionDS, MessageRepository $messageRepository): Response
    {
        $user = $userRepository->findOneBy(['uid' => $this->cookieDS->getWithFallback('uid', $request) ?: null]);
        try {
            $messages = $messageRepository->createQueryBuilder('m')
                ->where('m.recepteur = :user')
                ->andWhere('m.id <= :lastIdMessage')
                ->setParameter('user', $user)
                ->setParameter('lastIdMessage', $lastIdMessage)
                ->getQuery()
                ->getResult()
            ;
            
            foreach ($messages as $message) {
                $this->em->remove($message);
            }

            $this->em->flush();

            return new JsonResponse([
                'error' => false,
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error deleteMessageEnAttente : MessageController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
            ]);
        }
    }
}
