<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\UserRepository;
use App\Repository\BoostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



#[Route('/api', name: 'api_')]
class AddController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1);
    }
    
    #[Route('/getContactActuUser/{uid}', name: 'getContactActuUser', methods: ['POST', "GET"])]
    public function getContactActuUser(User $user, TraitementsDS $traitementsDS): Response
    {
        return new JsonResponse($traitementsDS->getAddDisponible($user));
    }

    #[Route('/addTousUserContact/{uid}/{langUserPhone}', name: 'addTousUserContact', methods: ['POST', "GET"])]
    public function addTousUserContact(Request $request, $langUserPhone, $uid, UserRepository $userRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS, TraitementsDS $traitementsDS, BoostRepository $boostRepository): Response
    {        
        $sessionDS->set("langUserPhone", $langUserPhone);

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
        $contactsAdd = [];
        if ($user->getTelIsVerified() == true) {
            foreach ($traitementsDS->getAddDisponible($user) as $add) {
                $userAdd = $userRepository->findOneBy(['tel' => $add['tel']]);
                if($userAdd){
                    if(($verificationsDS->permissionAdd($user))["permissionAdd"] == true){
                        // MOD
                        $user->getContact()->setNewIAdd($userAdd);
                        $userAdd->getContact()->setNewAddMe($user);
                        $this->em->flush();
                        // Incrémenter le boost actif de $userAdd s'il en a un (quota ou date)
                        $boostActif = $boostRepository->findBoostActif($userAdd);
                        if ($boostActif) {
                            $boostActif->setNbContactsObtenus($boostActif->getNbContactsObtenus() + 1);
                            $nbContactsMax = $boostActif->getFormuleBoost()->getNbContactsMax();
                            if ($nbContactsMax !== null && $boostActif->getNbContactsObtenus() >= $nbContactsMax) {
                                $boostActif->setDateExp(new \DateTime());
                            }
                            $this->em->flush();
                        }
                        array_push($contactsAdd, [
                            "pseudo" => $userAdd->getPseudo(),
                            "nom" => (string)$userAdd,
                            "tel" => $userAdd->getTel(),
                        ]);
                    } else {
                        return new JsonResponse([
                            'error' => true,
                            "contactsAdd" => $contactsAdd,
                            "permissionAdd" => ($verificationsDS->permissionAdd($user))["permissionAdd"],
                            "messageErreurPermissionAdd" => ($verificationsDS->permissionAdd($user))["messageErreurPermissionAdd"],
                        ]);
                    }
                }
            }
        }
        return new JsonResponse([
            'error' => false,
            'contactsAdd' => $contactsAdd,
        ]);
    }
}
