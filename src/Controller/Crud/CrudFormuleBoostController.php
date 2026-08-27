<?php

namespace App\Controller\Crud;

use App\Entity\FormuleBoost;
use App\Form\FormuleBoostType;
use App\Repository\BoostRepository;
use App\Repository\FormuleBoostRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/formule/boost')]
class CrudFormuleBoostController extends AbstractController
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

    #[Route('/', name: 'app_crud_formule_boost_index', methods: ['GET'])]
    public function index(FormuleBoostRepository $formuleBoostRepository): Response
    {
        return $this->render('crud_formule_boost/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_boosts' => $formuleBoostRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_crud_formule_boost_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formuleBoost = new FormuleBoost();
        $form = $this->createForm(FormuleBoostType::class, $formuleBoost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($formuleBoost);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_formule_boost_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_formule_boost/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_boost' => $formuleBoost,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_formule_boost_show', methods: ['GET'])]
    public function show(FormuleBoost $formuleBoost): Response
    {
        return $this->render('crud_formule_boost/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_boost' => $formuleBoost,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_formule_boost_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FormuleBoost $formuleBoost, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FormuleBoostType::class, $formuleBoost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_formule_boost_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_formule_boost/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_boost' => $formuleBoost,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_formule_boost_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        FormuleBoost $formuleBoost,
        EntityManagerInterface $entityManager,
        FormuleBoostRepository $formuleBoostRepository,
        BoostRepository $boostRepository
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$formuleBoost->getId(), $request->request->get('_token'))) {
            $boosts = $boostRepository->findBy(['formuleBoost' => $formuleBoost]);
            $boostCount = count($boosts);
            $replacement = $boostCount > 0
                ? $formuleBoostRepository->findReplacementForDeletion($formuleBoost)
                : null;

            if ($boostCount > 0 && $replacement === null) {
                $this->addFlash(
                    'danger',
                    sprintf(
                        'La formule « %s » ne peut pas être supprimée : aucun remplacement actif compatible (%s, %s) n’existe pour ses %d Boost(s).',
                        $formuleBoost->getTitre(),
                        $formuleBoost->getTypeBoost(),
                        $formuleBoost->getPrix() <= 0 ? 'gratuit' : 'payant',
                        $boostCount
                    )
                );

                return $this->redirectToRoute('app_crud_formule_boost_index', [], Response::HTTP_SEE_OTHER);
            }

            foreach ($boosts as $boost) {
                $boost->setFormuleBoost($replacement);
            }

            $entityManager->remove($formuleBoost);
            $entityManager->flush();

            $notification = $boostCount > 0
                ? sprintf(
                    'La formule Boost Contact « %s » a été supprimée et remplacée par « %s » pour %d Boost(s) existant(s).',
                    $formuleBoost->getTitre(),
                    $replacement->getTitre(),
                    $boostCount
                )
                : sprintf(
                    'La formule Boost Contact « %s » a été supprimée. Aucun Boost existant n’était lié à cette formule.',
                    $formuleBoost->getTitre()
                );

            $this->addFlash('success', $notification);
        }

        return $this->redirectToRoute('app_crud_formule_boost_index', [], Response::HTTP_SEE_OTHER);
    }
}
