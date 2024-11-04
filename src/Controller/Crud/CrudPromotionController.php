<?php

namespace App\Controller\Crud;

use App\Entity\Promotion;
use App\Form\PromotionType;
use App\Repository\FormulePromoAffaireRepository;
use App\Repository\PromotionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/crud/promotion/affaire')]
class CrudPromotionController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_promotion_index', methods: ['GET'])]
    public function index(PromotionRepository $promotionRepository): Response
    {
        return $this->render('crud_promotion/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promotions' => $promotionRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/promo_en_attente', name: 'app_crud_promotion_promo_en_attente', methods: ['GET'])]
    public function promo_en_attente(PromotionRepository $promotionRepository): Response
    {
        return $this->render('crud_promotion/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promotions' => $promotionRepository->findBy(['status' => 1], ['id' => 'DESC']),
        ]);
    }

    #[Route('/delete_inutiles', name: 'app_crud_promotion_delete_inutiles', methods: ['GET'])]
    public function delete_inutiles(PromotionRepository $promotionRepository): Response
    {
        foreach ($promotionRepository->findBy(['status' => 0]) as $une_promo) {
            # code...
        }

        foreach ($promotionRepository->findBy(['status' => 2]) as $une_promo) {
            # code...
        }

        return $this->redirectToRoute('app_crud_promotion_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/new', name: 'app_crud_promotion_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $promotion = new Promotion();
        $form = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($promotion);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_promotion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_promotion/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promotion' => $promotion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_promotion_show', methods: ['GET'])]
    public function show(Promotion $promotion): Response
    {
        return $this->render('crud_promotion/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promotion' => $promotion,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_promotion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Promotion $promotion, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_promotion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_promotion/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promotion' => $promotion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/accepter', name: 'app_crud_promotion_accepter', methods: ['GET', 'POST'])]
    public function accepter(Request $request, Promotion $promotion, EntityManagerInterface $entityManager, FormulePromoAffaireRepository $formulePromoAffaireRepository): Response
    {
        try {
            if($promotion->getTypePromotionAffaire() == "produit_service") {
                $promotion
                    ->setDateExp(new DateTime("+ ".$promotion->getFormulePromoAffaire()->getNbrJour()."days"))
                ;
            }
    
            if($promotion->getTypePromotionAffaire() == "dmd_emploi") {
                $promotion
                    ->setFormulePromoAffaire($formulePromoAffaireRepository->find(4))
                    ->setDateExp(new DateTime("+ ".$formulePromoAffaireRepository->find(4)->getNbrJour()."days"))
                ;
            }
    
            if($promotion->getTypePromotionAffaire() == "offre_emploi") {
                $promotion
                    ->setFormulePromoAffaire($formulePromoAffaireRepository->find(4))
                    ->setDateExp(new DateTime("+ ".$formulePromoAffaireRepository->find(4)->getNbrJour()."days"))
                ;
            }
    
            $promotion->setMotif("")->setStatus(3)->setDateDebut(new DateTime());
            $entityManager->flush();
            return new JsonResponse("Yes");
        } catch (\Throwable $th) {
            //throw $th;
            return new JsonResponse("No. ".(string)$th);
        }
    }

    #[Route('/{id}/refuser/{motif?}', name: 'app_crud_promotion_refuser', methods: ['GET', 'POST'])]
    public function refuser(Request $request, Promotion $promotion, $motif, EntityManagerInterface $entityManager): Response
    {
        try {
            $promotion->setMotif($motif)->setStatus(0);
            $entityManager->flush();
            return new JsonResponse("Yes");
        } catch (\Throwable $th) {
            //throw $th;
            return new JsonResponse("No. ".(string)$th);
        }
    }

    #[Route('/{id}', name: 'app_crud_promotion_delete', methods: ['POST'])]
    public function delete(Request $request, Promotion $promotion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$promotion->getId(), $request->request->get('_token'))) {
            if(str_starts_with($promotion->getImage(), 'dressur_pro_')) {
                unlink($this->getParameter('promotion_directory')."/".$promotion->getImage());
            }
            $entityManager->remove($promotion);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_promotion_index', [], Response::HTTP_SEE_OTHER);
    }
}
