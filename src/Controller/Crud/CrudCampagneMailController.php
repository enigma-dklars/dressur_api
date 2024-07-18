<?php

namespace App\Controller\Crud;

use App\Entity\CampagneMail;
use App\Form\CampagneMailType;
use App\Repository\CampagneMailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;

#[Route('/crud/campagne/mail')]
class CrudCampagneMailController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_campagne_mail_index', methods: ['GET'])]
    public function index(CampagneMailRepository $campagneMailRepository): Response
    {
        return $this->render('crud_campagne_mail/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'campagne_mails' => $campagneMailRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_crud_campagne_mail_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $campagneMail = new CampagneMail();
        $form = $this->createForm(CampagneMailType::class, $campagneMail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($campagneMail);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_campagne_mail_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_campagne_mail/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'campagne_mail' => $campagneMail,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_campagne_mail_show', methods: ['GET'])]
    public function show(CampagneMail $campagneMail): Response
    {
        return $this->render('crud_campagne_mail/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'campagne_mail' => $campagneMail,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_campagne_mail_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CampagneMail $campagneMail, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CampagneMailType::class, $campagneMail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_campagne_mail_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_campagne_mail/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'campagne_mail' => $campagneMail,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_campagne_mail_delete', methods: ['POST'])]
    public function delete(Request $request, CampagneMail $campagneMail, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$campagneMail->getId(), $request->request->get('_token'))) {
            $entityManager->remove($campagneMail);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_campagne_mail_index', [], Response::HTTP_SEE_OTHER);
    }
}
