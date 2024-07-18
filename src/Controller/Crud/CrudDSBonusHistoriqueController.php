<?php

namespace App\Controller\Crud;

use App\Entity\DSBonusHistorique;
use App\Form\DSBonusHistoriqueType;
use App\Repository\DSBonusHistoriqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;

#[Route('/crud/bonushistorique')]
class CrudDSBonusHistoriqueController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_d_s_bonus_historique_index', methods: ['GET'])]
    public function index(DSBonusHistoriqueRepository $dSBonusHistoriqueRepository): Response
    {
        return $this->render('crud_ds_bonus_historique/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'd_s_bonus_historiques' => $dSBonusHistoriqueRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_crud_d_s_bonus_historique_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $dSBonusHistorique = new DSBonusHistorique();
        $form = $this->createForm(DSBonusHistoriqueType::class, $dSBonusHistorique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($dSBonusHistorique);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_d_s_bonus_historique_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_ds_bonus_historique/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'd_s_bonus_historique' => $dSBonusHistorique,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_d_s_bonus_historique_show', methods: ['GET'])]
    public function show(DSBonusHistorique $dSBonusHistorique): Response
    {
        return $this->render('crud_ds_bonus_historique/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'd_s_bonus_historique' => $dSBonusHistorique,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_d_s_bonus_historique_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DSBonusHistorique $dSBonusHistorique, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DSBonusHistoriqueType::class, $dSBonusHistorique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_d_s_bonus_historique_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_ds_bonus_historique/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'd_s_bonus_historique' => $dSBonusHistorique,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_d_s_bonus_historique_delete', methods: ['POST'])]
    public function delete(Request $request, DSBonusHistorique $dSBonusHistorique, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$dSBonusHistorique->getId(), $request->request->get('_token'))) {
            $entityManager->remove($dSBonusHistorique);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_d_s_bonus_historique_index', [], Response::HTTP_SEE_OTHER);
    }
}
