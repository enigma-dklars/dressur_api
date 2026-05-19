<?php

namespace App\Controller\API;

use App\Entity\ContactsUser;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Services\VerificationsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]

class ContactController extends AbstractController
{
    private $em;
    private $userRepository;

    public function __construct(EntityManagerInterface $em, UserRepository $userRepository)
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
    }   
    
    #[Route('/listContactDS/{uid}/{langUserPhone}', name: 'listContactDS', methods: ['POST', "GET"])]
    public function listContactDS(User $user, $langUserPhone, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $sessionDS->set("langUserPhone", $langUserPhone);
        
        return new JsonResponse($traitementsDS->userContacts($user));
    }

    #[Route('/allUserAddDressur', name: 'addUserContact')]
    public function addUserContact(UserRepository $userRepository, VerificationsDS $verificationsDS): Response
    {
        try {
            $allUsers = $userRepository->findAll();
            foreach ($allUsers as $user) {
                $dressur = $userRepository->find(2);
                if(($verificationsDS->permissionAdd($user))["permissionAdd"] == true){
                    $user->getContact()->setNewIAdd($dressur);
                    $dressur->getContact()->setNewAddMe($user);
                }
            }
            $this->em->flush();
            return new JsonResponse([
                'error' => false,
            ]);
        } catch (\Throwable $th) {
            throw $th;            

            return new JsonResponse([
                'error' => true,
            ]);
        }
    }

    #[Route('/addUserContactAfterScanneQRCode', name: 'addUserContactAfterScanneQRCode')]
    public function addUserContactAfterScanneQRCode(Request $request, UserRepository $userRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $_COOKIE['uid'] ?? null;
        $tel = $datas->get('tel');

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

        $userAdd = $userRepository->findOneBy(['tel' => $tel]);
        if(!$userAdd){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "We have encountered a problem, contact Assistance by WhatsApp.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
            ]);
        }

        try {
            $user->getContact()->setNewIAdd($userAdd);
            $userAdd->getContact()->setNewAddMe($user);
            $this->em->flush();
            return new JsonResponse([
                'error' => false,
            ]);
        } catch (\Throwable $th) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
            ]);
        }
    }

    #[Route('/stockerUserContacts', name: 'stockerUserContacts', methods: ['POST', "GET"])]
    public function stockerUserContacts(Request $request): Response
    {
        $datas = $request->request;
        $contactsUserBeforeDS = json_decode($datas->get('contactsUserBeforeDS'));
        foreach ($contactsUserBeforeDS as $contact) {
            $contactsUser = new ContactsUser();
            $contactsUser->setNameTel($contact->nameTel)
                ->setDisplayNameTel($contact->displayNameTel)
                ->setNumberTel($contact->numberTel)
                ->setMailTel(isset($contact->mailTel) ? $contact->mailTel : null)
            ;
            $this->em->persist($contactsUser);
        }
        $this->em->flush();
        return new Response("OK");
    }
}
