<?php

namespace App\Controller\Crud;

use App\Services\CookieDS;
use App\Entity\EnvMailSender;
use App\Form\EnvMailSenderType;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EnvMailSenderRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/crud/env-mail-sender')]
class CrudEnvMailSenderController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_env_mail_sender_index', methods: ['GET'])]
    public function index(EnvMailSenderRepository $envMailSenderRepository): Response
    {
        return $this->render('crud_env_mail_sender/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'env_mail_senders' => $envMailSenderRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/remise-zero', name: 'app_crud_env_mail_sender_remise_zero', methods: ['GET'])]
    public function remise_zero(EnvMailSenderRepository $envMailSenderRepository, EntityManagerInterface $entityManager): Response
    {
        foreach ($envMailSenderRepository->findBy([], ['id' => 'DESC']) as $unEnvPaiement) {
            $unEnvPaiement->setCountMailSent(0);
        }
        $entityManager->flush();
        return $this->redirectToRoute('app_crud_env_mail_sender_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/new', name: 'app_crud_env_mail_sender_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $envMailSender = new EnvMailSender();
        $form = $this->createForm(EnvMailSenderType::class, $envMailSender);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($envMailSender);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_env_mail_sender_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_env_mail_sender/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'env_mail_sender' => $envMailSender,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_env_mail_sender_show', methods: ['GET'])]
    public function show(EnvMailSender $envMailSender): Response
    {
        return $this->render('crud_env_mail_sender/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'env_mail_sender' => $envMailSender,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_env_mail_sender_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EnvMailSender $envMailSender, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EnvMailSenderType::class, $envMailSender);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_env_mail_sender_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_env_mail_sender/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'env_mail_sender' => $envMailSender,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_env_mail_sender_delete', methods: ['POST'])]
    public function delete(Request $request, EnvMailSender $envMailSender, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$envMailSender->getId(), $request->request->get('_token'))) {
            $entityManager->remove($envMailSender);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_env_mail_sender_index', [], Response::HTTP_SEE_OTHER);
    }
}
