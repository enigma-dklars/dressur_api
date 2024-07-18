<?php

namespace App\Controller\Crud;

use App\Entity\DSBonus;
use App\Form\DSBonusType;
use App\Repository\DSBonusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;

#[Route('/crud/bonus')]
class CrudDSBonusController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_d_s_bonus_index', methods: ['GET'])]
    public function index(DSBonusRepository $dSBonusRepository): Response
    {
        return $this->render('crud_ds_bonus/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'd_s_bonuses' => $dSBonusRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_crud_d_s_bonus_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $dSBonu = new DSBonus();
        $form = $this->createForm(DSBonusType::class, $dSBonu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($dSBonu);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_d_s_bonus_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_ds_bonus/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'd_s_bonu' => $dSBonu,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_d_s_bonus_show', methods: ['GET'])]
    public function show(DSBonus $dSBonu): Response
    {
        return $this->render('crud_ds_bonus/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'd_s_bonu' => $dSBonu,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_d_s_bonus_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DSBonus $dSBonu, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DSBonusType::class, $dSBonu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_d_s_bonus_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_ds_bonus/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'd_s_bonu' => $dSBonu,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_d_s_bonus_delete', methods: ['POST'])]
    public function delete(Request $request, DSBonus $dSBonu, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$dSBonu->getId(), $request->request->get('_token'))) {
            $entityManager->remove($dSBonu);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_d_s_bonus_index', [], Response::HTTP_SEE_OTHER);
    }
}
