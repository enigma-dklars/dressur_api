<?php

namespace App\Controller\API;

use App\Entity\ContactsUser;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\SessionWP;
use App\Services\TraitementsWP;
use App\Services\VerificationsWP;
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
    
    #[Route('/listContactWP/{uid}/{langUserPhone}', name: 'listContactWP', methods: ['POST', "GET"])]
    public function listContactWP(User $user, $langUserPhone, TraitementsWP $traitementsWP, SessionWP $sessionWP): Response
    {
        $sessionWP->set("langUserPhone", $langUserPhone);
        
        return new JsonResponse($traitementsWP->userContacts($user));
    }

    #[Route('/addUserContact', name: 'addUserContact')]
    public function addUserContact(Request $request, UserRepository $userRepository, VerificationsWP $verificationsWP, SessionWP $sessionWP): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionWP->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $tel = $datas->get('tel');

        $verificationUser = $verificationsWP->verifUSer($uid);
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
            if($sessionWP->get("langUserPhone") != "fr") {
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

        if(($verificationsWP->permissionAdd($user))["permissionAdd"] == true){
            // MOD
            $user->getContact()->setNewIAdd($userAdd);
            $userAdd->getContact()->setNewAddMe($user);
            $this->em->flush();
            return new JsonResponse([
                'error' => false,
            ]);
        }

        return new JsonResponse([
            'error' => true,
            "permissionAdd" => ($verificationsWP->permissionAdd($user))["permissionAdd"],
            "messageErreurPermissionAdd" => ($verificationsWP->permissionAdd($user))["messageErreurPermissionAdd"],
        ]);
    }

    #[Route('/stockerUserContacts', name: 'stockerUserContacts', methods: ['POST', "GET"])]
    public function stockerUserContacts(Request $request): Response
    {
        $datas = $request->request;
        $contactsUserBeforeWP = json_decode($datas->get('contactsUserBeforeWP'));
        foreach ($contactsUserBeforeWP as $contact) {
            $contactsUser = new ContactsUser();
            $contactsUser->setNameTel($contact->nameTel)
                ->setDisplayNameTel($contact->displayNameTel)
                ->setNumberTel($contact->numberTel)
            ;
            $this->em->persist($contactsUser);
        }
        $this->em->flush();
        return new Response("OK");
    }
}
