<?php

namespace App\Controller\Crud;

use App\Entity\Promotion;
use App\Form\PromotionType;
use App\Repository\FormulePromoAffaireRepository;
use App\Repository\HistoriqueProgrammeRecompenseRepository;
use App\Repository\PromotionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Utilities\SendMail;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/crud/promotion/affaire')]
class CrudPromotionController extends AbstractController
{
    private $theme;
    private $cookieDS;
    private $traitementsDS;
    private $sendMail;

    public function __construct(CookieDS $cookieDS, TraitementsDS $traitementsDS, SendMail $sendMail)
    {
        $this->cookieDS = $cookieDS;
        $this->traitementsDS = $traitementsDS;
        $this->sendMail = $sendMail;
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
    
    #[Route('/', name: 'app_crud_promotion_index', methods: ['GET'])]
    public function index(PromotionRepository $promotionRepository): Response
    {
        return $this->render('crud_promotion/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promotions' => $promotionRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/promo_en_attente', name: 'app_crud_promotion_promo_en_attente', methods: ['GET'])]
    public function promo_en_attente(PromotionRepository $promotionRepository): Response
    {
        return $this->render('crud_promotion/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promotions' => $promotionRepository->findBy(['status' => 1], ['id' => 'DESC']),
        ]);
    }

    #[Route('/delete_images_no_use', name: 'app_crud_promotion_delete_images_no_use', methods: ['GET'])]
    public function delete_images_no_use(PromotionRepository $promotionRepository): Response
    {
        // Récupère le chemin du dossier promotion
        $promotionDirectory = $this->getParameter('promotion_directory');

        // Vérifie si le dossier existe
        if (!is_dir($promotionDirectory)) {
            throw new \Exception("Le dossier promotion n'existe pas.");
        }

        // Parcourt les fichiers dans le dossier promotion
        $files = scandir($promotionDirectory);

        foreach ($files as $file) {
            // Vérifie si le fichier commence par "dressur_pro_"
            if (strpos($file, 'dressur_pro_') === 0) {
                if (!$promotionRepository->findOneBy(['image' => $file])) {
                    unlink($promotionDirectory . '/' . $file);
                }
            }
        }

        // Redirige vers l'index des promotions
        return $this->redirectToRoute('app_crud_promotion_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/new', name: 'app_crud_promotion_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $promotion = new Promotion();
        $form = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($promotion);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_promotion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_promotion/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promotion' => $promotion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_promotion_show', methods: ['GET'])]
    public function show(Promotion $promotion): Response
    {
        return $this->render('crud_promotion/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promotion' => $promotion,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_promotion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Promotion $promotion, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_promotion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_promotion/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promotion' => $promotion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/accepter', name: 'app_crud_promotion_accepter', methods: ['GET', 'POST'])]
    public function accepter(Request $request, Promotion $promotion, EntityManagerInterface $entityManager, FormulePromoAffaireRepository $formulePromoAffaireRepository): Response
    {
        try {
            if($promotion->getTypePromotionAffaire() == "produit_service") {
                $promotion
                    ->setDateExp(new DateTime("+ ".$promotion->getFormulePromoAffaire()->getNbrJour()."days"))
                ;
            }
    
            if($promotion->getTypePromotionAffaire() == "dmd_emploi") {
                $promotion
                    ->setFormulePromoAffaire($formulePromoAffaireRepository->find(4))
                    ->setDateExp(new DateTime("+ ".$formulePromoAffaireRepository->find(4)->getNbrJour()."days"))
                ;
            }
    
            if($promotion->getTypePromotionAffaire() == "offre_emploi") {
                $promotion
                    ->setFormulePromoAffaire($formulePromoAffaireRepository->find(4))
                    ->setDateExp(new DateTime("+ ".$formulePromoAffaireRepository->find(4)->getNbrJour()."days"))
                ;
            }
    
            $promotion->setMotif("")->setStatus(3)->setDateDebut(new DateTime());
            $entityManager->flush();

            $user = $promotion->getUser();
            if ($user && $user->getMail()) {
                $formulePromoAffaire = $promotion->getFormulePromoAffaire();
                $htmlUser = $this->renderView('emails/promo_affaire_acceptee_user.html.twig', [
                    'user_nom'        => $user->getNom(),
                    'formule_titre'   => $formulePromoAffaire ? $formulePromoAffaire->getTitre() : null,
                    'formule_nbr_jour'=> $formulePromoAffaire ? $formulePromoAffaire->getNbrJour() : null,
                ]);
                $this->sendMail->smtpMail(
                    $user->getMail(),
                    "Votre promotion a été acceptée 🎉",
                    $htmlUser
                );
            }

            $flashMessage = "Promotion #".$promotion->getId()." acceptée avec succès.";
            $this->addFlash('success', $flashMessage);

            return new JsonResponse(['status' => 'Yes', 'flash' => ['type' => 'success', 'message' => $flashMessage]]);
        } catch (\Throwable $th) {
            return new JsonResponse(['status' => 'No', 'flash' => ['type' => 'danger', 'message' => (string)$th]]);
        }
    }

    #[Route('/{id}/refuser', name: 'app_crud_promotion_refuser', methods: ['POST'])]
    public function refuser(Request $request, Promotion $promotion, EntityManagerInterface $entityManager): Response
    {
        try {
            $motif = $request->request->get('motif', '');
            $promotion->setMotif($motif)->setStatus(0);
            $entityManager->flush();

            $user = $promotion->getUser();
            if ($user && $user->getMail()) {
                $htmlUser = $this->renderView('emails/promo_affaire_refusee_user.html.twig', [
                    'user_nom' => $user->getNom(),
                    'motif'    => $motif ?? '',
                ]);
                $this->sendMail->smtpMail(
                    $user->getMail(),
                    "Votre promotion a été refusée",
                    $htmlUser
                );
            }

            $flashMessage = "Promotion #".$promotion->getId()." refusée.";
            $this->addFlash('warning', $flashMessage);

            return new JsonResponse(['status' => 'Yes', 'flash' => ['type' => 'warning', 'message' => $flashMessage]]);
        } catch (\Throwable $th) {
            return new JsonResponse(['status' => 'No', 'flash' => ['type' => 'danger', 'message' => (string)$th]]);
        }
    }

    #[Route('/{id}', name: 'app_crud_promotion_delete', methods: ['POST'])]
    public function delete(Request $request, Promotion $promotion, EntityManagerInterface $entityManager, HistoriqueProgrammeRecompenseRepository $historiqueRepo): Response
    {
        if ($this->isCsrfTokenValid('delete'.$promotion->getId(), $request->request->get('_token'))) {
            $id = $promotion->getId();
            $type = $promotion->getTypePromotionAffaire();

            // Suppression de toutes les occurrences liées (HistoriqueProgrammeRecompense)
            $historiques = $historiqueRepo->findBy(['promotion' => $promotion]);
            foreach ($historiques as $historique) {
                $entityManager->remove($historique);
            }

            // Suppression de l'image uniquement pour les promotions de type produit_service
            if ($type === 'produit_service' && str_starts_with((string) $promotion->getImage(), 'dressur_pro_')) {
                try {
                    unlink($this->getParameter('promotion_directory')."/".$promotion->getImage());
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }

            $entityManager->remove($promotion);
            $entityManager->flush();

            $this->addFlash('success', 'Promotion #'.$id.' supprimée avec '.count($historiques).' historique(s) lié(s).');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide. Suppression annulée.');
        }

        return $this->redirectToRoute('app_crud_promotion_index', [], Response::HTTP_SEE_OTHER);
    }
}
