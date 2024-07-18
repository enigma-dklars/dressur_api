<?php

namespace App\Controller\Crud;

use App\Entity\FormuleBoost;
use App\Form\FormuleBoostType;
use App\Repository\FormuleBoostRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/formule/boost')]
class CrudFormuleBoostController extends AbstractController
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

    #[Route('/', name: 'app_crud_formule_boost_index', methods: ['GET'])]
    public function index(FormuleBoostRepository $formuleBoostRepository): Response
    {
        return $this->render('crud_formule_boost/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_boosts' => $formuleBoostRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_crud_formule_boost_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formuleBoost = new FormuleBoost();
        $form = $this->createForm(FormuleBoostType::class, $formuleBoost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($formuleBoost);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_formule_boost_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_formule_boost/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_boost' => $formuleBoost,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_formule_boost_show', methods: ['GET'])]
    public function show(FormuleBoost $formuleBoost): Response
    {
        return $this->render('crud_formule_boost/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_boost' => $formuleBoost,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_formule_boost_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FormuleBoost $formuleBoost, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FormuleBoostType::class, $formuleBoost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_formule_boost_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_formule_boost/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_boost' => $formuleBoost,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_formule_boost_delete', methods: ['POST'])]
    public function delete(Request $request, FormuleBoost $formuleBoost, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$formuleBoost->getId(), $request->request->get('_token'))) {
            $entityManager->remove($formuleBoost);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_formule_boost_index', [], Response::HTTP_SEE_OTHER);
    }
}
