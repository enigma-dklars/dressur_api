<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Services\SessionWP;
use App\Services\TraitementsWP;
use App\Repository\EnvRepository;
use App\Services\VerificationsWP;
use App\Repository\UserRepository;
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
    public function getContactActuUser(User $user, TraitementsWP $traitementsWP): Response
    {
        return new JsonResponse($traitementsWP->getAddDisponible($user));
    }

    #[Route('/addTousUserContact', name: 'addTousUserContact')]
    public function addTousUserContact(Request $request, UserRepository $userRepository, VerificationsWP $verificationsWP, SessionWP $sessionWP, TraitementsWP $traitementsWP): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionWP->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');

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
        $contactsAdd = [];
        foreach ($traitementsWP->getAddDisponible($user) as $add) {
            $userAdd = $userRepository->findOneBy(['tel' => $add['tel']]);
            if($userAdd){
                if(($verificationsWP->permissionAdd($user))["permissionAdd"] == true){
                    // MOD
                    $user->getContact()->setNewIAdd($userAdd);
                    $userAdd->getContact()->setNewAddMe($user);
                    $this->em->flush();
                    array_push($contactsAdd, [
                        "pseudo" => $userAdd->getPseudo(),
                        "tel" => $userAdd->getTel(),
                    ]);
                } else {
                    return new JsonResponse([
                        'error' => true,
                        "contactsAdd" => $contactsAdd,
                        "permissionAdd" => ($verificationsWP->permissionAdd($user))["permissionAdd"],
                        "messageErreurPermissionAdd" => ($verificationsWP->permissionAdd($user))["messageErreurPermissionAdd"],
                    ]);
                }
            }
        }
        return new JsonResponse([
            'error' => false,
            'contactsAdd' => $contactsAdd,
        ]);
    }
}
