<?php

namespace App\Controller;

use App\Entity\Promotion;
use App\Form\PromotionType;
use App\Services\TraitementsWP;
use App\Repository\PromotionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/promotion')]
class AdminPromotionController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    #[Route('/', name: 'app_admin_promotion_index', methods: ['GET'])]
    public function index(PromotionRepository $promotionRepository): Response
    {
        return $this->render('admin_promotion/index.html.twig', [
            'promotions' => $promotionRepository->findAll(),
        ]);
    }

    #[Route('/validation', name: 'app_admin_promotion_validation', methods: ['GET'])]
    public function validation(PromotionRepository $promotionRepository): Response
    {
        return $this->render('admin_promotion/validation.html.twig', [
            'promotions' => $promotionRepository->findBy(["status" => 1]),
        ]);
    }

    #[Route('/new', name: 'app_admin_promotion_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PromotionRepository $promotionRepository): Response
    {
        $promotion = new Promotion();
        $form = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $promotionRepository->save($promotion, true);

            return $this->redirectToRoute('app_admin_promotion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('admin_promotion/new.html.twig', [
            'promotion' => $promotion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/valider', name: 'app_admin_promotion_valider', methods: ['GET'])]
    public function valider(Promotion $promotion): Response
    {
        $user = $promotion->getUser();
        $text = $user->getPseudo().", votre promotion sur WhatsPerson a été accepter, vous pouvez donc passez au paiement depuis l'application.";
        $promotion->setStatus(2);
        $this->em->flush();
        return $this->redirect('https://wa.me/'.$user->getTel().'?text='.$text);
    }

    #[Route('/{id}/rejeter', name: 'app_admin_promotion_rejeter', methods: ['GET'])]
    public function rejeter(Promotion $promotion): Response
    {   
        $user = $promotion->getUser();
        $text = $user->getPseudo().", votre promotion sur WhatsPerson a été refuser.";
        $promotion->setStatus(0);
        $this->em->flush();
        return $this->redirect('https://wa.me/'.$user->getTel().'?text='.$text);
    }

    #[Route('/{id}/show', name: 'app_admin_promotion_show', methods: ['GET'])]
    public function show(Promotion $promotion): Response
    {
        return $this->render('admin_promotion/show.html.twig', [
            'promotion' => $promotion,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_promotion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Promotion $promotion, PromotionRepository $promotionRepository): Response
    {
        $form = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $promotionRepository->save($promotion, true);

            return $this->redirectToRoute('app_admin_promotion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('admin_promotion/edit.html.twig', [
            'promotion' => $promotion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_promotion_delete', methods: ['POST'])]
    public function delete(Request $request, Promotion $promotion, PromotionRepository $promotionRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$promotion->getId(), $request->request->get('_token'))) {
            $promotionRepository->remove($promotion, true);
        }

        return $this->redirectToRoute('app_admin_promotion_index', [], Response::HTTP_SEE_OTHER);
    }
}
