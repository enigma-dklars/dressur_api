<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Services\VerificationsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Utilities\SendMail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]

class ContactController extends AbstractController
{
    private $em;
    private $userRepository;
    private $cookieDS;
    private $sendMail;

    public function __construct(EntityManagerInterface $em, UserRepository $userRepository, CookieDS $cookieDS, SendMail $sendMail)
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->cookieDS = $cookieDS;
        $this->sendMail = $sendMail;
    }   
    
    #[Route('/listContactDS/{uid}/{langUserPhone}', name: 'listContactDS', methods: ['POST', "GET"])]
    public function listContactDS(User $user, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        return new JsonResponse($traitementsDS->userContacts($user));
    }

    #[Route('/allUserAddDressur', name: 'addUserContact')]
    public function addUserContact(UserRepository $userRepository, VerificationsDS $verificationsDS): Response
    {
        $dressurId  = 2;
        $batchSize  = 50;
        $iteration  = 0;

        try {
            $dressur = $userRepository->find($dressurId);

            $query          = $this->em->createQuery('SELECT u FROM App\Entity\User u');
            $iterableResult = $query->toIterable();

            foreach ($iterableResult as $user) {
                if (($verificationsDS->permissionAdd($user))["permissionAdd"] == true) {
                    $user->getContact()->setNewIAdd($dressur);
                    $dressur->getContact()->setNewAddMe($user);
                }

                $iteration++;
                if ($iteration % $batchSize === 0) {
                    $this->em->flush();
                    $this->em->clear();
                    $dressur = $userRepository->find($dressurId);
                }
            }

            $this->em->flush();

            return new JsonResponse([
                'error' => false,
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error addUserContact : ContactController', $th . '<br><br><br>');
            throw $th;
        }
    }
}
