<?php

namespace App\Controller;

use App\Controller\API\UserController;
use App\Repository\EnvRepository;
use App\Repository\FormuleCampagneMailRepository;
use App\Repository\PromotionRepository;
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
    public function index(CookieDS $cookieDS, UserRepository $userRepository, TraitementsDS $traitementsDS, PromotionRepository $promotionRepository): Response
    {
        if($cookieDS->get("uid")){
            $uid = $cookieDS->get("uid");
            $user = $userRepository->findOneBy(['uid' => $uid]);
            $count = $traitementsDS->vuesImpressionsCumulerUserPromos($user->getPromotions());
            if($user){
                return $this->render('private/index.html.twig', [
                    'user' => $traitementsDS->infosUser($user),
                    'bonus_user' => $traitementsDS->formatNumber($user->getSoldeBonus()),
                    'contacts_user' => $traitementsDS->formatNumber(count($traitementsDS->userContacts($user))),
                    'countVues' => $traitementsDS->formatNumber($count['countVues']),
                    'countImpressions' => $traitementsDS->formatNumber($count['countImpressions']),
                    'top_trois_affaires' => $traitementsDS->getTopAffaires(3),
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

    #[Route('/newcampagemail', name: 'app_newcampagemail')]
    public function newcampagemail(FormuleCampagneMailRepository $formuleCampagneMailRepository, TraitementsDS $traitementsDS): Response
    {
        $user = $this->getUserByUidInCookies();
        $formuleCampageMails = $formuleCampagneMailRepository->findAll();
        // dd($formuleCampageMails);
        $html = $this->renderView('private/newcampagemail.html.twig', [
            'formuleCampageMails' => $formuleCampageMails,
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/listecampagemail', name: 'app_listecampagemail')]
    public function listecampagemail(FormuleCampagneMailRepository $formuleCampagneMailRepository, TraitementsDS $traitementsDS): Response
    {
        $user = $this->getUserByUidInCookies();
        // dd($traitementsDS->userCampagneMail($user->getCampagneMails()));
        $html = $this->renderView('private/listecampagemail.html.twig', [
            'campagemails' => $traitementsDS->userCampagneMail($user->getCampagneMails()),
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/newpromoreseau', name: 'app_newpromoreseau')]
    public function newpromoreseau(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/newpromoreseau.html.twig', [
            'listSocialNetworks' => $traitementsDS->listeFormulePromoReseau(),
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/listepromoreseau', name: 'app_listepromoreseau')]
    public function listepromoreseau(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/listepromoreseau.html.twig', [
            'listepromoreseau' => $traitementsDS->userPromoReseaus($user->getPromoReseaus()),
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/newpromoaffaire', name: 'app_newpromoaffaire')]
    public function newpromoaffaire(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/newpromoaffaire.html.twig', [
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }

    #[Route('/listepromoaffaire', name: 'app_listepromoaffaire')]
    public function listepromoaffaire(TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $user = $this->getUserByUidInCookies();
        $sessionDS->set("langUserPhone", "fr");
        // dd($formuleCampageMails);
        $html = $this->renderView('private/listepromoaffaire.html.twig', [
            'listepromoaffaire' => $traitementsDS->userPromos($user->getPromotions()),
            'listeFormulBoost' => $traitementsDS->listeFormulBoost(),
            'user' => $traitementsDS->infosUser($user),
        ]);

        return new JsonResponse([
            'error' => false,
            'content' => $html,
        ]);
    }
}