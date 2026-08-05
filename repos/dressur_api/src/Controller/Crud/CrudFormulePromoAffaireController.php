<?php

namespace App\Controller\Crud;

use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Entity\FormulePromoAffaire;
use App\Form\FormulePromoAffaireType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\FormulePromoAffaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/crud/formule/promo/affaire')]
class CrudFormulePromoAffaireController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_formule_promo_affaire_index', methods: ['GET'])]
    public function index(FormulePromoAffaireRepository $formulePromoAffaireRepository): Response
    {
        return $this->render('crud_formule_promo_affaire/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_promo_affaires' => $formulePromoAffaireRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_crud_formule_promo_affaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formulePromoAffaire = new FormulePromoAffaire();
        $form = $this->createForm(FormulePromoAffaireType::class, $formulePromoAffaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($formulePromoAffaire);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_formule_promo_affaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_formule_promo_affaire/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_promo_affaire' => $formulePromoAffaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_formule_promo_affaire_show', methods: ['GET'])]
    public function show(FormulePromoAffaire $formulePromoAffaire): Response
    {
        return $this->render('crud_formule_promo_affaire/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_promo_affaire' => $formulePromoAffaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_formule_promo_affaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FormulePromoAffaire $formulePromoAffaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FormulePromoAffaireType::class, $formulePromoAffaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_formule_promo_affaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_formule_promo_affaire/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_promo_affaire' => $formulePromoAffaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_formule_promo_affaire_delete', methods: ['POST'])]
    public function delete(Request $request, FormulePromoAffaire $formulePromoAffaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$formulePromoAffaire->getId(), $request->request->get('_token'))) {
            $entityManager->remove($formulePromoAffaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_formule_promo_affaire_index', [], Response::HTTP_SEE_OTHER);
    }
}
