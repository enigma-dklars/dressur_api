<?php

namespace App\Controller;

use App\Entity\HistoriqueProgrammeRecompense;
use App\Form\HistoriqueProgrammeRecompenseType;
use App\Repository\HistoriqueProgrammeRecompenseRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/historique/programme/recompense')]
class HistoriqueProgrammeRecompenseController extends AbstractController
{
    private $theme;
    private $is_connect;
    private $cookieDS;
    private $traitementsDS;

    public function __construct(CookieDS $cookieDS, TraitementsDS $traitementsDS)
    {
        $this->cookieDS = $cookieDS;
        $this->traitementsDS = $traitementsDS;

        // Gestion du theme
        if ($this->cookieDS->check("theme")) {
            if ($this->cookieDS->get("theme") == "dark-theme") {
                $this->theme = "dark-theme";
            } else {
                $this->theme = "light-theme";
            }
        } else {
            $this->theme = "light-theme";
        }

        // Gestion connexion utilisateur
        $this->is_connect = $this->traitementsDS->getUserByUidInCookies() !== null;
    }

    #[Route('/', name: 'app_historique_programme_recompense_index', methods: ['GET'])]
    public function index(HistoriqueProgrammeRecompenseRepository $historiqueProgrammeRecompenseRepository, EntityManagerInterface $em): Response
    {
        foreach ($historiqueProgrammeRecompenseRepository->findBy(['status' => 'en_cours']) as $oneHistorique) {
            $promotion = $oneHistorique->getPromotion();
            if ($oneHistorique->getCreatedAt() <= (new \DateTime('-23 hours'))) {
                $oneHistorique->setStatus('echouer');
            }
            if(!$promotion->isInProgrammeRecompense()) {
                $oneHistorique->setStatus('echouer');
            }
        }
        $em->flush();

        foreach ($historiqueProgrammeRecompenseRepository->findBy(['terminer' => 'en_cours']) as $oneHistorique) {
            $promotion = $oneHistorique->getPromotion();
            if ($oneHistorique->getCreatedAt() <= (new \DateTime('-23 hours'))) {
                $oneHistorique->setStatus('echouer');
            }
            if(!$promotion->isInProgrammeRecompense()) {
                $oneHistorique->setStatus('echouer');
            }
        }
        $em->flush();
        
        return $this->render('historique_programme_recompense/index.html.twig', [
            'theme' => $this->theme,
            'is_connect' => $this->is_connect,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'historique_programme_recompenses' => $historiqueProgrammeRecompenseRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_historique_programme_recompense_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $entity = new HistoriqueProgrammeRecompense();
        $form = $this->createForm(HistoriqueProgrammeRecompenseType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();

            return $this->redirectToRoute('app_historique_programme_recompense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('historique_programme_recompense/new.html.twig', [
            'theme' => $this->theme,
            'is_connect' => $this->is_connect,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'historique_programme_recompense' => $entity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_historique_programme_recompense_show', methods: ['GET'])]
    public function show(HistoriqueProgrammeRecompense $entity): Response
    {
        return $this->render('historique_programme_recompense/show.html.twig', [
            'theme' => $this->theme,
            'is_connect' => $this->is_connect,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'historique_programme_recompense' => $entity,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_historique_programme_recompense_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, HistoriqueProgrammeRecompense $entity, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HistoriqueProgrammeRecompenseType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_historique_programme_recompense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('historique_programme_recompense/edit.html.twig', [
            'theme' => $this->theme,
            'is_connect' => $this->is_connect,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'historique_programme_recompense' => $entity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_historique_programme_recompense_delete', methods: ['POST'])]
    public function delete(Request $request, HistoriqueProgrammeRecompense $entity, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))) {
            $entityManager->remove($entity);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_historique_programme_recompense_index', [], Response::HTTP_SEE_OTHER);
    }
}
