<?php

namespace App\Controller\Crud;

use App\Entity\Boost;
use App\Form\BoostType;
use App\Repository\BoostRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/boost')]
class CrudBoostController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_boost_index', methods: ['GET'])]
    public function index(BoostRepository $boostRepository, Request $request): Response
    {
        $sourceFilter = $request->query->get('source', '');

        if ($sourceFilter === 'none') {
            $boosts = $boostRepository->findBy(['source' => null], ['id' => 'DESC']);
        } elseif (in_array($sourceFilter, ['web', 'mobile'])) {
            $boosts = $boostRepository->findBy(['source' => $sourceFilter], ['id' => 'DESC']);
        } else {
            $boosts = $boostRepository->findBy([], ['id' => 'DESC']);
        }

        return $this->render('crud_boost/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'boosts' => $boosts,
            'sourceFilter' => $sourceFilter,
            'sourceCounts' => $boostRepository->getSourceCounts(),
        ]);
    }

    #[Route('/new', name: 'app_crud_boost_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $boost = new Boost();
        $form = $this->createForm(BoostType::class, $boost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($boost);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_boost_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_boost/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'boost' => $boost,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_boost_show', methods: ['GET'])]
    public function show(Boost $boost): Response
    {
        return $this->render('crud_boost/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'boost' => $boost,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_boost_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Boost $boost, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BoostType::class, $boost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_boost_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_boost/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'boost' => $boost,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_boost_delete', methods: ['POST'])]
    public function delete(Request $request, Boost $boost, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$boost->getId(), $request->request->get('_token'))) {
            $entityManager->remove($boost);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_boost_index', [], Response::HTTP_SEE_OTHER);
    }
}
