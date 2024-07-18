<?php

namespace App\Controller;

use App\Entity\FormuleCampagneMail;
use App\Form\FormuleCampagneMailType;
use App\Repository\FormuleCampagneMailRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/formule/campagne/mail')]
class CrudFormuleCampagneMailController extends AbstractController
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

    #[Route('/', name: 'app_crud_formule_campagne_mail_index', methods: ['GET'])]
    public function index(FormuleCampagneMailRepository $formuleCampagneMailRepository): Response
    {
        return $this->render('crud_formule_campagne_mail/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_campagne_mails' => $formuleCampagneMailRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_crud_formule_campagne_mail_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formuleCampagneMail = new FormuleCampagneMail();
        $form = $this->createForm(FormuleCampagneMailType::class, $formuleCampagneMail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($formuleCampagneMail);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_formule_campagne_mail_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_formule_campagne_mail/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_campagne_mail' => $formuleCampagneMail,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_formule_campagne_mail_show', methods: ['GET'])]
    public function show(FormuleCampagneMail $formuleCampagneMail): Response
    {
        return $this->render('crud_formule_campagne_mail/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_campagne_mail' => $formuleCampagneMail,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_formule_campagne_mail_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FormuleCampagneMail $formuleCampagneMail, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FormuleCampagneMailType::class, $formuleCampagneMail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_formule_campagne_mail_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_formule_campagne_mail/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'formule_campagne_mail' => $formuleCampagneMail,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_formule_campagne_mail_delete', methods: ['POST'])]
    public function delete(Request $request, FormuleCampagneMail $formuleCampagneMail, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$formuleCampagneMail->getId(), $request->request->get('_token'))) {
            $entityManager->remove($formuleCampagneMail);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_formule_campagne_mail_index', [], Response::HTTP_SEE_OTHER);
    }
}
