<?php

namespace App\Controller;

use App\Entity\Preuve;
use App\Form\PreuveType;
use App\Repository\PreuveRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/preuve')]
class PreuveController extends AbstractController
{
    private $theme;
    private $is_connect;
    private $cookieDS;
    private $traitementsDS;

    public function __construct(CookieDS $cookieDS, TraitementsDS $traitementsDS)
    {
        $this->cookieDS = $cookieDS;
        $this->traitementsDS = $traitementsDS;

        $this->theme = $this->cookieDS->check("theme") &&
                       $this->cookieDS->get("theme") === "dark-theme"
                       ? "dark-theme"
                       : "light-theme";

        $this->is_connect = $this->traitementsDS->getUserByUidInCookies() !== null;
    }

    #[Route('/', name: 'app_preuve_index', methods: ['GET'])]
    public function index(PreuveRepository $preuveRepository): Response
    {
        return $this->render('preuve/index.html.twig', [
            'theme' => $this->theme,
            'is_connect' => $this->is_connect,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'preuves' => $preuveRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_preuve_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response
    {
        $preuve = new Preuve();
        $form = $this->createForm(PreuveType::class, $preuve);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->handleUpload($form, $preuve, $slugger);

            $entityManager->persist($preuve);
            $entityManager->flush();

            return $this->redirectToRoute('app_preuve_index');
        }

        return $this->renderForm('preuve/new.html.twig', [
            'theme' => $this->theme,
            'is_connect' => $this->is_connect,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_preuve_show', methods: ['GET'])]
    public function show(Preuve $preuve): Response
    {
        return $this->render('preuve/show.html.twig', [
            'theme' => $this->theme,
            'is_connect' => $this->is_connect,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'preuve' => $preuve,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_preuve_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Preuve $preuve,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response
    {
        $ancienneCaptureListe = $preuve->getCaptureListeStatut();
        $ancienneCaptureOuvert = $preuve->getCaptureStatutOuvert();

        $form = $this->createForm(PreuveType::class, $preuve);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->handleUpload($form, $preuve, $slugger, true, $ancienneCaptureListe, $ancienneCaptureOuvert);

            $entityManager->flush();

            return $this->redirectToRoute('app_preuve_index');
        }

        return $this->renderForm('preuve/edit.html.twig', [
            'theme' => $this->theme,
            'is_connect' => $this->is_connect,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_preuve_delete', methods: ['POST'])]
    public function delete(Request $request, Preuve $preuve, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$preuve->getId(), $request->request->get('_token'))) {

            $this->removeFile($preuve->getCaptureListeStatut());
            $this->removeFile($preuve->getCaptureStatutOuvert());

            $entityManager->remove($preuve);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_preuve_index');
    }

    /* ============================================================
       MÉTHODES PRIVÉES INDUSTRIALISÉES
       ============================================================ */

    private function handleUpload($form, Preuve $preuve, SluggerInterface $slugger, $isEdit = false, $oldListe = null, $oldOuvert = null)
    {
        $dossier = $this->getParameter('preuve_recompense');

        $fileListe = $form->get('captureListeStatut')->getData();
        $fileOuvert = $form->get('captureStatutOuvert')->getData();

        if ($fileListe) {
            if ($isEdit && $oldListe) {
                $this->removeFile($oldListe);
            }

            $newFilename = uniqid().'.'.$fileListe->guessExtension();

            try {
                $fileListe->move($dossier, $newFilename);
                $preuve->setCaptureListeStatut($newFilename);
            } catch (FileException $e) {
            }
        }

        if ($fileOuvert) {
            if ($isEdit && $oldOuvert) {
                $this->removeFile($oldOuvert);
            }

            $newFilename = uniqid().'.'.$fileOuvert->guessExtension();

            try {
                $fileOuvert->move($dossier, $newFilename);
                $preuve->setCaptureStatutOuvert($newFilename);
            } catch (FileException $e) {
            }
        }
    }

    private function removeFile(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        $filePath = $this->getParameter('preuve_recompense').'/'.$filename;

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
