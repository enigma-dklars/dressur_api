<?php

namespace App\Controller\Crud;

use App\Entity\FormulePromoReseau;
use App\Form\FormulePromoReseauType;
use App\Repository\FormulePromoReseauRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
            'formule_promo_reseaus' => $formulePromoReseauRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/available', name: 'app_crud_formule_promo_reseau_available', methods: ['GET'])]
    public function available(FormulePromoReseauRepository $formulePromoReseauRepository, TraitementsDS $traitementsDS): Response
    {
        $traitementsDS->majServicesZefame();
        return $this->render('crud_formule_promo_reseau/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_promo_reseaus' => $formulePromoReseauRepository->findBy(['available' => true], ['id' => 'DESC']),
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

    /**
     * Endpoint JSON — retourne les infos d'un service par idZefame
     * GET /crud/formule/promo/reseau/service_description/info?id_service=1234
     */
    /**
     * Endpoint JSON — retourne l'idZefame de la première formule active sans description
     * GET /crud/formule/promo/reseau/service_description/next-sans-description
     */
    #[Route('/service_description/next-sans-description', name: 'app_crud_formule_promo_reseau_next_sans_description', methods: ['GET'])]
    public function nextSansDescription(FormulePromoReseauRepository $repo): JsonResponse
    {
        $formule = $repo->createQueryBuilder('f')
            ->where('f.available = :available')
            ->andWhere('f.parent IS NOT NULL')
            ->andWhere('f.description IS NULL OR f.description = :empty')
            ->setParameter('available', true)
            ->setParameter('empty', '')
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$formule) {
            return $this->json(['found' => false, 'message' => 'Toutes les formules actives ont déjà une description. ✅']);
        }

        return $this->json([
            'found'    => true,
            'idZefame' => $formule->getIdZefame(),
        ]);
    }

    /**
     * Endpoint JSON — retourne les infos d'un service par idZefame
     * GET /crud/formule/promo/reseau/service_description/info?id_service=1234
     */
    #[Route('/service_description/info', name: 'app_crud_formule_promo_reseau_service_info', methods: ['GET'])]
    public function serviceInfo(Request $request, FormulePromoReseauRepository $repo): JsonResponse
    {
        $idService = (int) $request->query->get('id_service', 0);

        if ($idService <= 0) {
            return $this->json(['found' => false, 'message' => 'ID invalide']);
        }

        $f = $repo->findOneBy(['idZefame' => $idService]);

        if (!$f) {
            return $this->json(['found' => false, 'message' => 'Aucun service trouvé pour l\'ID ' . $idService]);
        }

        return $this->json([
            'found'       => true,
            'id'          => $f->getId(),
            'idZefame'    => $f->getIdZefame(),
            'titre'       => $f->getTitre(),
            'description' => $f->getDescription() ?? '',
            'prix'        => $f->getPrix(),
            'qte'         => $f->getQte(),
            'qteMin'      => $f->getQteMin(),
            'qteMax'      => $f->getQteMax(),
            'prixZefame'  => $f->getPrixZefame(),
            'available'   => $f->isAvailable(),
            'parent'      => $f->getParent() ? [
                'id'    => $f->getParent()->getId(),
                'titre' => $f->getParent()->getTitre(),
            ] : null,
        ]);
    }

    #[Route('/service_description', name: 'app_crud_formule_promo_reseau_service_description', methods: ['GET', 'POST'])]
    public function service_description(
        Request $request,
        EntityManagerInterface $entityManager,
        FormulePromoReseauRepository $formulePromoReseauRepository
    ): Response {
        $idService = '';

        if ($request->isMethod('POST')) {
            $idService = trim($request->request->get('id_service', ''));

            $descriptionService = trim($request->request->get('description_service', ''));
            $descriptionService = str_replace("Zefame", "Dressur", $descriptionService);
            $descriptionService = str_replace("zefame", "Dressur", $descriptionService);

            $prixRaw      = $request->request->get('prix', '');
            $qteMinRaw    = $request->request->get('qte_min', '');
            $qteMaxRaw    = $request->request->get('qte_max', '');
            $prixZefRaw   = $request->request->get('prix_zefame', '');

            if ($idService) {
                $service = $formulePromoReseauRepository->findOneBy(['idZefame' => $idService]);

                if ($service) {
                    // Description
                    if ($descriptionService !== '') {
                        $service->setDescription($descriptionService);
                    }

                    // Prix DS
                    if ($prixRaw !== '') {
                        $service->setPrix((float) str_replace(',', '.', $prixRaw));
                    }

                    // Qté Min
                    if ($qteMinRaw !== '') {
                        $service->setQteMin((int) $qteMinRaw);
                    }

                    // Qté Max
                    if ($qteMaxRaw !== '') {
                        $service->setQteMax((int) $qteMaxRaw);
                    }

                    // Prix Zefame
                    if ($prixZefRaw !== '') {
                        $service->setPrixZefame((float) str_replace(',', '.', $prixZefRaw));
                    }

                    $entityManager->flush();

                    $this->addFlash('success', "Informations mises à jour — service ID : {$idService} · {$service->getTitre()}");
                    $idService = '';
                } else {
                    $this->addFlash('danger', "Aucune formule trouvée — service ID : {$idService}");
                }
            } else {
                $this->addFlash('warning', "Veuillez saisir un ID de service.");
            }
        }

        return $this->render('crud_formule_promo_reseau/service_description.html.twig', [
            'theme'     => $this->theme,
            'user'      => $this->traitementsDS->getUserByUidInCookies(),
            'idService' => $idService,
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
            $this->addFlash('success', 'Formule Promo Réseau Edited');

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
