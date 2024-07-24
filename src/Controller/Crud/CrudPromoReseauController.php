<?php

namespace App\Controller\Crud;

use App\Entity\PromoReseau;
use App\Form\PromoReseauType;
use App\Repository\PromoReseauRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use DateTime;

#[Route('/crud/promo/reseau')]
class CrudPromoReseauController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_promo_reseau_index', methods: ['GET'])]
    public function index(PromoReseauRepository $promoReseauRepository): Response
    {
        return $this->render('crud_promo_reseau/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseaus' => $promoReseauRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_crud_promo_reseau_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $promoReseau = new PromoReseau();
        $form = $this->createForm(PromoReseauType::class, $promoReseau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($promoReseau);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_promo_reseau_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_promo_reseau/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseau' => $promoReseau,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_promo_reseau_show', methods: ['GET'])]
    public function show(PromoReseau $promoReseau): Response
    {
        return $this->render('crud_promo_reseau/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseau' => $promoReseau,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_promo_reseau_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PromoReseau $promoReseau, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PromoReseauType::class, $promoReseau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($promoReseau->getStatus() == 3) {
                $promoReseau->setCompteurRestant(0);
            }
            $promoReseau->setUpdatedAt(new DateTime());
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_promo_reseau_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_promo_reseau/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseau' => $promoReseau,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_promo_reseau_delete', methods: ['POST'])]
    public function delete(Request $request, PromoReseau $promoReseau, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$promoReseau->getId(), $request->request->get('_token'))) {
            $entityManager->remove($promoReseau);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_promo_reseau_index', [], Response::HTTP_SEE_OTHER);
    }
}
