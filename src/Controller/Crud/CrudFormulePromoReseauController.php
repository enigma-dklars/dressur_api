<?php

namespace App\Controller\Crud;

use App\Entity\FormulePromoReseau;
use App\Form\FormulePromoReseauType;
use App\Repository\FormulePromoReseauRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/formule/promo/reseau')]
class CrudFormulePromoReseauController extends AbstractController
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

    #[Route('/', name: 'app_crud_formule_promo_reseau_index', methods: ['GET'])]
    public function index(FormulePromoReseauRepository $formulePromoReseauRepository, TraitementsDS $traitementsDS): Response
    {
        $traitementsDS->majServicesZefame();
        return $this->render('crud_formule_promo_reseau/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_promo_reseaus' => $formulePromoReseauRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_crud_formule_promo_reseau_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formulePromoReseau = new FormulePromoReseau();
        $form = $this->createForm(FormulePromoReseauType::class, $formulePromoReseau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($formulePromoReseau);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_formule_promo_reseau_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_formule_promo_reseau/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_promo_reseau' => $formulePromoReseau,
            'form' => $form,
        ]);
    }

    #[Route('/service_description', name: 'app_crud_formule_promo_reseau_service_description', methods: ['GET', 'POST'])]
    public function service_description(
        Request $request,
        EntityManagerInterface $entityManager,
        FormulePromoReseauRepository $formulePromoReseauRepository
    ): Response {
        $message = null;
        $idService = "";
        $descriptionService = "";

        if ($request->isMethod('POST')) {
            $idService = trim($request->request->get('id_service'));
            $descriptionService = trim($request->request->get('description_service'));
            $descriptionService = str_replace("Zefame",  "Dressur", $descriptionService);
            $descriptionService = str_replace("zefame",  "Dressur", $descriptionService);

            if ($idService && $descriptionService) {
                // Vérifier si un service existe déjà
                $service = $formulePromoReseauRepository->findOneBy(['idZefame' => $idService]);

                if ($service) {
                    // Modifier la description existante
                    $service->setDescription($descriptionService);
                    $this->addFlash(
                        'success',
                        "Description mise à jour avec succès pour le service ID : {$idService}."
                    );
                    $idService = "";
                    $descriptionService = "";
                } else {
                    $this->addFlash(
                       'danger',
                       "Formule de promotion réseaux introuveable - service ID : {$idService}."
                    );
                }

                $entityManager->flush();
            } else {
                $this->addFlash(
                    'warning',
                    "Veuillez remplir tous les champs avant de soumettre le formulaire."
                );
            }
        }

        return $this->render('crud_formule_promo_reseau/service_description.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'idService' => $idService,
            'descriptionService' => $descriptionService,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_formule_promo_reseau_show', methods: ['GET'])]
    public function show(FormulePromoReseau $formulePromoReseau): Response
    {
        return $this->render('crud_formule_promo_reseau/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_promo_reseau' => $formulePromoReseau,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_formule_promo_reseau_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FormulePromoReseau $formulePromoReseau, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FormulePromoReseauType::class, $formulePromoReseau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_formule_promo_reseau_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_formule_promo_reseau/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_promo_reseau' => $formulePromoReseau,
            'form' => $form,
        ]);
    }

    #[Route('/{idzef?}/edit-by-id-zef', name: 'app_crud_formule_promo_reseau_edit_by_id_zef', methods: ['GET', 'POST'])]
    public function edit_by_id_zef($idzef, Request $request, FormulePromoReseauRepository $formulePromoReseauRepository, EntityManagerInterface $entityManager): Response
    {
        $formulePromoReseau = $formulePromoReseauRepository->findOneBy(['idZefame' => $idzef]);
        $form = $this->createForm(FormulePromoReseauType::class, $formulePromoReseau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash(
               'success',
               'Formule Promo Réseau Edited'
            );

            return $this->redirectToRoute('app_crud_formule_promo_reseau_edit_by_id_zef', ['idzef' => $idzef], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_formule_promo_reseau/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_promo_reseau' => $formulePromoReseau,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_formule_promo_reseau_delete', methods: ['POST'])]
    public function delete(Request $request, FormulePromoReseau $formulePromoReseau, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$formulePromoReseau->getId(), $request->request->get('_token'))) {
            $entityManager->remove($formulePromoReseau);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_formule_promo_reseau_index', [], Response::HTTP_SEE_OTHER);
    }
}
