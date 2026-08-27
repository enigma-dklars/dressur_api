<?php

namespace App\Controller\Crud;

use App\Entity\Env;
use App\Form\EnvType;
use App\Repository\EnvRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/env')]
class CrudEnvController extends AbstractController
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

    #[Route('/', name: 'app_crud_env_index', methods: ['GET'])]
    public function index(EnvRepository $envRepository): Response
    {
        return $this->render('crud_env/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'envs' => $envRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_crud_env_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $env = new Env();
        $form = $this->createForm(EnvType::class, $env);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyZefameApiKey($env, $form);
            $entityManager->persist($env);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_env_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_env/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'env' => $env,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_env_show', methods: ['GET'])]
    public function show(Env $env): Response
    {
        return $this->render('crud_env/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'env' => $env,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_env_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Env $env, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EnvType::class, $env);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyZefameApiKey($env, $form);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_env_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_env/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'env' => $env,
            'form' => $form,
        ]);
    }

    private function applyZefameApiKey(Env $env, FormInterface $form): void
    {
        if ($form->get('clearZefameApiKey')->getData() === true) {
            $env->setZefameApiKey(null);
            return;
        }

        $newApiKey = trim((string)($form->get('zefameApiKey')->getData() ?? ''));
        if ($newApiKey !== '') {
            $env->setZefameApiKey($newApiKey);
        }
    }

    #[Route('/{id}', name: 'app_crud_env_delete', methods: ['POST'])]
    public function delete(Request $request, Env $env, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$env->getId(), $request->request->get('_token'))) {
            $entityManager->remove($env);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_env_index', [], Response::HTTP_SEE_OTHER);
    }
}
