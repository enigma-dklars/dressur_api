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
    ): Response {
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
    ): Response {
        // On récupère les anciennes captures pour supprimer si nécessaire
        $ancienneCaptureListe = $preuve->getCaptureListeStatut();
        $ancienneCaptureOuvert = $preuve->getCaptureStatutOuvert();

        // Création du formulaire
        $form = $this->createForm(PreuveType::class, $preuve);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Gestion upload fichiers (capture liste et capture ouvert)
            $this->handleUpload(
                $form,
                $preuve,
                $slugger,
                true,
                $ancienneCaptureListe,
                $ancienneCaptureOuvert
            );

            // ⚡ Logique métier : si preuve traitée, statut historique = 'terminer'
            if ($preuve->isIsTreated()) {
                $historique = $preuve->getHistoriqueProgrammeRecompense();
                if ($historique) {
                    $historique->setStatus('terminer');
                }
            }


            // Sauvegarde en base
            $entityManager->flush();

            return $this->redirectToRoute('app_preuve_index');
        }

        // Rendu du formulaire
        return $this->renderForm('preuve/edit.html.twig', [
            'preuve' => $preuve,
            'theme' => $this->theme,
            'is_connect' => $this->is_connect,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'form' => $form,
        ]);
    }

    #[Route('/{id}/accept', name: 'app_preuve_accept', methods: ['POST'])]
    public function accept(
        Preuve $preuve,
        EntityManagerInterface $em,
        Request $request, 
        TraitementsDS $traitementsDS
    ): Response {
        // 🔐 Vérification CSRF
        if (!$this->isCsrfTokenValid('accept' . $preuve->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_preuve_index');
        }

        // 🔒 Protection contre double traitement
        if ($preuve->isIsTreated()) {
            $this->addFlash('warning', 'Cette preuve a déjà été traitée.');
            return $this->redirectToRoute('app_preuve_index');
        }

        // 📌 Récupération de l'historique lié
        $historique = $preuve->getHistoriqueProgrammeRecompense();

        if (!$historique) {
            $this->addFlash('danger', 'Historique introuvable.');
            return $this->redirectToRoute('app_preuve_index');
        }

        // 📥 Récupération du nombre de vues depuis la modal
        $nbrVue = (int) $request->request->get('nbrVue');

        if ($nbrVue <= 0) {
            $this->addFlash('danger', 'Nombre de vues invalide.');
            return $this->redirectToRoute('app_preuve_index');
        }

        // definir la date d'expiration de la promotion en cours qui accompagne la validation de cette preuve pour lhistorique
        $historique->setExpiredAt($historique->getPromotion()->getDateExp());

        // 💾 On sauvegarde la valeur validée
        $historique->setNbrVue($nbrVue);

        // 🧮 Calcul du gain
        $gain = $this->calculerGain($nbrVue);

        // 🚫 Si moins de 250 vues → refus implicite
        if ($gain <= 0) {
            $this->addFlash('danger', 'Nombre de vues insuffisant pour validation.');
            return $this->redirectToRoute('app_preuve_index');
        }

        // ✅ Validation finale
        $preuve->setIsTreated(true);
        $historique->setStatus('approuver');

        $user = $historique->getUser();

        if ($user) {
            $user->addSoldeDressur($gain);
        }

        $traitementsDS->addNotification("Preuve approuvée et gain ajouté.", $user);
        $em->flush();

        $this->addFlash('success', 'Preuve approuvée et gain ajouté.');

        return $this->redirectToRoute('app_preuve_index');
    }



    /**
     * Calcul du gain selon les paliers de vues
     */
    private function calculerGain(int $nbrVue): int
    {

        if ($nbrVue < 250) {
            return 0;
        }

        if ($nbrVue < 500) {
            return 100;
        }

        if ($nbrVue < 1000) {
            return 200;
        }

        if ($nbrVue < 2000) {
            return 500;
        }

        if ($nbrVue < 4000) {
            return 1000;
        }

        return 2500;
    }

    #[Route('/{id}/refuse', name: 'app_preuve_refuse', methods: ['POST'])]
    public function refuse(Preuve $preuve, EntityManagerInterface $em, Request $request, TraitementsDS $traitementsDS): Response
    {
        if ($this->isCsrfTokenValid('refuse' . $preuve->getId(), $request->request->get('_token'))) {
            $preuve->setIsTreated(true);

            $historique = $preuve->getHistoriqueProgrammeRecompense();
            if ($historique) {
                $historique->setStatus('refuser');
            }

            $user = $historique->getUser();
            $traitementsDS->addNotification("Preuve refusée !", $user);
            $em->flush();
            $this->addFlash('danger', 'Preuve refusée !');
        }

        return $this->redirectToRoute('app_preuve_index');
    }



    #[Route('/{id}', name: 'app_preuve_delete', methods: ['POST'])]
    public function delete(Request $request, Preuve $preuve, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $preuve->getId(), $request->request->get('_token'))) {

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

            $newFilename = bin2hex(random_bytes(8)) . '.' . $fileListe->guessExtension();

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

            $newFilename = bin2hex(random_bytes(8)) . '.' . $fileOuvert->guessExtension();

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

        $filePath = $this->getParameter('preuve_recompense') . '/' . $filename;

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
