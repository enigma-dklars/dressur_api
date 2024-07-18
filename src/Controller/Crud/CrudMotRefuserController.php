<?php

namespace App\Controller\Crud;

use App\Entity\MotRefuser;
use App\Form\MotRefuserType;
use App\Repository\MotRefuserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;

#[Route('/crud/mot/refuser')]
class CrudMotRefuserController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_mot_refuser_index', methods: ['GET'])]
    public function index(MotRefuserRepository $motRefuserRepository): Response
    {
        return $this->render('crud_mot_refuser/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'mot_refusers' => $motRefuserRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_crud_mot_refuser_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $motRefuser = new MotRefuser();
        $form = $this->createForm(MotRefuserType::class, $motRefuser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($motRefuser);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_mot_refuser_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_mot_refuser/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'mot_refuser' => $motRefuser,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_mot_refuser_show', methods: ['GET'])]
    public function show(MotRefuser $motRefuser): Response
    {
        return $this->render('crud_mot_refuser/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'mot_refuser' => $motRefuser,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_mot_refuser_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MotRefuser $motRefuser, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MotRefuserType::class, $motRefuser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_mot_refuser_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_mot_refuser/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'mot_refuser' => $motRefuser,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_mot_refuser_delete', methods: ['POST'])]
    public function delete(Request $request, MotRefuser $motRefuser, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$motRefuser->getId(), $request->request->get('_token'))) {
            $entityManager->remove($motRefuser);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_mot_refuser_index', [], Response::HTTP_SEE_OTHER);
    }
}
