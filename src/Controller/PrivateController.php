<?php

namespace App\Controller;

use App\Controller\API\UserController;
use App\Repository\EnvRepository;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PrivateController extends AbstractController
{
    private $em;
    private $env;
    private $cookieDS;
    private $traitementsDS;
    private $userRepository;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, UserRepository $userRepository, CookieDS $cookieDS, TraitementsDS $traitementsDS)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->cookieDS = $cookieDS;
        $this->traitementsDS = $traitementsDS;
        $this->userRepository = $userRepository;
    }

    function getUserByUidInCookies(){
        if($this->cookieDS->get("uid")){
            $uid = $this->cookieDS->get("uid");
            $user = $this->userRepository->findOneBy(['uid' => $uid]);
            if($user){
                return $user;
            }
        }
        return false;
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(CookieDS $cookieDS, UserRepository $userRepository, TraitementsDS $traitementsDS): Response
    {
        $cookieDS->remove("uid");
        return $this->redirectToRoute('app_connexion');
    }

    #[Route('/private', name: 'app_private')]
    public function index(CookieDS $cookieDS, UserRepository $userRepository, TraitementsDS $traitementsDS): Response
    {
        if($cookieDS->get("uid")){
            $uid = $cookieDS->get("uid");
            $user = $userRepository->findOneBy(['uid' => $uid]);
            if($user){
                return $this->render('private/index.html.twig', [
                    'user' => $traitementsDS->infosUser($user),
                ]);
            }
        }
        return $this->redirectToRoute('app_connexion');
    }

    #[Route('/actu', name: 'app_actu')]
    public function actu(): Response
    {
        $user = $this->getUserByUidInCookies();
        $userinfo = $this->traitementsDS->infosUser($user);
        $html = $this->renderView('private/actu.html.twig', [
            'actus' => json_decode($userinfo['lesPublicites']),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        $user = $this->getUserByUidInCookies();
        $userinfo = $this->traitementsDS->userContacts($user);
        $html = $this->renderView('private/contact.html.twig', [
            'contacts' => $userinfo,
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }
}
