<?php

namespace App\Controller\Crud;

use App\Entity\UserBot;
use App\Form\UserBotType;
use App\Repository\UserBotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;

#[Route('/crud/user/bot')]
class CrudUserBotController extends AbstractController
{
    private $theme;
    private $cookieDS;
    private $traitementsDS;

    public function __construct(CookieDS $cookieDS, TraitementsDS $traitementsDS)
    {
        $this->cookieDS = $cookieDS;
        $this->traitementsDS = $traitementsDS;
        if($this->cookieDS->check("theme")) {
            if($this->cookieDS->get("theme") == "dark-theme") {
                $this->theme = "dark-theme";
            } else {
                $this->theme = "light-theme";
            }
        } else {
            $this->theme = "light-theme";
        }
    }
    
    #[Route('/', name: 'app_crud_user_bot_index', methods: ['GET'])]
    public function index(UserBotRepository $userBotRepository): Response
    {
        return $this->render('crud_user_bot/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'user_bots' => $userBotRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_crud_user_bot_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userBot = new UserBot();
        $form = $this->createForm(UserBotType::class, $userBot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($userBot);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_user_bot_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_user_bot/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'user_bot' => $userBot,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_user_bot_show', methods: ['GET'])]
    public function show(UserBot $userBot): Response
    {
        return $this->render('crud_user_bot/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'user_bot' => $userBot,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_user_bot_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserBot $userBot, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserBotType::class, $userBot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_user_bot_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_user_bot/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'user_bot' => $userBot,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_user_bot_delete', methods: ['POST'])]
    public function delete(Request $request, UserBot $userBot, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$userBot->getId(), $request->request->get('_token'))) {
            $entityManager->remove($userBot);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_user_bot_index', [], Response::HTTP_SEE_OTHER);
    }
}
