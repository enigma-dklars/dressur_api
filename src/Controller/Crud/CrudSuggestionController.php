<?php

namespace App\Controller\Crud;

use App\Entity\Suggestion;
use App\Form\SuggestionType;
use App\Repository\SuggestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;

#[Route('/crud/suggestion')]
class CrudSuggestionController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_suggestion_index', methods: ['GET'])]
    public function index(SuggestionRepository $suggestionRepository): Response
    {
        return $this->render('crud_suggestion/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'suggestions' => $suggestionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_crud_suggestion_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $suggestion = new Suggestion();
        $form = $this->createForm(SuggestionType::class, $suggestion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($suggestion);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_suggestion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_suggestion/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'suggestion' => $suggestion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_suggestion_show', methods: ['GET'])]
    public function show(Suggestion $suggestion): Response
    {
        return $this->render('crud_suggestion/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'suggestion' => $suggestion,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_suggestion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Suggestion $suggestion, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SuggestionType::class, $suggestion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_suggestion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_suggestion/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'suggestion' => $suggestion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_suggestion_delete', methods: ['POST'])]
    public function delete(Request $request, Suggestion $suggestion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$suggestion->getId(), $request->request->get('_token'))) {
            $entityManager->remove($suggestion);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_suggestion_index', [], Response::HTTP_SEE_OTHER);
    }
}
