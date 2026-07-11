<?php

namespace App\Controller\Crud;

use App\Entity\Tuto;
use App\Form\TutoType;
use App\Repository\TutoRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/tuto')]
class TutoController extends AbstractController
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

    #[Route('/', name: 'app_tuto_index', methods: ['GET'])]
    public function index(TutoRepository $tutoRepository): Response
    {
        return $this->render('tuto/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'tutos' => $tutoRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_tuto_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tuto = new Tuto();
        $form = $this->createForm(TutoType::class, $tuto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tuto);
            $entityManager->flush();

            return $this->redirectToRoute('app_tuto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tuto/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'tuto' => $tuto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tuto_show', methods: ['GET'])]
    public function show(Tuto $tuto): Response
    {
        return $this->render('tuto/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'tuto' => $tuto,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tuto_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tuto $tuto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TutoType::class, $tuto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_tuto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tuto/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'tuto' => $tuto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tuto_delete', methods: ['POST'])]
    public function delete(Request $request, Tuto $tuto, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tuto->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tuto);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_tuto_index', [], Response::HTTP_SEE_OTHER);
    }
}
