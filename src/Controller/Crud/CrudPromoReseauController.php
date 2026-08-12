<?php

namespace App\Controller\Crud;

use App\Entity\PromoReseau;
use App\Form\PromoReseauType;
use App\Repository\PromoReseauRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Utilities\ZefameApi;
use DateTime;
use Doctrine\DBAL\LockMode;

#[Route('/crud/promo/reseau')]
class CrudPromoReseauController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_promo_reseau_index', methods: ['GET'])]
    public function index(PromoReseauRepository $promoReseauRepository, TraitementsDS $traitementsDS, Request $request): Response
    {
        $traitementsDS->checkAndUpdateStatusZefame();
        $sourceFilter = $request->query->get('source', '');

        if ($sourceFilter === 'none') {
            $promo_reseaus = $promoReseauRepository->findBy(['source' => null], ['id' => 'DESC']);
        } elseif (in_array($sourceFilter, ['web', 'mobile'])) {
            $promo_reseaus = $promoReseauRepository->findBy(['source' => $sourceFilter], ['id' => 'DESC']);
        } else {
            $promo_reseaus = $promoReseauRepository->findBy([], ['id' => 'DESC']);
        }

        return $this->render('crud_promo_reseau/index.html.twig', [
            'theme' => $this->theme,
            'soldeZefame' => $traitementsDS->getSoldeZefame(),
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseaus' => $promo_reseaus,
            'sourceFilter' => $sourceFilter,
            'sourceCounts' => $promoReseauRepository->getSourceCounts(),
            'commentairesRequis' => $this->commentairesRequisParPromotion($promo_reseaus),
        ]);
    }

    #[Route('/promo_reseau_en_attente', name: 'app_crud_promo_reseau_promo_reseau_en_attente', methods: ['GET'])]
    public function promo_reseau_en_attente(PromoReseauRepository $promoReseauRepository, TraitementsDS $traitementsDS): Response
    {
        $traitementsDS->checkAndUpdateStatusZefame();
        $promo_reseaus = $promoReseauRepository->findBy(['status' => 1], ['id' => 'DESC']);

        return $this->render('crud_promo_reseau/index.html.twig', [
            'theme' => $this->theme,
            'soldeZefame' => $traitementsDS->getSoldeZefame(),
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseaus' => $promo_reseaus,
            'commentairesRequis' => $this->commentairesRequisParPromotion($promo_reseaus),
        ]);
    }

    /**
     * @param iterable<PromoReseau> $promoReseaus
     * @return array<int, bool>
     */
    private function commentairesRequisParPromotion(iterable $promoReseaus): array
    {
        $commentairesRequis = [];

        foreach ($promoReseaus as $promoReseau) {
            $formule = $promoReseau->getFormulePromoReseau();
            $commentairesRequis[$promoReseau->getId()] = $formule !== null
                && $this->traitementsDS->formuleNecessiteCommentaires($formule);
        }

        return $commentairesRequis;
    }

    #[Route('/new', name: 'app_crud_promo_reseau_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $promoReseau = new PromoReseau();
        $form = $this->createForm(PromoReseauType::class, $promoReseau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($promoReseau);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_promo_reseau_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_promo_reseau/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseau' => $promoReseau,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_promo_reseau_show', methods: ['GET'])]
    public function show(PromoReseau $promoReseau): Response
    {
        return $this->render('crud_promo_reseau/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseau' => $promoReseau,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_promo_reseau_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PromoReseau $promoReseau, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PromoReseauType::class, $promoReseau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($promoReseau->getStatus() == 3) {
                $promoReseau->setCompteurRestant(0);
            }
            if($promoReseau->getIdZefame() != "*****") {
                $promoReseau->setStatus(2);
            }
            $promoReseau->setUpdatedAt(new DateTime());
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_promo_reseau_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_promo_reseau/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseau' => $promoReseau,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/demarrage_direct_zefame', name: 'app_crud_promo_reseau_demarrage_direct_zefame', methods: ['POST'])]
    public function demarrage_direct_zefame(Request $request, PromoReseau $promoReseau, EntityManagerInterface $em, ZefameApi $zefame): Response
    {
        if (!$this->isCsrfTokenValid(
            'demarrage_direct_zefame_'.$promoReseau->getId(),
            (string)$request->request->get('_token')
        )) {
            $this->addFlash('danger', 'Le formulaire de démarrage est invalide ou a expiré.');

            return $this->redirectToRoute('app_crud_promo_reseau_promo_reseau_en_attente');
        }

        $connection = $em->getConnection();
        $connection->beginTransaction();

        try {
            $promoReseau = $em->find(
                PromoReseau::class,
                $promoReseau->getId(),
                LockMode::PESSIMISTIC_WRITE
            );

            if ($promoReseau === null) {
                $connection->rollBack();
                $this->addFlash('danger', 'La promotion demandée est introuvable.');

                return $this->redirectToRoute('app_crud_promo_reseau_promo_reseau_en_attente');
            }

            if ($promoReseau->getStatus() !== 1) {
                $connection->rollBack();
                $this->addFlash('warning', 'Cette promotion ne peut plus être démarrée.');

                return $this->redirectToRoute('app_crud_promo_reseau_promo_reseau_en_attente');
            }

            if ($promoReseau->getIdZefame() !== null && $promoReseau->getIdZefame() !== '*****') {
                $connection->rollBack();
                $this->addFlash('warning', 'Une commande Zefame existe déjà pour cette promotion.');

                return $this->redirectToRoute('app_crud_promo_reseau_promo_reseau_en_attente');
            }

            $formule = $promoReseau->getFormulePromoReseau();
            if ($formule === null) {
                $connection->rollBack();
                $this->addFlash('danger', 'La formule de cette promotion est introuvable.');

                return $this->redirectToRoute('app_crud_promo_reseau_promo_reseau_en_attente');
            }

            $idServiceZefame = $formule->getIdZefame();
            if ($idServiceZefame === null || $idServiceZefame <= 0) {
                $connection->rollBack();
                $this->addFlash('danger', 'La formule ne possède pas de service Zefame valide.');

                return $this->redirectToRoute('app_crud_promo_reseau_promo_reseau_en_attente');
            }

            $qte = $promoReseau->getQteDemander();
            if ($qte === null || $qte <= 0) {
                $connection->rollBack();
                $this->addFlash('danger', 'La quantité de la promotion est invalide.');

                return $this->redirectToRoute('app_crud_promo_reseau_promo_reseau_en_attente');
            }

            $formuleNecessiteCommentaires = $this->traitementsDS->formuleNecessiteCommentaires($formule);
            $parametresCommande = [
                'service' => $idServiceZefame,
                'link' => $promoReseau->getUrl(),
                'quantity' => $qte,
                'runs' => 2,
                'interval' => 5,
            ];

            if ($formuleNecessiteCommentaires) {
                $commentaires = $this->traitementsDS->normaliserCommentaires(
                    $request->request->get('comments')
                );

                if ($commentaires === []) {
                    $connection->rollBack();
                    $this->addFlash('danger', 'Ajoutez au moins un commentaire avant de démarrer la promotion.');

                    return $this->redirectToRoute('app_crud_promo_reseau_promo_reseau_en_attente');
                }

                if (count($commentaires) > $qte) {
                    $connection->rollBack();
                    $this->addFlash(
                        'danger',
                        sprintf(
                            'Le nombre de commentaires (%d) ne peut pas dépasser la quantité commandée (%d).',
                            count($commentaires),
                            $qte
                        )
                    );

                    return $this->redirectToRoute('app_crud_promo_reseau_promo_reseau_en_attente');
                }

                $parametresCommande['comments'] = implode("\n", $commentaires);
            }

            $resultZefame = $zefame->order($parametresCommande);

            if (!isset($resultZefame->order) || $resultZefame->order === '') {
                $messageErreur = isset($resultZefame->error) && $resultZefame->error !== ''
                    ? (string)$resultZefame->error
                    : 'Zefame n’a pas retourné d’identifiant de commande.';
                $connection->rollBack();
                $this->addFlash('danger', $messageErreur);

                return $this->redirectToRoute('app_crud_promo_reseau_promo_reseau_en_attente');
            }

            $promoReseau
                ->setIdZefame((string)$resultZefame->order)
                ->setStatus(2);
            $em->flush();
            $connection->commit();
            $this->addFlash('success', 'La promotion a été démarrée avec succès.');
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $this->addFlash('danger', 'Impossible de démarrer la promotion. Aucune commande n’a été enregistrée.');
        }

        return $this->redirectToRoute('app_crud_promo_reseau_promo_reseau_en_attente');
    }

    #[Route('/{id}', name: 'app_crud_promo_reseau_delete', methods: ['POST'])]
    public function delete(Request $request, PromoReseau $promoReseau, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$promoReseau->getId(), $request->request->get('_token'))) {
            $entityManager->remove($promoReseau);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_promo_reseau_index', [], Response::HTTP_SEE_OTHER);
    }
}
