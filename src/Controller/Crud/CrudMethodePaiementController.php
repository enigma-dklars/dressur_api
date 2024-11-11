<?php

namespace App\Controller\Crud;

use App\Services\CookieDS;
use App\Entity\MethodePaiement;
use App\Services\TraitementsDS;
use App\Form\MethodePaiementType;
use App\Repository\EnvRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MethodePaiementRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/crud/methode/paiement')]
class CrudMethodePaiementController extends AbstractController
{
    private $em;
    private $env;
    private $theme;
    private $cookieDS;
    private $traitementsDS;

    public function __construct(EntityManagerInterface $em, CookieDS $cookieDS, TraitementsDS $traitementsDS, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1);
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
    
    #[Route('/', name: 'app_methode_paiement_index', methods: ['GET'])]
    public function index(MethodePaiementRepository $methodePaiementRepository): Response
    {
        return $this->render('crud_methode_paiement/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'methode_paiements' => $methodePaiementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_methode_paiement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $methodePaiement = new MethodePaiement();
        $form = $this->createForm(MethodePaiementType::class, $methodePaiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($methodePaiement);
            $entityManager->flush();

            return $this->redirectToRoute('app_methode_paiement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_methode_paiement/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'methode_paiement' => $methodePaiement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_methode_paiement_show', methods: ['GET'])]
    public function show(MethodePaiement $methodePaiement): Response
    {
        return $this->render('crud_methode_paiement/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'methode_paiement' => $methodePaiement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_methode_paiement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MethodePaiement $methodePaiement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MethodePaiementType::class, $methodePaiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_methode_paiement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_methode_paiement/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'methode_paiement' => $methodePaiement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_methode_paiement_delete', methods: ['POST'])]
    public function delete(Request $request, MethodePaiement $methodePaiement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$methodePaiement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($methodePaiement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_methode_paiement_index', [], Response::HTTP_SEE_OTHER);
    }
}
