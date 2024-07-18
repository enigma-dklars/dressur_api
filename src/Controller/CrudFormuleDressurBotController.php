<?php

namespace App\Controller;

use App\Entity\FormuleDressurBot;
use App\Form\FormuleDressurBotType;
use App\Repository\FormuleDressurBotRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/formule/dressur/bot')]
class CrudFormuleDressurBotController extends AbstractController
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

    #[Route('/', name: 'app_crud_formule_dressur_bot_index', methods: ['GET'])]
    public function index(FormuleDressurBotRepository $formuleDressurBotRepository): Response
    {
        return $this->render('crud_formule_dressur_bot/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_dressur_bots' => $formuleDressurBotRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_crud_formule_dressur_bot_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formuleDressurBot = new FormuleDressurBot();
        $form = $this->createForm(FormuleDressurBotType::class, $formuleDressurBot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($formuleDressurBot);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_formule_dressur_bot_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_formule_dressur_bot/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_dressur_bot' => $formuleDressurBot,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_formule_dressur_bot_show', methods: ['GET'])]
    public function show(FormuleDressurBot $formuleDressurBot): Response
    {
        return $this->render('crud_formule_dressur_bot/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_dressur_bot' => $formuleDressurBot,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_formule_dressur_bot_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FormuleDressurBot $formuleDressurBot, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FormuleDressurBotType::class, $formuleDressurBot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_formule_dressur_bot_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_formule_dressur_bot/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_dressur_bot' => $formuleDressurBot,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_formule_dressur_bot_delete', methods: ['POST'])]
    public function delete(Request $request, FormuleDressurBot $formuleDressurBot, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$formuleDressurBot->getId(), $request->request->get('_token'))) {
            $entityManager->remove($formuleDressurBot);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_formule_dressur_bot_index', [], Response::HTTP_SEE_OTHER);
    }
}
