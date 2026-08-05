<?php

namespace App\Controller\Crud;

use App\Services\CookieDS;
use App\Entity\EnvPaiementApi;
use App\Services\TraitementsDS;
use App\Form\EnvPaiementApiType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EnvPaiementApiRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/crud/env-paiement-api')]
class CrudEnvPaiementApiController extends AbstractController
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
    
    #[Route('/', name: 'app_env_paiement_api_index', methods: ['GET'])]
    public function index(EnvPaiementApiRepository $envPaiementApiRepository): Response
    {
        return $this->render('crud_env_paiement_api/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'env_paiement_apis' => $envPaiementApiRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/remise-zero', name: 'app_env_paiement_api_remise_zero', methods: ['GET'])]
    public function remise_zero(EnvPaiementApiRepository $envPaiementApiRepository, EntityManagerInterface $entityManager): Response
    {
        foreach ($envPaiementApiRepository->findBy([], ['id' => 'DESC']) as $unEnvPaiement) {
            $unEnvPaiement->setCountTransactionApproved(0);
        }
        $entityManager->flush();
        return $this->redirectToRoute('app_env_paiement_api_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/new', name: 'app_env_paiement_api_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $envPaiementApi = new EnvPaiementApi();
        $form = $this->createForm(EnvPaiementApiType::class, $envPaiementApi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($envPaiementApi);
            $entityManager->flush();

            return $this->redirectToRoute('app_env_paiement_api_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_env_paiement_api/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'env_paiement_api' => $envPaiementApi,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_env_paiement_api_show', methods: ['GET'])]
    public function show(EnvPaiementApi $envPaiementApi): Response
    {
        return $this->render('crud_env_paiement_api/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'env_paiement_api' => $envPaiementApi,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_env_paiement_api_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EnvPaiementApi $envPaiementApi, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EnvPaiementApiType::class, $envPaiementApi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_env_paiement_api_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_env_paiement_api/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'env_paiement_api' => $envPaiementApi,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_env_paiement_api_delete', methods: ['POST'])]
    public function delete(Request $request, EnvPaiementApi $envPaiementApi, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$envPaiementApi->getId(), $request->request->get('_token'))) {
            $entityManager->remove($envPaiementApi);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_env_paiement_api_index', [], Response::HTTP_SEE_OTHER);
    }
}
