<?php

namespace App\Controller\Crud;

use App\Entity\DeletedDS;
use App\Form\DeletedDSType;
use App\Repository\DeletedDSRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;

#[Route('/crud/deleted/d/s')]
class CrudDeletedDSController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_deleted_d_s_index', methods: ['GET'])]
    public function index(DeletedDSRepository $deletedDSRepository): Response
    {
        return $this->render('crud_deleted_ds/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'deleted_ds' => $deletedDSRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_crud_deleted_d_s_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $deletedD = new DeletedDS();
        $form = $this->createForm(DeletedDSType::class, $deletedD);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($deletedD);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_deleted_d_s_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_deleted_ds/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'deleted_d' => $deletedD,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_deleted_d_s_show', methods: ['GET'])]
    public function show(DeletedDS $deletedD): Response
    {
        return $this->render('crud_deleted_ds/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'deleted_d' => $deletedD,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_deleted_d_s_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DeletedDS $deletedD, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DeletedDSType::class, $deletedD);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_deleted_d_s_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_deleted_ds/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'deleted_d' => $deletedD,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_deleted_d_s_delete', methods: ['POST'])]
    public function delete(Request $request, DeletedDS $deletedD, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$deletedD->getId(), $request->request->get('_token'))) {
            $entityManager->remove($deletedD);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_deleted_d_s_index', [], Response::HTTP_SEE_OTHER);
    }
}
