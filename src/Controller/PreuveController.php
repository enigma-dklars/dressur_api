<?php

namespace App\Controller;

use App\Entity\Preuve;
use App\Form\PreuveType;
use App\Repository\PreuveRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/preuve')]
class PreuveController extends AbstractController
{
    private $theme;
    private $is_connect;
    private $cookieDS;
    private $traitementsDS;

    public function __construct(CookieDS $cookieDS, TraitementsDS $traitementsDS)
    {
        $this->cookieDS = $cookieDS;
        $this->traitementsDS = $traitementsDS;

        // Gestion du thème
        if ($this->cookieDS->check("theme")) {
            if ($this->cookieDS->get("theme") == "dark-theme") {
                $this->theme = "dark-theme";
            } else {
                $this->theme = "light-theme";
            }
        } else {
            $this->theme = "light-theme";
        }

        // Vérification connexion
        $this->is_connect = $this->traitementsDS->getUserByUidInCookies() !== null;
    }

    #[Route('/', name: 'app_preuve_index', methods: ['GET'])]
    public function index(PreuveRepository $preuveRepository): Response
    {
        return $this->render('preuve/index.html.twig', [
            'theme' => $this->theme,
            'is_connect' => $this->is_connect,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'preuves' => $preuveRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_preuve_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $preuve = new Preuve();
        $form = $this->createForm(PreuveType::class, $preuve);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($preuve);
            $entityManager->flush();

            return $this->redirectToRoute('app_preuve_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('preuve/new.html.twig', [
            'theme' => $this->theme,
            'is_connect' => $this->is_connect,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'preuve' => $preuve,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_preuve_show', methods: ['GET'])]
    public function show(Preuve $preuve): Response
    {
        return $this->render('preuve/show.html.twig', [
            'theme' => $this->theme,
            'is_connect' => $this->is_connect,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'preuve' => $preuve,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_preuve_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Preuve $preuve, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PreuveType::class, $preuve);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_preuve_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('preuve/edit.html.twig', [
            'theme' => $this->theme,
            'is_connect' => $this->is_connect,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'preuve' => $preuve,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_preuve_delete', methods: ['POST'])]
    public function delete(Request $request, Preuve $preuve, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$preuve->getId(), $request->request->get('_token'))) {
            $entityManager->remove($preuve);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_preuve_index', [], Response::HTTP_SEE_OTHER);
    }
}
